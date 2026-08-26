<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores explicit one-level cascade policy and its compiler-derived bound. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipCascadeDefinition
{
    public function __construct(
        private string $relationship,
        private string $targetType,
        private bool $persist,
        private bool $remove,
        private int $maxRelated,
    ) {
        if (
            '' === $relationship
            || strlen($relationship) > PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $relationship)
            || '' === $targetType
            || strlen($targetType) > PersistenceModelCompiler::MAX_FQCN_BYTES
            || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $targetType)
            || $maxRelated < 1
            || $maxRelated > AggregateLoadPlan::MAX_RELATED_OBJECTS_PER_TO_MANY
        ) {
            throw InvalidPersistenceSourceException::invalid(PersistenceBuildDiagnostic::InvalidAggregateRepositorySource, PersistenceBuildFailureCategory::AggregateRepositorySource);
        }
    }

    public function relationship(): string
    {
        return $this->relationship;
    }
    public function targetType(): string
    {
        return $this->targetType;
    }
    public function persist(): bool
    {
        return $this->persist;
    }
    public function remove(): bool
    {
        return $this->remove;
    }
    public function maxRelated(): int
    {
        return $this->maxRelated;
    }
}
