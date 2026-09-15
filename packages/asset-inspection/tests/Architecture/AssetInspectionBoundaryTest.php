<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class AssetInspectionBoundaryTest extends TestCase
{
    public function testDependenciesAreExactAndNoLowerPackageConsumesTheLeaf(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/asset-inspection/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/asset-application' => '*',
            'kongono/asset-resolution' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        foreach ($manifests as $path) {
            if (str_replace('\\', '/', $path) === str_replace('\\', '/', $root . '/packages/asset-inspection/composer.json')) {
                continue;
            }
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertArrayNotHasKey('kongono/asset-inspection', is_array($candidate['require'] ?? null) ? $candidate['require'] : []);
        }
    }

    public function testProductionContainsNoForbiddenIoExecutionOrRoleDependency(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'realpath(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'time(', 'AssetApplicationWorkflow', 'AssetResolutionArtifactFactory', 'NativeAssetBuilder', 'Adapter', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Command', 'Controller', 'Worker', 'Watcher', 'Daemon'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertStringContainsString('new AssetResolutionArtifact(', $source);
        self::assertStringContainsString('new AssetResolutionEntry(', $source);
        self::assertStringContainsString('new AssetResolutionObject(', $source);
        self::assertStringNotContainsString('AssetIntelligenceDeriver', (string) file_get_contents(dirname(__DIR__, 2) . '/src/Verification/AssetIntelligenceVerifier.php'));
    }

    public function testPackageFileInventoryIsExact(): void
    {
        $root = dirname(__DIR__, 2);
        $expected = [
            'README.md',
            'composer.json',
            'src/Derivation/AssetIntelligenceDeriver.php',
            'src/Derivation/AssetIntelligenceView.php',
            'src/Diagnostic/AssetInspectionDiagnostic.php',
            'src/Exception/AssetInspectionBindingException.php',
            'src/Exception/AssetInspectionException.php',
            'src/Exception/AssetInspectionProjectionException.php',
            'src/Exception/AssetInspectionVerificationException.php',
            'src/Exception/ExceptionInterface.php',
            'src/Exception/InvalidAssetInspectionException.php',
            'src/Exception/InvalidAssetIntelligenceEvidenceException.php',
            'src/Graph/AssetGraphEvidence.php',
            'src/Graph/AssetGraphEvidenceProjector.php',
            'src/Inspection/AssetInspection.php',
            'src/Inspection/AssetInspectionRequest.php',
            'src/Inspection/AssetInspector.php',
            'src/Internal/CanonicalJson.php',
            'src/Projection/AssetObservationProjector.php',
            'src/Projection/AssetSecurityAuditProjector.php',
            'src/Role/AssetInspectionDeveloperRole.php',
            'src/Verification/AssetIntelligenceVerdict.php',
            'src/Verification/AssetIntelligenceVerification.php',
            'src/Verification/AssetIntelligenceVerifier.php',
            'tests/Architecture/AssetInspectionBoundaryTest.php',
            'tests/Security/AssetInspectionDisclosureTest.php',
            'tests/Support/AssetInspectionFactory.php',
            'tests/Unit/Derivation/AssetIntelligenceTest.php',
            'tests/Unit/Inspection/AssetInspectorTest.php',
            'tests/Unit/Projection/AssetProjectionVerificationTest.php',
        ];
        $observed = array_map(static fn(string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1)), $this->allFiles($root));
        self::assertSame($expected, $observed);
        self::assertCount(22, $this->phpFiles($root . '/src'));
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
