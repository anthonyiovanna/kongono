<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Tests\Integration;

use Closure;
use Kongono\StorageAdapterContract\Tests\Fixture\StorageFixture;
use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageAdapterLocal\Exception\InvalidLocalStorageConfigurationException;
use Kongono\StorageAdapterLocal\Internal\LocalAdapterGuard;
use Kongono\StorageAdapterLocal\Internal\LocalPathLayout;
use Kongono\StorageAdapterLocal\Tests\Integration\LocalStorageAdapterContainmentTest as StorageCorrectionFixture;
use Kongono\StorageBuild\Profile\StorageProfileName;
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
use Kongono\StorageContract\Outcome\StorageListOutcome;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Outcome\StorageReadOutcome;
use Kongono\StorageContract\Request\StorageDeleteRequest;
use Kongono\StorageContract\Request\StorageListRequest;
use Kongono\StorageContract\Request\StorageReadRequest;
use Kongono\StorageContract\Request\StorageWriteRequest;
use Kongono\StorageContract\Stream\StorageByteSource;
use Kongono\StorageContract\Version\StorageObjectVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalStorageAdapterContainmentTest extends TestCase
{
    #[DataProvider('invalidRoots')] public function testUnsafeRootFormsAreRejected(string $root): void
    {
        $this->expectException(InvalidLocalStorageConfigurationException::class);
        new LocalStorageRoot($root);
    }

    /** @return list<array{string}> */
    public static function invalidRoots(): array
    {
        return [['../escape'],['php://memory'],['C:/tmp/*'],['C:/tmp:stream'],['C:/CON']];
    }

    public function testRedirectedAncestorsRejectAllOperationsAndCleanupDoesNotFollowThem(): void
    {
        $fixture = StorageCorrectionFixture::fixture();
        try {
            $cases = [];
            $links = [];
            foreach (['namespace', 'objects', 'locks'] as $case) {
                [$adapter, $layout] = $fixture->adapter($case);
                $created = $adapter->write(StorageCorrectionFixture::write(), StorageCorrectionFixture::source());
                self::assertSame(StorageMutationOutcome::Completed, $created->outcome());
                $version = $created->resultingVersion();
                self::assertNotNull($version);
                $objects = $layout->objectDirectory('objects');
                $ancestor = match ($case) {
                    'namespace' => dirname($objects),
                    'objects' => $objects,
                    'locks' => dirname($layout->lockPath('objects', 'one')),
                };
                $outside = $fixture->root . '/outside-' . $case;
                self::assertTrue(rename($ancestor, $outside));
                $links[$ancestor] = $outside;
                $cases[] = [$adapter, $layout, $version, $outside, StorageCorrectionFixture::inventory($outside)];
            }
            [$cleanupAdapter, $cleanupLayout] = $fixture->adapter('cleanup');
            $cleanupLayout->ensureNamespace('objects');
            $cleanupOutside = $fixture->root . '/outside-cleanup';
            self::assertTrue(mkdir($cleanupOutside));
            self::assertSame(8, file_put_contents($cleanupOutside . '/sentinel', 'sentinel'));
            $stagedLink = $cleanupLayout->temporaryPath('objects', 'one', 'create-one');
            $links[$stagedLink] = $cleanupOutside;
            $fixture->junctions($links);
            foreach ($cases as [$adapter, $layout, $version, $outside, $before]) {
                self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write('create-other', logical: 'other'), StorageCorrectionFixture::source())->outcome());
                self::assertSame(StorageReadOutcome::Unavailable, $adapter->read(StorageCorrectionFixture::read())->result()->outcome());
                self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write('replace-one', $version), StorageCorrectionFixture::source())->outcome());
                self::assertSame(StorageMutationOutcome::Unavailable, $adapter->delete(StorageCorrectionFixture::delete($version))->outcome());
                self::assertNotSame(StorageListOutcome::Completed, $adapter->list(StorageCorrectionFixture::list())->outcome());
                self::assertSame($before, StorageCorrectionFixture::inventory($outside));
            }
            $before = StorageCorrectionFixture::inventory($cleanupOutside);
            $source = StorageCorrectionFixture::source();
            $result = $cleanupAdapter->write(StorageCorrectionFixture::write(), $source);
            self::assertSame(StorageMutationOutcome::Unavailable, $result->outcome());
            self::assertNull($result->resultingVersion());
            self::assertFalse($source->closed());
            self::assertSame($before, StorageCorrectionFixture::inventory($cleanupOutside));
            self::assertSame($cleanupOutside, LocalAdapterGuard::normalize((string) realpath($stagedLink)));
            self::assertFileDoesNotExist($cleanupLayout->objectPath('objects', 'one'));
        } finally {
            $fixture->cleanup();
        }
    }

    public function testMissingParentsAndUnexpectedFinalTypesAreHandledBeforeEffect(): void
    {
        $fixture = StorageCorrectionFixture::fixture();
        try {
            [$adapter, $layout] = $fixture->adapter();
            self::assertSame(StorageReadOutcome::Absent, $adapter->read(StorageCorrectionFixture::read())->result()->outcome());
            $path = $layout->objectPath('objects', 'one');
            self::assertTrue(mkdir($path));
            self::assertSame(StorageReadOutcome::Unavailable, $adapter->read(StorageCorrectionFixture::read())->result()->outcome());
            self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write(), StorageCorrectionFixture::source())->outcome());
            self::assertDirectoryExists($path);
            self::assertTrue(rmdir($path));
            $lock = $layout->lockPath('objects', 'one');
            self::assertTrue(unlink($lock));
            self::assertTrue(mkdir($lock));
            self::assertSame(StorageMutationOutcome::Unavailable, $adapter->write(StorageCorrectionFixture::write(), StorageCorrectionFixture::source())->outcome());
            self::assertDirectoryExists($lock);
            self::assertFileDoesNotExist($path);
        } finally {
            $fixture->cleanup();
        }
    }
    public static function fixture(): self
    {
        $fixture = new self('fixture');
        $fixture->initializeFixture();
        return $fixture;
    }

    public string $root = '';
    /** @var array<string, string> */
    private array $links = [];

    private function initializeFixture(): void
    {
        $parent = realpath(sys_get_temp_dir());
        if (false === $parent) {
            throw new RuntimeException('Fixture parent unavailable.');
        }
        $this->root = LocalAdapterGuard::normalize($parent) . '/storage-correction-' . bin2hex(random_bytes(16));
        if (!mkdir($this->root, 0700)) {
            throw new RuntimeException('Fixture root unavailable.');
        }
    }

    /** @return array{ContainedLocalStorageAdapter, LocalPathLayout} */
    public function adapter(string $name = 'adapter'): array
    {
        $path = $this->root . '/' . $name;
        if (!mkdir($path, 0700)) {
            throw new RuntimeException('Fixture adapter unavailable.');
        }
        $root = new LocalStorageRoot($path);
        return [new ContainedLocalStorageAdapter($root, StorageFixture::artifact(), new StorageProfileName('local-test')), new LocalPathLayout($root)];
    }

    public static function identity(string $logical = 'one'): StorageObjectIdentity
    {
        return new StorageObjectIdentity(new StorageNamespace('objects'), new LogicalObjectIdentity($logical));
    }

    public static function write(string $operation = 'create-one', ?StorageObjectVersion $version = null, string $media = 'text/plain', string $logical = 'one'): StorageWriteRequest
    {
        return new StorageWriteRequest(ContractVersion::Version1, new StorageOperationIdentity($operation), null === $version ? StorageOperation::Create : StorageOperation::Replace, self::identity($logical), new StorageByteCount(5), new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', 'hello')), new StorageMediaType($media), new StorageMetadata([]), null === $version ? StorageMutationCondition::ifAbsent() : StorageMutationCondition::ifVersion($version));
    }

    public static function delete(StorageObjectVersion $version): StorageDeleteRequest
    {
        return new StorageDeleteRequest(ContractVersion::Version1, new StorageOperationIdentity('delete-one'), self::identity(), StorageMutationCondition::ifVersion($version));
    }

    public static function read(): StorageReadRequest
    {
        return new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('read-one'), self::identity(), new StorageByteCount(10));
    }

    public static function list(): StorageListRequest
    {
        return new StorageListRequest(ContractVersion::Version1, new StorageOperationIdentity('list-one'), new StorageNamespace('objects'), 2);
    }

    public static function source(string $bytes = 'hello', ?Closure $before = null): StorageByteSource
    {
        return new class ($bytes, $before) implements StorageByteSource {
            private int $offset = 0;
            private bool $closed = false;
            public function __construct(private readonly string $bytes, private readonly ?Closure $before) {}
            public function read(int $maximumBytes): ?StorageChunk
            {
                if (null !== $this->before && 0 === $this->offset) {
                    ($this->before)();
                }
                if ($this->offset >= strlen($this->bytes)) {
                    return null;
                }
                $part = substr($this->bytes, $this->offset, $maximumBytes);
                $this->offset += strlen($part);
                return new StorageChunk($part);
            }
            public function exhausted(): bool
            {
                return $this->offset >= strlen($this->bytes);
            }
            public function close(): void
            {
                $this->closed = true;
            }
            public function closed(): bool
            {
                return $this->closed;
            }
        };
    }

    /** @param array<string, string> $links */
    public function junctions(array $links): void
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            foreach ($links as $path => $target) {
                if (!symlink($target, $path)) {
                    throw new RuntimeException('Required link capability unavailable.');
                }
                $this->links[$path] = $target;
            }
            return;
        }
        $commands = [];
        foreach ($links as $path => $target) {
            if (!str_starts_with($path, $this->root . '/') || !str_starts_with($target, $this->root . '/') || false !== @lstat($path) || !is_dir($target)) {
                throw new RuntimeException('Junction fixture containment failed.');
            }
            $commands[] = "New-Item -ItemType Junction -Path '" . str_replace("'", "''", $path) . "' -Target '" . str_replace("'", "''", $target) . "' -ErrorAction Stop | Out-Null";
        }
        $process = proc_open(['C:/Windows/System32/WindowsPowerShell/v1.0/powershell.exe', '-NoProfile', '-NonInteractive', '-Command', implode('; ', $commands)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->root);
        if (!is_resource($process)) {
            throw new RuntimeException('Required junction capability unavailable.');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $deadline = microtime(true) + 30;
        $status = proc_get_status($process);
        $captured = '';
        while ($status['running']) {
            $captured .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            if (microtime(true) > $deadline || strlen($captured) > 65_536) {
                proc_terminate($process);
                throw new RuntimeException('Junction fixture process limit; retain fixture.');
            }
            usleep(10_000);
            $status = proc_get_status($process);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        foreach ($links as $path => $target) {
            clearstatcache(true, $path);
            if (LocalAdapterGuard::normalize((string) realpath($path)) !== $target) {
                throw new RuntimeException('Junction fixture creation unproved; retain fixture.');
            }
            $this->links[$path] = $target;
        }
        if (0 !== $status['exitcode']) {
            throw new RuntimeException('Required junction capability unavailable.');
        }
    }

    public function movedLink(string $from, string $to): void
    {
        $target = $this->links[$from] ?? throw new RuntimeException('Unknown fixture link.');
        unset($this->links[$from]);
        $this->links[$to] = $target;
    }

    /** @return array<string, string> */
    public static function inventory(string $path): array
    {
        $result = [];
        self::walk($path, $result);
        ksort($result, SORT_STRING);
        return $result;
    }

    /** @param array<string, string> $result */
    private static function walk(string $path, array &$result): void
    {
        clearstatcache(true, $path);
        if (is_link($path) || LocalAdapterGuard::normalize((string) realpath($path)) !== $path) {
            throw new RuntimeException('Fixture inventory refuses redirected path.');
        }
        if (is_file($path)) {
            $result[$path] = (string) hash_file('sha256', $path);
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file instanceof \SplFileInfo) {
                throw new RuntimeException('Unexpected fixture entry.');
            }
            self::walk(LocalAdapterGuard::normalize($file->getPathname()), $result);
        }
    }

    public function cleanup(): void
    {
        foreach ($this->links as $path => $target) {
            clearstatcache(true, $path);
            if (LocalAdapterGuard::normalize((string) realpath($path)) !== $target || !str_starts_with($path, $this->root . '/')) {
                throw new RuntimeException('Fixture link cleanup unproved; retain fixture.');
            }
            $before = self::inventory($target);
            $removed = 'Windows' === PHP_OS_FAMILY ? rmdir($path) : unlink($path);
            if (!$removed || self::inventory($target) !== $before) {
                throw new RuntimeException('Fixture link retirement unproved; retain fixture.');
            }
        }
        $this->links = [];
        $this->remove($this->root);
    }

    private function remove(string $path): void
    {
        clearstatcache(true, $path);
        if (($path !== $this->root && !str_starts_with($path, $this->root . '/')) || is_link($path) || LocalAdapterGuard::normalize((string) realpath($path)) !== $path) {
            throw new RuntimeException('Fixture cleanup containment unproved; retain fixture.');
        }
        if (is_file($path)) {
            if (!unlink($path)) {
                throw new RuntimeException('Fixture file retirement failed.');
            }
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file instanceof \SplFileInfo) {
                throw new RuntimeException('Unexpected fixture entry.');
            }
            $this->remove(LocalAdapterGuard::normalize($file->getPathname()));
        }
        if (!rmdir($path)) {
            throw new RuntimeException('Fixture directory retirement failed.');
        }
    }
}
