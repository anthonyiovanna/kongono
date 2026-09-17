<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Unit\Entry;

use Kongono\CacheContract\Codec\CacheCodecIdentity;
use Kongono\CacheContract\ContractVersion;
use Kongono\CacheContract\Entry\CacheEntryEvidence;
use Kongono\CacheContract\Exception\InvalidCacheContractException;
use Kongono\CacheContract\Freshness\CacheFreshnessWindow;
use Kongono\CacheContract\Identity\CacheContentIdentity;
use Kongono\CacheContract\Identity\CacheKeyIdentity;
use Kongono\CacheContract\Identity\CacheNamespace;
use Kongono\CacheContract\Identity\LogicalCacheKey;
use Kongono\CacheContract\Time\CacheInstant;
use Kongono\CacheContract\Value\CacheValueBytes;
use Kongono\CacheContract\Version\CacheEntryVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CacheEntryEvidenceTest extends TestCase
{
    public function testExactGettersEqualityAndDetachedShapeArePreserved(): void
    {
        $first = $this->evidence('value');
        $second = $this->evidence('value');
        self::assertNotSame($first, $second);
        self::assertTrue($first->equals($second));
        self::assertSame(ContractVersion::Version1, $first->contractVersion());
        self::assertSame('text@1', $first->codecIdentity()->canonical());
        self::assertSame('value', $first->valueBytes()->bytes());
        self::assertTrue($first->contentIdentity()->equals($first->valueBytes()->contentIdentity()));
        self::assertSame(100, $first->freshnessWindow()->storedAt()->microseconds());
        self::assertSame(str_repeat('c', 64), $first->entryVersion()->token());

        $properties = array_map(static fn($property): string => $property->getName(), (new ReflectionClass(CacheEntryEvidence::class))->getProperties());
        sort($properties, SORT_STRING);
        self::assertSame(['codecIdentity', 'contentIdentity', 'contractVersion', 'entryVersion', 'freshnessWindow', 'keyIdentity', 'valueBytes'], $properties);
    }

    public function testContentMismatchFailsBeforeEvidenceExists(): void
    {
        try {
            new CacheEntryEvidence(
                ContractVersion::Version1,
                CacheKeyIdentity::from(new CacheNamespace('app'), new LogicalCacheKey('key')),
                new CacheCodecIdentity('text', 1),
                new CacheValueBytes('value'),
                CacheContentIdentity::fromBytes('other'),
                new CacheFreshnessWindow(new CacheInstant(100), new CacheInstant(200)),
                new CacheEntryVersion(str_repeat('c', 64)),
            );
            self::fail('Expected mismatching content identity to fail.');
        } catch (InvalidCacheContractException $failure) {
            self::assertSame('KGN-CACHE-CONTRACT-2005', $failure->diagnosticId());
        }
    }

    private function evidence(string $bytes): CacheEntryEvidence
    {
        $value = new CacheValueBytes($bytes);

        return new CacheEntryEvidence(
            ContractVersion::Version1,
            CacheKeyIdentity::from(new CacheNamespace('app'), new LogicalCacheKey('key')),
            new CacheCodecIdentity('text', 1),
            $value,
            $value->contentIdentity(),
            new CacheFreshnessWindow(new CacheInstant(100), new CacheInstant(200)),
            new CacheEntryVersion(str_repeat('c', 64)),
        );
    }
}
