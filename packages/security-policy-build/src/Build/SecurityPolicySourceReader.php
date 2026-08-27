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
use Kongono\SecurityPolicyBuild\Exception\InvalidSecurityPolicySourceException;
use Kongono\SecurityPolicyBuild\Internal\CanonicalJson;
use Kongono\SecurityPolicyBuild\Internal\PathGuard;
use Kongono\SecurityPolicyBuild\Model\SecurityPolicyModel;
use Throwable;

/** Reads and compiles the sole closed rule-free policy identity source. */
#[Api(ApiStatus::Experimental)]
final class SecurityPolicySourceReader
{
    /** @return array{model:SecurityPolicyModel,path:string,bytes:string,size:int,sha256:string,absolute:string,stat:array<string|int,mixed>} */
    public function read(string $applicationRoot): array
    {
        $root = PathGuard::applicationRoot($applicationRoot);
        $snapshot = PathGuard::source($root);
        $this->validateBytes($snapshot['bytes']);
        try {
            $source = CanonicalJson::decodeObject($snapshot['bytes'], 8);
        } catch (JsonException) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        if (!$this->hasExactKeys($source, ['schema', 'version', 'policy'])) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        if ('kongono.security-policy' !== $source['schema'] || 1 !== $source['version']) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        $policy = $source['policy'];
        if (!is_array($policy) || array_is_list($policy) || !$this->hasExactKeys($policy, ['name', 'version', 'provenance'])) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        if (!is_string($policy['name']) || !is_string($policy['version']) || !is_string($policy['provenance'])) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        try {
            $reference = new AuthorizationPolicyReference(
                new AuthorizationPolicyName($policy['name']),
                new AuthorizationPolicyVersion($policy['version']),
                new AuthorizationPolicyProvenance($policy['provenance']),
            );
        } catch (Throwable) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }

        return ['model' => new SecurityPolicyModel($reference)] + $snapshot;
    }

    private function validateBytes(string $bytes): void
    {
        if ('' === $bytes || strlen($bytes) > PathGuard::MAX_SOURCE_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || 1 !== preg_match('//u', $bytes)) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
        if (1 === preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $bytes)) {
            throw new InvalidSecurityPolicySourceException(SecurityPolicyBuildDiagnostic::InvalidSource);
        }
    }

    /** @param array<array-key, mixed> $value
     * @param list<string> $expected
     */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = [];
        foreach (array_keys($value) as $key) {
            if (!is_string($key)) {
                return false;
            }
            $keys[] = $key;
        }
        sort($keys, SORT_STRING);
        sort($expected, SORT_STRING);

        return $keys === $expected;
    }
}
