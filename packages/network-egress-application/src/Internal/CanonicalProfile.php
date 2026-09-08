<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifact;
use Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicySource;

/** Produces a versioned LF-terminated projection of bounded identity evidence. */
#[Api(ApiStatus::Internal)]
final class CanonicalProfile
{
    private const string CONTRACT = 'kongono.network-egress-application.profile/v1;exact-current-policy;direct-resolver;direct-transport;single-terminal-operation';

    public static function bytes(EgressPolicySource $source, EgressPolicyArtifact $artifact): string
    {
        $claims = [
            ['application_profile_version', '1'],
            ['profile_contract', self::CONTRACT],
            ['policy_identity', $source->identity()->value()],
            ['source_sha256', $artifact->sourceSha256()],
            ['artifact_sha256', $artifact->artifactSha256()],
        ];
        foreach ($source->requiredCapabilities()->all() as $position => $capability) {
            $claims[] = ['required_capability.' . $position, $capability->value];
        }

        $bytes = '';
        foreach ($claims as [$name, $value]) {
            $bytes .= strlen($name) . ':' . $name . ':' . strlen($value) . ':' . $value . "\n";
        }

        return $bytes;
    }

    public static function capabilitySha256(EgressPolicySource $source): string
    {
        $bytes = '';
        foreach ($source->requiredCapabilities()->all() as $capability) {
            $bytes .= $capability->value . "\n";
        }

        return hash('sha256', $bytes);
    }

    private function __construct() {}
}
