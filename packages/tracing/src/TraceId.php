<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Internal\TracingValidation;

/** Stores one exact opaque nonzero 128-bit trace identifier. */
#[Api(ApiStatus::Experimental)]
final readonly class TraceId
{
    public function __construct(private string $value)
    {
        if (!TracingValidation::isIdentifier($value, 32)) {
            throw InvalidTracingValueException::identifier();
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
