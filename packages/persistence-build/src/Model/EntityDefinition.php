<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PathGuard;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one fully validated entity contract and reflected source identity. */
#[Api(ApiStatus::Experimental)]
final readonly class EntityDefinition
{
    /** @var list<FieldDefinition> */
    private array $fields;

    /** @param array<array-key, mixed> $fields */
    public function __construct(
        private string $entity,
        private string $storageRecord,
        private ConstructionDefinition $construction,
        private string $identity,
        private ?string $concurrency,
        array $fields,
        private string $sourcePath,
        private int $sourceBytes,
        private string $sourceSha256,
    ) {
        if (
            !$this->validPhpName($entity)
            || !$this->validPhpName($storageRecord)
            || !$this->validIdentifier($identity)
            || (null !== $concurrency && !$this->validIdentifier($concurrency))
            || !array_is_list($fields)
            || [] === $fields
            || count($fields) > PersistenceModelCompiler::MAX_FIELDS
            || !$this->validSourcePath($sourcePath)
            || $sourceBytes < 1
            || $sourceBytes > PathGuard::MAX_SOURCE_BYTES
            || 1 !== preg_match('/^[a-f0-9]{64}$/D', $sourceSha256)
        ) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $accepted = [];
        $fieldNames = [];
        $recordFields = [];
        $factoryParameters = [];
        $accessors = [];
        $identityCount = 0;
        $concurrencyCount = 0;
        foreach ($fields as $field) {
            if (!$field instanceof FieldDefinition) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
            if (
                isset($fieldNames[$field->field()])
                || isset($recordFields[$field->recordField()])
                || isset($factoryParameters[$field->factoryParameter()])
                || isset($accessors[$field->accessor()])
            ) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
            $fieldNames[$field->field()] = true;
            $recordFields[$field->recordField()] = true;
            $factoryParameters[$field->factoryParameter()] = true;
            $accessors[$field->accessor()] = true;
            if (FieldRole::Identity === $field->role()) {
                ++$identityCount;
                if ($field->field() !== $identity) {
                    throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
                }
            }
            if (FieldRole::Concurrency === $field->role()) {
                ++$concurrencyCount;
                if ($field->field() !== $concurrency) {
                    throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
                }
            }
            $accepted[] = $field;
        }
        if (1 !== $identityCount || (null === $concurrency ? 0 !== $concurrencyCount : 1 !== $concurrencyCount)) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
        $this->fields = $accepted;
    }

    public function entity(): string
    {
        return $this->entity;
    }
    public function storageRecord(): string
    {
        return $this->storageRecord;
    }
    public function construction(): ConstructionDefinition
    {
        return $this->construction;
    }
    public function identity(): string
    {
        return $this->identity;
    }
    public function concurrency(): ?string
    {
        return $this->concurrency;
    }
    /** @return list<FieldDefinition> */
    public function fields(): array
    {
        return $this->fields;
    }
    public function sourcePath(): string
    {
        return $this->sourcePath;
    }
    public function sourceBytes(): int
    {
        return $this->sourceBytes;
    }
    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }

    public function field(string $name): ?FieldDefinition
    {
        foreach ($this->fields as $field) {
            if ($field->field() === $name) {
                return $field;
            }
        }

        return null;
    }

    private function validPhpName(string $value): bool
    {
        if (
            '' === $value
            || strlen($value) > PersistenceModelCompiler::MAX_FQCN_BYTES
            || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $value)
        ) {
            return false;
        }
        $reserved = ['self', 'parent', 'static', 'int', 'float', 'string', 'bool', 'array', 'object', 'mixed', 'void', 'never', 'null', 'false', 'true', 'iterable', 'callable', 'resource'];
        foreach (explode('\\', $value) as $segment) {
            if (in_array(strtolower($segment), $reserved, true)) {
                return false;
            }
        }

        return true;
    }

    private function validIdentifier(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }

    private function validSourcePath(string $path): bool
    {
        if ('' === $path || strlen($path) > PathGuard::MAX_SOURCE_BYTES || str_contains($path, '\\') || str_starts_with($path, '/') || str_contains($path, ':')) {
            return false;
        }
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return false;
            }
        }

        return true;
    }
}
