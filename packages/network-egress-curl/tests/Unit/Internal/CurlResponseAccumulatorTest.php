<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressContract\Budget\EgressBudget;
use Kongono\NetworkEgressCurl\Internal\CurlResponseAccumulator;
use PHPUnit\Framework\TestCase;

final class CurlResponseAccumulatorTest extends TestCase
{
    public function testHeaderAndBodyBytesAreCountedAndRetainedInMemory(): void
    {
        $budget = new EgressBudget(1, 0, 1, 1024, 1024, 8, 1024, 1024, 1024, 100, 100, 500);
        $accumulator = new CurlResponseAccumulator($budget);
        self::assertSame(17, $accumulator->appendHeader("HTTP/1.1 200 OK\r\n"));
        self::assertSame(2, $accumulator->appendHeader("\r\n"));
        self::assertSame(3, $accumulator->appendBody('abc'));
        self::assertSame(22, $accumulator->responseBytes());
        self::assertSame(3, $accumulator->bufferedBytes());
        self::assertNull($accumulator->violation());
        $accumulator->close();
    }

    public function testLimitPlusOneAbortsBodyCallback(): void
    {
        $budget = new EgressBudget(1, 0, 1, 1024, 1024, 8, 1024, 3, 1024, 100, 100, 500);
        $accumulator = new CurlResponseAccumulator($budget);
        self::assertSame(3, $accumulator->appendBody('abc'));
        self::assertSame(0, $accumulator->appendBody('d'));
        self::assertSame('response_budget', $accumulator->violation());
        $accumulator->close();
    }
}
