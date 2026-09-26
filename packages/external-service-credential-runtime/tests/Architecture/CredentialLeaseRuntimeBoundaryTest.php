<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CredentialLeaseRuntimeBoundaryTest extends TestCase
{
    public function testExactFilesDeclarationsApiStatusesDependenciesAndRootIntegration(): void
    {
        $root = dirname(__DIR__, 4);
        $package = $root . '/packages/external-service-credential-runtime';
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([
            'name' => 'kongono/external-service-credential-runtime',
            'description' => 'Operation-scoped credential lease coordination for Kongono external services.',
            'type' => 'library',
            'require' => [
                'php' => '>=8.5.0 <8.6.0',
                'kongono/api-metadata' => '*',
                'kongono/diagnostics' => '*',
                'kongono/external-service-contract' => '*',
            ],
            'require-dev' => ['phpunit/phpunit' => '^13.0'],
            'autoload' => ['psr-4' => ['Kongono\\ExternalServiceCredentialRuntime\\' => 'src/']],
            'autoload-dev' => ['psr-4' => ['Kongono\\ExternalServiceCredentialRuntime\\Tests\\' => 'tests/']],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ], $manifest);
        $sourceFiles = self::phpFiles($package . '/src');
        $testFiles = self::phpFiles($package . '/tests');
        self::assertCount(20, $sourceFiles);
        self::assertCount(16, $testFiles);

        $internal = [
            'Kongono\\ExternalServiceCredentialRuntime\\Diagnostic\\CredentialLeaseRuntimeDiagnostic',
            'Kongono\\ExternalServiceCredentialRuntime\\Exception\\ExternalServiceCredentialRuntimeException',
            'Kongono\\ExternalServiceCredentialRuntime\\Internal\\CredentialLeaseGuard',
            'Kongono\\ExternalServiceCredentialRuntime\\Internal\\LeaseBudgetMeter',
            'Kongono\\ExternalServiceCredentialRuntime\\Internal\\LeaseLifecycle',
            'Kongono\\ExternalServiceCredentialRuntime\\Internal\\LifecycleFailureComposer',
            'Kongono\\ExternalServiceCredentialRuntime\\Internal\\ResultFactory',
        ];
        foreach ($sourceFiles as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($package . '/src') + 1));
            $class = 'Kongono\\ExternalServiceCredentialRuntime\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            self::assertTrue(class_exists($class) || interface_exists($class) || enum_exists($class), $class);
            $status = 'Kongono\\ExternalServiceCredentialRuntime\\Exception\\ExceptionInterface' === $class
                ? ApiStatus::Public
                : (in_array($class, $internal, true) ? ApiStatus::Internal : ApiStatus::Experimental);
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes, $class);
            self::assertSame($status, $attributes[0]->newInstance()->status, $class);
        }

        $rootManifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($rootManifest);
        $rootRequire = $rootManifest['require'] ?? null;
        $rootAutoloadDev = $rootManifest['autoload-dev'] ?? null;
        $rootScripts = $rootManifest['scripts'] ?? null;
        self::assertIsArray($rootRequire);
        self::assertIsArray($rootAutoloadDev);
        self::assertIsArray($rootScripts);
        $rootAutoloadDevPsr4 = $rootAutoloadDev['psr-4'] ?? null;
        $validatePackages = $rootScripts['validate:packages'] ?? null;
        self::assertIsArray($rootAutoloadDevPsr4);
        self::assertIsArray($validatePackages);
        self::assertSame('@dev', $rootRequire['kongono/external-service-credential-runtime'] ?? null);
        self::assertSame('packages/external-service-credential-runtime/tests/', $rootAutoloadDevPsr4['Kongono\\ExternalServiceCredentialRuntime\\Tests\\'] ?? null);
        $validation = 'composer --working-dir=packages/external-service-credential-runtime validate --no-check-publish';
        self::assertSame(1, count(array_keys($validatePackages, $validation, true)));
        self::assertCount(116, $validatePackages);

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(116, $manifests);
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
            if (!in_array($candidate['name'], ['kongono/external-service-application', 'kongono/external-service-credential-runtime', 'kongono/external-service-inspection', 'kongono/model-provider-openai-responses'], true)) {
                self::assertArrayNotHasKey('kongono/external-service-credential-runtime', $candidate['require']);
            }
        }
        self::assertSame(501, $edges);
        self::assertSame(['kongono/api-metadata', 'kongono/diagnostics', 'kongono/external-service-contract'], array_values(array_filter(array_keys($manifest['require']), static fn(string $name): bool => str_starts_with($name, 'kongono/'))));

        foreach ([
            'phpstan.neon.dist' => ['packages/external-service-credential-runtime/src', 'packages/external-service-credential-runtime/tests'],
            '.php-cs-fixer.dist.php' => ['packages/external-service-credential-runtime/src', 'packages/external-service-credential-runtime/tests'],
            'phpunit.xml.dist' => ['packages/external-service-credential-runtime/src', 'packages/external-service-credential-runtime/tests'],
        ] as $toolingPath => $needles) {
            $tooling = (string) file_get_contents($root . '/' . $toolingPath);
            foreach ($needles as $needle) {
                self::assertSame(1, substr_count($tooling, $needle), $toolingPath . ':' . $needle);
            }
        }
    }

    public function testProductionContainsNoForbiddenAmbientEffectOrProjectionSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'dns_', 'gethostbyname(', 'fsockopen(', 'stream_socket_', 'socket_', 'proc_open(', 'shell_exec(', 'serialize(', 'json_encode(', 'JsonSerializable', 'Stringable', '__serialize(', '__unserialize(', '__debugInfo(', '__clone(', '__destruct('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
