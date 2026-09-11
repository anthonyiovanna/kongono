<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationContract\Input\InputRole;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapter;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Request\ResponsesRequestEncoder;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Throwable;

/** Opens one exact OpenAI Responses invocation session. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesInvocationAdapter implements InvocationAdapter
{
    public const string IDENTITY = 'openai.responses.v1';

    private ?OpenAiResponsesProfile $profile;
    private ?OpenAiResponsesInvocationContext $context;
    private bool $opened = false;

    public function __construct(OpenAiResponsesProfile $profile, OpenAiResponsesInvocationContext $context)
    {
        $this->profile = $profile;
        $this->context = $context;
    }

    public function identity(): AdapterIdentity
    {
        return new AdapterIdentity(self::IDENTITY);
    }

    public function open(InvocationPlanSource $source): InvocationAdapterSession
    {
        if ($this->opened) {
            throw OpenAiResponsesInvocationException::lifecycle('adapter_reused');
        }
        $this->opened = true;
        $profile = $this->profile;
        $context = $this->context;
        $this->profile = null;
        $this->context = null;
        if (!$profile instanceof OpenAiResponsesProfile || !$context instanceof OpenAiResponsesInvocationContext) {
            throw OpenAiResponsesInvocationException::lifecycle('adapter_reused');
        }

        try {
            $this->validateSource($source, $profile, $context);
            $applicationProfile = $context->applicationFactory()->profile();
            self::validateApplicationProfile($applicationProfile);
            $encoded = (new ResponsesRequestEncoder())->encode($source->request(), $profile);

            return $source->request()->streaming()
                ? new OpenAiResponsesStreamingInvocationSession($source, $profile, $context, $applicationProfile, $encoded)
                : new OpenAiResponsesInvocationSession($source, $profile, $context, $applicationProfile, $encoded);
        } catch (OpenAiResponsesInvocationException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw OpenAiResponsesInvocationException::invalidContext('encoding', $failure);
        } finally {
            unset($profile, $context, $applicationProfile, $encoded);
        }
    }

    private function validateSource(
        InvocationPlanSource $source,
        OpenAiResponsesProfile $profile,
        OpenAiResponsesInvocationContext $context,
    ): void {
        if (!$source->adapterIdentity()->equals($this->identity())) {
            throw OpenAiResponsesInvocationException::invalidContext('adapter_identity');
        }
        if (!$source->identity()->equals($context->expectedPlanIdentity())) {
            throw OpenAiResponsesInvocationException::invalidContext('plan_identity');
        }
        if (!$source->request()->profile()->equals($profile->neutralProfile())) {
            throw OpenAiResponsesInvocationException::invalidContext('profile');
        }
        if ($context->credentialMaximumLifetimeMilliseconds() > $source->budget()->maxDurationMilliseconds()) {
            throw OpenAiResponsesInvocationException::invalidContext('credential_lifetime');
        }
        $supported = match ($profile->mappingVersion()) {
            \Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion::Version1 => ResultMode::Text === $source->request()->resultMode(),
            \Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion::Version2 => !$source->request()->streaming()
                && in_array($source->request()->resultMode(), [ResultMode::Structured, ResultMode::ActionSuggestion], true),
        };
        if (!$supported) {
            throw OpenAiResponsesInvocationException::invalidContext('unsupported_input');
        }
        foreach ($source->request()->messages() as $message) {
            if (InputRole::Tool === $message->role()) {
                throw OpenAiResponsesInvocationException::invalidContext('unsupported_input');
            }
        }
    }

    private static function validateApplicationProfile(ExternalServiceApplicationProfile $profile): void
    {
        if (OriginScheme::Https !== $profile->binding()->endpoint()->origin()->scheme()
            || !$profile->binding()->endpoint()->basePath()->isEmpty()
        ) {
            throw OpenAiResponsesInvocationException::invalidContext('endpoint');
        }
    }
}
