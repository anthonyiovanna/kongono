<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Artifact;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Exception\StaleExternalServiceBindingArtifactException;
use Kongono\ExternalServiceBindingBuild\Internal\BindingArtifactCodec;
use Kongono\ExternalServiceBindingBuild\Internal\CanonicalJson;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Throwable;

/** Validates canonical binding structure and caller-relative currentness separately. */
#[Api(ApiStatus::Experimental)]
final class ExternalServiceBindingArtifactValidator
{
    public function validateStructure(string $bytes): ExternalServiceBindingArtifact
    {
        if (strlen($bytes) > CanonicalJson::MAX_ARTIFACT_BYTES) {
            throw InvalidExternalServiceBindingArtifactException::limitExceeded(strlen($bytes), CanonicalJson::MAX_ARTIFACT_BYTES);
        }
        try {
            $data = CanonicalJson::decodeObject($bytes);
            if (!hash_equals(CanonicalJson::encode($data), $bytes)) {
                throw InvalidExternalServiceBindingArtifactException::invalid('noncanonical_json');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidExternalServiceBindingArtifactException) {
                throw $failure;
            }
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_json', artifactBytes: strlen($bytes), previous: $failure);
        }
        $this->keys($data, ['schema', 'artifact_version', 'contract_version', 'builder', 'source', 'source_sha256']);
        if ('kongono.external-service-binding-artifact' !== ($data['schema'] ?? null) || 1 !== ($data['artifact_version'] ?? null) || 1 !== ($data['contract_version'] ?? null)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('schema_or_version', 'schema');
        }
        $builder = $this->object($data['builder'] ?? null, 'builder');
        $this->keys($builder, ['package', 'version'], 'builder');
        if ('kongono/external-service-binding-build' !== ($builder['package'] ?? null) || 1 !== ($builder['version'] ?? null)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('builder_contract', 'builder');
        }

        try {
            $source = BindingArtifactCodec::source($data['source'] ?? null);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidExternalServiceBindingArtifactException) {
                throw $failure;
            }
            throw InvalidExternalServiceBindingArtifactException::invalid('source_invalid', 'source', previous: $failure);
        }
        $sourceSha256 = $this->digest($data['source_sha256'] ?? null, 'source_sha256');
        $rebuilt = (new ExternalServiceBindingArtifactBuilder())->build($source);
        if (!hash_equals($rebuilt->sourceSha256(), $sourceSha256) || !hash_equals($rebuilt->canonicalBytes(), $bytes)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('source_digest_mismatch', 'source_sha256');
        }

        return $rebuilt;
    }

    public function validateCurrent(ExternalServiceBindingSource $source, string $bytes): ExternalServiceBindingArtifact
    {
        $artifact = $this->validateStructure($bytes);
        $current = (new ExternalServiceBindingArtifactBuilder())->build($source);
        if (
            !hash_equals($current->canonicalBytes(), $bytes)
            || !hash_equals($current->sourceSha256(), $artifact->sourceSha256())
            || !hash_equals($current->artifactSha256(), $artifact->artifactSha256())
        ) {
            throw StaleExternalServiceBindingArtifactException::currentnessMismatch();
        }

        return $artifact;
    }

    /** @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected, ?string $field = null): void
    {
        if (array_keys($object) !== $expected) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $field): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field);
        }

        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidExternalServiceBindingArtifactException::invalid('invalid_shape', $field);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    private function digest(mixed $value, string $field): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidExternalServiceBindingArtifactException::invalid('digest_grammar', $field);
        }

        return $value;
    }
}
