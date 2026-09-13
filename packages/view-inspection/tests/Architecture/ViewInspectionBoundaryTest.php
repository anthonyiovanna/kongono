<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ViewInspectionBoundaryTest extends TestCase
{
    public function testManifestDependenciesAreExactAndNoProductionPackageConsumesThisLeaf(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/view-inspection/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
            'kongono/view-application' => '*',
            'kongono/view-build' => '*',
            'kongono/view-contract' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $packageManifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($packageManifests);
        foreach ($packageManifests as $path) {
            if (str_replace('\\', '/', $path) === str_replace('\\', '/', $root . '/packages/view-inspection/composer.json')) {
                continue;
            }
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertArrayNotHasKey('kongono/view-inspection', is_array($candidate['require'] ?? null) ? $candidate['require'] : []);
        }
    }

    public function testProductionContainsNoForbiddenExecutionIoObservationOrRoleSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['ViewApplicationFactory', 'ViewApplicationBuildWorkflow', 'CompiledViewArtifactBuilder', 'ViewSourceParser', 'CompiledViewRenderer', 'CompiledViewArtifactLoader', 'CompletedViewResponseAdapter', 'class_exists(', 'require(', 'require_once(', 'include(', 'include_once(', 'eval(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'time(', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Command', 'Controller', 'Worker', 'Watcher', 'Daemon'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertSame(2, substr_count($source, 'CompiledViewArtifactValidator'));
        self::assertSame(1, substr_count($source, '->validateStructure('));
        self::assertStringNotContainsString('ViewIntelligenceDeriver', (string) file_get_contents(dirname(__DIR__, 2) . '/src/Verification/ViewIntelligenceVerifier.php'));
    }

    public function testPackageContainsExactlyTheAuthorizedFiles(): void
    {
        $root = dirname(__DIR__, 2);
        $expected = [
            'README.md',
            'composer.json',
            'src/Derivation/ViewIntelligenceDeriver.php',
            'src/Derivation/ViewIntelligenceView.php',
            'src/Diagnostic/ViewInspectionDiagnostic.php',
            'src/Exception/ExceptionInterface.php',
            'src/Exception/InvalidViewInspectionException.php',
            'src/Exception/InvalidViewIntelligenceEvidenceException.php',
            'src/Exception/ViewArtifactEvidenceException.php',
            'src/Exception/ViewInspectionBindingException.php',
            'src/Exception/ViewInspectionException.php',
            'src/Exception/ViewInspectionProjectionException.php',
            'src/Graph/ViewGraphEvidence.php',
            'src/Graph/ViewGraphEvidenceProjector.php',
            'src/Inspection/ViewInspection.php',
            'src/Inspection/ViewInspectionRequest.php',
            'src/Inspection/ViewInspector.php',
            'src/Internal/CanonicalJson.php',
            'src/Projection/ViewObservationProjector.php',
            'src/Projection/ViewSecurityAuditProjector.php',
            'src/Role/ViewInspectionDeveloperRole.php',
            'src/Verification/ViewIntelligenceVerdict.php',
            'src/Verification/ViewIntelligenceVerification.php',
            'src/Verification/ViewIntelligenceVerifier.php',
            'tests/Architecture/ViewInspectionBoundaryTest.php',
            'tests/Security/ViewInspectionDisclosureTest.php',
            'tests/Support/ViewInspectionFactory.php',
            'tests/Unit/Derivation/ViewIntelligenceTest.php',
            'tests/Unit/Inspection/ViewInspectorTest.php',
            'tests/Unit/Projection/ViewProjectionTest.php',
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
