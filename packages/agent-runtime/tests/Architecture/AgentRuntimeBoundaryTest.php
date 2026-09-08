<?php

declare(strict_types=1);

namespace Kongono\AgentRuntime\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AgentRuntimeBoundaryTest extends TestCase
{
    public function testExactInventoriesManifestAndApiStatuses(): void
    {
        $package = dirname(__DIR__, 2);
        self::assertSame([
            'Authority/AgentAuthorityCurrentness.php',
            'Budget/AgentRuntimeBudget.php',
            'Budget/AgentRuntimeUsage.php',
            'Cancellation/AgentCancellation.php',
            'Capability/AgentCapabilityInvocation.php',
            'Capability/AgentCapabilityOutcome.php',
            'Capability/AgentCapabilityPort.php',
            'Capability/AgentCapabilityResult.php',
            'Clock/AgentMonotonicClock.php',
            'Diagnostic/AgentRuntimeDiagnostic.php',
            'Exception/AgentRuntimeCleanupException.php',
            'Exception/AgentRuntimeException.php',
            'Exception/AgentRuntimeOperationAndCleanupException.php',
            'Exception/AgentRuntimeOperationException.php',
            'Exception/ExceptionInterface.php',
            'Exception/InvalidAgentRuntimeException.php',
            'Internal/BudgetMeter.php',
            'Internal/LifecycleFailureComposer.php',
            'Internal/ReceiptFactory.php',
            'Internal/RuntimeGuard.php',
            'Proposal/AgentProposalPort.php',
            'Proposal/AgentProposalRequest.php',
            'Proposal/AgentProposalResult.php',
            'Runtime/AgentRuntime.php',
            'Runtime/AgentRuntimeOutcome.php',
            'Runtime/AgentRuntimeRequest.php',
            'Runtime/AgentRuntimeResult.php',
            'Runtime/AgentRuntimeState.php',
        ], self::phpFiles($package . '/src'));
        self::assertSame([
            'Architecture/AgentRuntimeBoundaryTest.php',
            'Security/AgentRuntimeDisclosureTest.php',
            'Support/AgentRuntimeFactory.php',
            'Support/RecordingAuthorityCurrentness.php',
            'Support/RecordingCancellation.php',
            'Support/RecordingCapabilityPort.php',
            'Support/RecordingClock.php',
            'Support/RecordingProposalPort.php',
            'Unit/Budget/AgentRuntimeBudgetTest.php',
            'Unit/Capability/AgentCapabilityResultTest.php',
            'Unit/Diagnostic/AgentRuntimeDiagnosticTest.php',
            'Unit/Exception/AgentRuntimeExceptionTest.php',
            'Unit/Proposal/AgentProposalResultTest.php',
            'Unit/Runtime/AgentRuntimeTest.php',
        ], self::phpFiles($package . '/tests'));

        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/agent-runtime', $manifest['name']);
        self::assertSame('Provider-neutral bounded operation runtime for Kongono Agent Principals.', $manifest['description']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/agent-admission' => '*',
            'kongono/agent-declaration-build' => '*',
            'kongono/agent-principal-contract' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $statuses = [
            'Kongono\\AgentRuntime\\Exception\\ExceptionInterface' => ApiStatus::Public,
            'Kongono\\AgentRuntime\\Diagnostic\\AgentRuntimeDiagnostic' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Exception\\AgentRuntimeException' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Internal\\BudgetMeter' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Internal\\LifecycleFailureComposer' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Internal\\ReceiptFactory' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Internal\\RuntimeGuard' => ApiStatus::Internal,
            'Kongono\\AgentRuntime\\Runtime\\AgentRuntime' => ApiStatus::Experimental,
            'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeRequest' => ApiStatus::Experimental,
            'Kongono\\AgentRuntime\\Runtime\\AgentRuntimeResult' => ApiStatus::Experimental,
        ];
        foreach ($statuses as $class => $status) {
            $attributes = (new ReflectionClass($class))->getAttributes(Api::class);
            self::assertCount(1, $attributes);
            self::assertSame($status, $attributes[0]->newInstance()->status);
        }
    }

    public function testTopologyIsExactlyAcyclicAndRuntimeHasNoPackageConsumers(): void
    {
        $root = dirname(__DIR__, 4);
        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(81, $manifests);
        $graph = [];
        $edges = 0;
        foreach ($manifests as $path) {
            $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
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
            if (!in_array($name, ['kongono/agent-application', 'kongono/agent-inspection', 'kongono/agent-runtime', 'kongono/model-invocation-agent-bridge'], true)) {
                self::assertArrayNotHasKey('kongono/agent-runtime', $manifest['require']);
            }
        }
        self::assertSame(317, $edges);
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

    public function testSourceContainsNoAmbientOrProhibitedAdapterMechanism(): void
    {
        $source = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $relative) {
            $source .= file_get_contents(dirname(__DIR__, 2) . '/src/' . $relative);
        }
        foreach (['Reflection', 'Fiber', 'PDO', 'curl_', 'file_get_contents(', 'file_put_contents(', 'scandir(', 'glob(', 'getenv(', 'putenv(', 'proc_open(', 'shell_exec(', 'random_bytes(', 'mt_rand(', 'microtime(', 'hrtime(', 'sleep(', 'usleep(', '$_ENV', '$_SERVER', '$GLOBALS'] as $forbidden) {
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
