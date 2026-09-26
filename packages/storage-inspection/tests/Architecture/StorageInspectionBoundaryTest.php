<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class StorageInspectionBoundaryTest extends TestCase
{
    public function testDependenciesAreExactAndNoLowerPackageConsumesTheLeaf(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/storage-inspection/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/storage-application' => '*',
            'kongono/storage-build' => '*',
            'kongono/storage-contract' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            if (str_replace('\\', '/', $path) === str_replace('\\', '/', $root . '/packages/storage-inspection/composer.json')) {
                continue;
            }
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertArrayNotHasKey('kongono/storage-inspection', is_array($candidate['require'] ?? null) ? $candidate['require'] : []);
        }
    }

    public function testProductionContainsNoForbiddenIoRuntimeMutationOrRoleDependency(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'realpath(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'StorageApplicationWorkflow', 'StorageApplicationRuntimeFactory', 'Kongono\\StorageRuntime\\', 'Kongono\\StorageAdapter', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Command', 'Controller', 'Worker', 'Watcher', 'Daemon'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertDoesNotMatchRegularExpression('/\\btime\\s*\\(/', $source);
        self::assertStringContainsString('new StorageProfileSet(', $source);
        self::assertStringContainsString('validateStructure($suppliedArtifact->bytes())', $source);
        self::assertStringNotContainsString('StorageIntelligenceDeriver', (string) file_get_contents(dirname(__DIR__, 2) . '/src/Verification/StorageIntelligenceVerifier.php'));
    }

    public function testPackageFileInventoryIsExact(): void
    {
        $root = dirname(__DIR__, 2);
        $expected = [
            'README.md',
            'composer.json',
            'src/Derivation/StorageIntelligenceDeriver.php',
            'src/Derivation/StorageIntelligenceView.php',
            'src/Diagnostic/StorageInspectionDiagnostic.php',
            'src/Exception/ExceptionInterface.php',
            'src/Exception/InvalidStorageInspectionException.php',
            'src/Exception/InvalidStorageIntelligenceEvidenceException.php',
            'src/Exception/StorageInspectionBindingException.php',
            'src/Exception/StorageInspectionException.php',
            'src/Exception/StorageInspectionProjectionException.php',
            'src/Exception/StorageInspectionVerificationException.php',
            'src/Graph/StorageGraphEvidence.php',
            'src/Graph/StorageGraphEvidenceProjector.php',
            'src/Inspection/StorageInspection.php',
            'src/Inspection/StorageInspectionRequest.php',
            'src/Inspection/StorageInspector.php',
            'src/Internal/CanonicalJson.php',
            'src/Projection/StorageObservationProjector.php',
            'src/Projection/StorageSecurityAuditProjector.php',
            'src/Role/StorageInspectionDeveloperRole.php',
            'src/RuntimeEvidence/DetachedStorageRuntimeEvidence.php',
            'src/Verification/StorageIntelligenceVerdict.php',
            'src/Verification/StorageIntelligenceVerification.php',
            'src/Verification/StorageIntelligenceVerifier.php',
            'tests/Architecture/StorageInspectionBoundaryTest.php',
            'tests/Security/StorageInspectionDisclosureTest.php',
            'tests/Support/StorageInspectionFactory.php',
            'tests/Unit/Derivation/StorageIntelligenceTest.php',
            'tests/Unit/Inspection/StorageInspectorTest.php',
            'tests/Unit/Projection/StorageProjectionVerificationTest.php',
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
