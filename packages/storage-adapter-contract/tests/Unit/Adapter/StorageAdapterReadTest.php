<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Unit\Adapter;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageAdapterContract\Exception\InvalidStorageAdapterContractException;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Result\StorageReadResult;
use PHPUnit\Framework\TestCase;

final class StorageAdapterReadTest extends TestCase
{
    public function testNonCompletedCannotCarrySource(): void
    {
        $request = new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('read-1'), new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('item')), new StorageByteCount(1));
        $this->expectException(InvalidStorageAdapterContractException::class);
        new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent), new class implements \Kongono\StorageContract\Stream\StorageByteSource {
            public function read(int $maximumBytes): ?\Kongono\StorageContract\Content\StorageChunk
            {
                return null;
            } public function exhausted(): bool
            {
                return true;
            } public function close(): void {} public function closed(): bool
            {
                return false;
            }
        });
    }
}
