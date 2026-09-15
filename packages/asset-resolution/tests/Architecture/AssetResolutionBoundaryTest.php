<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;

final class AssetResolutionBoundaryTest extends TestCase
{
    public function testManifestDependenciesInventoryAndApiStatusesAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/asset-resolution/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $require = $manifest['require'] ?? null;
        $requireDev = $manifest['require-dev'] ?? null;
        self::assertIsArray($require);
        self::assertIsArray($requireDev);
        self::assertSame(['php', 'kongono/api-metadata', 'kongono/diagnostics', 'kongono/asset-contract', 'kongono/asset-build', 'kongono/asset-adapter-contract'], array_keys($require));
        self::assertSame(['phpunit/phpunit' => '^13.0'], $requireDev);
        self::assertCount(24, self::phpFiles($root . '/packages/asset-resolution/src'));
        self::assertCount(16, self::phpFiles($root . '/packages/asset-resolution/tests'));

        $statuses = [ApiStatus::Experimental->value => 0, ApiStatus::Public->value => 0, ApiStatus::Internal->value => 0, 'stable' => 0];
        foreach (self::declarations($root . '/packages/asset-resolution/src') as $class) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $status = $attributes[0]->newInstance()->status->value;
            self::assertArrayHasKey($status, $statuses);
            ++$statuses[$status];
        }
        self::assertSame([ApiStatus::Experimental->value => 20, ApiStatus::Public->value => 1, ApiStatus::Internal->value => 3, 'stable' => 0], $statuses);
    }

    public function testProductionHasNoEffectOrAmbientStateApi(): void
    {
        $root = dirname(__DIR__, 2) . '/src';
        $source = implode("\n", array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles($root)));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'proc_open(', 'shell_exec(', 'getenv(', 'putenv(', 'time(', 'microtime(', 'random_', 'Reflection', 'header(', 'setcookie(', 'serialize(', '__wakeup', '__sleep', '__destruct', 'register_shutdown_function'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        /** @var list<string> $files */
        $files = [];
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @return list<class-string> */
    private static function declarations(string $directory): array
    {
        $classes = [];
        foreach (self::phpFiles($directory) as $path) {
            $relative = substr($path, strlen($directory) + 1, -4);
            $class = 'Kongono\\AssetResolution\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                self::fail('Missing declaration for ' . $path);
            }
            $classes[] = $class;
        }

        return $classes;
    }
}
