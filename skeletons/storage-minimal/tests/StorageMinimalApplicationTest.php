<?php

declare(strict_types=1);

namespace StorageMinimalApp\Tests;

use Kongono\StorageAdapterLocal\Adapter\ContainedLocalStorageAdapter;
use Kongono\StorageAdapterLocal\Configuration\LocalStorageRoot;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageContract\Condition\StorageMutationCondition;
use Kongono\StorageContract\Content\StorageByteCount;
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
use PHPUnit\Framework\TestCase;
use StorageMinimalApp\ExampleByteSource;
use StorageMinimalApp\StorageDemo;
use StorageMinimalApp\StorageWorkflow;

final class StorageMinimalApplicationTest extends TestCase
{
    public function testExplicitPrivateRootsPreserveUnrelatedSentinel(): void
    {
        $parent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-storage-minimal-' . bin2hex(random_bytes(8));
        $application = $parent . DIRECTORY_SEPARATOR . 'application';
        $output = $application . DIRECTORY_SEPARATOR . 'profiles';
        $objects = $parent . DIRECTORY_SEPARATOR . 'objects';
        mkdir($parent);
        mkdir($application);
        mkdir($output);
        mkdir($objects);
        $sentinel = $objects . DIRECTORY_SEPARATOR . 'sentinel';
        file_put_contents($sentinel, 'preserve');
        $source = file_get_contents(dirname(__DIR__) . '/kongono.storage.json');
        self::assertIsString($source);
        $customSource = $source . "\n";
        file_put_contents($application . DIRECTORY_SEPARATOR . 'kongono.storage.json', $customSource);
        try {
            $workflow = new StorageWorkflow($application, 'kongono.storage.json', $output);
            self::assertTrue($workflow->plan()->publicationRequired());
            self::assertSame(1, $workflow->build()->profiles()->profileCount());
            self::assertSame(1, $workflow->check()->profiles()->profileCount());
            self::assertFalse($workflow->plan()->publicationRequired());
            self::assertSame(
                ['create' => 'completed', 'read' => 'completed', 'replace' => 'completed', 'delete' => 'completed'],
                (new StorageDemo())->run($application, 'kongono.storage.json', $output, $objects),
            );
            self::assertSame('preserve', file_get_contents($sentinel));
            self::assertSame($customSource, file_get_contents($application . DIRECTORY_SEPARATOR . 'kongono.storage.json'));
        } finally {
            $this->removePrivate($parent);
        }
    }

    public function testPreexistingDemoObjectIsNotOverwrittenOrDeleted(): void
    {
        $parent = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-storage-preexisting-' . bin2hex(random_bytes(8));
        $application = $parent . DIRECTORY_SEPARATOR . 'application';
        $output = $application . DIRECTORY_SEPARATOR . 'profiles';
        $objects = $parent . DIRECTORY_SEPARATOR . 'objects';
        self::assertTrue(mkdir($parent));
        self::assertTrue(mkdir($application));
        self::assertTrue(mkdir($output));
        self::assertTrue(mkdir($objects));
        self::assertSame(8, file_put_contents($objects . DIRECTORY_SEPARATOR . 'sentinel', 'preserve'));
        $source = file_get_contents(dirname(__DIR__) . '/kongono.storage.json');
        self::assertIsString($source);
        self::assertSame(strlen($source), file_put_contents($application . DIRECTORY_SEPARATOR . 'kongono.storage.json', $source));
        try {
            $workflow = new StorageWorkflow($application, 'kongono.storage.json', $output);
            $current = $workflow->build();
            $adapter = new ContainedLocalStorageAdapter(new LocalStorageRoot($objects), $current->artifact(), new StorageProfileName('demo'));
            $identity = new StorageObjectIdentity(new StorageNamespace('demo-objects'), new LogicalObjectIdentity('demo-object'));
            $bytes = 'caller owned existing content';
            $write = new StorageWriteRequest(
                ContractVersion::Version1,
                new StorageOperationIdentity('preexisting-create'),
                StorageOperation::Create,
                $identity,
                new StorageByteCount(strlen($bytes)),
                new StorageContentDigest(StorageDigestAlgorithm::Sha256, hash('sha256', $bytes)),
                new StorageMediaType('text/plain'),
                new StorageMetadata([]),
                StorageMutationCondition::ifAbsent(),
            );
            $writeSource = new ExampleByteSource($bytes);
            self::assertSame(StorageMutationOutcome::Completed, $adapter->write($write, $writeSource)->outcome());
            self::assertFalse($writeSource->closed());
            $writeSource->close();
            self::assertSame(
                ['create' => 'already_exists', 'read' => 'skipped', 'replace' => 'skipped', 'delete' => 'skipped'],
                (new StorageDemo())->run($application, 'kongono.storage.json', $output, $objects),
            );
            $read = $adapter->read(new StorageReadRequest(ContractVersion::Version1, new StorageOperationIdentity('verify-existing'), $identity, new StorageByteCount(4096)));
            self::assertSame(StorageReadOutcome::Completed, $read->result()->outcome());
            $readSource = $read->source();
            self::assertNotNull($readSource);
            try {
                self::assertSame($bytes, $readSource->read(1024)?->bytes());
            } finally {
                $readSource->close();
            }
            self::assertSame('preserve', file_get_contents($objects . DIRECTORY_SEPARATOR . 'sentinel'));
        } finally {
            $this->removePrivate($parent);
        }
    }

    private function removePrivate(string $path, ?string $base = null): void
    {
        if (!is_dir($path)) {
            return;
        }
        $resolved = realpath($path);
        self::assertIsString($resolved);
        $base ??= $resolved;
        self::assertTrue($resolved === $base || str_starts_with($resolved, $base . DIRECTORY_SEPARATOR));
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                self::fail('Unexpected private fixture entry.');
            }
            self::assertFalse($entry->isLink());
            if ($entry->isDir()) {
                $this->removePrivate($entry->getPathname(), $base);
            } else {
                self::assertTrue(unlink($entry->getPathname()));
            }
        }
        self::assertTrue(rmdir($path));
    }
}
