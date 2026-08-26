<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Identifies one already-normalized named input without token prefixes. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandInputName
{
    public function __construct(private string $value)
    {
        if (
            '' === $value
            || strlen($value) > 64
            || 1 !== preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $value)
        ) {
            throw InvalidConsoleValueException::input();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
