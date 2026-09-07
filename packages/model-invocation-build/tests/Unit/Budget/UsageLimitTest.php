<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Budget;

use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;

final class UsageLimitTest extends TestCase
{
    public function testExactSafeIntegerBoundsAndEquality(): void
    {
        $minimum = new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), 0);
        $maximum = new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), 9_007_199_254_740_991);
        self::assertSame(0, $minimum->maximumQuantity());
        self::assertSame(9_007_199_254_740_991, $maximum->maximumQuantity());
        self::assertTrue($minimum->equals(new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), 0)));

        foreach ([-1, 9_007_199_254_740_992] as $invalid) {
            try {
                new UsageLimit(new UsageMetricName('tokens'), new UsageUnitName('count'), $invalid);
                self::fail('Expected usage limit rejection.');
            } catch (InvalidInvocationPlanSourceException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1412', $failure->diagnosticId());
            }
        }
    }
}
