<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Stores one bounded single-line human-readable command summary. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandSummary
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 160
            || 1 !== preg_match('//u', $value)
            || trim($value) !== $value
            || 1 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value)
        ) {
            throw InvalidConsoleValueException::summary();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
