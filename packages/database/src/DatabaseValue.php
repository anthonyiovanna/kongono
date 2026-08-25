<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Stores one explicitly typed detached neutral database value. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseValue
{
    private const MAX_BYTES = 16_777_216;

    private function __construct(
        private DatabaseValueKind $kind,
        private bool|int|float|string|null $value,
    ) {}

    public static function null(): self
    {
        return new self(DatabaseValueKind::Null, null);
    }

    public static function boolean(bool $value): self
    {
        return new self(DatabaseValueKind::Boolean, $value);
    }

    public static function integer(int $value): self
    {
        return new self(DatabaseValueKind::Integer, $value);
    }

    public static function float(float $value): self
    {
        if (!is_finite($value)) {
            throw InvalidDatabaseValueException::validation();
        }

        return new self(DatabaseValueKind::Float, $value);
    }

    public static function text(string $value): self
    {
        if (1 !== preg_match('//u', $value) || strlen($value) > self::MAX_BYTES) {
            throw InvalidDatabaseValueException::validation();
        }

        return new self(DatabaseValueKind::Text, $value);
    }

    public static function binary(string $value): self
    {
        if (strlen($value) > self::MAX_BYTES) {
            throw InvalidDatabaseValueException::validation();
        }

        return new self(DatabaseValueKind::Binary, $value);
    }

    public function kind(): DatabaseValueKind
    {
        return $this->kind;
    }

    public function value(): bool|int|float|string|null
    {
        return $this->value;
    }
}
