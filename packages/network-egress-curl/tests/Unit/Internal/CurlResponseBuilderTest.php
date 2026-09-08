<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Internal\CurlResponseAccumulator;
use Kongono\NetworkEgressCurl\Internal\CurlResponseBuilder;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;

final class CurlResponseBuilderTest extends TestCase
{
    public function testReturnedResponseOwnsReadableSeekableBodyAtZero(): void
    {
        $accumulator = new CurlResponseAccumulator(FakeCurlExecutor::budget());
        $accumulator->appendHeader("HTTP/1.1 200 OK\r\n");
        $accumulator->appendHeader("Content-Type: text/plain\r\n");
        $accumulator->appendHeader("\r\n");
        $accumulator->appendBody('response');
        $response = (new CurlResponseBuilder())->build($accumulator->headerBlock(), $accumulator);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertTrue($response->getBody()->isReadable());
        self::assertTrue($response->getBody()->isSeekable());
        self::assertSame(0, $response->getBody()->tell());
        self::assertSame('response', $response->getBody()->getContents());
        $accumulator->close();
    }
}
