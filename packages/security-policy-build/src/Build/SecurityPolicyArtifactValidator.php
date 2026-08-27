<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Authorization\AuthorizationPolicyName;
use Kongono\Authorization\AuthorizationPolicyProvenance;
use Kongono\Authorization\AuthorizationPolicyReference;
use Kongono\Authorization\AuthorizationPolicyVersion;
use Kongono\SecurityPolicyBuild\Diagnostic\SecurityPolicyBuildDiagnostic;
use Kongono\SecurityPolicyBuild\Exception\InvalidSecurityPolicyArtifactException;
use Kongono\SecurityPolicyBuild\Exception\StaleSecurityPolicyArtifactException;
use Kongono\SecurityPolicyBuild\Internal\CanonicalJson;
use Kongono\SecurityPolicyBuild\Internal\PathGuard;
use Throwable;

/** Separately proves canonical structure and equality with complete current state. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicyArtifactValidator
{
    public function validateStructure(string $bytes): SecurityPolicyArtifact
    {
        if (
            '' === $bytes
            || strlen($bytes) > SecurityPolicyArtifactBuilder::MAX_ARTIFACT_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            $this->invalid();
        }
        try {
            $data = CanonicalJson::decodeObject($bytes, 16);
        } catch (JsonException) {
            $this->invalid();
        }
        if (CanonicalJson::encode($data) !== $bytes || array_keys($data) !== ['schema', 'version', 'policy', 'source', 'model_sha256', 'builder']) {
            $this->invalid();
        }
        if ('kongono.security-policy-artifact' !== $data['schema'] || 1 !== $data['version']) {
            $this->invalid();
        }
        $policy = $this->object($data['policy']);
        if (array_keys($policy) !== ['name', 'version', 'provenance']) {
            $this->invalid();
        }
        try {
            $reference = new AuthorizationPolicyReference(
                new AuthorizationPolicyName($this->text($policy['name'])),
                new AuthorizationPolicyVersion($this->text($policy['version'])),
                new AuthorizationPolicyProvenance($this->text($policy['provenance'])),
            );
        } catch (Throwable) {
            $this->invalid();
        }
        $source = $this->object($data['source']);
        if (array_keys($source) !== ['path', 'sha256'] || PathGuard::SOURCE_PATH !== $source['path']) {
            $this->invalid();
        }
        $sourceSha256 = $this->digest($source['sha256']);
        $modelSha256 = $this->digest($data['model_sha256']);
        if (hash('sha256', CanonicalJson::encode($policy)) !== $modelSha256) {
            $this->invalid();
        }
        $builder = $this->object($data['builder']);
        if (
            array_keys($builder) !== ['package', 'contract_version', 'implementation_sha256']
            || 'kongono/security-policy-build' !== $builder['package']
            || SecurityPolicyArtifactBuilder::CONTRACT_VERSION !== $builder['contract_version']
        ) {
            $this->invalid();
        }
        $implementationSha256 = $this->digest($builder['implementation_sha256']);

        return new SecurityPolicyArtifact(
            $bytes,
            hash('sha256', $bytes),
            $sourceSha256,
            $modelSha256,
            $implementationSha256,
            $reference,
        );
    }

    public function validateCurrent(string $applicationRoot, string $bytes): SecurityPolicyArtifact
    {
        $artifact = $this->validateStructure($bytes);
        $current = (new SecurityPolicyArtifactBuilder())->build($applicationRoot);
        if (!hash_equals($current->canonicalBytes(), $bytes)) {
            throw new StaleSecurityPolicyArtifactException(SecurityPolicyBuildDiagnostic::StaleArtifact);
        }

        return $artifact;
    }

    /** @return array<string,mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid();
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                $this->invalid();
            }
            $object[$key] = $item;
        }

        return $object;
    }

    private function text(mixed $value): string
    {
        if (!is_string($value)) {
            $this->invalid();
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid();
        }

        return $value;
    }

    private function invalid(): never
    {
        throw new InvalidSecurityPolicyArtifactException(SecurityPolicyBuildDiagnostic::InvalidArtifact);
    }
}
