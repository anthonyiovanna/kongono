<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ExternalServiceApplicationBoundaryTest extends TestCase
{
    public function testExactFilesDependenciesApiStatusesAndTopology(): void
    {
        $root = dirname(__DIR__, 4);
        $package = $root . '/packages/external-service-application';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/external-service-application',
            'description' => 'Exact-current one-operation external-service composition for Kongono.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/external-service-binding-build' => '*',
                'kongono/external-service-contract' => '*',
                'kongono/external-service-credential-runtime' => '*',
                'kongono/network-egress-application' => '*',
                'kongono/network-egress-contract' => '*',
                'kongono/network-egress-policy-build' => '*',
                'kongono/network-egress-runtime' => '*',
                'psr/http-message' => '^2.0',
            ],
            'require-dev' => [
                'kongono/http-message' => '*',
                'phpunit/phpunit' => '^13.0',
            ],
            'autoload' => ['psr-4' => ['Kongono\\ExternalServiceApplication\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExternalServiceApplication\\Tests\\' => 'tests/']],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ], $manifest);

        $sourceFiles = self::phpFiles($package . '/src');
        $testFiles = self::phpFiles($package . '/tests');
        self::assertCount(20, $sourceFiles);
        self::assertCount(18, $testFiles);

        $statusCounts = ['public' => 0, 'internal' => 0, 'experimental' => 0];
        foreach ($sourceFiles as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\ExternalServiceApplication\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $status = 'Kongono\\ExternalServiceApplication\\Exception\\ExceptionInterface' === $class
                ? ApiStatus::Public
                : (str_starts_with($class, 'Kongono\\ExternalServiceApplication\\Internal\\') ? ApiStatus::Internal : ApiStatus::Experimental);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($status, $attributes[0]->newInstance()->status, $class);
            ++$statusCounts[$status->value];
        }
        self::assertSame(['public' => 1, 'internal' => 6, 'experimental' => 13], $statusCounts);

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(87, $manifests);
        $names = [];
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsString($candidate['name']);
            $names[$candidate['name']] = true;
        }
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach (array_keys($candidate['require']) as $dependency) {
                if (isset($names[$dependency])) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/external-service-application', 'kongono/external-service-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/external-service-application', $candidate['require']);
            }
        }
        self::assertSame(355, $edges);

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        self::assertIsArray($rootManifest['require']);
        self::assertIsArray($rootManifest['autoload-dev']);
        self::assertIsArray($rootManifest['autoload-dev']['psr-4']);
        self::assertIsArray($rootManifest['scripts']);
        self::assertIsArray($rootManifest['scripts']['validate:packages']);
        self::assertSame('@dev', $rootManifest['require']['kongono/external-service-application'] ?? null);
        self::assertSame('packages/external-service-application/tests/', $rootManifest['autoload-dev']['psr-4']['Kongono\\ExternalServiceApplication\\Tests\\'] ?? null);
        $validation = 'composer --working-dir=packages/external-service-application validate --no-check-publish';
        self::assertSame(1, count(array_keys($rootManifest['scripts']['validate:packages'], $validation, true)));
        self::assertCount(87, $rootManifest['scripts']['validate:packages']);
    }

    public function testProductionContainsNoAmbientProviderDiscoveryOrSecretSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'dns_', 'gethostbyname(', 'fsockopen(', 'stream_socket_', 'socket_', 'proc_open(', 'shell_exec(', 'sleep(', 'usleep(', 'serialize(', 'json_encode(', 'JsonSerializable', '__serialize(', '__unserialize(', '__debugInfo(', '__clone(', '__destruct('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
