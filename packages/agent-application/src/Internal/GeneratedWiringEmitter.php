<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Internal;

use JsonException;
use Kongono\AgentApplication\Exception\InvalidGeneratedAgentWiringException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Emits one fixed ordinary-PHP template from already validated bounded metadata. */
#[Api(ApiStatus::Internal)]
final class GeneratedWiringEmitter
{
    /** @param array<string, mixed> $metadata */
    public static function emit(array $metadata): string
    {
        try {
            $json = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_json', $failure);
        }
        $namespace = self::text($metadata, 'generated_namespace');
        $profile = self::text($metadata, 'profile_identity');
        $artifact = self::text($metadata, 'declaration_artifact_sha256');
        $declaration = self::text($metadata, 'declaration_identity');
        $admission = self::text($metadata, 'admission_identity');
        $capabilities = var_export($metadata['permitted_capability_identities'], true);

        return <<<PHP
<?php

declare(strict_types=1);

// {$json}

namespace {$namespace};

use Kongono\AgentApplication\Exception\InvalidAgentApplicationProfileException;
use Kongono\AgentApplication\Generated\GeneratedAgentApplicationBoundary;
use Kongono\AgentApplication\Profile\AgentApplicationProfile;
use Kongono\AgentApplication\Runtime\AgentApplication;
use Kongono\AgentPrincipalContract\Capability\CapabilityIdentity;

final class AgentApplicationBoundary implements GeneratedAgentApplicationBoundary
{
    public function start(AgentApplicationProfile \$profile): AgentApplication
    {
        \$capabilities = array_map(
            static fn(CapabilityIdentity \$identity): string => \$identity->value(),
            \$profile->permittedCapabilityIdentities(),
        );
        if (
            !hash_equals('{$profile}', \$profile->identity()->value())
            || !hash_equals('{$artifact}', \$profile->declarationArtifact()->artifactSha256())
            || '{$declaration}' !== \$profile->declaration()->identity()->value()
            || !hash_equals('{$admission}', \$profile->authorityPlan()->identity()->value())
            || {$capabilities} !== \$capabilities
        ) {
            throw InvalidAgentApplicationProfileException::invalid('generated_profile_mismatch');
        }

        return new AgentApplication(\$profile);
    }
}
PHP . "\n";
    }

    /** @param array<string, mixed> $metadata */
    private static function text(array $metadata, string $key): string
    {
        $value = $metadata[$key] ?? null;
        if (!is_string($value)) {
            throw InvalidGeneratedAgentWiringException::invalid('metadata_shape');
        }

        return $value;
    }

    private function __construct() {}
}
