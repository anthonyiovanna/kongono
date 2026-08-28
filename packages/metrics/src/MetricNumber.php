<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricValueException;

/** Stores one exact finite native integer or floating-point metric value. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricNumber
{
    public function __construct(private int|float $value)
    {
        if (is_float($value) && !is_finite($value)) {
            throw InvalidMetricValueException::measurement();
        }
    }

    public function value(): int|float
    {
        return $this->value;
    }

    public function isInteger(): bool
    {
        return is_int($this->value);
    }

    public function isFloat(): bool
    {
        return is_float($this->value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
