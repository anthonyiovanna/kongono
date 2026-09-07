<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Unit\Mapping;

use Kongono\ModelInvocationAgentBridge\Mapping\ActionProposalMapping;
use Kongono\ModelInvocationAgentBridge\Mapping\ProposalUsageSelector;
use Kongono\ModelInvocationAgentBridge\Tests\Fixture\BridgeFixture;
use Kongono\ModelInvocationContract\Result\ActionName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActionProposalMapping::class)]
final class ActionProposalMappingTest extends TestCase
{
    public function testItRetainsTheExactApplicationOwnedValues(): void
    {
        $action = new ActionName('calendar.read');
        $capability = BridgeFixture::capability();
        $selector = new ProposalUsageSelector(new \Kongono\ModelInvocationContract\Usage\UsageMetricName('tokens'), new \Kongono\ModelInvocationContract\Usage\UsageUnitName('count'));
        $mapping = new ActionProposalMapping($action, $capability, $selector);

        self::assertSame($action, $mapping->expectedActionName());
        self::assertSame($capability, $mapping->capabilityRequest());
        self::assertSame($selector, $mapping->usageSelector());
    }
}
