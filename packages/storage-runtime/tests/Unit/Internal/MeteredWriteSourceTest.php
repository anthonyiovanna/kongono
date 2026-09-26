<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Internal;

use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageRuntime\Internal\MeteredWriteSource;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class MeteredWriteSourceTest extends TestCase
{
    public function testMetersDigestExhaustionAndClosesOnce(): void
    {
        $inner = new ScriptedStorageByteSource('abcd');
        $source = new MeteredWriteSource($inner, 'create', 4, 2, new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', 'abcd')));
        $first = $source->read(2);
        self::assertNotNull($first);
        self::assertSame('ab', $first->bytes());
        $second = $source->read(2);
        self::assertNotNull($second);
        self::assertSame('cd', $second->bytes());
        self::assertNull($source->read(2));
        $source->validateCompleted();
        $source->close();
        $source->close();
        self::assertSame(1, $inner->closeCalls);
    }
}
