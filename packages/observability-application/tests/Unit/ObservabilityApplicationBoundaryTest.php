<?php

declare(strict_types=1);

namespace Kongono\ObservabilityApplication\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ObservabilityApplicationBoundaryTest extends TestCase
{
    public function testManifestDependenciesAndProductionInventoryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/observability-application', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/metrics' => '*',
            'kongono/observability' => '*',
            'kongono/observability-build' => '*',
            'kongono/opentelemetry' => '*',
            'kongono/operational-events' => '*',
            'kongono/security-audit' => '*',
            'kongono/tracing' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);

        $sourceFiles = [];
        foreach (['/*.php', '/*/*.php'] as $pattern) {
            $matches = glob($root . '/src' . $pattern);
            self::assertIsArray($matches);
            foreach ($matches as $path) {
                $sourceFiles[] = str_replace('\\', '/', substr($path, strlen($root . '/src') + 1));
            }
        }
        sort($sourceFiles, SORT_STRING);
        self::assertSame([
            'ApplicationObservability.php',
            'ApplicationObservabilityFactory.php',
            'ApplicationObservabilityOperation.php',
            'ApplicationObservabilityOperationState.php',
            'ApplicationObservabilityPlan.php',
            'ApplicationObservabilityWork.php',
            'Diagnostic/ObservabilityApplicationDiagnostic.php',
            'Exception/ApplicationObservabilityCleanupException.php',
            'Exception/ApplicationObservabilityLifecycleException.php',
            'Exception/ExceptionInterface.php',
            'Exception/ObservabilityApplicationException.php',
            'Internal/ApplicationObservabilityOperationBoundary.php',
        ], $sourceFiles);
    }
}
