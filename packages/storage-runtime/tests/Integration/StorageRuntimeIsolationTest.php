<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Integration;

use Kongono\StorageAdapterContract\Adapter\StorageAdapterRead;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Result\StorageObjectEvidence;
use Kongono\StorageContract\Result\StorageReadResult;
use Kongono\StorageContract\Version\StorageObjectVersion;
use Kongono\StorageRuntime\Exception\StorageRuntimeOperationException;
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeIsolationTest extends TestCase
{
    public function testOpenReadRejectsSameInstanceReentrancyAndCloseReleasesIt(): void
    {
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor(), static function (StorageReadRequest $request): StorageAdapterRead {
            $bytes = 'x';
            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Completed, new StorageObjectEvidence($request->objectIdentity(), new StorageByteCount(1), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)), new StorageMediaType('text/plain'), new StorageMetadata([]), new StorageObjectVersion('version-one'))), new ScriptedStorageByteSource($bytes));
        });
        $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
        $open = $runtime->read(ScriptedStorageAdapter::readRequest());
        try {
            $runtime->read(ScriptedStorageAdapter::readRequest());
            self::fail('Expected reentrancy failure.');
        } catch (StorageRuntimeOperationException $failure) {
            self::assertSame('KGN-STORAGE-RUNTIME-2135', $failure->diagnosticId());
        }
        $open->source()?->close();
        self::assertSame(StorageReadOutcome::Completed, $runtime->read(ScriptedStorageAdapter::readRequest())->result()->outcome());
    }
}
