<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Exception\CurlTransportOperationException;
use Kongono\NetworkEgressCurl\Internal\CurlEgressTransportSession;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CurlEgressTransportSessionTest extends TestCase
{
    public function testOneExecutionTerminalThenNullAndIdempotentClose(): void
    {
        $executor = new FakeCurlExecutor([FakeCurlExecutor::snapshot()]);
        $session = new CurlEgressTransportSession($executor);
        self::assertTrue($session->next()?->terminal());
        self::assertNull($session->next());
        $session->close();
        $session->close();
        self::assertSame(1, $executor->executions);
        self::assertSame(1, $executor->closes);
    }

    public function testCleanupFailureIsBoundedAndPreservesPrevious(): void
    {
        $foreign = new RuntimeException('private cleanup detail');
        $session = new CurlEgressTransportSession(new FakeCurlExecutor([], $foreign));
        try {
            $session->close();
            self::fail('Expected cleanup failure.');
        } catch (CurlTransportOperationException $failure) {
            self::assertSame($foreign, $failure->getPrevious());
            self::assertStringNotContainsString('private cleanup detail', (string) $failure);
        }
    }
}
