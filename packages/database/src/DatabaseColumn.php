<?php

declare(strict_types=1);

namespace Kongono\Database;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\Exception\InvalidDatabaseValueException;

/** Preserves one exact zero-based provider column identity. */
#[Api(ApiStatus::Experimental)]
final readonly class DatabaseColumn
{
    public function __construct(
        private int $ordinal,
        private string $label,
    ) {
        if ($ordinal < 0 || '' === $label || strlen($label) > 1_024 || str_contains($label, "\0") || 1 !== preg_match('//u', $label)) {
            throw InvalidDatabaseValueException::validation();
        }
    }

    public function ordinal(): int
    {
        return $this->ordinal;
    }

    public function label(): string
    {
        return $this->label;
    }
}
