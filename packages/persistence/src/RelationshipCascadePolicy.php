<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;

/** Stores explicit one-level cascade authority for one relationship. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipCascadePolicy
{
    public function __construct(
        private string $relationshipName,
        private string $targetType,
        private bool $persist,
        private bool $remove,
    ) {
        if (
            '' === $relationshipName
            || strlen($relationshipName) > 191
            || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $relationshipName)
            || '' === $targetType
            || strlen($targetType) > 255
            || 1 !== preg_match('/^(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*[A-Za-z_][A-Za-z0-9_]*$/D', $targetType)
        ) {
            throw InvalidAggregatePersistenceException::invalid();
        }
    }

    public function relationshipName(): string
    {
        return $this->relationshipName;
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
}
