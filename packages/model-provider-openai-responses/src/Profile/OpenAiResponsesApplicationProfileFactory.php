<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Profile;

use Error;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfileFactory;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelProviderOpenAiResponses\Adapter\OpenAiResponsesInvocationAdapter;
use Kongono\ModelProviderOpenAiResponses\Exception\InvalidOpenAiResponsesApplicationProfileException;
use Kongono\ModelProviderOpenAiResponses\Internal\CanonicalApplicationProfile;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\NetworkEgressContract\Origin\OriginScheme;
use Throwable;

/** Re-establishes all exact-current component relations before deriving identity. */
#[Api(ApiStatus::Experimental)]
final class OpenAiResponsesApplicationProfileFactory
{
    public function create(
        OpenAiResponsesProfile $providerProfile,
        ModelInvocationApplicationProfile $modelProfile,
        ExternalServiceApplicationProfile $externalProfile,
    ): OpenAiResponsesApplicationProfile {
        try {
            $currentModel = (new ModelInvocationApplicationProfileFactory())->create(
                $modelProfile->source(),
                $modelProfile->artifact()->canonicalBytes(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('model_application_profile', $failure);
        }
        if (!$currentModel->identity()->equals($modelProfile->identity())) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('model_application_profile');
        }

        try {
            $currentExternal = (new ExternalServiceApplicationProfileFactory())->create(
                $externalProfile->bindingSource(),
                $externalProfile->bindingArtifact()->canonicalBytes(),
                $externalProfile->binding()->identity(),
                $externalProfile->networkEgressProfile(),
            );
        } catch (Throwable $failure) {
            if ($failure instanceof Error) {
                throw $failure;
            }
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('external_service_application_profile', $failure);
        }
        if (!$currentExternal->identity()->equals($externalProfile->identity())) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('external_service_application_profile');
        }

        $source = $modelProfile->source();
        $request = $source->request();
        if (!$source->adapterIdentity()->equals(new AdapterIdentity(OpenAiResponsesInvocationAdapter::IDENTITY))) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('adapter_identity');
        }
        if (!$providerProfile->neutralProfile()->equals($request->profile())) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('neutral_profile');
        }
        if ('openai' !== $providerProfile->neutralProfile()->providerIdentity()->value()) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('provider_identity');
        }
        $endpoint = $externalProfile->binding()->endpoint();
        if (OriginScheme::Https !== $endpoint->origin()->scheme()) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('endpoint_scheme');
        }
        if (!$endpoint->basePath()->isEmpty()) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('endpoint_base_path');
        }
        $supported = match ($providerProfile->mappingVersion()) {
            OpenAiResponsesMappingVersion::Version1 => ResultMode::Text === $request->resultMode(),
            OpenAiResponsesMappingVersion::Version2 => !$request->streaming()
                && in_array($request->resultMode(), [ResultMode::Structured, ResultMode::ActionSuggestion], true),
        };
        if (!$supported) {
            throw InvalidOpenAiResponsesApplicationProfileException::invalid('mapping_surface');
        }

        $identity = new OpenAiResponsesApplicationProfileIdentity(hash('sha256', CanonicalApplicationProfile::bytes(
            $providerProfile,
            $modelProfile,
            $externalProfile,
        )));

        return new OpenAiResponsesApplicationProfile($providerProfile, $modelProfile, $externalProfile, $identity);
    }
}
