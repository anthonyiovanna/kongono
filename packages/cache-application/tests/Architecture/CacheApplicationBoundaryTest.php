<?php

declare(strict_types=1);

namespace Kongono\CacheApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

#[CoversNothing]
final class CacheApplicationBoundaryTest extends TestCase
{
    public function testDependenciesInventoryAndApiStatusesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            self::fail('Package manifest must decode to an object.');
        }
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/cache-contract' => '*',
            'kongono/cache-build' => '*', 'kongono/cache-adapter-contract' => '*', 'kongono/cache-runtime' => '*',
        ], $manifest['require']);
        self::assertSame(['kongono/cache-adapter-memory' => '*', 'phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $files = self::phpFiles($package . '/src');
        self::assertCount(20, $files);
        foreach ($files as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\CacheApplication\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            self::assertContains($attributes[0]->newInstance()->status, [ApiStatus::Public, ApiStatus::Experimental, ApiStatus::Internal]);
        }
    }

    public function testNoForbiddenAmbientOrCodecExecutionSurface(): void
    {
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $source = (string) file_get_contents($file);
            foreach (['serialize(', 'unserialize(', 'Reflection', 'getenv(', 'curl_', 'Redis', 'Memcached', 'BoundedMemoryCacheAdapter', 'sleep(', 'glob('] as $token) {
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
