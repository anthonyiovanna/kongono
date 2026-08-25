<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Retains one ordered non-empty conjunction. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlAndPredicate implements SqlPredicate
{
    /** @var list<SqlPredicate> */
    private array $predicates;

    /** @param array<array-key, mixed> $predicates */
    public function __construct(array $predicates)
    {
        $this->predicates = self::validated($predicates);
    }

    /** @return list<SqlPredicate> */
    public function predicates(): array
    {
        return [...$this->predicates];
    }

    /** @param array<array-key, mixed> $predicates
     *  @return list<SqlPredicate>
     */
    private static function validated(array $predicates): array
    {
        if (!array_is_list($predicates) || [] === $predicates || count($predicates) > 4_096) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        foreach ($predicates as $predicate) {
            if (!$predicate instanceof SqlPredicate) {
                throw InvalidSqlPlanException::validation();
            }
            SqlValidation::assertPredicate($predicate);
            $detached[] = $predicate;
        }

        return $detached;
    }
}
