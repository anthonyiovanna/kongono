<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceContract\Binding\ServiceBinding;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;

/** Produces a versioned LF-terminated projection of bounded identity evidence. */
#[Api(ApiStatus::Internal)]
final class CanonicalProfile
{
    private const string PACKAGE = 'kongono/external-service-application';
    private const string CONTRACT = 'kongono.external-service-application.profile/v1;exact-current-binding;exact-current-egress;operation-scoped-credential;single-terminal-operation';

    public static function bytes(
        ExternalServiceBindingArtifact $bindingArtifact,
        ServiceBinding $binding,
        NetworkEgressApplicationProfile $networkProfile,
    ): string {
        $claims = [
            ['package', self::PACKAGE],
            ['application_profile_version', '1'],
            ['profile_contract', self::CONTRACT],
            ['binding_source_identity', $bindingArtifact->sourceIdentity()->value()],
            ['binding_source_sha256', $bindingArtifact->sourceSha256()],
            ['binding_artifact_sha256', $bindingArtifact->artifactSha256()],
            ['binding_identity', $binding->identity()->value()],
            ['service_profile_identity', $binding->profile()->value()],
            ['network_application_profile_identity', $networkProfile->identity()->value()],
            ['policy_identity', $networkProfile->source()->identity()->value()],
            ['policy_source_sha256', $networkProfile->artifact()->sourceSha256()],
            ['policy_artifact_sha256', $networkProfile->artifact()->artifactSha256()],
        ];

        $bytes = '';
        foreach ($claims as [$name, $value]) {
            $bytes .= strlen($name) . ':' . $name . ':' . strlen($value) . ':' . $value . "\n";
        }

        return $bytes;
    }

    private function __construct() {}
}
