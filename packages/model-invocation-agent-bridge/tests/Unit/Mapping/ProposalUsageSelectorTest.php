<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Unit\Mapping;

use Kongono\ModelInvocationAgentBridge\Mapping\ProposalUsageSelector;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProposalUsageSelector::class)]
final class ProposalUsageSelectorTest extends TestCase
{
    public function testItRetainsTheExactMetricAndUnitWithoutDefaults(): void
    {
        $metric = new UsageMetricName('tokens');
        $unit = new UsageUnitName('count');
        $selector = new ProposalUsageSelector($metric, $unit);

        self::assertSame($metric, $selector->metric());
        self::assertSame($unit, $selector->unit());
    }
}
