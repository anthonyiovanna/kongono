<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Describes one bounded Delete with explicit predicate or all-row intent. */
#[Api(ApiStatus::Experimental)]
final readonly class DeletePlan implements SqlPlan
{
    /** @var list<SqlProjection> */
    private array $returning;

    /** @param array<array-key, mixed> $returning */
    public function __construct(
        private SqlQualifiedName $target,
        private SqlMutationScope $scope,
        array $returning = [],
    ) {
        $this->returning = SqlValidation::projections($returning, false);
    }

    public function kind(): SqlPlanKind
    {
        return SqlPlanKind::Delete;
    }

    public function target(): SqlQualifiedName
    {
        return $this->target;
    }

    public function scope(): SqlMutationScope
    {
        return $this->scope;
    }

    /** @return list<SqlProjection> */
    public function returning(): array
    {
        return [...$this->returning];
    }

    public function requiredCapabilities(): SqlDialectCapabilities
    {
        $capabilities = [SqlDialectCapability::Delete, ...SqlValidation::predicateCapabilities($this->scope->predicate())];
        if ([] !== $this->returning) {
            $capabilities[] = SqlDialectCapability::Returning;
        }

        return SqlValidation::capabilitySet($capabilities);
    }
}
