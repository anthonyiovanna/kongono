<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Build;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Exception\InvalidMappingArtifactException;
use Kongono\DatabaseMapping\Exception\StaleMappingArtifactException;
use Kongono\DatabaseMapping\Internal\CanonicalJson;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;
use Kongono\DatabaseMapping\Internal\MappingImplementation;
use Kongono\DatabaseMapping\Internal\PathGuard;
use Kongono\DatabaseSql\SqlIdentifier;
use Throwable;

/** Proves artifact structure and exact equality with current application evidence. */
#[Api(ApiStatus::Experimental)]
final class MappingArtifactValidator
{
    public function validateStructure(string $artifactBytes): MappingArtifact
    {
        if (
            '' === $artifactBytes
            || strlen($artifactBytes) > MappingArtifactBuilder::MAX_ARTIFACT_BYTES
            || str_starts_with($artifactBytes, "\xEF\xBB\xBF")
            || str_contains($artifactBytes, "\r")
            || str_contains($artifactBytes, "\0")
            || 1 !== preg_match('//u', $artifactBytes)
            || !str_ends_with($artifactBytes, "\n")
            || str_ends_with($artifactBytes, "\n\n")
        ) {
            $this->invalid(MappingFailureCategory::ArtifactBytes);
        }
        try {
            $artifact = CanonicalJson::decodeObject($artifactBytes, 64);
        } catch (JsonException) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        if (CanonicalJson::encode($artifact) !== $artifactBytes) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        $this->assertKeys($artifact, ['schema', 'version', 'source', 'project', 'builder', 'declaration_state', 'model_sha256', 'model']);
        if ('kongono.data-mapping-artifact' !== ($artifact['schema'] ?? null) || 1 !== ($artifact['version'] ?? null)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        $source = $this->object($artifact['source'] ?? null);
        $this->assertKeys($source, ['path', 'bytes', 'sha256']);
        $path = $this->relativePath($source['path'] ?? null);
        if (PathGuard::SOURCE_BASENAME !== basename($path)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        $this->boundedInteger($source['bytes'] ?? null, 1, PathGuard::MAX_SOURCE_BYTES);
        $sourceSha256 = $this->digest($source['sha256'] ?? null);

        $project = $this->object($artifact['project'] ?? null);
        $this->assertKeys($project, ['composer_json_sha256', 'composer_lock_sha256']);
        $this->digest($project['composer_json_sha256'] ?? null);
        $this->digest($project['composer_lock_sha256'] ?? null);

        $builder = $this->object($artifact['builder'] ?? null);
        $this->assertKeys($builder, ['package', 'contract_version', 'implementation_sha256']);
        if ('kongono/database-mapping' !== ($builder['package'] ?? null) || 1 !== ($builder['contract_version'] ?? null)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        $builderSha256 = $this->digest($builder['implementation_sha256'] ?? null);
        if (!hash_equals(MappingImplementation::builderSha256(), $builderSha256)) {
            $this->invalid(MappingFailureCategory::ArtifactIdentity);
        }

        $state = $this->object($artifact['declaration_state'] ?? null);
        $this->assertKeys($state, ['source_set_sha256', 'source_count', 'source_bytes', 'sources']);
        $sourceSetSha256 = $this->digest($state['source_set_sha256'] ?? null);
        $sourceCount = $this->boundedInteger($state['source_count'] ?? null, 1, 256);
        $sourceBytes = $this->boundedInteger($state['source_bytes'] ?? null, 1, 268_435_456);
        $sources = $this->list($state['sources'] ?? null);
        if (count($sources) !== $sourceCount) {
            $this->invalid(MappingFailureCategory::ArtifactIdentity);
        }
        $records = [];
        $total = 0;
        $previous = null;
        foreach ($sources as $sourceValue) {
            $record = $this->object($sourceValue);
            $this->assertKeys($record, ['path', 'bytes', 'sha256']);
            $recordPath = $this->relativePath($record['path'] ?? null);
            if (null !== $previous && strcmp($previous, $recordPath) >= 0) {
                $this->invalid(MappingFailureCategory::ArtifactIdentity);
            }
            $bytes = $this->boundedInteger($record['bytes'] ?? null, 1, PathGuard::MAX_SOURCE_BYTES);
            $sha = $this->digest($record['sha256'] ?? null);
            $records[] = implode("\t", [$recordPath, (string) $bytes, $sha]);
            $total += $bytes;
            $previous = $recordPath;
        }
        if ($total !== $sourceBytes || !hash_equals($sourceSetSha256, hash('sha256', implode("\n", $records)))) {
            $this->invalid(MappingFailureCategory::ArtifactIdentity);
        }

        $modelSha256 = $this->digest($artifact['model_sha256'] ?? null);
        $model = $this->object($artifact['model'] ?? null);
        $this->validateModel($model);
        if (!hash_equals($modelSha256, hash('sha256', CanonicalJson::encode($model)))) {
            $this->invalid(MappingFailureCategory::ArtifactIdentity);
        }

        return new MappingArtifact(
            $artifactBytes,
            hash('sha256', $artifactBytes),
            $sourceSha256,
            $modelSha256,
            $sourceSetSha256,
            $builderSha256,
        );
    }

    public function validateCurrent(string $applicationRoot, string $sourceRelativePath, string $artifactBytes): MappingArtifact
    {
        $artifact = $this->validateStructure($artifactBytes);
        $current = (new MappingArtifactBuilder())->build($applicationRoot, $sourceRelativePath);
        if (!hash_equals($current->canonicalBytes(), $artifactBytes)) {
            throw StaleMappingArtifactException::currentMismatch();
        }

        return $artifact;
    }

    /** @param array<string, mixed> $model */
    private function validateModel(array $model): void
    {
        $this->assertKeys($model, ['schema', 'version', 'generated_namespace', 'mappings']);
        if ('kongono.data-mapping' !== ($model['schema'] ?? null) || 1 !== ($model['version'] ?? null)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        $this->phpName($model['generated_namespace'] ?? null, false);
        $mappings = $this->list($model['mappings'] ?? null);
        if ([] === $mappings || count($mappings) > 256) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        $totalFields = 0;
        $previousRecord = null;
        foreach ($mappings as $mappingValue) {
            $mapping = $this->object($mappingValue);
            $this->assertKeys($mapping, ['record', 'mapper', 'repository', 'table', 'identity', 'identity_ordinal', 'fields']);
            $record = $this->phpName($mapping['record'] ?? null, true);
            if (null !== $previousRecord && strcmp($previousRecord, $record) >= 0) {
                $this->invalid(MappingFailureCategory::ArtifactStructure);
            }
            $previousRecord = $record;
            $this->identifier($mapping['mapper'] ?? null, 255);
            $this->identifier($mapping['repository'] ?? null, 255);
            $table = $this->list($mapping['table'] ?? null);
            if ([] === $table || count($table) > 3) {
                $this->invalid(MappingFailureCategory::ArtifactStructure);
            }
            foreach ($table as $segment) {
                if (!is_string($segment)) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
                try {
                    new SqlIdentifier($segment);
                } catch (Throwable) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
            }
            $identity = $this->identifier($mapping['identity'] ?? null, 128);
            $fields = $this->list($mapping['fields'] ?? null);
            if (count($fields) < 2 || count($fields) > 256) {
                $this->invalid(MappingFailureCategory::ArtifactStructure);
            }
            $identityOrdinal = $this->boundedInteger($mapping['identity_ordinal'] ?? null, 0, count($fields) - 1);
            $totalFields += count($fields);
            if ($totalFields > 8192) {
                $this->invalid(MappingFailureCategory::ArtifactStructure);
            }
            foreach ($fields as $ordinal => $fieldValue) {
                $field = $this->object($fieldValue);
                $this->assertKeys($field, ['field', 'column', 'kind', 'nullable', 'constructor_parameter', 'accessor']);
                $fieldName = $this->identifier($field['field'] ?? null, 128);
                $this->identifier($field['constructor_parameter'] ?? null, 128);
                $this->identifier($field['accessor'] ?? null, 128);
                if (!is_string($field['column'] ?? null)) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
                try {
                    new SqlIdentifier($field['column']);
                } catch (Throwable) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
                if (!in_array($field['kind'] ?? null, ['boolean', 'integer', 'float', 'text', 'binary'], true) || !is_bool($field['nullable'] ?? null)) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
                if ($ordinal === $identityOrdinal && ($fieldName !== $identity || true === $field['nullable'] || !in_array($field['kind'], ['integer', 'text'], true))) {
                    $this->invalid(MappingFailureCategory::ArtifactStructure);
                }
            }
        }
    }

    private function digest(mixed $value): string
    {
        if (!is_string($value) || 1 !== preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function boundedInteger(mixed $value, int $minimum, int $maximum): int
    {
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function relativePath(mixed $value): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 4096 || str_contains($value, '\\') || str_starts_with($value, '/') || str_contains($value, ':')) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
        foreach (explode('/', $value) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                $this->invalid(MappingFailureCategory::ArtifactStructure);
            }
        }

        return $value;
    }

    private function phpName(mixed $value, bool $requireNamespace): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > 255 || ($requireNamespace && !str_contains($value, '\\')) || 1 !== preg_match('/^(?:[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\\\\)*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $value)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    private function identifier(mixed $value, int $limit): string
    {
        if (!is_string($value) || '' === $value || strlen($value) > $limit || 1 !== preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $value)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @return list<mixed> */
    private function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $expected
     */
    private function assertKeys(array $value, array $expected): void
    {
        if (array_keys($value) !== $expected) {
            $this->invalid(MappingFailureCategory::ArtifactStructure);
        }
    }

    private function invalid(MappingFailureCategory $category): never
    {
        throw InvalidMappingArtifactException::invalid($category);
    }
}
