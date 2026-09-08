<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Internal\CurlRequestBodyReader;
use Kongono\NetworkEgressCurl\Tests\Fixture\FakeCurlExecutor;
use PHPUnit\Framework\TestCase;

final class CurlRequestBodyReaderTest extends TestCase
{
    public function testBodyIsConsumedOnceFromCurrentCursorWithoutRewind(): void
    {
        $stream = FakeCurlExecutor::stream('abcdef', 2);
        $reader = new CurlRequestBodyReader($stream, 4, 10);
        self::assertSame('cd', $reader->read(2));
        self::assertSame('ef', $reader->read(8));
        self::assertSame('', $reader->read(8));
        self::assertSame(4, $reader->consumedBytes());
        self::assertSame(14, $reader->observedRequestBytes());
        self::assertSame(6, $stream->tell());
    }
}
