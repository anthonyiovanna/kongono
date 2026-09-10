<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ExternalServiceInspectionBoundaryTest extends TestCase
{
    public function testManifestDependenciesAreExactAndLeafOnly(): void
    {
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/diagnostics' => '*',
            'kongono/external-service-application' => '*',
            'kongono/external-service-binding-build' => '*',
            'kongono/external-service-contract' => '*',
            'kongono/external-service-credential-runtime' => '*',
            'kongono/network-egress-application' => '*',
            'kongono/network-egress-contract' => '*',
            'kongono/network-egress-policy-build' => '*',
            'kongono/network-egress-runtime' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        foreach (['kongono/external-service-resolver-system', 'kongono/network-egress-curl', 'kongono/network-egress-inspection'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $manifest['require']);
        }
    }

    public function testProductionContainsNoExecutionIoOrRoleImplementation(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['new ExternalServiceApplication(', 'new CredentialLeaseRuntime(', 'new NetworkEgressRuntime(', '->run(', '->acquire(', '->withMaterial(', '->retire(', '->resolve(', '->open(', '->request()', 'require(', 'require_once(', 'include(', 'include_once(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Retry', 'Fallback', 'Registry', 'Worker', 'Scheduler', 'Command', 'Controller'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertDoesNotMatchRegularExpression('/(?<![A-Za-z0-9_])time\s*\(/', $source);
        self::assertSame(2, substr_count($source, 'ExternalServiceApplicationProfileFactory'));
        self::assertSame(1, substr_count($source, 'new ExternalServiceApplicationProfileFactory'));
    }

    public function testPackageContainsOnlyAuthorizedFilesAndDeclarations(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertCount(22, $this->phpFiles($root . '/src'));
        self::assertCount(6, $this->phpFiles($root . '/tests'));
        foreach ($this->allFiles($root) as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
            self::assertTrue(in_array($relative, ['composer.json', 'README.md'], true) || 1 === preg_match('#\A(?:src|tests)/.+\.php\z#D', $relative), $relative);
        }
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles($root . '/src')));
        self::assertSame(22, preg_match_all('/#\[Api\(ApiStatus::(?:Internal|Experimental)\)\]/', $source));
        self::assertSame(3, substr_count($source, '#[Api(ApiStatus::Internal)]'));
        self::assertSame(19, substr_count($source, '#[Api(ApiStatus::Experimental)]'));
    }

    /** @return list<string> */
    private function phpFiles(string $root): array
    {
        return array_values(array_filter($this->allFiles($root), static fn(string $path): bool => str_ends_with($path, '.php')));
    }

    /** @return list<string> */
    private function allFiles(string $root): array
    {
        $paths = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths, SORT_STRING);

        return $paths;
    }
}
