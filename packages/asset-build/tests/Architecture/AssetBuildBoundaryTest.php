<?php

declare(strict_types=1);

namespace Kongono\AssetBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class AssetBuildBoundaryTest extends TestCase
{
    public function testPackageInventoryDependenciesAndStatusesAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame(['php' => '>=8.5.0 <8.6.0', 'kongono/api-metadata' => '*', 'kongono/diagnostics' => '*', 'kongono/asset-contract' => '*'], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $production = self::phpFiles($package . '/src');
        $tests = self::phpFiles($package . '/tests');
        self::assertCount(30, $production);
        self::assertCount(17, $tests);
        $statuses = array_fill_keys(array_map(static fn(ApiStatus $status): string => $status->value, ApiStatus::cases()), 0);
        foreach ($production as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\AssetBuild\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $attributes = (new \ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            ++$statuses[$attributes[0]->newInstance()->status->value];
        }
        self::assertSame(22, $statuses[ApiStatus::Experimental->value]);
        self::assertSame(1, $statuses[ApiStatus::Public->value]);
        self::assertSame(7, $statuses[ApiStatus::Internal->value]);

        foreach ($production as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\AssetBuild\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            $reflection = self::reflection($class);
            if (!$reflection->isInterface() && 'Kongono\\AssetBuild\\Exception\\AssetBuildException' !== $class) {
                self::assertTrue($reflection->isFinal(), $class);
            }
            self::assertSame([], array_filter($reflection->getProperties(), static fn(\ReflectionProperty $property): bool => $property->isStatic()), $class);
        }
    }

    public function testOnlyAuthorizedProductionPackagesDependOnAssetBuild(): void
    {
        $packages = dirname(__DIR__, 3);
        $repository = dirname($packages);
        $authorizedConsumers = [
            'packages/asset-adapter-contract/composer.json',
            'packages/asset-adapter-vite/composer.json',
            'packages/asset-application/composer.json',
            'packages/asset-resolution/composer.json',
        ];
        $observedConsumers = [];
        $manifestPaths = glob($packages . '/*/composer.json');
        foreach (false === $manifestPaths ? [] : $manifestPaths as $manifestPath) {
            if (str_replace('\\', '/', $manifestPath) === str_replace('\\', '/', dirname(__DIR__, 2) . '/composer.json')) {
                continue;
            }
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            $requirements = $manifest['require'] ?? null;
            self::assertIsArray($requirements);
            if (!array_key_exists('kongono/asset-build', $requirements)) {
                continue;
            }

            $normalizedManifestPath = str_replace('\\', '/', substr($manifestPath, strlen($repository) + 1));
            self::assertTrue(in_array($normalizedManifestPath, $authorizedConsumers, true), $normalizedManifestPath);
            self::assertSame('*', $requirements['kongono/asset-build']);
            $observedConsumers[] = $normalizedManifestPath;
        }

        sort($observedConsumers, SORT_STRING);
        self::assertSame($authorizedConsumers, $observedConsumers);
    }

    public function testProductionHasNoAmbientOrUnauthorizedEffectSurface(): void
    {
        $package = dirname(__DIR__, 2);
        $all = '';
        $outsidePublisher = '';
        foreach (self::phpFiles($package . '/src') as $path) {
            $source = (string) file_get_contents($path);
            $all .= $source;
            if (!str_ends_with(str_replace('\\', '/', $path), '/Artifact/AssetBuildArtifactPublisher.php') && !str_ends_with(str_replace('\\', '/', $path), '/Internal/PathGuard.php')) {
                $outsidePublisher .= $source;
            }
        }
        foreach (['proc_open', 'shell_exec', 'passthru', 'curl_', 'getenv(', '$_ENV', '$_SERVER', 'eval(', 'Psr\\Http', 'Kongono\\View\\', 'Kongono\\ApplicationGraph\\'] as $denied) {
            self::assertStringNotContainsString($denied, $all, $denied);
        }
        foreach (['fopen(', 'file_get_contents(', 'scandir(', 'realpath(', 'random_bytes('] as $filesystemOrRandom) {
            self::assertStringNotContainsString($filesystemOrRandom, $outsidePublisher, $filesystemOrRandom);
        }
        $publisher = (string) file_get_contents($package . '/src/Artifact/AssetBuildArtifactPublisher.php');
        foreach (['fopen($path, \'xb\')', 'fwrite(', 'fflush(', 'fsync(', 'fstat(', 'rename(', 'acceptExisting(', 'cleanup('] as $publicationProof) {
            self::assertStringContainsString($publicationProof, $publisher, $publicationProof);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $paths = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            if ($entry->isFile() && 'php' === $entry->getExtension()) {
                $paths[] = $entry->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }

    /** @return \ReflectionClass<object> */
    private static function reflection(string $class): \ReflectionClass
    {
        if (!class_exists($class) && !interface_exists($class) && !enum_exists($class)) {
            throw new \LogicException('Expected production declaration was not loadable.');
        }

        return new \ReflectionClass($class);
    }
}
