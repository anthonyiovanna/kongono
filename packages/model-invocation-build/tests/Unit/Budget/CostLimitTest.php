<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Budget;

use Kongono\ModelInvocationBuild\Budget\CostLimit;
use Kongono\ModelInvocationContract\Cost\CurrencyCode;
use Kongono\ModelInvocationContract\Cost\DecimalAmount;
use PHPUnit\Framework\TestCase;

final class CostLimitTest extends TestCase
{
    public function testExactValuesAndEqualityRemainInert(): void
    {
        $limit = new CostLimit(new CurrencyCode('USD'), new DecimalAmount('12.50'));
        self::assertSame('USD', $limit->currency()->value());
        self::assertSame('12.50', $limit->maximumAmount()->value());
        self::assertTrue($limit->equals(new CostLimit(new CurrencyCode('USD'), new DecimalAmount('12.50'))));
        self::assertFalse($limit->equals(new CostLimit(new CurrencyCode('EUR'), new DecimalAmount('12.50'))));
    }
}
