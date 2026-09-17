<?php

declare(strict_types=1);

namespace Kongono\CacheAdapterContract\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

#[CoversNothing]
final class CacheAdapterContractBoundaryTest extends TestCase
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
        ], $manifest['require']);
        $files = $this->phpFiles($package . '/src');
        self::assertCount(18, $files);
        $statuses = [];
        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file, strlen($package . '/src') + 1));
            $class = 'Kongono\\CacheAdapterContract\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
                self::fail(sprintf('Declaration %s is not loadable.', $class));
            }
            /** @var class-string $class */
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            $statuses[] = $attributes[0]->newInstance()->status;
        }
        self::assertSame(15, count(array_keys($statuses, ApiStatus::Experimental, true)));
        self::assertSame(3, count(array_keys($statuses, ApiStatus::Internal, true)));
        self::assertCount(13, $this->phpFiles($package . '/tests'));
    }

    public function testProductionSurfaceHasNoAmbientOrLaterCapability(): void
    {
        foreach ($this->phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $source = (string) file_get_contents($file);
            foreach (['serialize(', 'unserialize(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'getenv(', 'random_', 'ReflectionClass', 'CacheRuntime', 'Redis', 'Memcached', 'Psr\\Cache'] as $token) {
                self::assertStringNotContainsString($token, $source, $file . ':' . $token);
            }
            self::assertSame(0, preg_match('/(?<![A-Za-z0-9_])(?:time|microtime)\s*\(/', $source), $file);
        }
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
