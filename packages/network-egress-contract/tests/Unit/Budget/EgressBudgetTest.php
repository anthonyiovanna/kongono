<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Tests\Unit\Budget;

use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressContract\Exception\InvalidNetworkEgressContractException;
use PHPUnit\Framework\TestCase;

final class EgressBudgetTest extends TestCase
{
    /** @return array<string, int> */
    private static function minimum(): array
    {
        return [
            'maximumConnectionAttempts' => 1,
            'maximumRedirects' => 0,
            'maximumResolutionCandidates' => 1,
            'maximumRequestBytes' => 0,
            'maximumResponseBytes' => 0,
            'maximumHeaderCount' => 1,
            'maximumAggregateHeaderBytes' => 1,
            'maximumBufferedBytes' => 1,
            'maximumRetainedEvidenceBytes' => 1,
            'connectTimeoutMilliseconds' => 1,
            'firstByteTimeoutMilliseconds' => 1,
            'totalTimeoutMilliseconds' => 1,
        ];
    }

    /** @return array<string, int> */
    private static function maximum(): array
    {
        return [
            'maximumConnectionAttempts' => 16,
            'maximumRedirects' => 8,
            'maximumResolutionCandidates' => 64,
            'maximumRequestBytes' => 1073741824,
            'maximumResponseBytes' => 1073741824,
            'maximumHeaderCount' => 256,
            'maximumAggregateHeaderBytes' => 1048576,
            'maximumBufferedBytes' => 16777216,
            'maximumRetainedEvidenceBytes' => 1048576,
            'connectTimeoutMilliseconds' => 300000,
            'firstByteTimeoutMilliseconds' => 300000,
            'totalTimeoutMilliseconds' => 3600000,
        ];
    }

    public function testEveryMinimumMaximumAccessorAndEqualityAreExact(): void
    {
        foreach ([self::minimum(), self::maximum()] as $values) {
            $budget = new EgressBudget(...$values);
            self::assertSame($values['maximumConnectionAttempts'], $budget->maximumConnectionAttempts());
            self::assertSame($values['maximumRedirects'], $budget->maximumRedirects());
            self::assertSame($values['maximumResolutionCandidates'], $budget->maximumResolutionCandidates());
            self::assertSame($values['maximumRequestBytes'], $budget->maximumRequestBytes());
            self::assertSame($values['maximumResponseBytes'], $budget->maximumResponseBytes());
            self::assertSame($values['maximumHeaderCount'], $budget->maximumHeaderCount());
            self::assertSame($values['maximumAggregateHeaderBytes'], $budget->maximumAggregateHeaderBytes());
            self::assertSame($values['maximumBufferedBytes'], $budget->maximumBufferedBytes());
            self::assertSame($values['maximumRetainedEvidenceBytes'], $budget->maximumRetainedEvidenceBytes());
            self::assertSame($values['connectTimeoutMilliseconds'], $budget->connectTimeoutMilliseconds());
            self::assertSame($values['firstByteTimeoutMilliseconds'], $budget->firstByteTimeoutMilliseconds());
            self::assertSame($values['totalTimeoutMilliseconds'], $budget->totalTimeoutMilliseconds());
            self::assertTrue($budget->equals(new EgressBudget(...$values)));
        }
    }

    public function testEveryLowerAndLimitPlusOneBoundaryFailsClosed(): void
    {
        $minimums = self::minimum();
        foreach ($minimums as $field => $minimum) {
            if (0 === $minimum) {
                $values = $minimums;
                $values[$field] = -1;
            } else {
                $values = $minimums;
                $values[$field] = $minimum - 1;
            }
            $this->expectInvalidBudget($values);
        }

        $maximums = self::maximum();
        foreach ($maximums as $field => $maximum) {
            $values = $maximums;
            $values[$field] = $maximum + 1;
            $this->expectInvalidBudget($values);
        }
    }

    public function testTimeoutRelationsFailClosed(): void
    {
        $values = self::minimum();
        $values['connectTimeoutMilliseconds'] = 2;
        $this->expectInvalidBudget($values);
        $values = self::minimum();
        $values['firstByteTimeoutMilliseconds'] = 2;
        $this->expectInvalidBudget($values);
    }

    /** @param array<string, int> $values */
    private function expectInvalidBudget(array $values): void
    {
        try {
            new EgressBudget(...$values);
            self::fail('Expected budget rejection.');
        } catch (InvalidNetworkEgressContractException $exception) {
            self::assertContains($exception->diagnosticId(), ['KGN-EGRESS-CONTRACT-1504', 'KGN-EGRESS-CONTRACT-1508']);
        }
    }
}
