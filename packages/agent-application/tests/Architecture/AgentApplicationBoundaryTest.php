<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Tests\Architecture;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SplFileInfo;

final class AgentApplicationBoundaryTest extends TestCase
{
    public function testManifestDependenciesApiStatusesAndReverseConsumersAreExact(): void
    {
        $package = dirname(__DIR__, 2);
        $root = dirname($package, 2);
        $manifest = json_decode((string) file_get_contents($package . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/agent-application', $manifest['name']);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/agent-admission' => '*',
            'kongono/agent-declaration-build' => '*',
            'kongono/agent-principal-contract' => '*',
            'kongono/agent-runtime' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);

        $manifests = glob($root . '/packages/*/composer.json');
        self::assertIsArray($manifests);
        self::assertCount(81, $manifests);
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
            if (!in_array($candidate['name'], ['kongono/agent-application', 'kongono/agent-inspection'], true)) {
                self::assertArrayNotHasKey('kongono/agent-application', $candidate['require']);
            }
        }
        self::assertSame(317, $edges);

        foreach (self::phpFiles($package . '/src') as $path) {
            $source = (string) file_get_contents($path);
            self::assertSame(1, preg_match_all('/#\[Api\(ApiStatus::(?:Experimental|Internal|Public)\)\]/', $source), $path);
            $expectedDeclarations = str_ends_with(str_replace('\\', '/', $path), '/Internal/GeneratedWiringEmitter.php') ? 2 : 1;
            self::assertSame($expectedDeclarations, preg_match_all('/\b(?:final readonly class|final class|abstract class|interface|enum)\s+[A-Za-z_][A-Za-z0-9_]*/', $source), $path);
        }
        $marker = new ReflectionClass('Kongono\\AgentApplication\\Exception\\ExceptionInterface');
        self::assertSame(ApiStatus::Public, $marker->getAttributes(Api::class)[0]->newInstance()->status);
    }

    public function testProductionSourceContainsNoProviderAmbientAutomationOrFuturePhaseSurface(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), self::phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'PDO', 'curl_', 'scandir(', 'glob(', 'shell_exec(', 'proc_open(', 'Fiber', 'Queue', 'Scheduler', 'Worker', 'Retry', 'DelegationEngine', 'ApplicationGraph', 'ExtensionHost', 'Chie', 'Hantei', 'Oshie', 'MCP', 'A2A'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
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
