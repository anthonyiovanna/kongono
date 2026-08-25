<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Binds one exact ASCII placeholder name to one explicit neutral value. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseParameter
{
    public function __construct(
        private string $name,
        private DatabaseValue $value,
    ) {
        if (strlen($name) > 128 || 1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name)) {
            throw InvalidDatabaseValueException::validation();
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): DatabaseValue
    {
        return $this->value;
    }
}
