<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Request;

use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Pagination\StorageContinuationToken;
use Kongono\StorageContract\Request\StorageListRequest;
use PHPUnit\Framework\TestCase;

final class StorageListRequestTest extends TestCase
{
    public function testListRequestPreservesBoundedPaginationEvidence(): void
    {
        $token = new StorageContinuationToken('bmV4dA');
        $request = new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('list_1'), new StorageNamespace('objects'), 1000, $token);
        self::assertSame(StorageOperation::List, $request->operation());
        self::assertSame(1000, $request->pageSize());
        self::assertSame($token, $request->continuationToken());
    }

    public function testPageSizeOutsideClosedRangeFails(): void
    {
        foreach ([0, 1001] as $pageSize) {
            try {
                new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('list_1'), new StorageNamespace('objects'), $pageSize);
                self::fail('Expected invalid page size.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2105', 'KGN-STORAGE-CONTRACT-2108']);
            }
        }
    }
}
