<?php

declare(strict_types=1);

namespace CacheMinimalApp\Tests;

use CacheMinimalApp\ApplicationJsonCodec;
use CacheMinimalApp\CacheDemo;
use CacheMinimalApp\CacheWorkflow;
use JsonException;
use Kongono\CacheContract\Value\CacheValueBytes;
use PHPUnit\Framework\TestCase;

final class CacheMinimalApplicationTest extends TestCase
{
    public function testOwnershipInventoryWorkflowCodecAndLossBehavior(): void
    {
        $root = dirname(__DIR__);
        $manifest = json_decode((string) file_get_contents($root . '/kongono.skeleton.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || !isset($manifest['template_paths']) || !is_array($manifest['template_paths'])) {
            self::fail('Ownership metadata must contain paths.');
        }
        $paths = [];
        foreach ($manifest['template_paths'] as $path) {
            self::assertIsString($path);
            $paths[] = $path;
        }
        $sorted = $paths;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $paths);
        foreach ($paths as $path) {
            self::assertFileExists($root . '/' . $path);
        }

        $codec = new ApplicationJsonCodec();
        self::assertSame('application-json@1', $codec->identity()->canonical());
        self::assertSame(['message' => 'hello'], $codec->decode($codec->encode(['message' => 'hello'])));
        foreach (['{}', '{"message":""}', '{"message":"hello","extra":true}', '{'] as $invalid) {
            try {
                $codec->decode(new CacheValueBytes($invalid));
                self::fail('Expected malformed application-owned bytes to fail.');
            } catch (JsonException) {
                self::addToAssertionCount(1);
            }
        }
        $copy = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-minimal-test-' . bin2hex(random_bytes(8));
        mkdir($copy . '/var/cache', 0777, true);
        copy($root . '/kongono.cache.json', $copy . '/kongono.cache.json');
        try {
            $plan = (new CacheWorkflow($copy))->plan();
            self::assertSame(1, $plan->profileCount());
            self::assertSame([], array_values(array_diff((array) scandir($copy . '/var/cache'), ['.', '..'])));
            $result = (new CacheDemo())->run($copy);
            self::assertSame('available', $result['application_truth']);
            self::assertSame('miss', $result['loss']);
        } finally {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($copy, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $entry) {
                if (!$entry instanceof \SplFileInfo) {
                    self::fail('Copied inventory must contain file information.');
                }
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }
            rmdir($copy);
        }
    }
}
