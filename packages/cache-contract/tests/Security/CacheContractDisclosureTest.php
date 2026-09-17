<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Security;

use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use PHPUnit\Framework\TestCase;

final class CacheContractDisclosureTest extends TestCase
{
    public function testRejectedRawValuesNeverReachPackageOwnedPresentation(): void
    {
        $privateInputs = [
            'PRIVATE-NAMESPACE' => static fn() => new CacheNamespace('PRIVATE-NAMESPACE'),
            'PRIVATE KEY' => static fn() => new LogicalCacheKey('PRIVATE KEY'),
            'PRIVATE/CODEC' => static fn() => new CacheCodecIdentity('PRIVATE/CODEC', 1),
            str_repeat('v', 65) => static fn() => new CacheEntryVersion(str_repeat('v', 65)),
            str_repeat('x', 1_048_577) => static fn() => new CacheValueBytes(str_repeat('x', 1_048_577)),
        ];
        foreach ($privateInputs as $private => $construct) {
            try {
                $construct();
                self::fail('Expected rejected raw value.');
            } catch (InvalidCacheContractException $failure) {
                self::assertStringNotContainsString($private, $failure->getMessage());
                self::assertStringNotContainsString($private, (string) $failure);
                foreach ($failure->diagnosticContext()->fields() as $field) {
                    self::assertStringNotContainsString($private, (string) $field->value());
                }
            }
        }
    }

    public function testSequentialOverlappingAndReentrantConstructionSharesNoState(): void
    {
        $first = new CacheValueBytes('first');
        $outer = CacheKeyIdentity::from(new CacheNamespace('outer'), new LogicalCacheKey('key'));
        $inner = (static fn(): CacheKeyIdentity => CacheKeyIdentity::from(new CacheNamespace('inner'), new LogicalCacheKey('key')))();
        $second = new CacheValueBytes('second');
        self::assertSame('first', $first->bytes());
        self::assertSame('second', $second->bytes());
        self::assertNotSame($outer->digest(), $inner->digest());
        self::assertTrue(CacheContentIdentity::fromBytes('first')->equals($first->contentIdentity()));
        self::assertFalse(class_exists('Kongono\\CacheContract\\Adapter'));
        self::assertFalse(interface_exists('Kongono\\CacheContract\\Backend'));
        self::assertFalse(class_exists(CacheEntryEvidence::class . 'Runtime'));
    }
}
