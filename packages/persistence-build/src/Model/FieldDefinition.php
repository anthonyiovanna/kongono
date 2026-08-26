<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one detached entity-to-record field binding. */
#[Api(ApiStatus::Experimental)]
final readonly class FieldDefinition
{
    public function __construct(
        private string $field,
        private string $recordField,
        private string $factoryParameter,
        private string $accessor,
        private FieldRole $role,
        private FieldValueKind $valueKind,
        private bool $nullable,
    ) {
        foreach ([$field, $recordField, $factoryParameter, $accessor] as $name) {
            if (
                '' === $name
                || strlen($name) > PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
                || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name)
            ) {
                throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
            }
        }
    }

    public function field(): string
    {
        return $this->field;
    }
    public function recordField(): string
    {
        return $this->recordField;
    }
    public function factoryParameter(): string
    {
        return $this->factoryParameter;
    }
    public function accessor(): string
    {
        return $this->accessor;
    }
    public function role(): FieldRole
    {
        return $this->role;
    }
    public function valueKind(): FieldValueKind
    {
        return $this->valueKind;
    }
    public function nullable(): bool
    {
        return $this->nullable;
    }
}
