<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class CacheBuildBoundaryTest extends TestCase
{
    public function testPackageInventoryDependenciesAndStatusesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/cache-contract' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $production = self::phpFiles($package . '/src');
        self::assertCount(24, $production);
        self::assertCount(15, self::phpFiles($package . '/tests'));
        $statuses = [];
        foreach ($production as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\CacheBuild\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $statuses[] = $attributes[0]->newInstance()->status;
            if (!$reflection->isInterface() && 'Kongono\\CacheBuild\\Exception\\CacheBuildException' !== $class) {
                self::assertTrue($reflection->isFinal(), $class);
            }
            self::assertSame([], array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $property->isStatic()), $class);
        }
        self::assertSame(17, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(1, count(array_keys($statuses, ApiStatus::Public, true)));
        self::assertSame(6, count(array_keys($statuses, ApiStatus::Internal, true)));
    }

    public function testOnlyCacheRuntimeDependsOnCacheBuild(): void
    {
        $packages = dirname(__DIR__, 3);
        $manifestPaths = glob($packages . '/*/composer.json');
        $consumers = [];
        foreach (false === $manifestPaths ? [] : $manifestPaths as $manifestPath) {
            if (str_replace('\\', '/', $manifestPath) === str_replace('\\', '/', dirname(__DIR__, 2) . '/composer.json')) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            $requirements = $manifest['require'] ?? null;
            self::assertIsArray($requirements);
            if (array_key_exists('kongono/cache-build', $requirements)) {
                $consumers[] = basename(dirname($manifestPath));
            }
        }
        sort($consumers, SORT_STRING);
        self::assertSame(['cache-application', 'cache-inspection', 'cache-runtime'], $consumers);
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
