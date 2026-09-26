<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Request;

use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;

final class StorageWriteRequestTest extends TestCase
{
    public function testCreateAndReplaceRequireTheirExactConditions(): void
    {
        $create = $this->request(StorageOperation::Create, StorageMutationCondition::ifAbsent());
        self::assertSame(StorageOperation::Create, $create->operation());
        self::assertSame(3, $create->declaredByteCount()->value());
        self::assertSame('text/plain', $create->mediaType()->value());
        $replace = $this->request(StorageOperation::Replace, StorageMutationCondition::ifVersion(new StorageObjectVersion('djE')));
        self::assertSame(StorageOperation::Replace, $replace->operation());
    }

    public function testUnsupportedOperationAndMismatchedConditionsFail(): void
    {
        foreach ([
            [StorageOperation::Read, StorageMutationCondition::ifAbsent()],
            [StorageOperation::Create, StorageMutationCondition::ifVersion(new StorageObjectVersion('djE'))],
            [StorageOperation::Replace, StorageMutationCondition::ifAbsent()],
        ] as [$operation, $condition]) {
            try {
                $this->request($operation, $condition);
                self::fail('Expected invalid write relation.');
            } catch (InvalidStorageContractException $failure) {
                self::assertContains($failure->diagnosticId(), ['KGN-STORAGE-CONTRACT-2104', 'KGN-STORAGE-CONTRACT-2105']);
            }
        }
    }

    private function request(StorageOperation $operation, StorageMutationCondition $condition): StorageWriteRequest
    {
        return new StorageWriteRequest(
            ContractVersion::Version1,
            new StorageOperationIdentity('write_1'),
            $operation,
            new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('item')),
            new StorageByteCount(3),
            new StorageContentDigest(StorageDigestAlgorithm::Sha256, str_repeat('a', 64)),
            new StorageMediaType('text/plain'),
            new StorageMetadata([]),
            $condition,
        );
    }
}
