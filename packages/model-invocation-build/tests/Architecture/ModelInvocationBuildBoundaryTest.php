<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ModelInvocationBuildBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Artifact/InvocationPlanArtifact.php',
            'Artifact/InvocationPlanArtifactBuilder.php',
            'Artifact/InvocationPlanArtifactPublisher.php',
            'Artifact/InvocationPlanArtifactValidator.php',
            'Budget/CostLimit.php',
            'Budget/InvocationExecutionBudget.php',
            'Budget/UsageLimit.php',
            'Diagnostic/ModelInvocationBuildDiagnostic.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidInvocationPlanArtifactException.php',
            'Exception/InvalidInvocationPlanSourceException.php',
            'Exception/InvocationPlanPublicationException.php',
            'Exception/ModelInvocationBuildException.php',
            'Exception/StaleInvocationPlanArtifactException.php',
            'Internal/CanonicalJson.php',
            'Internal/PathGuard.php',
            'Plan/AdapterIdentity.php',
            'Plan/InvocationPlanIdentity.php',
            'Plan/InvocationPlanSource.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/ModelInvocationBuildBoundaryTest.php',
            'Security/ModelInvocationBuildDisclosureTest.php',
            'Support/InvocationPlanSourceFactory.php',
            'Unit/Artifact/InvocationPlanArtifactBuilderTest.php',
            'Unit/Artifact/InvocationPlanArtifactPublisherTest.php',
            'Unit/Artifact/InvocationPlanArtifactValidatorTest.php',
            'Unit/Budget/CostLimitTest.php',
            'Unit/Budget/InvocationExecutionBudgetTest.php',
            'Unit/Budget/UsageLimitTest.php',
            'Unit/Diagnostic/ModelInvocationBuildDiagnosticTest.php',
            'Unit/Exception/ModelInvocationBuildExceptionTest.php',
            'Unit/Plan/AdapterIdentityTest.php',
            'Unit/Plan/InvocationPlanIdentityTest.php',
            'Unit/Plan/InvocationPlanSourceTest.php',
        ], self::phpFiles($package . '/tests'));

        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/model-invocation-contract' => '*',
        ], $manifest['require']);

        $statuses = [
            'Kongono\\ModelInvocationBuild\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\ModelInvocationBuild\\Diagnostic\\ModelInvocationBuildDiagnostic' => ApiStatus::Internal,
            'Kongono\\ModelInvocationBuild\\Exception\\ModelInvocationBuildException' => ApiStatus::Internal,
            'Kongono\\ModelInvocationBuild\\Internal\\CanonicalJson' => ApiStatus::Internal,
            'Kongono\\ModelInvocationBuild\\Internal\\PathGuard' => ApiStatus::Internal,
        ];
        foreach ($statuses as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testTopologyIsExactAcyclicAndHasNoReverseConsumer(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(89, $manifests);
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
            if (!in_array($name, ['kongono/model-invocation-application', 'kongono/model-invocation-build', 'kongono/model-invocation-inspection', 'kongono/model-invocation-runtime', 'kongono/model-provider-openai-responses'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-build', $manifest['require']);
            }
        }
        self::assertSame(375, $edges);
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

    public function testSourceContainsNoForbiddenRuntimeOrDiscoveryBehavior(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['curl_', 'getenv(', 'putenv(', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'eval(', 'Authorization', 'AgentPrincipal', 'Registry', 'Command', 'Credential', 'HttpClient', 'ProviderAdapter'] as $forbidden) {
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
