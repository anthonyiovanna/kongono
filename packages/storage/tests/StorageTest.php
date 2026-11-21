<?php

declare(strict_types=1);

namespace Kongono\Storage\Tests;

use Kongono\Storage\LocalStorage;
use Kongono\Storage\MemoryStorage;
use Kongono\Storage\StorageException;
use Kongono\Storage\StorageInterface;
use Kongono\Storage\StorageOutcome;
use PHPUnit\Framework\TestCase;

final class StorageTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-storage-test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testLocalStorageLifecycleConcurrencyAndContainment(): void
    {
        $storage = new LocalStorage($this->tempDir);
        $this->assertStorageContract($storage);

        $this->expectException(StorageException::class);
        $storage->put('../../outside.txt', 'nope');
    }

    public function testMemoryStorageMatchesExactContract(): void
    {
        $this->assertStorageContract(new MemoryStorage());
    }

    private function assertStorageContract(StorageInterface $storage): void
    {
        $created = $storage->putIfAbsent('docs/hello.txt', 'v1 content', 'text/plain', ['author' => 'ada']);
        self::assertSame(StorageOutcome::Created, $created->outcome);
        self::assertNotNull($created->meta);
        self::assertSame(hash('sha256', 'v1 content'), $created->meta->sha256);
        self::assertSame('v1 content', $storage->get('docs/hello.txt'));

        // putIfAbsent refuses overwrite
        $dup = $storage->putIfAbsent('docs/hello.txt', 'overwrite');
        self::assertSame(StorageOutcome::AlreadyExists, $dup->outcome);
        self::assertSame('v1 content', $storage->get('docs/hello.txt'));

        // replaceIfVersion detects stale version
        $conflict = $storage->replaceIfVersion('docs/hello.txt', 'wrong-version', 'v2');
        self::assertSame(StorageOutcome::Conflict, $conflict->outcome);

        // replaceIfVersion succeeds with matching version
        $replaced = $storage->replaceIfVersion('docs/hello.txt', $created->meta->version, 'v2 content', 'text/plain');
        self::assertSame(StorageOutcome::Replaced, $replaced->outcome);
        self::assertNotNull($replaced->meta);
        self::assertSame('v2 content', $storage->get('docs/hello.txt'));

        // list with prefix
        $storage->put('images/logo.png', 'PNG', 'image/png');
        $docs = $storage->list('docs/');
        self::assertCount(1, $docs);
        self::assertSame('docs/hello.txt', $docs[0]->key);

        // conditional delete
        self::assertSame(StorageOutcome::Conflict, $storage->delete('docs/hello.txt', 'wrong'));
        self::assertSame(StorageOutcome::Deleted, $storage->delete('docs/hello.txt', $replaced->meta->version));
        self::assertFalse($storage->exists('docs/hello.txt'));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = scandir($dir);
        if (false === $entries) {
            return;
        }
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
