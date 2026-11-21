<?php

declare(strict_types=1);

namespace Kongono\Database;

/** Immutable compiled SQL statement with positional parameter bindings. */
final readonly class Sql
{
    /** @param list<mixed> $bindings */
    public function __construct(
        public string $query,
        public array $bindings = [],
    ) {}
}
