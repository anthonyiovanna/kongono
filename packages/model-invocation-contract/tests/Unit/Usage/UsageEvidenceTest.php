<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Tests\Unit\Usage;

use Kongono\ModelInvocationContract\Exception\InvalidModelInvocationContractException;
use Kongono\ModelInvocationContract\Usage\UsageEvidence;
use Kongono\ModelInvocationContract\Usage\UsageMetricName;
use Kongono\ModelInvocationContract\Usage\UsageQuantity;
use Kongono\ModelInvocationContract\Usage\UsageUnitName;
use PHPUnit\Framework\TestCase;

final class UsageEvidenceTest extends TestCase
{
    public function testQuantitiesCanonicalOrderIntegersAndFreshArraysAreExact(): void
    {
        $first = new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('byte'), 0);
        $second = new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('token'), PHP_INT_MAX);
        $evidence = new UsageEvidence([$first, $second]);
        $returned = $evidence->quantities();
        array_pop($returned);
        self::assertCount(2, $evidence->quantities());
        self::assertSame(PHP_INT_MAX, $second->quantity());
        self::assertTrue($evidence->equals(new UsageEvidence([$first, $second])));
        $maximum = [];
        foreach (range(1, 16) as $position) {
            $maximum[] = new UsageQuantity(new UsageMetricName('metric.' . str_pad((string) $position, 2, '0', STR_PAD_LEFT)), new UsageUnitName('unit'), $position);
        }
        self::assertCount(16, (new UsageEvidence($maximum))->quantities());
    }

    public function testNegativeNonListDuplicateOrderEmptyAndAboveLimitFailClosed(): void
    {
        $a = new UsageQuantity(new UsageMetricName('a'), new UsageUnitName('unit'), 1);
        $b = new UsageQuantity(new UsageMetricName('b'), new UsageUnitName('unit'), 1);
        $cases = [[1 => $a], [], [$a, $a], [$b, $a], array_fill(0, 17, $a)];
        foreach ($cases as $case) {
            try {
                (new \ReflectionClass(UsageEvidence::class))->newInstanceArgs([$case]);
                self::fail('Expected invalid usage evidence.');
            } catch (InvalidModelInvocationContractException $exception) {
                self::assertContains($exception->diagnosticId(), ['KGN-MODEL-CONTRACT-1407', 'KGN-MODEL-CONTRACT-1409']);
            }
        }
        $this->expectException(InvalidModelInvocationContractException::class);
        new UsageQuantity(new UsageMetricName('input'), new UsageUnitName('token'), -1);
    }
}
