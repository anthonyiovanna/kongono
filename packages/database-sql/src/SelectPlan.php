<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Describes one bounded single-source Select plan. */
#[Api(ApiStatus::Experimental)]
final readonly class SelectPlan implements SqlPlan
{
    /** @var list<SqlProjection> */
    private array $projections;

    /** @var list<SqlOrder> */
    private array $orders;

    /** @param array<array-key, mixed> $projections
     *  @param array<array-key, mixed> $orders
     */
    public function __construct(
        private SqlQualifiedName $source,
        array $projections,
        private ?SqlIdentifier $sourceAlias = null,
        private ?SqlPredicate $predicate = null,
        array $orders = [],
        private ?int $limit = null,
        private ?int $offset = null,
    ) {
        if ((null !== $limit && $limit < 0) || (null !== $offset && $offset < 0)) {
            throw InvalidSqlPlanException::validation();
        }
        if (null !== $predicate) {
            SqlValidation::assertPredicate($predicate);
        }
        $this->projections = SqlValidation::projections($projections, true);
        $this->orders = SqlValidation::orders($orders);
    }

    public function kind(): SqlPlanKind
    {
        return SqlPlanKind::Select;
    }

    public function source(): SqlQualifiedName
    {
        return $this->source;
    }

    public function sourceAlias(): ?SqlIdentifier
    {
        return $this->sourceAlias;
    }

    /** @return list<SqlProjection> */
    public function projections(): array
    {
        return [...$this->projections];
    }

    public function predicate(): ?SqlPredicate
    {
        return $this->predicate;
    }

    /** @return list<SqlOrder> */
    public function orders(): array
    {
        return [...$this->orders];
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function offset(): ?int
    {
        return $this->offset;
    }

    public function requiredCapabilities(): SqlDialectCapabilities
    {
        $capabilities = [SqlDialectCapability::Select, ...SqlValidation::predicateCapabilities($this->predicate)];
        if (null !== $this->limit || null !== $this->offset) {
            $capabilities[] = SqlDialectCapability::LimitOffset;
        }
        foreach ($this->orders as $order) {
            if (SqlNullOrdering::Default !== $order->nullOrdering()) {
                $capabilities[] = SqlDialectCapability::NullOrdering;
                break;
            }
        }

        return SqlValidation::capabilitySet($capabilities);
    }
}
