<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Describes one bounded single-target Insert plan. */
#[Api(ApiStatus::Experimental)]
final readonly class InsertPlan implements SqlPlan
{
    /** @var list<SqlAssignment> */
    private array $assignments;

    /** @var list<SqlProjection> */
    private array $returning;

    /** @param array<array-key, mixed> $assignments
     *  @param array<array-key, mixed> $returning
     */
    public function __construct(private SqlQualifiedName $target, array $assignments, array $returning = [])
    {
        $this->assignments = SqlValidation::assignments($assignments);
        $this->returning = SqlValidation::projections($returning, false);
    }

    public function kind(): SqlPlanKind
    {
        return SqlPlanKind::Insert;
    }

    public function target(): SqlQualifiedName
    {
        return $this->target;
    }

    /** @return list<SqlAssignment> */
    public function assignments(): array
    {
        return [...$this->assignments];
    }

    /** @return list<SqlProjection> */
    public function returning(): array
    {
        return [...$this->returning];
    }

    public function requiredCapabilities(): SqlDialectCapabilities
    {
        $capabilities = [SqlDialectCapability::Insert];
        if ([] !== $this->returning) {
            $capabilities[] = SqlDialectCapability::Returning;
        }

        return new SqlDialectCapabilities($capabilities);
    }
}
