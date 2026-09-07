<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Usage;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;

/** Carries one nonnegative usage claim without metering or quota truth. */
#[Api(ApiStatus::Experimental)]
final readonly class UsageQuantity
{
    public function __construct(
        private UsageMetricName $metric,
        private UsageUnitName $unit,
        private int $quantity,
    ) {
        if ($quantity < 0) {
            throw InvalidModelInvocationContractException::invalidUsageOrCostEvidence('usage_quantity_range');
        }
    }

    public function metric(): UsageMetricName
    {
        return $this->metric;
    }

    public function unit(): UsageUnitName
    {
        return $this->unit;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function equals(self $other): bool
    {
        return $this->metric->equals($other->metric) && $this->unit->equals($other->unit) && $this->quantity === $other->quantity;
    }
}
