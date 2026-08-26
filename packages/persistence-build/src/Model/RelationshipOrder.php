<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one declarative relationship ordering entry. */
#[Api(ApiStatus::Experimental)]
final readonly class RelationshipOrder
{
    public function __construct(private string $field, private SortDirection $direction)
    {
        if (
            '' === $field
            || strlen($field) > PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $field)
        ) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
    }

    public function field(): string
    {
        return $this->field;
    }
    public function direction(): SortDirection
    {
        return $this->direction;
    }
}
