<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Adapter;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterDescriptor;
use Kongono\StorageAdapterContract\Adapter\StorageAdapterIdentity;
use Kongono\StorageAdapterContract\AdapterContractVersion;
use Kongono\StorageAdapterContract\Exception\InvalidStorageAdapterContractException;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use Kongono\StorageAdapterContract\Tests\Fixture\StorageFixture;
use Kongono\StorageBuild\Profile\StorageProfileName;
use PHPUnit\Framework\TestCase;

final class StorageAdapterDescriptorTest extends TestCase
{
    public function testBindsExactArtifactProfile(): void
    {
        $a = StorageFixture::artifact();
        $d = new StorageAdapterDescriptor(AdapterContractVersion::Version1, new StorageAdapterIdentity('test', 1), $a, new StorageProfileName('local-test'), new StorageAdapterGuaranteeSet([]), new StorageAdapterLimitationSet([]));
        self::assertTrue($a->artifactIdentity()->equals($d->artifactIdentity()));
        self::assertSame('local-test', $d->profile()->name()->value());
    }
    public function testAbsentProfileFails(): void
    {
        $this->expectException(InvalidStorageAdapterContractException::class);
        $a = StorageFixture::artifact();
        new StorageAdapterDescriptor(AdapterContractVersion::Version1, new StorageAdapterIdentity('test', 1), $a, new StorageProfileName('absent'), new StorageAdapterGuaranteeSet([]), new StorageAdapterLimitationSet([]));
    }
}
