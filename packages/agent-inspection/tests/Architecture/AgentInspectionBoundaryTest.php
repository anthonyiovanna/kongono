<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class AgentInspectionBoundaryTest extends TestCase
{
    public function testManifestAndProductionDependencyBoundaryAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/agent-inspection/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/agent-inspection', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/agent-admission' => '*',
            'kongono/agent-application' => '*',
            'kongono/agent-declaration-build' => '*',
            'kongono/agent-principal-contract' => '*',
            'kongono/agent-runtime' => '*',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/diagnostics' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $productionPaths = glob($root . '/packages/agent-inspection/src/*/*.php');
        self::assertIsArray($productionPaths);
        $production = implode("\n", array_map(static fn(string $path): string => (string) file_get_contents($path), $productionPaths));
        foreach (['Kongono\\Chie', 'Kongono\\Hantei', 'Kongono\\Oshie', 'ObservationRecorder', 'SecurityAuditRuntime', 'AgentRuntime(', 'AgentApplication(', 'getenv(', 'file_get_contents(', 'Reflection'] as $prohibited) {
            self::assertStringNotContainsString($prohibited, $production);
        }
    }

    public function testRepositoryGraphIsExactlySeventyPackagesAndTwoHundredSixtyTwoEdges(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(109, $manifests);
        $names = [];
        $decoded = [];
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name'] ?? null);
            self::assertIsArray($manifest['require'] ?? null);
            $names[$manifest['name']] = true;
            $decoded[] = $manifest;
        }
        $edges = 0;
        foreach ($decoded as $manifest) {
            foreach (array_keys($manifest['require']) as $requirement) {
                if (isset($names[$requirement])) {
                    ++$edges;
                }
            }
        }
        self::assertSame(467, $edges);
    }
}
