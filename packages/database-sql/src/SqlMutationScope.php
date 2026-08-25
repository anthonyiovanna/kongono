<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Internal\SqlValidation;

/** Distinguishes predicate-scoped mutation from deliberate all-row intent. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlMutationScope
{
    private function __construct(private ?SqlPredicate $predicate) {}

    public static function forPredicate(SqlPredicate $predicate): self
    {
        SqlValidation::assertPredicate($predicate);

        return new self($predicate);
    }

    public static function allRows(): self
    {
        return new self(null);
    }

    public function predicate(): ?SqlPredicate
    {
        return $this->predicate;
    }

    public function isAllRows(): bool
    {
        return null === $this->predicate;
    }
}
