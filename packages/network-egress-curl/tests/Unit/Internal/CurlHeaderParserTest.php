<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Tests\Unit\Internal;

use Kongono\NetworkEgressCurl\Exception\InvalidCurlTransportProfileException;
use Kongono\NetworkEgressCurl\Internal\CurlHeaderParser;
use PHPUnit\Framework\TestCase;

final class CurlHeaderParserTest extends TestCase
{
    public function testInterimBlockIsIgnoredAndFinalBlockIsMapped(): void
    {
        $block = (new CurlHeaderParser())->parse([
            "HTTP/1.1 100 Continue\r\n",
            "\r\n",
            "HTTP/1.1 204 No Content\r\n",
            "X-Evidence: one\r\n",
            "X-Evidence: two\r\n",
            "\r\n",
        ], 96);
        self::assertSame(204, $block->statusCode());
        self::assertSame('No Content', $block->reasonPhrase());
        self::assertSame(['X-Evidence' => ['one', 'two']], $block->headers());
        self::assertSame(96, $block->retainedBytes());
    }

    public function testMalformedOrIncompleteHeaderEvidenceFailsClosed(): void
    {
        $this->expectException(InvalidCurlTransportProfileException::class);
        (new CurlHeaderParser())->parse(["HTTP/2 200 OK\r\n", "\r\n"], 20);
    }
}
