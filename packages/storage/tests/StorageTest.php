<?php

declare(strict_types=1);

namespace Kongono\Storage\Tests;

use Kongono\Http\UploadedFile;
use Kongono\Storage\LocalStorage;
use Kongono\Storage\MemoryStorage;
use Kongono\Storage\S3Storage;
use Kongono\Storage\SignedLocalUrl;
use Kongono\Storage\SigV4Signer;
use Kongono\Storage\StorageDisks;
use Kongono\Storage\StorageException;
use Kongono\Storage\StorageInterface;
use Kongono\Storage\StorageOutcome;
use Kongono\Storage\UploadedFileStore;
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

        // v1.13.0: streaming putStream / getStream (>128KB multi-chunk) + copy() + move()
        $bigPayload = str_repeat("kongono-stream-chunk-0123456789\n", 5000);
        $in = fopen('php://temp', 'r+b');
        self::assertIsResource($in);
        fwrite($in, $bigPayload);
        rewind($in);
        $streamPut = $storage->putStream('exports/report.txt', $in, 'text/plain', ['kind' => 'report']);
        fclose($in);
        self::assertSame(StorageOutcome::Created, $streamPut->outcome);
        self::assertNotNull($streamPut->meta);
        self::assertSame(strlen($bigPayload), $streamPut->meta->size);
        self::assertSame(hash('sha256', $bigPayload), $streamPut->meta->sha256);

        $out = $storage->getStream('exports/report.txt');
        self::assertIsResource($out);
        $readBack = stream_get_contents($out);
        fclose($out);
        self::assertSame($bigPayload, $readBack);
        self::assertNull($storage->getStream('exports/does-not-exist.txt'));

        $copied = $storage->copy('exports/report.txt', 'archive/report-copy.txt');
        self::assertTrue($copied->succeeded());
        self::assertSame($bigPayload, $storage->get('archive/report-copy.txt'));
        self::assertTrue($storage->exists('exports/report.txt'));

        $moved = $storage->move('archive/report-copy.txt', 'archive/report-final.txt');
        self::assertTrue($moved->succeeded());
        self::assertFalse($storage->exists('archive/report-copy.txt'));
        self::assertSame($bigPayload, $storage->get('archive/report-final.txt'));
        self::assertSame(StorageOutcome::Absent, $storage->copy('missing/nope.txt', 'archive/x.txt')->outcome);
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

    public function testRound3LocalStorageSidecarsWindowsDevicesAndS3EtagCrlfHardening(): void
    {
        $local = new LocalStorage($this->tempDir);
        foreach (['file.KGNMETA.JSON', 'file.kgnmeta.json.', 'file.tmp', '.kgnlocks/a', 'docs/CON.txt', 'docs/NUL', '.'] as $badKey) {
            try {
                $local->put($badKey, 'evil');
                self::fail('Expected rejected storage key: ' . $badKey);
            } catch (StorageException) {
                self::addToAssertionCount(1);
            }
        }

        // S3Storage rejects ETag wildcard/quote injection in If-Match
        $s3 = new S3Storage(
            bucket: 'my-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            httpSender: static fn(): array => ['status' => 200, 'headers' => [], 'body' => ''],
        );
        try {
            $s3->replaceIfVersion('docs/a.txt', 'a", *, "b', 'overwritten');
            self::fail('Expected ETag injection to be rejected.');
        } catch (StorageException $e) {
            self::assertStringContainsString('Invalid S3 ETag', $e->getMessage());
        }

        // SigV4Signer rejects CRLF headers even with custom httpSender
        $signer = new SigV4Signer('AKIAIOSFODNN7EXAMPLE', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unsafe header');
        $signer->signHeaders('GET', 's3.amazonaws.com', '/k', headers: ['x-custom' => "ok\r\nX-Injected: 1"]);
    }

    public function testRound4S3SigV4DotKeysXmlXxeAndMemoryStorageHardening(): void
    {
        // 1. S3Storage rejects crafted region authority override
        try {
            new S3Storage('my-bucket', 'AKIAIOSFODNN7EXAMPLE', 'secret', region: 'evil.com#');
            self::fail('Expected crafted S3 region to fail.');
        } catch (StorageException $e) {
            self::assertStringContainsString('Invalid S3 region', $e->getMessage());
        }

        // 2. S3Storage, MemoryStorage, and LocalStorage reject dot segments & empty segments
        $s3 = new S3Storage(
            bucket: 'my-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'secret',
            httpSender: static fn(): array => ['status' => 200, 'headers' => [], 'body' => ''],
        );
        $memory = new MemoryStorage();
        $local = new LocalStorage($this->tempDir);

        foreach (['.', 'a/./b', 'a//b'] as $badKey) {
            foreach ([$s3, $memory, $local] as $driver) {
                try {
                    $driver->put($badKey, 'x');
                    self::fail('Expected invalid storage key to fail: ' . $badKey);
                } catch (StorageException) {
                    self::addToAssertionCount(1);
                }
            }
        }

        // 3. S3Storage::list() rejects UTF-16LE / NUL-byte DOCTYPE XXE payloads
        $utf16Xxe = "\xFF\xFE" . implode("\x00", str_split('<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e "x">]><ListBucketResult/>')) . "\x00";
        $xxeS3 = new S3Storage(
            bucket: 'my-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'secret',
            httpSender: static fn(): array => ['status' => 200, 'headers' => [], 'body' => $utf16Xxe],
        );
        try {
            $xxeS3->list();
            self::fail('Expected UTF-16LE XML DOCTYPE payload to fail.');
        } catch (StorageException) {
            self::addToAssertionCount(1);
        }

        // 4. SigV4Signer validates accessKeyId, region, and presignUrl() host
        try {
            new SigV4Signer("AKIA\r\nEVIL", 'secret');
            self::fail('Expected CRLF in SigV4 accessKeyId to fail.');
        } catch (StorageException $e) {
            self::assertStringContainsString('Invalid S3 accessKeyId', $e->getMessage());
        }

        $signer = new SigV4Signer('AKIAIOSFODNN7EXAMPLE', 'secret');
        $this->expectException(StorageException::class);
        $signer->presignUrl('GET', 'https', "evil.example\r\nX-Injected: 1", '/bucket/key');
    }

    public function testStorageFactorySwitchesBetweenLocalAndS3FromEnv(): void
    {
        $prev = [
            'S3_BUCKET' => $_ENV['S3_BUCKET'] ?? null,
            'S3_ACCESS_KEY_ID' => $_ENV['S3_ACCESS_KEY_ID'] ?? null,
            'S3_SECRET_ACCESS_KEY' => $_ENV['S3_SECRET_ACCESS_KEY'] ?? null,
        ];

        try {
            unset($_ENV['S3_BUCKET'], $_SERVER['S3_BUCKET'], $_ENV['S3_ACCESS_KEY_ID'], $_SERVER['S3_ACCESS_KEY_ID'], $_ENV['S3_SECRET_ACCESS_KEY'], $_SERVER['S3_SECRET_ACCESS_KEY']);
            self::assertInstanceOf(LocalStorage::class, \Kongono\Storage\StorageFactory::fromEnv($this->tempDir));

            $_ENV['S3_BUCKET'] = 'prod-bucket';
            $_ENV['S3_ACCESS_KEY_ID'] = 'AKIAIOSFODNN7EXAMPLE';
            $_ENV['S3_SECRET_ACCESS_KEY'] = 'super-secret-s3-key';
            self::assertInstanceOf(S3Storage::class, \Kongono\Storage\StorageFactory::fromEnv($this->tempDir));
        } finally {
            foreach ($prev as $k => $v) {
                if (null === $v) {
                    unset($_ENV[$k], $_SERVER[$k]);
                } else {
                    $_ENV[$k] = $v;
                }
            }
        }
    }

    public function testS3PaginationNativeServerSideCopyMoveAndStreams(): void
    {
        $calls = [];
        $s3 = new S3Storage(
            bucket: 'media-bucket',
            accessKeyId: 'AKIAIOSFODNN7EXAMPLE',
            secretAccessKey: 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            region: 'us-east-1',
            endpoint: 'https://r2.example.com',
            httpSender: static function (string $method, string $url, array $headers, string $body) use (&$calls): array {
                $calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];

                if ('GET' === $method && str_contains($url, 'list-type=2')) {
                    if (str_contains($url, 'continuation-token=tok-page-2')) {
                        return [
                            'status' => 200,
                            'headers' => [],
                            'body' => '<ListBucketResult><IsTruncated>false</IsTruncated>'
                                . '<Contents><Key>logs/3.txt</Key><Size>3</Size><ETag>"e3"</ETag><LastModified>2026-11-22T00:00:00Z</LastModified></Contents>'
                                . '</ListBucketResult>',
                        ];
                    }

                    return [
                        'status' => 200,
                        'headers' => [],
                        'body' => '<ListBucketResult><IsTruncated>true</IsTruncated><NextContinuationToken>tok-page-2</NextContinuationToken>'
                            . '<Contents><Key>logs/1.txt</Key><Size>1</Size><ETag>"e1"</ETag><LastModified>2026-11-22T00:00:00Z</LastModified></Contents>'
                            . '<Contents><Key>logs/2.txt</Key><Size>2</Size><ETag>"e2"</ETag><LastModified>2026-11-22T00:00:00Z</LastModified></Contents>'
                            . '</ListBucketResult>',
                    ];
                }

                if ('PUT' === $method && isset($headers['x-amz-copy-source'])) {
                    return [
                        'status' => 200,
                        'headers' => [],
                        'body' => '<CopyObjectResult><ETag>"copied-etag-77"</ETag></CopyObjectResult>',
                    ];
                }

                if ('PUT' === $method) {
                    return ['status' => 200, 'headers' => ['etag' => '"stream-etag"'], 'body' => ''];
                }

                if ('GET' === $method) {
                    return ['status' => 200, 'headers' => ['content-type' => 'text/plain'], 'body' => 'streamed s3 payload'];
                }

                return ['status' => 204, 'headers' => [], 'body' => ''];
            },
        );

        // 1. Paginated ListObjectsV2 follows NextContinuationToken across pages
        $all = $s3->list('logs/');
        self::assertCount(3, $all);
        self::assertSame(['logs/1.txt', 'logs/2.txt', 'logs/3.txt'], array_map(static fn($m): string => $m->key, $all));

        // maxKeys caps early without fetching extra pages
        $capped = $s3->list('logs/', maxKeys: 2);
        self::assertCount(2, $capped);

        // 2. Native S3 server-side copy & move
        $copyRes = $s3->copy('logs/1.txt', 'archive/1.txt');
        self::assertSame(StorageOutcome::Created, $copyRes->outcome);
        self::assertSame('copied-etag-77', $copyRes->meta?->version);

        $copyCall = end($calls);
        self::assertIsArray($copyCall);
        self::assertSame('/media-bucket/logs/1.txt', $copyCall['headers']['x-amz-copy-source'] ?? null);

        $moveRes = $s3->move('logs/2.txt', 'archive/2.txt');
        self::assertTrue($moveRes->succeeded());
        $lastCall = end($calls);
        self::assertIsArray($lastCall);
        self::assertSame('DELETE', $lastCall['method']);

        // 3. putStream & getStream on S3
        $in = fopen('php://temp', 'r+b');
        self::assertIsResource($in);
        fwrite($in, 'streamed s3 payload');
        rewind($in);
        $putStream = $s3->putStream('uploads/demo.txt', $in, 'text/plain');
        fclose($in);
        self::assertSame('stream-etag', $putStream->meta?->version);

        $out = $s3->getStream('uploads/demo.txt');
        self::assertIsResource($out);
        self::assertSame('streamed s3 payload', stream_get_contents($out));
        fclose($out);
    }

    public function testStorageDisksSignedLocalUrlAndUploadedFileStoreHardening(): void
    {
        // 1. StorageDisks named registry
        $publicDisk = new MemoryStorage();
        $privateDisk = new LocalStorage($this->tempDir);
        $disks = new StorageDisks(['public' => $publicDisk, 'private' => $privateDisk], default: 'private');

        self::assertSame('private', $disks->defaultName());
        self::assertSame(['private', 'public'], $disks->names());
        self::assertSame($privateDisk, $disks->disk());
        self::assertSame($publicDisk, $disks->disk('public'));
        self::assertTrue($disks->has('public'));

        try {
            $disks->disk('nonexistent');
            self::fail('Expected unknown disk to throw.');
        } catch (StorageException $e) {
            self::assertStringContainsString('not registered', $e->getMessage());
        }

        // 2. UploadedFileStore: magic-byte sniffing accepts true PNG/PDF and blocks spoofed PHP/SVG/HTML & oversized uploads
        $uploader = new UploadedFileStore(
            storage: $privateDisk,
            allowedMimeTypes: ['image/png', 'application/pdf'],
            maxBytes: 1024,
        );

        $validPng = "\x89PNG\r\n\x1A\n" . str_repeat("\x00", 32);
        $pngUpload = UploadedFile::fromString($validPng, '../../evil/avatar.php', 'application/x-httpd-php');
        $stored = $uploader->store($pngUpload, prefix: 'avatars');
        self::assertSame(StorageOutcome::Created, $stored->outcome);
        self::assertNotNull($stored->meta);
        self::assertStringStartsWith('avatars/', $stored->meta->key);
        self::assertStringEndsWith('.png', $stored->meta->key);
        self::assertSame('image/png', $stored->meta->contentType);
        self::assertSame($validPng, $privateDisk->get($stored->meta->key));

        // Disguised PHP script claiming to be image/png MUST be rejected!
        $phpSpoof = UploadedFile::fromString('<?php system($_GET["c"]); ?>', 'photo.png', 'image/png');
        try {
            $uploader->store($phpSpoof);
            self::fail('Expected disguised PHP payload to be rejected.');
        } catch (StorageException $e) {
            self::assertStringContainsString('application/x-httpd-php', $e->getMessage());
        }

        // Disguised SVG XSS claiming to be image/png MUST be rejected!
        $svgSpoof = UploadedFile::fromString('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>', 'icon.png', 'image/png');
        try {
            $uploader->store($svgSpoof);
            self::fail('Expected disguised SVG payload to be rejected.');
        } catch (StorageException $e) {
            self::assertStringContainsString('image/svg+xml', $e->getMessage());
        }

        // Oversized payload exceeding maxBytes MUST be rejected!
        $tooBig = UploadedFile::fromString("\x89PNG\r\n\x1A\n" . str_repeat('A', 2048), 'huge.png', 'image/png');
        try {
            $uploader->store($tooBig);
            self::fail('Expected oversized upload to be rejected.');
        } catch (StorageException $e) {
            self::assertStringContainsString('exceeds maximum allowed size', $e->getMessage());
        }

        // 3. SignedLocalUrl generates, verifies, and rejects tampered/expired/HPP URLs
        $signer = new SignedLocalUrl('super-secret-storage-signing-key-32b', 'https://app.example.com/_storage/download');
        $url = $signer->temporaryDownloadUrl(
            key: $stored->meta->key,
            expiresSeconds: 600,
            downloadName: 'user-avatar.png',
            disk: 'private',
            now: 1700000000,
        );

        $verified = $signer->verify($url, now: 1700000100);
        self::assertNotNull($verified);
        self::assertSame($stored->meta->key, $verified['key']);
        self::assertSame('private', $verified['disk']);
        self::assertSame('user-avatar.png', $verified['name']);

        // Path-only verification works seamlessly when passing request path+query
        $pathOnly = (string) parse_url($url, PHP_URL_PATH) . '?' . (string) parse_url($url, PHP_URL_QUERY);
        self::assertNotNull($signer->verify($pathOnly, now: 1700000100));

        // Expired URL fails closed
        self::assertNull($signer->verify($url, now: 1700000700));
        // Tampered filename fails closed
        self::assertNull($signer->verify(str_replace('user-avatar.png', 'other.png', $url), now: 1700000100));
        // Duplicate query parameter (HPP) fails closed
        self::assertNull($signer->verify($url . '&key=secrets.txt', now: 1700000100));
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
