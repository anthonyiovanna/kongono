<?php

declare(strict_types=1);

namespace Kongono\StorageRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

#[CoversNothing]
final class StorageRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoryDependenciesAndStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('require', $manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame(['php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/storage-adapter-contract' => '*', 'kongono/storage-build' => '*', 'kongono/storage-contract' => '*'], $manifest['require']);
        self::assertArrayNotHasKey('kongono/storage-adapter-local', $manifest['require']);
        $production = self::phpFiles($package . '/src');
        self::assertCount(17, $production);
        self::assertCount(17, self::phpFiles($package . '/tests'));
        $statuses = [];
        foreach ($production as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\StorageRuntime\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $statuses[] = $attributes[0]->newInstance()->status;
        }
        self::assertSame(8, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(8, count(array_keys($statuses, ApiStatus::Internal, true)));
        self::assertSame(1, count(array_keys($statuses, ApiStatus::Public, true)));
        foreach ($production as $file) {
            $source = (string) file_get_contents($file);
            self::assertStringNotContainsString('StorageAdapterLocal', $source);
            self::assertStringNotContainsString('__destruct', $source);
            self::assertStringNotContainsString('sleep(', $source);
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
