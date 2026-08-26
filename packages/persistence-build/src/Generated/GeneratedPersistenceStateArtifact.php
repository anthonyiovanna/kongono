<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Generated;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;

/** Stores canonical generated state PHP and its detached exact provenance. */
#[Api(ApiStatus::Experimental)]
final readonly class GeneratedPersistenceStateArtifact
{
    /**
     * @param array<string, string> $classMap
     */
    public function __construct(
        private string $canonicalBytes,
        private string $generatedFileSha256,
        private string $persistenceArtifactSha256,
        private string $persistenceModelSha256,
        private string $persistenceBuilderImplementationSha256,
        private string $persistenceGeneratorImplementationSha256,
        private string $generatedNamespace,
        private array $classMap,
    ) {
        foreach ([
            $generatedFileSha256,
            $persistenceArtifactSha256,
            $persistenceModelSha256,
            $persistenceBuilderImplementationSha256,
            $persistenceGeneratorImplementationSha256,
        ] as $digest) {
            if (1 !== preg_match('/^[a-f0-9]{64}$/D', $digest)) {
                $this->invalid();
            }
        }
        if (!hash_equals($generatedFileSha256, hash('sha256', $canonicalBytes))) {
            $this->invalid();
        }
        if (1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $generatedNamespace)) {
            $this->invalid();
        }
        if ([] === $classMap || count($classMap) > 256) {
            $this->invalid();
        }
        $ordinal = 1;
        foreach ($classMap as $entity => $adapter) {
            $expected = $generatedNamespace . '\\' . sprintf('EntityState_%04d', $ordinal);
            if (!$this->phpName($entity) || $adapter !== $expected) {
                $this->invalid();
            }
            ++$ordinal;
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
        foreach ($classMap as $entity => $adapter) {
            $expectedMap[] = ['entity' => $entity, 'state_adapter' => $adapter];
        }
        if (
            ($metadata['persistence_artifact_sha256'] ?? null) !== $persistenceArtifactSha256
            || ($metadata['persistence_model_sha256'] ?? null) !== $persistenceModelSha256
            || ($metadata['persistence_builder_implementation_sha256'] ?? null) !== $persistenceBuilderImplementationSha256
            || ($metadata['persistence_generator_implementation_sha256'] ?? null) !== $persistenceGeneratorImplementationSha256
            || ($metadata['generated_namespace'] ?? null) !== $generatedNamespace
            || ($metadata['class_map'] ?? null) !== $expectedMap
        ) {
            $this->invalid();
        }
    }

    public function canonicalBytes(): string
    {
        return $this->canonicalBytes;
    }

    public function generatedFileSha256(): string
    {
        return $this->generatedFileSha256;
    }

    public function persistenceArtifactSha256(): string
    {
        return $this->persistenceArtifactSha256;
    }

    public function persistenceModelSha256(): string
    {
        return $this->persistenceModelSha256;
    }

    public function persistenceBuilderImplementationSha256(): string
    {
        return $this->persistenceBuilderImplementationSha256;
    }

    public function persistenceGeneratorImplementationSha256(): string
    {
        return $this->persistenceGeneratorImplementationSha256;
    }

    public function generatedNamespace(): string
    {
        return $this->generatedNamespace;
    }

    /** @return array<string, string> */
    public function classMap(): array
    {
        return [...$this->classMap];
    }

    private function phpName(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= 512
            && 1 === preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function invalid(): never
    {
        throw InvalidGeneratedPersistenceStateException::invalid(PersistenceBuildFailureCategory::GeneratedStructure);
    }
}
