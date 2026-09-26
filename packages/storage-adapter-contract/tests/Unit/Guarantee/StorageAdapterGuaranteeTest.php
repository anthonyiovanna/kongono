<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Guarantee;

use Kongono\StorageAdapterContract\Exception\InvalidStorageAdapterContractException;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuarantee;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterGuaranteeSet;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitation;
use Kongono\StorageAdapterContract\Guarantee\StorageAdapterLimitationSet;
use PHPUnit\Framework\TestCase;

final class StorageAdapterGuaranteeTest extends TestCase
{
    public function testSetsUseDeclarationOrder(): void
    {
        $set = new StorageAdapterGuaranteeSet([StorageAdapterGuarantee::OperationCleanup,StorageAdapterGuarantee::CreateIfAbsent]);
        self::assertSame([StorageAdapterGuarantee::CreateIfAbsent,StorageAdapterGuarantee::OperationCleanup], $set->guarantees());
        self::assertCount(10, (new StorageAdapterLimitationSet(StorageAdapterLimitation::cases()))->limitations());
    }
    public function testDuplicateIsRejected(): void
    {
        $this->expectException(InvalidStorageAdapterContractException::class);
        new StorageAdapterGuaranteeSet([StorageAdapterGuarantee::CreateIfAbsent,StorageAdapterGuarantee::CreateIfAbsent]);
    }
}
