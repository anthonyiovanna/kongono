<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;

/** Stores generated aggregate-repository PHP and exact detached provenance. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedAggregateRepositoryArtifact
{
    /** @var array<string, array{repository: string, root_type: string, load_methods: list<string>}> */
    private array $classMap;

    /**
     * @param array<string, array{repository: string, root_type: string, load_methods: list<string>}> $classMap
     */
    public function __construct(
        private string $canonicalBytes,
        private string $generatedFileSha256,
        private string $sourcePath,
        private string $sourceSha256,
        private string $persistenceArtifactSha256,
        private string $persistenceModelSha256,
        private string $persistenceStateGeneratorImplementationSha256,
        private string $aggregateRepositoryGeneratorImplementationSha256,
        private string $generatedNamespace,
        array $classMap,
    ) {
        foreach ([$generatedFileSha256, $sourceSha256, $persistenceArtifactSha256, $persistenceModelSha256, $persistenceStateGeneratorImplementationSha256, $aggregateRepositoryGeneratorImplementationSha256] as $digest) {
            if (1 !== preg_match('/^[a-f0-9]{64}$/D', $digest)) {
                $this->invalid();
            }
        }
        if (!hash_equals($generatedFileSha256, hash('sha256', $canonicalBytes)) || [] === $classMap) {
            $this->invalid();
        }
        if (1 !== preg_match('/\A<\?php\n\ndeclare\(strict_types=1\);\n\n\/\/ (\{[^\n]+\})\n/D', $canonicalBytes, $matches)) {
            $this->invalid();
        }
        try {
            $metadata = json_decode($matches[1], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid();
        }
        if (!is_array($metadata) || array_is_list($metadata)) {
            $this->invalid();
        }
        $expectedMap = [];
        foreach ($classMap as $aggregate => $entry) {
            $expectedMap[] = ['aggregate' => $aggregate, ...$entry];
        }
        if (
            ($metadata['schema'] ?? null) !== 'kongono.generated-aggregate-repositories'
            || ($metadata['version'] ?? null) !== 1
            || ($metadata['aggregate_repository_source_path'] ?? null) !== $sourcePath
            || ($metadata['aggregate_repository_source_sha256'] ?? null) !== $sourceSha256
            || ($metadata['persistence_artifact_sha256'] ?? null) !== $persistenceArtifactSha256
            || ($metadata['persistence_model_sha256'] ?? null) !== $persistenceModelSha256
            || ($metadata['persistence_state_generator_implementation_sha256'] ?? null) !== $persistenceStateGeneratorImplementationSha256
            || ($metadata['aggregate_repository_generator_implementation_sha256'] ?? null) !== $aggregateRepositoryGeneratorImplementationSha256
            || ($metadata['generated_namespace'] ?? null) !== $generatedNamespace
            || ($metadata['class_map'] ?? null) !== $expectedMap
        ) {
            $this->invalid();
        }
        $this->classMap = $classMap;
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }
    public function generatedFileSha256(): string
    {
        return $this->generatedFileSha256;
    }
    public function sourcePath(): string
    {
        return $this->sourcePath;
    }
    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
    public function persistenceArtifactSha256(): string
    {
        return $this->persistenceArtifactSha256;
    }
    public function persistenceModelSha256(): string
    {
        return $this->persistenceModelSha256;
    }
    public function persistenceStateGeneratorImplementationSha256(): string
    {
        return $this->persistenceStateGeneratorImplementationSha256;
    }
    public function aggregateRepositoryGeneratorImplementationSha256(): string
    {
        return $this->aggregateRepositoryGeneratorImplementationSha256;
    }
    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }
    /** @return array<string, array{repository: string, root_type: string, load_methods: list<string>}> */
    public function classMap(): array
    {
        return $this->classMap;
    }

    private function invalid(): never
    {
        throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidGeneratedAggregateRepositories, PersistenceBuildFailureCategory::GeneratedAggregateRepositories);
    }
}
