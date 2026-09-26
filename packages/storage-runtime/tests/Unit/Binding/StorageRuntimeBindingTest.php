<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Unit\Binding;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageRuntime\Binding\StorageRuntimeBinding;
use Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeBindingTest extends TestCase
{
    public function testExactBindingAndIdentityFailure(): void
    {
        $set = ScriptedStorageAdapter::set();
        $artifact = ScriptedStorageAdapter::artifact($set);
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor($artifact));
        $binding = new StorageRuntimeBinding($set, $artifact->bytes(), new StorageProfileName('primary'), new StorageAdapterIdentity('scripted', 1), $adapter);
        self::assertSame($adapter, $binding->adapter());
        self::assertSame('primary', $binding->profile()->name()->value());
        self::assertSame(0, $adapter->calls);
        $this->expectException(InvalidStorageRuntimeException::class);
        new StorageRuntimeBinding($set, $artifact->bytes(), new StorageProfileName('primary'), new StorageAdapterIdentity('other', 1), $adapter);
    }
}
