<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Mapping;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;

/** Selects one exact usage metric and unit without aliases or estimation. */
#[Api(ApiStatus::Experimental)]
final readonly class ProposalUsageSelector
{
    public function __construct(
        private UsageMetricName $metric,
        private UsageUnitName $unit,
    ) {}

    public function metric(): UsageMetricName
    {
        return $this->metric;
    }

    public function unit(): UsageUnitName
    {
        return $this->unit;
    }
}
