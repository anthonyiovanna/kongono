<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Value;

use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Value\CacheValueBytes;
use PHPUnit\Framework\TestCase;

final class CacheValueBytesTest extends TestCase
{
    public function testEmptyOneByteAndMaximumValuesAreOpaqueAndExact(): void
    {
        $empty = new CacheValueBytes('');
        self::assertSame('', $empty->bytes());
        self::assertSame(0, $empty->byteCount());
        self::assertSame(hash('sha256', ''), $empty->contentIdentity()->digest());

        $one = new CacheValueBytes("\0");
        self::assertSame(1, $one->byteCount());
        self::assertTrue($one->equals(new CacheValueBytes("\0")));
        self::assertTrue($one->contentIdentity()->equals(CacheContentIdentity::fromBytes("\0")));

        $maximum = str_repeat('x', 1_048_576);
        self::assertSame(1_048_576, (new CacheValueBytes($maximum))->byteCount());
        try {
            new CacheValueBytes($maximum . 'x');
            self::fail('Expected the value-byte plus-one bound to fail.');
        } catch (InvalidCacheContractException $failure) {
            self::assertSame('KGN-CACHE-CONTRACT-2006', $failure->diagnosticId());
        }
        try {
            CacheContentIdentity::fromBytes($maximum . 'x');
            self::fail('Expected the content-identity plus-one bound to fail.');
        } catch (InvalidCacheContractException $failure) {
            self::assertSame('KGN-CACHE-CONTRACT-2006', $failure->diagnosticId());
        }
    }
}
