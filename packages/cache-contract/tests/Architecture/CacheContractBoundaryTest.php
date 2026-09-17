<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Diagnostic\CacheContractDiagnostic;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

final class CacheContractBoundaryTest extends TestCase
{
    public function testPackageDependenciesInventoryAndApiStatusesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/cache-contract', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $paths = $this->phpFiles($package . '/src');
        self::assertCount(21, $paths);
        $statuses = [];
        foreach ($paths as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\CacheContract\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $statuses[] = $attributes[0]->newInstance()->status;
        }
        self::assertSame(17, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(1, count(array_keys($statuses, ApiStatus::Public, true)));
        self::assertSame(3, count(array_keys($statuses, ApiStatus::Internal, true)));
        self::assertCount(14, $this->phpFiles($package . '/tests'));
    }

    public function testProductionSurfaceHasNoAmbientAuthorityOrLaterCapability(): void
    {
        $package = dirname(__DIR__, 2);
        foreach ($this->phpFiles($package . '/src') as $path) {
            $source = (string) file_get_contents($path);
            foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'glob(', 'scandir(', 'getenv(', '$_ENV', '$_SERVER', 'ReflectionClass', 'curl_', 'json_', 'serialize(', 'unserialize(', 'random_', 'exec(', 'proc_open(', 'shell_exec(', 'eval(', 'DateTime', 'callback', 'Backend', 'Adapter', 'CacheRuntime', 'ApplicationService'] as $prohibited) {
                self::assertStringNotContainsString($prohibited, $source, $path . ':' . $prohibited);
            }
            self::assertSame(0, preg_match('/(?<![A-Za-z0-9_])(?:time|microtime)\s*\(/', $source), $path . ':clock');
            self::assertStringNotContainsString('static $', $source, $path);
            self::assertStringNotContainsString('__destruct', $source, $path);
        }
        self::assertSame(array_map(static fn(int $number): string => 'KGN-CACHE-CONTRACT-' . $number, range(2001, 2006)), array_column(CacheContractDiagnostic::cases(), 'value'));
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
