<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Identifies one command through a bounded canonical ASCII name. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandIdentifier
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 128
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*(?::[a-z][a-z0-9]*(?:-[a-z0-9]+)*)*$/D', $value)
        ) {
            throw InvalidConsoleValueException::identifier();
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
