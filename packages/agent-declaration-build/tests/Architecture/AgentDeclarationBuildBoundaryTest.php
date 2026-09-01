<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AgentDeclarationBuildBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Artifact/AgentDeclarationArtifact.php',
            'Artifact/AgentDeclarationArtifactBuilder.php',
            'Artifact/AgentDeclarationArtifactPublisher.php',
            'Artifact/AgentDeclarationArtifactValidator.php',
            'Declaration/AgentDeclaration.php',
            'Declaration/AgentDeclarationIdentity.php',
            'Diagnostic/AgentDeclarationBuildDiagnostic.php',
            'Exception/AgentDeclarationBuildException.php',
            'Exception/AgentDeclarationPublicationException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidAgentDeclarationArtifactException.php',
            'Exception/InvalidAgentDeclarationException.php',
            'Exception/StaleAgentDeclarationArtifactException.php',
            'Internal/CanonicalJson.php',
            'Internal/PathGuard.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/AgentDeclarationBuildBoundaryTest.php',
            'Security/AgentDeclarationDisclosureTest.php',
            'Support/AgentDeclarationFactory.php',
            'Unit/Artifact/AgentDeclarationArtifactBuilderTest.php',
            'Unit/Artifact/AgentDeclarationArtifactPublisherTest.php',
            'Unit/Artifact/AgentDeclarationArtifactValidatorTest.php',
            'Unit/Declaration/AgentDeclarationTest.php',
            'Unit/Diagnostic/AgentDeclarationBuildDiagnosticTest.php',
            'Unit/Exception/AgentDeclarationBuildExceptionTest.php',
        ], self::phpFiles($package . '/tests'));

        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertIsArray($manifest['require']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/agent-principal-contract' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);

        $expected = [
            'Kongono\\AgentDeclarationBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\AgentDeclarationBuild\\Diagnostic\\AgentDeclarationBuildDiagnostic' => ApiStatus::Internal,
            'Kongono\\AgentDeclarationBuild\\Exception\\AgentDeclarationBuildException' => ApiStatus::Internal,
            'Kongono\\AgentDeclarationBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
            'Kongono\\AgentDeclarationBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        ];
        foreach ($expected as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testTopologyIsAcyclicAndHasNoReverseDependency(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(69, $manifests);
        $graph = [];
        $edges = 0;
        foreach ($manifests as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($manifest);
            self::assertIsString($manifest['name']);
            self::assertIsArray($manifest['require']);
            $name = $manifest['name'];
            $graph[$name] = [];
            foreach ($manifest['require'] as $dependency => $_constraint) {
                self::assertIsString($dependency);
                if (str_starts_with($dependency, 'kongono/')) {
                    $graph[$name][] = $dependency;
                    ++$edges;
                }
            }
            if (!in_array($name, ['kongono/agent-admission', 'kongono/agent-application', 'kongono/agent-declaration-build', 'kongono/agent-inspection', 'kongono/agent-runtime'], true)) {
                self::assertArrayNotHasKey('kongono/agent-declaration-build', $manifest['require']);
            }
        }
        self::assertSame(260, $edges);
        $visit = static function (string $name, array $path = []) use (&$visit, $graph): void {
            self::assertArrayNotHasKey($name, $path);
            $path[$name] = true;
            foreach ($graph[$name] ?? [] as $dependency) {
                $visit($dependency, $path);
            }
        };
        foreach (array_keys($graph) as $name) {
            $visit($name);
        }
    }

    public function testSourceContainsNoForbiddenIntegrationOrExecutionSymbols(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['ApplicationGraph', 'Extension', 'Composition', 'Model', 'Provider', 'Authorization', 'Registry', 'Loader', 'Command', 'PDO', 'curl_', 'scandir(', 'glob(', 'getenv(', 'putenv('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
