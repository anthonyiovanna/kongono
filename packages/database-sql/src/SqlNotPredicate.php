<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Negates exactly one explicit predicate. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlNotPredicate implements SqlPredicate
{
    public function __construct(private SqlPredicate $predicate)
    {
        SqlValidation::assertPredicate($predicate);
    }

    public function predicate(): SqlPredicate
    {
        return $this->predicate;
    }
}
