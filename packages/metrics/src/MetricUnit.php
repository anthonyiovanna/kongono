<?php

declare(strict_types=1);

namespace Kongono\Metrics;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Metrics\Exception\InvalidMetricValueException;
use Kongono\Metrics\Internal\MetricsValidation;

/** Stores one exact bounded ASCII metric unit without claiming a unit standard. */
#[Api(ApiStatus::Experimental)]
final readonly class MetricUnit
{
    public function __construct(private string $value)
    {
        if (!MetricsValidation::isUnit($value)) {
            throw InvalidMetricValueException::unit();
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
