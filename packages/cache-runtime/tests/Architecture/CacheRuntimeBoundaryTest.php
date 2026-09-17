<?php

declare(strict_types=1);

namespace Kongono\CacheRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

#[CoversNothing]
final class CacheRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoryDependenciesAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/cache-contract' => '*',
            'kongono/cache-build' => '*',
            'kongono/cache-adapter-contract' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        self::assertArrayNotHasKey('kongono/cache-adapter-memory', $manifest['require']);
        $production = self::phpFiles($package . '/src');
        self::assertCount(16, $production);
        self::assertCount(15, self::phpFiles($package . '/tests'));
        $statuses = [];
        foreach ($production as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\CacheRuntime\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $statuses[] = $attributes[0]->newInstance()->status;
            if (!$reflection->isInterface() && 'Kongono\\CacheRuntime\\Exception\\CacheRuntimeException' !== $class) {
                self::assertTrue($reflection->isFinal(), $class);
            }
        }
        self::assertSame(11, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(1, count(array_keys($statuses, ApiStatus::Public, true)));
        self::assertSame(4, count(array_keys($statuses, ApiStatus::Internal, true)));
    }

    public function testProductionSurfaceHasNoAmbientOrLaterCapability(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $source = (string) file_get_contents($file);
            foreach (['serialize(', 'unserialize(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'getenv(', 'random_', 'ReflectionClass', 'BoundedMemoryCacheAdapter', 'Redis', 'Memcached', 'Psr\\Cache', 'Fiber', 'sleep('] as $token) {
                self::assertStringNotContainsString($token, $source, $file . ':' . $token);
            }
            self::assertSame(0, preg_match('/(?<![A-Za-z0-9_])(?:time|microtime)\s*\(/', $source), $file);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && 'php' === $entry->getExtension()) {
                $files[] = $entry->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
