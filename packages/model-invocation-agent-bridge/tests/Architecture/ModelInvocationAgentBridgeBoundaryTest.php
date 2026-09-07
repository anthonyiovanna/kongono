<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ModelInvocationAgentBridgeBoundaryTest extends TestCase
{
    public function testManifestHasOnlyTheSixAuthorizedLocalProductionDependencies(): void
    {
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $requirements = $manifest['require'] ?? null;
        self::assertIsArray($requirements);
        unset($requirements['php']);

        self::assertSame([
            'kongono/agent-principal-contract' => '*',
            'kongono/agent-runtime' => '*',
            'kongono/api-metadata' => '*',
            'kongono/diagnostics' => '*',
            'kongono/model-invocation-contract' => '*',
            'kongono/model-invocation-runtime' => '*',
        ], $requirements);
    }

    public function testProductionSourceContainsNoProhibitedAuthorityOrIoMechanism(): void
    {
        $source = $this->sourceText();
        foreach ([
            'AgentProposalPort', '->propose(', '->close(', 'new ModelInvocationRuntime', 'InvocationAdapterSession',
            'AgentCapabilityPort', 'curl_', 'file_get_contents', 'getenv(', 'Reflection', 'eval(', 'exec(',
            'shell_exec', 'proc_open', 'service locator', 'registry',
        ] as $prohibited) {
            self::assertStringNotContainsString($prohibited, $source, $prohibited);
        }
    }

    public function testPackageInventoryAndApiDeclarationsAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $production = $this->phpFiles($root . '/src');
        $tests = $this->phpFiles($root . '/tests');

        self::assertCount(13, $production);
        self::assertCount(9, $tests);
        foreach ($production as $file) {
            self::assertStringContainsString('#[Api(ApiStatus::', (string) file_get_contents($file), $file);
        }
    }

    /** @return list<string> */
    private function phpFiles(string $root): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if (!$file instanceof SplFileInfo) {
                throw new RuntimeException('Package inventory entry is not a file descriptor.');
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    private function sourceText(): string
    {
        $source = '';
        foreach ($this->phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $source .= (string) file_get_contents($file);
        }

        return $source;
    }
}
