<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Exception\InvalidGeneratedNetworkEgressWiringException;

/** Emits one fixed ordinary-PHP template from bounded validated metadata. */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringEmitter
{
    /** @param array<string, mixed> $metadata */
    public static function emit(array $metadata): string
    {
        try {
            $json = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_json', $failure);
        }
        $namespace = self::text($metadata, 'generated_namespace');
        $applicationProfile = self::text($metadata, 'application_profile_identity');
        $policy = self::text($metadata, 'policy_identity');
        $artifact = self::text($metadata, 'artifact_sha256');
        $source = self::text($metadata, 'source_sha256');
        $capabilities = self::text($metadata, 'required_capabilities_sha256');

        return <<<PHP
<?php

declare(strict_types=1);

// {$json}

namespace {$namespace};

use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;
use Kongono\NetworkEgressApplication\Generated\GeneratedNetworkEgressApplicationBoundary;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfile;
use Kongono\NetworkEgressApplication\Runtime\NetworkEgressApplication;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactValidator;
use Kongono\NetworkEgressRuntime\Resolver\EgressResolver;
use Kongono\NetworkEgressRuntime\Transport\EgressTransport;

final class NetworkEgressApplicationBoundary implements GeneratedNetworkEgressApplicationBoundary
{
    public function start(NetworkEgressApplicationProfile \$profile, EgressResolver \$resolver, EgressTransport \$transport): NetworkEgressApplication
    {
        \$current = (new EgressPolicyArtifactValidator())->validateCurrent(\$profile->source(), \$profile->artifact()->canonicalBytes());
        \$capabilityBytes = '';
        foreach (\$profile->source()->requiredCapabilities()->all() as \$capability) {
            \$capabilityBytes .= \$capability->value . "\\n";
        }
        if (
            !hash_equals('{$applicationProfile}', \$profile->identity()->value())
            || '{$policy}' !== \$profile->source()->identity()->value()
            || '{$policy}' !== \$current->policyIdentity()->value()
            || !hash_equals('{$artifact}', \$current->artifactSha256())
            || !hash_equals('{$source}', \$current->sourceSha256())
            || !hash_equals('{$capabilities}', hash('sha256', \$capabilityBytes))
        ) {
            throw InvalidNetworkEgressApplicationProfileException::invalid('generated_profile_mismatch');
        }

        return new NetworkEgressApplication(\$profile, \$resolver, \$transport);
    }
}
PHP . "\n";
    }

    /** @param array<string, mixed> $metadata */
    private static function text(array $metadata, string $key): string
    {
        $value = $metadata[$key] ?? null;
        if (!is_string($value)) {
            throw InvalidGeneratedNetworkEgressWiringException::invalid('metadata_shape');
        }

        return $value;
    }

    private function __construct() {}
}
