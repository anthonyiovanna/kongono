<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Integration;

use Kongono\StorageAdapterContract\Adapter\StorageAdapter;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageAdapterContract\Tests\Fixture\FakeStorageAdapter;
use Kongono\StorageAdapterContract\Tests\Fixture\StorageFixture;
use Kongono\StorageBuild\Profile\StorageProfileName;
use PHPUnit\Framework\TestCase;

final class StorageAdapterProtocolTest extends TestCase
{
    public function testFakeIsSubstitutableWithoutDiscovery(): void
    {
        $a = StorageFixture::artifact();
        $d = new StorageAdapterDescriptor(AdapterContractVersion::Version1, new StorageAdapterIdentity('fake', 1), $a, new StorageProfileName('local-test'), new StorageAdapterGuaranteeSet([]), new StorageAdapterLimitationSet([]));
        self::assertInstanceOf(StorageAdapter::class, new FakeStorageAdapter($d));
    }
}
