<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Generated;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifact;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactValidator;
use Kongono\ExternalServiceBindingBuild\Exception\InvalidGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceBindingBuild\Exception\StaleGeneratedExternalServiceBindingsException;
use Kongono\ExternalServiceBindingBuild\Internal\BindingArtifactCodec;
use Kongono\ExternalServiceBindingBuild\Internal\CanonicalJson;
use Kongono\ExternalServiceBindingBuild\Internal\GeneratedBindingsEmitter;
use Kongono\ExternalServiceBindingBuild\Internal\GeneratedBindingsImplementation;
use Kongono\ExternalServiceBindingBuild\Source\ExternalServiceBindingSource;
use Throwable;

/** Reconstructs and re-emits generated PHP as inert bytes without loading it. */
#[Api(ApiStatus::Experimental)]
final class GeneratedExternalServiceBindingsValidator
{
    public function validateStructure(string $bytes): GeneratedExternalServiceBindings
    {
        $length = strlen($bytes);
        if ($length > CanonicalJson::MAX_GENERATED_BYTES) {
            throw InvalidGeneratedExternalServiceBindingsException::limitExceeded($length, CanonicalJson::MAX_GENERATED_BYTES);
        }
        if (
            '' === $bytes
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
            || 1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n\n/D', $bytes, $matches)
        ) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('generated_header');
        }

        try {
            $metadata = CanonicalJson::decodeInlineObject($matches[1]);
            if (!hash_equals(CanonicalJson::encodeInline($metadata), $matches[1])) {
                throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_json');
            }
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidGeneratedExternalServiceBindingsException) {
                throw $failure;
            }
            throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_json', previous: $failure);
        }
        $this->keys($metadata, ['schema', 'version', 'contract_version', 'generator', 'source_identity', 'source_sha256', 'binding_artifact_sha256', 'bindings']);
        if ('kongono.external-service-bindings.generated-php' !== ($metadata['schema'] ?? null) || 1 !== ($metadata['version'] ?? null) || 1 !== ($metadata['contract_version'] ?? null)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('schema_or_version', 'schema');
        }
        $generator = $this->object($metadata['generator'] ?? null, 'generator');
        $this->keys($generator, ['package', 'version', 'implementation_sha256'], 'generator');
        if ('kongono/external-service-binding-build' !== ($generator['package'] ?? null) || 1 !== ($generator['version'] ?? null)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('generator_contract', 'generator');
        }
        $implementationSha256 = $this->digest($generator['implementation_sha256'] ?? null, 'implementation_sha256');
        if (!hash_equals(GeneratedBindingsImplementation::sha256(), $implementationSha256)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('implementation_mismatch', 'implementation_sha256');
        }
        $sourceSha256 = $this->digest($metadata['source_sha256'] ?? null, 'source_sha256');
        $artifactSha256 = $this->digest($metadata['binding_artifact_sha256'] ?? null, 'binding_artifact_sha256');

        try {
            $source = BindingArtifactCodec::source([
                'source_identity' => $metadata['source_identity'] ?? null,
                'bindings' => $metadata['bindings'] ?? null,
            ]);
            $artifact = (new ExternalServiceBindingArtifactBuilder())->build($source);
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw InvalidGeneratedExternalServiceBindingsException::invalid('source_invalid', 'bindings', previous: $failure);
        }
        if (!hash_equals($artifact->sourceSha256(), $sourceSha256) || !hash_equals($artifact->artifactSha256(), $artifactSha256)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('artifact_mismatch');
        }
        try {
            $expected = GeneratedBindingsEmitter::emit($metadata, $source->bindings());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error || $failure instanceof InvalidGeneratedExternalServiceBindingsException) {
                throw $failure;
            }
            throw InvalidGeneratedExternalServiceBindingsException::invalid('template_mismatch', previous: $failure);
        }
        if (!hash_equals($expected, $bytes)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('template_mismatch');
        }

        return new GeneratedExternalServiceBindings($bytes, hash('sha256', $bytes), $source->identity(), $sourceSha256, $artifactSha256, $source->bindings(), $implementationSha256);
    }

    public function validateCurrent(ExternalServiceBindingSource $source, ExternalServiceBindingArtifact $artifact, string $bytes): GeneratedExternalServiceBindings
    {
        $validated = $this->validateStructure($bytes);
        try {
            $currentArtifact = (new ExternalServiceBindingArtifactValidator())->validateCurrent($source, $artifact->canonicalBytes());
        } catch (Throwable $failure) {
            if ($failure instanceof \Error) {
                throw $failure;
            }
            throw StaleGeneratedExternalServiceBindingsException::currentnessMismatch();
        }
        if (
            !hash_equals($currentArtifact->artifactSha256(), $artifact->artifactSha256())
            || !hash_equals($currentArtifact->sourceSha256(), $artifact->sourceSha256())
            || !$currentArtifact->source()->equals($artifact->source())
        ) {
            throw StaleGeneratedExternalServiceBindingsException::currentnessMismatch();
        }
        $expected = (new GeneratedExternalServiceBindingsGenerator())->generate($currentArtifact);
        if (
            !$validated->sourceIdentity()->equals($source->identity())
            || !hash_equals($expected->canonicalBytes(), $bytes)
            || !hash_equals($expected->generatedPhpSha256(), $validated->generatedPhpSha256())
            || !hash_equals($expected->bindingArtifactSha256(), $validated->bindingArtifactSha256())
            || !hash_equals($expected->sourceSha256(), $validated->sourceSha256())
        ) {
            throw StaleGeneratedExternalServiceBindingsException::currentnessMismatch();
        }

        return $validated;
    }

    /** @param array<string, mixed> $object
     * @param list<string> $expected
     */
    private function keys(array $object, array $expected, ?string $field = null): void
    {
        if (array_keys($object) !== $expected) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_shape', $field);
        }
    }

    /** @return array<string, mixed> */
    private function object(mixed $value, string $field): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_shape', $field);
        }

        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidGeneratedExternalServiceBindingsException::invalid('metadata_shape', $field);
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    private function digest(mixed $value, string $field): string
    {
        if (!is_string($value) || 1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidGeneratedExternalServiceBindingsException::invalid('digest_grammar', $field);
        }

        return $value;
    }
}
