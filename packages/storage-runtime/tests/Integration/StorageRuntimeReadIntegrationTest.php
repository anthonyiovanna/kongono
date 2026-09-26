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
use Kongono\StorageRuntime\Runtime\ExactCurrentStorageRuntime;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageAdapter;
use Kongono\StorageRuntime\Tests\Fixture\ScriptedStorageByteSource;
use PHPUnit\Framework\TestCase;

final class StorageRuntimeReadIntegrationTest extends TestCase
{
    public function testCompletedReadIsStreamedValidatedAndReleased(): void
    {
        $bytes = 'abcdef';
        $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor(), static function (StorageReadRequest $request) use ($bytes): StorageAdapterRead {
            $evidence = new StorageObjectEvidence($request->objectIdentity(), new StorageByteCount(strlen($bytes)), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)), new StorageMediaType('text/plain'), new StorageMetadata([]), new StorageObjectVersion('version-one'));
            return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Completed, $evidence), new ScriptedStorageByteSource($bytes));
        });
        $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
        $read = $runtime->read(ScriptedStorageAdapter::readRequest());
        $source = $read->source();
        self::assertNotNull($source);
        $first = $source->read(4);
        self::assertNotNull($first);
        self::assertSame('abcd', $first->bytes());
        $secondChunk = $source->read(4);
        self::assertNotNull($secondChunk);
        self::assertSame('ef', $secondChunk->bytes());
        self::assertNull($source->read(4));
        self::assertTrue($source->closed());
        $second = $runtime->read(ScriptedStorageAdapter::readRequest());
        self::assertSame(StorageReadOutcome::Completed, $second->result()->outcome());
        $secondSource = $second->source();
        self::assertNotNull($secondSource);
        $secondSource->close();
    }
}
