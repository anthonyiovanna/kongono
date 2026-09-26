<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Request;

use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;

final class StorageDeleteRequestTest extends TestCase
{
    public function testDeleteRequiresExactVersionEvidence(): void
    {
        $identity = new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('item'));
        $request = new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('delete_1'), $identity, StorageMutationCondition::ifVersion(new StorageObjectVersion('djE')));
        self::assertSame(ContractVersion::Version1, $request->contractVersion());
        self::assertSame(StorageOperation::Delete, $request->operation());
        self::assertSame($identity, $request->objectIdentity());

        $this->expectException(InvalidStorageContractException::class);
        new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('delete_2'), $identity, StorageMutationCondition::ifAbsent());
    }
}
