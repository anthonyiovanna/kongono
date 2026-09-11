<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\ContractVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ModelInvocationRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertCount(21, self::phpFiles($package . '/src'));
        self::assertCount(12, self::phpFiles($package . '/tests'));
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/model-invocation-runtime', $manifest['name']);
        self::assertSame('Bounded provider-neutral execution runtime for Kongono model invocation.', $manifest['description']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/model-invocation-contract' => '*',
            'kongono/model-invocation-build' => '*',
        ], $manifest['require']);

        $statuses = [
            'Kongono\\ModelInvocationRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\ModelInvocationRuntime\\Diagnostic\\ModelInvocationRuntimeDiagnostic' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Exception\\ModelInvocationRuntimeException' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Internal\\BudgetMeter' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Internal\\ReceiptFactory' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Internal\\RuntimeGuard' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Internal\\StreamGuard' => ApiStatus::Internal,
            'Kongono\\ModelInvocationRuntime\\Runtime\\ModelInvocationRuntime' => ApiStatus::Experimental,
        ];
        foreach ($statuses as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
        self::assertSame([ContractVersion::Version1], ContractVersion::cases());
        self::assertStringContainsString(
            'public function version(): ContractVersion',
            (string) file_get_contents(dirname(__DIR__, 3) . '/model-invocation-contract/src/Result/ModelResult.php'),
        );
    }

    public function testTopologyIsExactly74By280AcyclicWithOnlyTheAuthorizedUpperConsumers(): void
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
                if (str_starts_with((string) $dependency, 'kongono/')) {
                    $graph[$name][] = $dependency;
                    ++$edges;
                }
            }
            if (!in_array($name, ['kongono/model-invocation-agent-bridge', 'kongono/model-invocation-application', 'kongono/model-invocation-inspection', 'kongono/model-invocation-runtime', 'kongono/model-provider-openai-responses', 'kongono/model-provider-openai-responses-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-runtime', $manifest['require']);
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

    public function testSourceContainsNoAmbientProviderRegistryOrAgentBehavior(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['curl_', 'getenv(', 'putenv(', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'eval(', 'microtime(', 'hrtime(', 'sleep(', 'usleep(', '$_ENV', '$_SERVER', '$GLOBALS', 'AgentPrincipal', 'ServiceLocator', 'Registry'] as $forbidden) {
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
