<?php

declare(strict_types=1);

namespace Kongono\Storage\Tests;

use Kongono\Storage\LocalStorage;
use Kongono\Storage\MemoryStorage;
use Kongono\Storage\S3Storage;
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

    public function testS3StorageSignsSigV4ConditionalWritesListsXmlAndPresignsUrls(): void
    {
        $calls = [];
        $s3 = new S3Storage(
            bucket: 'my-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            region: 'us-east-1',
            endpoint: 'https://r2.example.com',
            httpSender: static function (string $method, string $url, array $headers, string $body) use (&$calls): array {
                $calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
                if ('PUT' === $method && isset($headers['if-none-match'])) {
                    return ['status' => 412, 'headers' => [], 'body' => ''];
                }
                if ('PUT' === $method) {
                    return ['status' => 200, 'headers' => ['etag' => '"abc123"'], 'body' => ''];
                }
                if ('GET' === $method && str_contains($url, 'list-type=2')) {
                    return [
                        'status' => 200,
                        'headers' => [],
                        'body' => '<ListBucketResult><Contents><Key>docs/a.txt</Key><Size>5</Size><ETag>"e1"</ETag><LastModified>2026-11-20T00:00:00Z</LastModified></Contents></ListBucketResult>',
                    ];
                }
                if ('GET' === $method) {
                    return ['status' => 200, 'headers' => ['content-type' => 'text/plain'], 'body' => 'cloud bytes'];
                }

                return ['status' => 204, 'headers' => [], 'body' => ''];
            },
        );

        $put = $s3->put('docs/a.txt', 'cloud bytes', 'text/plain');
        self::assertSame(StorageOutcome::Created, $put->outcome);
        self::assertSame('abc123', $put->meta?->version);
        self::assertStringStartsWith('AWS4-HMAC-SHA256 Credential=AKIAIOSFODNN7EXAMPLE/', $calls[0]['headers']['authorization']);

        $dup = $s3->putIfAbsent('docs/a.txt', 'dup');
        self::assertSame(StorageOutcome::AlreadyExists, $dup->outcome);

        self::assertSame('cloud bytes', $s3->get('docs/a.txt'));
        $listed = $s3->list('docs/');
        self::assertCount(1, $listed);
        self::assertSame('docs/a.txt', $listed[0]->key);

        $presigned = $s3->presignedGetUrl('docs/a.txt', expiresSeconds: 300, timestamp: 1700000000);
        self::assertStringContainsString('X-Amz-Algorithm=AWS4-HMAC-SHA256', $presigned);
        self::assertStringContainsString('X-Amz-Signature=', $presigned);
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

    public function testStorageRejectsAlternateDataStreamsAndS3XxePayloads(): void
    {
        $local = new LocalStorage($this->tempDir);
        try {
            $local->put('report.txt:hidden_stream', 'evil');
            self::fail('Expected NTFS ADS / colon storage key to be rejected.');
        } catch (StorageException $e) {
            self::assertStringContainsString('Invalid storage key', $e->getMessage());
        }

        $s3 = new S3Storage(
            bucket: 'my-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            httpSender: static fn(): array => [
                'status' => 200,
                'headers' => [],
                'body' => '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><ListBucketResult/>',
            ],
        );
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unsafe XML DOCTYPE/ENTITY');
        $s3->list();
    }

    public function testS3StorageRejectsNonHttpEndpointScheme(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unsupported S3 endpoint scheme');
        new S3Storage('my-bucket', 'AKIAIOSFODNN7EXAMPLE', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', endpoint: 'gopher://127.0.0.1:6379/');
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
