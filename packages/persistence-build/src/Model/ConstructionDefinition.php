<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Model;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceArtifactException;
use Kongono\PersistenceBuild\Internal\PersistenceBuildFailureCategory;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;

/** Stores one validated constructor or static-factory selection. */
#[Api(ApiStatus::Experimental)]
final readonly class ConstructionDefinition
{
    public function __construct(private ConstructionKind $kind, private ?string $method)
    {
        if (
            (ConstructionKind::Constructor === $kind && null !== $method)
            || (ConstructionKind::StaticFactory === $kind && (null === $method || !$this->validIdentifier($method)))
        ) {
            throw InvalidPersistenceArtifactException::invalid(PersistenceBuildFailureCategory::ArtifactStructure);
        }
    }

    public function kind(): ConstructionKind
    {
        return $this->kind;
    }

    public function method(): ?string
    {
        return $this->method;
    }

    private function validIdentifier(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= PersistenceModelCompiler::MAX_IDENTIFIER_BYTES
            && 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value);
    }
}
