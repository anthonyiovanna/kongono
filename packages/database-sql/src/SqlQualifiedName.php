<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;

/** Stores one to three exact identifier segments in caller order. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlQualifiedName
{
    /** @var list<SqlIdentifier> */
    private array $segments;

    /** @param array<array-key, mixed> $segments */
    public function __construct(array $segments)
    {
        if (!array_is_list($segments) || [] === $segments || count($segments) > 3) {
            throw InvalidSqlPlanException::validation();
        }
        $detached = [];
        foreach ($segments as $segment) {
            if (!$segment instanceof SqlIdentifier) {
                throw InvalidSqlPlanException::validation();
            }
            $detached[] = $segment;
        }
        $this->segments = $detached;
    }

    /** @return list<SqlIdentifier> */
    public function segments(): array
    {
        return [...$this->segments];
    }
}
