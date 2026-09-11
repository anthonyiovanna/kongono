<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponses\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Profile\ExternalServiceApplicationProfile;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfile;
use Kongono\ModelInvocationContract\Invocation\ResultMode;
use Kongono\ModelProviderOpenAiResponses\OpenAiResponsesMappingVersion;
use Kongono\ModelProviderOpenAiResponses\Profile\OpenAiResponsesProfile;

/** Emits the fixed bounded nondisclosing Phase 17F identity projection. */
#[Api(ApiStatus::Internal)]
final class CanonicalApplicationProfile
{
    public static function bytes(
        OpenAiResponsesProfile $providerProfile,
        ModelInvocationApplicationProfile $modelProfile,
        ExternalServiceApplicationProfile $externalProfile,
    ): string {
        $artifact = $modelProfile->artifact();
        $request = $modelProfile->source()->request();
        $lines = [
            'contract=kongono-openai-responses-application-profile/1',
            'model_application_profile_identity=' . $modelProfile->identity()->value(),
            'plan_identity=' . $modelProfile->source()->identity()->value(),
            'plan_source_sha256=' . $artifact->sourceSha256(),
            'plan_artifact_sha256=' . $artifact->artifactSha256(),
            'plan_request_sha256=' . $artifact->requestSha256(),
            'invocation_identity=' . $request->identity()->value(),
            'neutral_profile_identity=' . $request->profile()->identity()->value(),
            'adapter_identity=' . $modelProfile->source()->adapterIdentity()->value(),
            'external_service_application_profile_identity=' . $externalProfile->identity()->value(),
            'binding_identity=' . $externalProfile->binding()->identity()->value(),
            'mapping_version=' . $providerProfile->mappingVersion()->value,
            'storage_preference=' . $providerProfile->storagePreference()->value,
            'mode=' . $request->resultMode()->value,
        ];
        if (OpenAiResponsesMappingVersion::Version2 === $providerProfile->mappingVersion()) {
            if (ResultMode::Structured === $request->resultMode()) {
                $definition = $providerProfile->structuredOutput();
                if (null !== $definition) {
                    $lines[] = 'definition_name=' . $definition->name();
                    $lines[] = 'schema_sha256=' . hash('sha256', $definition->schema()->canonicalJson());
                }
            } else {
                $definition = $providerProfile->functionDefinition();
                if (null !== $definition) {
                    $lines[] = 'definition_name=' . $definition->name()->value();
                    $lines[] = 'schema_sha256=' . hash('sha256', $definition->parameters()->canonicalJson());
                }
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function __construct() {}
}
