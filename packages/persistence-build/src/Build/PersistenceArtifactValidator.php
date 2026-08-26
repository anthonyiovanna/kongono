<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Exception\StalePersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;
use Throwable;

/** Separately proves artifact structure and exact equality with current inputs. */
#[Api(ApiStatus::Experimental)]
final class PersistenceArtifactValidator
{
    public function validateStructure(string $artifactBytes): PersistenceArtifact
    {
        if (
            '' === $artifactBytes
            || strlen($artifactBytes) > PersistenceArtifactBuilder::MAX_ARTIFACT_BYTES
            || str_starts_with($artifactBytes, "\xEF\xBB\xBF")
            || str_contains($artifactBytes, "\r")
            || str_contains($artifactBytes, "\0")
            || 1 !== preg_match('//u', $artifactBytes)
            || !str_ends_with($artifactBytes, "\n")
            || str_ends_with($artifactBytes, "\n\n")
        ) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactBytes);
        }
        try {
            $artifact = CanonicalJson::decodeObject($artifactBytes, 96);
        } catch (JsonException) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        if (CanonicalJson::encode($artifact) !== $artifactBytes) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $this->assertKeys($artifact, ['schema', 'version', 'model_schema', 'model_version', 'source', 'provenance', 'model']);
        if (
            'kongono.persistence-artifact' !== ($artifact['schema'] ?? null)
            || 1 !== ($artifact['version'] ?? null)
            || 'kongono.persistence-model' !== ($artifact['model_schema'] ?? null)
            || 1 !== ($artifact['model_version'] ?? null)
        ) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $source = $this->sourceIdentity($artifact['source'] ?? null, PathGuard::SOURCE_BASENAME);
        $provenance = $this->object($artifact['provenance'] ?? null);
        $this->assertKeys($provenance, [
            'composer_json',
            'composer_lock',
            'persistence_source',
            'mapping_source',
            'mapping_artifact',
            'mapping_model',
            'persistence_builder',
            'domain_sources',
            'generated_namespace',
            'persistence_model',
        ]);
        $this->byteIdentity($provenance['composer_json'] ?? null);
        $this->byteIdentity($provenance['composer_lock'] ?? null);
        $provenanceSource = $this->sourceIdentity($provenance['persistence_source'] ?? null, PathGuard::SOURCE_BASENAME);
        if ($source !== $provenanceSource) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
        }
        $this->sourceIdentity($provenance['mapping_source'] ?? null, PathGuard::MAPPING_SOURCE_BASENAME);
        $mappingArtifact = $this->object($provenance['mapping_artifact'] ?? null);
        $this->assertKeys($mappingArtifact, ['bytes', 'sha256', 'builder_implementation_sha256']);
        $this->positiveInteger($mappingArtifact['bytes'] ?? null, PersistenceArtifactBuilder::MAX_ARTIFACT_BYTES);
        $mappingArtifactSha256 = $this->digest($mappingArtifact['sha256'] ?? null);
        $this->digest($mappingArtifact['builder_implementation_sha256'] ?? null);
        $this->byteIdentity($provenance['mapping_model'] ?? null);
        $builder = $this->object($provenance['persistence_builder'] ?? null);
        $this->assertKeys($builder, ['package', 'contract_version', 'implementation_sha256']);
        if ('kongono/persistence-build' !== ($builder['package'] ?? null) || 1 !== ($builder['contract_version'] ?? null)) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $implementationSha256 = $this->digest($builder['implementation_sha256'] ?? null);
        $modelData = $this->object($artifact['model'] ?? null);
        try {
            $compiler = new PersistenceModelCompiler();
            $model = $compiler->hydrateModel($modelData);
        } catch (Throwable) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        if (!is_string($provenance['generated_namespace'] ?? null) || $provenance['generated_namespace'] !== $model->generatedNamespace()) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
        }
        $modelBytes = CanonicalJson::encode($modelData);
        if (CanonicalJson::encode($compiler->modelData($model)) !== $modelBytes) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $modelIdentity = $this->byteIdentity($provenance['persistence_model'] ?? null);
        if ($modelIdentity['bytes'] !== strlen($modelBytes) || !hash_equals($modelIdentity['sha256'], hash('sha256', $modelBytes))) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
        }
        $domainValues = $this->list($provenance['domain_sources'] ?? null);
        if (count($domainValues) !== count($model->entities())) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
        }
        foreach ($domainValues as $ordinal => $domainValue) {
            $domain = $this->object($domainValue);
            $this->assertKeys($domain, ['entity', 'path', 'bytes', 'sha256']);
            $entity = $model->entities()[$ordinal];
            if (
                ($domain['entity'] ?? null) !== $entity->entity()
                || ($domain['path'] ?? null) !== $entity->sourcePath()
                || ($domain['bytes'] ?? null) !== $entity->sourceBytes()
                || ($domain['sha256'] ?? null) !== $entity->sourceSha256()
            ) {
                $this->invalid(PersistenceBuildFailureCategory::ArtifactIdentity);
            }
        }

        return new PersistenceArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $source['sha256'],
            $modelIdentity['sha256'],
            $mappingArtifactSha256,
            $implementationSha256,
            $model,
        );
    }

    public function validateCurrent(string $applicationRoot, string $sourceRelativePath, string $artifactBytes): PersistenceArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new PersistenceArtifactBuilder())->build($applicationRoot, $sourceRelativePath);
        if (!hash_equals($current->canonicalBytes(), $artifactBytes)) {
            throw StalePersistenceArtifactException::currentMismatch();
        }

        return $artifact;
    }

    /** @return array{path:string,bytes:int,sha256:string} */
    private function sourceIdentity(mixed $value, string $basename): array
    {
        $identity = $this->object($value);
        $this->assertKeys($identity, ['path', 'bytes', 'sha256']);
        $path = $identity['path'] ?? null;
        if (!is_string($path) || basename($path) !== $basename || str_contains($path, '\\') || str_starts_with($path, '/') || str_contains($path, ':')) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
        }

        return ['path' => $path, 'bytes' => $this->positiveInteger($identity['bytes'] ?? null, PathGuard::MAX_SOURCE_BYTES), 'sha256' => $this->digest($identity['sha256'] ?? null)];
    }

    /** @return array{bytes:int,sha256:string} */
    private function byteIdentity(mixed $value): array
    {
        $identity = $this->object($value);
        $this->assertKeys($identity, ['bytes', 'sha256']);

        return ['bytes' => $this->positiveInteger($identity['bytes'] ?? null, PersistenceArtifactBuilder::MAX_ARTIFACT_BYTES), 'sha256' => $this->digest($identity['sha256'] ?? null)];
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private function assertKeys(array $value, array $keys): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
    }

    private function positiveInteger(mixed $value, int $maximum): int
    {
        if (!is_int($value) || $value < 1 || $value > $maximum) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function invalid(PersistenceBuildFailureCategory $category): never
    {
        throw InvalidPersistenceArtifactException::invalid($category);
    }
}
