<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Adapter;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Execution\ExternalServiceApplicationRunInput;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Runtime\ExternalServiceApplicationState;
use Kongono\ExternalServiceContract\Outcome\ExternalServiceOutcome;
use Kongono\ModelInvocationBuild\Plan\InvocationPlanSource;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterSession;
use Kongono\ModelProviderOpenAiResponses\Exception\OpenAiResponsesInvocationException;
use Kongono\ModelProviderOpenAiResponses\Execution\OpenAiResponsesInvocationContext;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesRequestFactory;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesResponseBodyReader;
use Kongono\ModelProviderOpenAiResponses\Internal\OpenAiResponsesResultNormalizer;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;
use Kongono\ModelProviderOpenAiResponses\Request\EncodedResponsesRequest;
use Kongono\ModelProviderOpenAiResponses\Response\ResponsesResponseDecoder;
use Throwable;

/** Executes and retires one pull-driven external-service operation. */
#[Api(ApiStatus::Internal)]
final class OpenAiResponsesInvocationSession implements InvocationAdapterSession
{
    private OpenAiResponsesInvocationSessionState $state = OpenAiResponsesInvocationSessionState::Ready;
    private ?InvocationPlanSource $source;
    private ?OpenAiResponsesProfile $profile;
    private ?OpenAiResponsesInvocationContext $context;
    private ?ExternalServiceApplicationProfile $applicationProfile;
    private ?EncodedResponsesRequest $encodedRequest;

    public function __construct(
        InvocationPlanSource $source,
        OpenAiResponsesProfile $profile,
        OpenAiResponsesInvocationContext $context,
        ExternalServiceApplicationProfile $applicationProfile,
        EncodedResponsesRequest $encodedRequest,
    ) {
        $this->source = $source;
        $this->profile = $profile;
        $this->context = $context;
        $this->applicationProfile = $applicationProfile;
        $this->encodedRequest = $encodedRequest;
    }

    public function state(): OpenAiResponsesInvocationSessionState
    {
        return $this->state;
    }

    public function next(): ?InvocationAdapterEmission
    {
        if (OpenAiResponsesInvocationSessionState::Emitted === $this->state) {
            $this->state = OpenAiResponsesInvocationSessionState::Exhausted;

            return null;
        }
        if (in_array($this->state, [OpenAiResponsesInvocationSessionState::Exhausted, OpenAiResponsesInvocationSessionState::Closed], true)) {
            return null;
        }
        if (OpenAiResponsesInvocationSessionState::Ready !== $this->state) {
            throw OpenAiResponsesInvocationException::lifecycle('session_pull');
        }

        $source = $this->source;
        $profile = $this->profile;
        $context = $this->context;
        $applicationProfile = $this->applicationProfile;
        $encoded = $this->encodedRequest;
        if (!$source instanceof InvocationPlanSource
            || !$profile instanceof OpenAiResponsesProfile
            || !$context instanceof OpenAiResponsesInvocationContext
            || !$applicationProfile instanceof ExternalServiceApplicationProfile
            || !$encoded instanceof EncodedResponsesRequest
        ) {
            $this->release();
            $this->state = OpenAiResponsesInvocationSessionState::Failed;
            throw OpenAiResponsesInvocationException::lifecycle('session_state');
        }

        $this->state = OpenAiResponsesInvocationSessionState::Active;
        try {
            try {
                $application = $context->applicationFactory()->create();
            } catch (Throwable $failure) {
                throw OpenAiResponsesInvocationException::externalService('application_creation', $failure);
            }
            if ($application->profile() !== $applicationProfile) {
                throw OpenAiResponsesInvocationException::invalidContext('application_profile');
            }
            if (ExternalServiceApplicationState::Ready !== $application->state() || $application->hasActiveOperation()) {
                throw OpenAiResponsesInvocationException::invalidContext('application_state');
            }

            $requestFactory = new OpenAiResponsesRequestFactory($encoded, $context->operationIdentity());
            $input = new ExternalServiceApplicationRunInput(
                $context->operationIdentity(),
                $context->credentialPurpose(),
                $context->credentialMaximumLifetimeMilliseconds(),
                $requestFactory,
                $context->receiptIdentity(),
                $context->attemptIdentity(),
                $context->authorityReference(),
                $context->authorityCurrentness(),
                $context->cancellation(),
                $context->credentialClock(),
                $context->egressClock(),
            );
            try {
                $applicationResult = $application->run($input);
            } catch (Throwable $failure) {
                throw OpenAiResponsesInvocationException::externalService('application_run', $failure);
            }
            if (ExternalServiceOutcome::Succeeded !== $applicationResult->operationResult()->outcome()) {
                throw OpenAiResponsesInvocationException::externalService('operation_outcome');
            }
            $response = $applicationResult->egressResult()->operationResult()->response();
            if (null === $response) {
                throw OpenAiResponsesInvocationException::externalService('response_missing');
            }
            $body = (new OpenAiResponsesResponseBodyReader())->read($response);
            $decoded = (new ResponsesResponseDecoder())->decode($source->request(), $profile, $body);
            $result = (new OpenAiResponsesResultNormalizer())->normalize($source->request(), $decoded);
            $this->state = OpenAiResponsesInvocationSessionState::Emitted;

            return InvocationAdapterEmission::result($result);
        } catch (OpenAiResponsesInvocationException $failure) {
            $this->state = OpenAiResponsesInvocationSessionState::Failed;
            throw $failure;
        } catch (Throwable $failure) {
            $this->state = OpenAiResponsesInvocationSessionState::Failed;
            throw OpenAiResponsesInvocationException::externalService('application_run', $failure);
        } finally {
            $this->release();
            unset($source, $profile, $context, $applicationProfile, $encoded, $application, $requestFactory, $input, $applicationResult, $response, $body, $decoded, $result);
        }
    }

    public function close(): void
    {
        if (OpenAiResponsesInvocationSessionState::Closed === $this->state) {
            return;
        }
        $this->release();
        $this->state = OpenAiResponsesInvocationSessionState::Closed;
    }

    private function release(): void
    {
        $this->source = null;
        $this->profile = null;
        $this->context = null;
        $this->applicationProfile = null;
        $this->encodedRequest = null;
    }
}
