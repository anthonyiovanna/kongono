<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Budget;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;

/** Declares one inert application-owned usage ceiling without metering it. */
#[Api(ApiStatus::Experimental)]
final readonly class UsageLimit
{
    public function __construct(
        private UsageMetricName $metric,
        private UsageUnitName $unit,
        private int $maximumQuantity,
    ) {
        if ($maximumQuantity < 0 || $maximumQuantity > 9_007_199_254_740_991) {
            throw InvalidInvocationPlanSourceException::invalidBudget('usage_maximum_quantity_range', 'maximum_quantity');
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

    public function maximumQuantity(): int
    {
        return $this->maximumQuantity;
    }

    public function equals(self $other): bool
    {
        return $this->metric->equals($other->metric)
            && $this->unit->equals($other->unit)
            && $this->maximumQuantity === $other->maximumQuantity;
    }
}
