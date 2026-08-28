<?php

declare(strict_types=1);

namespace Kongono\Tracing;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Tracing\Exception\InvalidTracingValueException;
use Kongono\Tracing\Internal\TracingValidation;

/** Stores one exact opaque nonzero 64-bit span identifier. */
#[Api(ApiStatus::Experimental)]
final readonly class SpanId
{
    public function __construct(private string $value)
    {
        if (!TracingValidation::isIdentifier($value, 16)) {
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
