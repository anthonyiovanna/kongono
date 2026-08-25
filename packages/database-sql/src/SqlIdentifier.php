<?php

declare(strict_types=1);

namespace Kongono\DatabaseSql;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseSql\Exception\InvalidSqlPlanException;

/** Stores one exact identifier segment without normalization or delimiter inference. */
#[Api(ApiStatus::Experimental)]
final readonly class SqlIdentifier
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 128
            || 1 !== preg_match('//u', $value)
            || 1 === preg_match('/[\x00-\x1F\x7F]/', $value)
        ) {
            throw InvalidSqlPlanException::validation();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
