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

    public function testRejectedReturnedSourceIsRetiredOnceAndScopeReleased(): void
    {
        foreach (['echo', 'evidence'] as $kind) {
            foreach ([false, true] as $failClose) {
                $source = new ScriptedStorageByteSource('hello', failClose: $failClose);
                $attempt = 0;
                $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor(), static function (StorageReadRequest $request) use ($source, $kind, &$attempt): StorageAdapterRead {
                    if (++$attempt > 1) {
                        return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent));
                    }
                    $echo = 'echo' === $kind ? new StorageReadRequest($request->contractVersion(), new \Kongono\StorageContract\Operation\StorageOperationIdentity('wrong-echo'), $request->objectIdentity(), $request->maximumByteCount()) : $request;
                    $evidence = new StorageObjectEvidence($request->objectIdentity(), new StorageByteCount('evidence' === $kind ? 17 : 5), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', 'hello')), new StorageMediaType('text/plain'), new StorageMetadata([]), new StorageObjectVersion('version-one'));
                    return new StorageAdapterRead($echo, new StorageReadResult(StorageReadOutcome::Completed, $evidence), $source);
                });
                $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
                try {
                    $runtime->read(ScriptedStorageAdapter::readRequest());
                    self::fail('Invalid returned read accepted.');
                } catch (\Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException $failure) {
                    self::assertFalse($failClose);
                    self::assertNotSame('', $failure->getMessage());
                } catch (\Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException $failure) {
                    self::assertTrue($failClose);
                    self::assertInstanceOf(\Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException::class, $failure->primaryFailure());
                    self::assertCount(1, $failure->cleanupFailures());
                    self::assertSame('fixture-close-failure', $failure->cleanupFailures()[0]->getMessage());
                    self::assertSame($failure->primaryFailure(), $failure->getPrevious());
                }
                self::assertSame(1, $source->closeCalls);
                self::assertTrue($source->closed());
                self::assertSame(StorageReadOutcome::Absent, $runtime->read(ScriptedStorageAdapter::readRequest())->result()->outcome());
            }
        }
    }

    public function testValidHandoffAlreadyClosedObservationAndAdapterThrow(): void
    {
        foreach (['valid', 'closed', 'observation', 'throw'] as $kind) {
            $source = new ScriptedStorageByteSource('hello');
            $attempt = 0;
            $original = new \Error('synthetic-adapter-error');
            $adapter = new ScriptedStorageAdapter(ScriptedStorageAdapter::makeDescriptor(), static function (StorageReadRequest $request) use ($source, $kind, $original, &$attempt): StorageAdapterRead {
                if (++$attempt > 1) {
                    return new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Absent));
                }
                if ('throw' === $kind) {
                    throw $original;
                }
                $evidence = new StorageObjectEvidence($request->objectIdentity(), new StorageByteCount(5), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', 'hello')), new StorageMediaType('text/plain'), new StorageMetadata([]), new StorageObjectVersion('version-one'));
                $read = new StorageAdapterRead($request, new StorageReadResult(StorageReadOutcome::Completed, $evidence), $source);
                if ('closed' === $kind) {
                    $source->close();
                }
                $source->failClosedObservation = 'observation' === $kind;
                return $read;
            });
            $runtime = new ExactCurrentStorageRuntime(ScriptedStorageAdapter::binding($adapter));
            try {
                $read = $runtime->read(ScriptedStorageAdapter::readRequest());
                self::assertSame('valid', $kind);
                self::assertSame(0, $source->closeCalls);
                self::assertFalse($source->closed());
                $read->source()?->close();
                self::assertSame(1, $source->closeCalls);
            } catch (\Error $failure) {
                self::assertSame('throw', $kind);
                self::assertSame($original, $failure);
                self::assertSame(0, $source->closeCalls);
            } catch (\Kongono\StorageRuntime\Exception\InvalidStorageRuntimeException) {
                self::assertSame('closed', $kind);
                self::assertSame(1, $source->closeCalls);
            } catch (\Kongono\StorageRuntime\Exception\StorageRuntimeOperationAndCleanupException $failure) {
                self::assertSame('observation', $kind);
                self::assertInstanceOf(\Kongono\StorageRuntime\Exception\StorageRuntimeOperationException::class, $failure->primaryFailure());
                self::assertCount(1, $failure->cleanupFailures());
                self::assertSame(0, $source->closeCalls);
            }
            self::assertSame(StorageReadOutcome::Absent, $runtime->read(ScriptedStorageAdapter::readRequest())->result()->outcome());
            $source->failClosedObservation = false;
            if (!$source->closed()) {
                $source->close();
            }
        }
    }
}
