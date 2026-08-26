<?php

declare(strict_types=1);

namespace Kongono\Console;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Console\Exception\InvalidConsoleValueException;

/** Identifies one structured result field through a bounded canonical ASCII name. */
#[Api(ApiStatus::Experimental)]
final readonly class CommandResultFieldName
{
    public function __construct(private string $value)
    {
        if ('' === $value || strlen($value) > 64 || 1 !== preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/D', $value)) {
            throw InvalidConsoleValueException::result();
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
