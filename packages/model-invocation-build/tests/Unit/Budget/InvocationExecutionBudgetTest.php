<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Budget;

use Kongono\ModelInvocationBuild\Budget\InvocationExecutionBudget;
use Kongono\ModelInvocationBuild\Budget\UsageLimit;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class InvocationExecutionBudgetTest extends TestCase
{
    public function testBoundsDetachedListAndEqualityAreExact(): void
    {
        $limits = [self::limit('input', 1), self::limit('output', 2)];
        $budget = new InvocationExecutionBudget(1, 1, 1_048_576, $limits, null);
        array_pop($limits);
        $returned = $budget->usageLimits();
        array_pop($returned);
        self::assertCount(2, $budget->usageLimits());
        self::assertSame([1, 1, 1_048_576], [$budget->maxDurationMilliseconds(), $budget->maxAttempts(), $budget->maxRetainedEvidenceBytes()]);
        self::assertTrue($budget->equals(new InvocationExecutionBudget(1, 1, 1_048_576, [self::limit('input', 1), self::limit('output', 2)], null)));
    }

    public function testInvalidRangesListsAndOrderFailClosed(): void
    {
        $cases = [
            [0, 1, 1, []], [300_001, 1, 1, []], [1, 2, 1, []], [1, 1, 0, []], [1, 1, 1_048_577, []],
            [1, 1, 1, [self::limit('output', 1), self::limit('input', 1)]],
            [1, 1, 1, [self::limit('input', 1), self::limit('input', 2)]],
            [1, 1, 1, array_fill(0, 17, self::limit('input', 1))],
        ];
        foreach ($cases as [$duration, $attempts, $bytes, $limits]) {
            try {
                new InvocationExecutionBudget($duration, $attempts, $bytes, $limits, null);
                self::fail('Expected budget rejection.');
            } catch (InvalidInvocationPlanSourceException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1412', $failure->diagnosticId());
            }
        }
        $this->expectException(InvalidInvocationPlanSourceException::class);
        (new ReflectionClass(InvocationExecutionBudget::class))->newInstanceArgs([1, 1, 1, ['bad'], null]);
    }

    private static function limit(string $metric, int $quantity): UsageLimit
    {
        return new UsageLimit(new UsageMetricName($metric), new UsageUnitName('tokens'), $quantity);
    }
}
