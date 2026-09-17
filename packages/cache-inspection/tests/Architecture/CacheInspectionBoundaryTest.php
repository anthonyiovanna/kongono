<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CacheInspectionBoundaryTest extends TestCase
{
    public function testDependenciesAreExactAndNoLowerPackageConsumesTheLeaf(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/cache-inspection/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/cache-application' => '*',
            'kongono/cache-build' => '*',
            'kongono/cache-contract' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            if (str_replace('\\', '/', $path) === str_replace('\\', '/', $root . '/packages/cache-inspection/composer.json')) {
                continue;
            }
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertArrayNotHasKey('kongono/cache-inspection', is_array($candidate['require'] ?? null) ? $candidate['require'] : []);
        }
    }

    public function testProductionContainsNoForbiddenIoRuntimeMutationOrRoleDependency(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'realpath(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'CacheApplicationWorkflow', 'CacheApplicationRuntimeFactory', 'Kongono\\CacheRuntime\\', 'Kongono\\CacheAdapter', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Command', 'Controller', 'Worker', 'Watcher', 'Daemon'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertDoesNotMatchRegularExpression('/\\btime\\s*\\(/', $source);
        self::assertStringContainsString('new CacheProfileSet(', $source);
        self::assertStringContainsString('validateStructure($suppliedArtifact->bytes())', $source);
        self::assertStringNotContainsString('CacheIntelligenceDeriver', (string) file_get_contents(dirname(__DIR__, 2) . '/src/Verification/CacheIntelligenceVerifier.php'));
    }

    public function testPackageFileInventoryIsExact(): void
    {
        $root = dirname(__DIR__, 2);
        $expected = [
            'README.md',
            'composer.json',
            'src/Derivation/CacheIntelligenceDeriver.php',
            'src/Derivation/CacheIntelligenceView.php',
            'src/Diagnostic/CacheInspectionDiagnostic.php',
            'src/Exception/CacheInspectionBindingException.php',
            'src/Exception/CacheInspectionException.php',
            'src/Exception/CacheInspectionProjectionException.php',
            'src/Exception/CacheInspectionVerificationException.php',
            'src/Exception/ExceptionInterface.php',
            'src/Exception/InvalidCacheInspectionException.php',
            'src/Exception/InvalidCacheIntelligenceEvidenceException.php',
            'src/Graph/CacheGraphEvidence.php',
            'src/Graph/CacheGraphEvidenceProjector.php',
            'src/Inspection/CacheInspection.php',
            'src/Inspection/CacheInspectionRequest.php',
            'src/Inspection/CacheInspector.php',
            'src/Internal/CanonicalJson.php',
            'src/Projection/CacheObservationProjector.php',
            'src/Projection/CacheSecurityAuditProjector.php',
            'src/Role/CacheInspectionDeveloperRole.php',
            'src/RuntimeEvidence/DetachedCacheRuntimeEvidence.php',
            'src/Verification/CacheIntelligenceVerdict.php',
            'src/Verification/CacheIntelligenceVerification.php',
            'src/Verification/CacheIntelligenceVerifier.php',
            'tests/Architecture/CacheInspectionBoundaryTest.php',
            'tests/Security/CacheInspectionDisclosureTest.php',
            'tests/Support/CacheInspectionFactory.php',
            'tests/Unit/Derivation/CacheIntelligenceTest.php',
            'tests/Unit/Inspection/CacheInspectorTest.php',
            'tests/Unit/Projection/CacheProjectionVerificationTest.php',
        ];
        $observed = array_map(static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1)), $this->allFiles($root));
        self::assertSame($expected, $observed);
        self::assertCount(23, $this->phpFiles($root . '/src'));
        self::assertCount(6, $this->phpFiles($root . '/tests'));
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
