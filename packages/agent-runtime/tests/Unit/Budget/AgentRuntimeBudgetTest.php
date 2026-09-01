<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Unit\Budget;

use Kongono\AgentRuntime\Budget\AgentRuntimeBudget;
use Kongono\AgentRuntime\Exception\InvalidAgentRuntimeException;
use PHPUnit\Framework\TestCase;

final class AgentRuntimeBudgetTest extends TestCase
{
    public function testAllNineNonNegativeDimensionsAreRetainedExactly(): void
    {
        $budget = new AgentRuntimeBudget(1, 2, 3, 4, 5, 6, 7, 8, 9);
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], $budget->values());
        self::assertSame(1, $budget->stepLimit());
        self::assertSame(9, $budget->concurrencyLimit());
    }

    public function testNegativeDimensionRejectsWithoutEchoingValue(): void
    {
        try {
            new AgentRuntimeBudget(0, 0, 0, 0, 0, 0, -987_654, 0, 1);
            self::fail('Negative budget must reject.');
        } catch (InvalidAgentRuntimeException $failure) {
            self::assertStringNotContainsString('-987654', (string) $failure);
        }
    }
}
