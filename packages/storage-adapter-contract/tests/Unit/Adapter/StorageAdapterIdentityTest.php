<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Adapter;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\Exception\InvalidStorageAdapterContractException;
use PHPUnit\Framework\TestCase;

final class StorageAdapterIdentityTest extends TestCase
{
    public function testCanonicalIdentity(): void
    {
        self::assertSame('local-adapter@1', (new StorageAdapterIdentity('local-adapter', 1))->canonical());
    }
    public function testInvalidIdentityIsRejected(): void
    {
        $this->expectException(InvalidStorageAdapterContractException::class);
        new StorageAdapterIdentity('LOCAL', 1);
    }
}
