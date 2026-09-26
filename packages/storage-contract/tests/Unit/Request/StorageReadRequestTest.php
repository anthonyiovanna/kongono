<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Request;

use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Request\StorageReadRequest;
use PHPUnit\Framework\TestCase;

final class StorageReadRequestTest extends TestCase
{
    public function testReadRequestPreservesExactInertEvidence(): void
    {
        $operationIdentity = new StorageOperationIdentity('read_1');
        $objectIdentity = new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('item'));
        $maximum = new StorageByteCount(1024);
        $request = new StorageReadRequest(ContractVersion::Version1, $operationIdentity, $objectIdentity, $maximum);
        self::assertSame(ContractVersion::Version1, $request->contractVersion());
        self::assertSame($operationIdentity, $request->operationIdentity());
        self::assertSame(StorageOperation::Read, $request->operation());
        self::assertSame($objectIdentity, $request->objectIdentity());
        self::assertSame($maximum, $request->maximumByteCount());
    }
}
