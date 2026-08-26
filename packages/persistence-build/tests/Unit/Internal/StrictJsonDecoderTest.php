<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use JsonException;
use Kongono\PersistenceBuild\Internal\StrictJsonDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrictJsonDecoderTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function invalidJson(): iterable
    {
        yield 'duplicate nested key' => ['{"outer":{"secret":1,"secret":2}}'];
        yield 'trailing data' => ['{}{}'];
        yield 'malformed unicode' => ['{"x":"\\u0xx0"}'];
        yield 'too deep' => ['[[[]]]'];
    }

    #[DataProvider('invalidJson')]
    public function testInvalidJsonFailsClosed(string $bytes): void
    {
        $this->expectException(JsonException::class);
        (new StrictJsonDecoder($bytes, 2))->decode();
    }

    public function testKeyOrderAndLineEndingsAreAcceptedAsSourceSyntax(): void
    {
        self::assertSame(['b' => 2, 'a' => 1], (new StrictJsonDecoder("{\r\n\"b\":2,\"a\":1\r\n}", 4))->decode());
    }
}
