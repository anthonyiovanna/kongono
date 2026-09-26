<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Kongono\StorageAdapterContract\Tests\Fixture\StorageFixture;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
use Kongono\StorageContract\Content\StorageChunk;
use Kongono\StorageContract\Content\StorageMediaType;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\LogicalObjectIdentity;
use Kongono\StorageContract\Identity\StorageNamespace;
use Kongono\StorageContract\Identity\StorageObjectIdentity;
use Kongono\StorageContract\Metadata\StorageMetadata;
use Kongono\StorageContract\Operation\StorageOperation;
use Kongono\StorageContract\Operation\StorageOperationIdentity;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Stream\StorageByteSource;
use PHPUnit\Framework\TestCase;

final class LocalStorageAdapterCreateReadTest extends TestCase
{
    public function testCreateThenReadStreamsExactBytes(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-local-' . bin2hex(random_bytes(6));
        mkdir($root);
        try {
            $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($root), StorageFixture::artifact(), new \Kongono\StorageBuild\Profile\StorageProfileName('local-test'));
            $id = new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity('one'));
            $bytes = 'hello';
            $source = new class ($bytes) implements StorageByteSource {
                private int $p = 0;
                private bool $c = false;
                public function __construct(private string $b) {}public function read(int $m): ?StorageChunk
                {
                    if ($this->p >= strlen($this->b)) {
                        return null;
                    }$v = substr($this->b, $this->p, $m);
                    $this->p += strlen($v);
                    return new StorageChunk($v);
                }public function exhausted(): bool
                {
                    return $this->p >= strlen($this->b);
                }public function close(): void
                {
                    $this->c = true;
                }public function closed(): bool
                {
                    return $this->c;
                }
            };
            $write = new StorageWriteRequest(ContractVersion::Version1, new StorageOperationIdentity('create-one'), StorageOperation::Create, $id, new StorageByteCount(strlen($bytes)), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)), new StorageMediaType('text/plain'), new StorageMetadata([]), StorageMutationCondition::ifAbsent());
            self::assertSame(StorageMutationOutcome::Completed, $adapter->write($write, $source)->outcome());
            self::assertFalse($source->closed());
            $read = $adapter->read(new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('read-one'), $id, new StorageByteCount(10)));
            self::assertSame(StorageReadOutcome::Completed, $read->result()->outcome());
            self::assertSame($bytes, $read->source()?->read(4)?->bytes() . $read->source()?->read(4)?->bytes());
            $read->source()?->close();
        } finally {
            $this->remove($root);
        }
    }
    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $f) {
            if (!$f instanceof \SplFileInfo) {
                self::fail('Fixture cleanup iterator returned an unexpected value.');
            }
            $f->isDir() ? $this->remove($f->getPathname()) : unlink($f->getPathname());
        }rmdir($path);
    }
}
