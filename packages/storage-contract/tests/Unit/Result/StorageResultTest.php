<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Tests\Unit\Result;

use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Pagination\StorageContinuationToken;
use Kongono\StorageContract\Result\StorageListEntry;
use Kongono\StorageContract\Result\StorageListPage;
use Kongono\StorageContract\Result\StorageListResult;
use Kongono\StorageContract\Result\StorageMutationResult;
use Kongono\StorageContract\Result\StorageObjectEvidence;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\TestCase;

final class StorageResultTest extends TestCase
{
    public function testCompletedResultsCarryOnlyTheirAllowedDetachedEvidence(): void
    {
        $identity = self::identity('a');
        $version = new StorageObjectVersion('djE');
        $digest = new StorageContentDigest(StorageDigestAlgorithm::Sha256, str_repeat('a', 64));
        $evidence = new StorageObjectEvidence($identity, new StorageByteCount(3), $digest, new StorageMediaType('text/plain'), new StorageMetadata([]), $version);
        self::assertSame($evidence, (new StorageReadResult(StorageReadOutcome::Completed, $evidence))->evidence());
        self::assertSame($version, (new StorageMutationResult(StorageMutationOutcome::Completed, $version, $digest))->resultingVersion());

        $entry = new StorageListEntry($identity, new StorageByteCount(3), $version);
        $complete = new StorageListPage([$entry], true);
        self::assertSame([$entry], (new StorageListResult(StorageListOutcome::Completed, $complete))->page()?->entries());
        $continued = new StorageListPage([$entry], false, new StorageContinuationToken('bmV4dA'));
        self::assertFalse($continued->complete());
    }

    public function testFailureOutcomesCarryNoSuccessEvidenceAndPagesAreOrdered(): void
    {
        $invalid = [
            static fn() => new StorageReadResult(StorageReadOutcome::Absent, new StorageObjectEvidence(self::identity('a'), new StorageByteCount(0), null, null, new StorageMetadata([]), null)),
            static fn() => new StorageMutationResult(StorageMutationOutcome::Conflict, new StorageObjectVersion('djE')),
            static fn() => new StorageMutationResult(StorageMutationOutcome::Completed),
            static fn() => new StorageListResult(StorageListOutcome::Unavailable, new StorageListPage([], true)),
            static fn() => new StorageListPage([], true, new StorageContinuationToken('bmV4dA')),
            static fn() => new StorageListPage([new StorageListEntry(self::identity('b')), new StorageListEntry(self::identity('a'))], true),
        ];
        foreach ($invalid as $construct) {
            try {
                $construct();
                self::fail('Expected invalid result relation.');
            } catch (InvalidStorageContractException $failure) {
                self::assertSame('KGN-STORAGE-CONTRACT-2106', $failure->diagnosticId());
            }
        }
    }

    private static function identity(string $logical): StorageObjectIdentity
    {
        return new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity($logical));
    }
}
