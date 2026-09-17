<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;

final class ModelInvocationApplicationBoundaryTest extends TestCase
{
    public function testManifestDependenciesApiStatusesTopologyAndLeafDirectionAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/model-invocation-application', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/model-invocation-contract' => '*',
            'kongono/model-invocation-build' => '*',
            'kongono/model-invocation-runtime' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(109, $manifests);
        $edges = 0;
        foreach ($manifests as $path) {
            $candidate = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($candidate);
            self::assertIsArray($candidate['require']);
            foreach ($candidate['require'] as $dependency => $_constraint) {
                if (str_starts_with((string) $dependency, 'kongono/')) {
                    ++$edges;
                }
            }
            if (!in_array($candidate['name'], ['kongono/model-invocation-application', 'kongono/model-invocation-inspection', 'kongono/model-provider-openai-responses'], true)) {
                self::assertArrayNotHasKey('kongono/model-invocation-application', $candidate['require']);
            }
        }
        self::assertSame(467, $edges);

        foreach (self::phpFiles($package . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source), $path);
            $expected = str_ends_with(str_replace('\\', '/', $path), '/Internal/GeneratedWiringEmitter.php') ? 2 : 1;
            self::assertSame($expected, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source), $path);
        }
        $marker = new ReflectionClass('Kongono\\ModelInvocationApplication\\Exception\\ExceptionInterface');
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
    }

    public function testProductionContainsNoProviderBridgeAgentAmbientAutomationOrDiscoverySurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['ModelInvocationAgentBridge', 'AgentProposalPort', 'AgentCapabilityPort', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'PDO', 'curl_', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'Fiber', 'Queue', 'Scheduler', 'Worker', 'Retry', 'Fallback', 'Registry', 'Credential', 'ApplicationGraph', 'Chie', 'Hantei', 'Oshie'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            self::assertInstanceOf(SplFileInfo::class, $file);
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
