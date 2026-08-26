<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Stores one bounded display label without normalizing caller-owned text. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResultFieldLabel
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 80
            || trim($value) !== $value
            || 1 !== preg_match('//u', $value)
            || str_starts_with($value, "\xEF\xBB\xBF")
            || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value)
        ) {
            throw InvalidConsoleValueException::result();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
