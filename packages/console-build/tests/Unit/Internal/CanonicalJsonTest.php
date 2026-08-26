<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit\Internal;

use JsonException;
use Kongono\ConsoleBuild\Internal\CanonicalJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CanonicalJsonTest extends TestCase
{
    public function testEncodingIsCompactUnicodePreservingAndLfTerminated(): void
    {
        self::assertSame("{\"path\":\"a/b\",\"text\":\"café\"}\n", CanonicalJson::encode(['path' => 'a/b', 'text' => 'café']));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidJson(): iterable
    {
        yield 'duplicate key' => ['{"x":1,"x":2}'];
        yield 'nested duplicate key' => ['{"outer":{"x":1,"x":2}}'];
        yield 'trailing content' => ['{}{}'];
        yield 'excess depth' => ['[[[]]]'];
    }

    #[DataProvider('invalidJson')]
    public function testStrictDecodingRejectsAmbiguousOrTrailingInput(string $bytes): void
    {
        $this->expectException(JsonException::class);
        CanonicalJson::decodeObject($bytes, 2);
    }
}
