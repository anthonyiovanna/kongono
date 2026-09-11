<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class OpenAiResponsesInspectionBoundaryTest extends TestCase
{
    public function testManifestDependenciesAreExactAndLeafOnly(): void
    {
        $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame([
            'php' => '>=8.5.0 <8.6.0',
            'kongono/api-metadata' => '*',
            'kongono/application-graph-intelligence' => '*',
            'kongono/diagnostics' => '*',
            'kongono/model-invocation-contract' => '*',
            'kongono/model-invocation-runtime' => '*',
            'kongono/model-provider-openai-responses' => '*',
            'kongono/observability' => '*',
            'kongono/security-audit' => '*',
        ], $manifest['require']);
        self::assertSame(['phpunit/phpunit' => '^13.0'], $manifest['require-dev']);
    }

    public function testProductionContainsNoForbiddenExecutionIoOrDisclosureMechanism(): void
    {
        $source = implode('', array_map(static fn(string $path): string => (string) file_get_contents($path), $this->phpFiles(dirname(__DIR__, 2) . '/src')));
        foreach (['OpenAiResponsesApplication(', 'InvocationRuntime(', 'OpenAiResponsesInvocationAdapter(', 'ExternalServiceApplication(', 'CredentialLease', 'Resolver', 'Transport', 'Cancellation', 'Clock', 'require(', 'require_once(', 'include(', 'include_once(', 'file_get_contents(', 'file_put_contents(', 'fopen(', 'scandir(', 'glob(', 'getenv(', 'putenv(', '$_ENV', '$_SERVER', '$GLOBALS', 'curl_', 'PDO', 'Reflection', 'shell_exec(', 'proc_open(', 'random_', 'microtime(', 'time(', 'Recorder', 'Exporter', 'Chie\\', 'Hantei\\', 'Oshie\\', 'Retry', 'Fallback', 'Registry', 'Worker', 'Scheduler', 'Command', 'Controller'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
        self::assertSame(2, substr_count($source, 'OpenAiResponsesApplicationProfileFactory'));
        self::assertSame(1, substr_count($source, ')->create('));
        foreach (['->text()', '->structuredValue()', '->actionSuggestion()', '->arguments()', '->payload()', '->responseId()', '->body()'] as $hiddenAccessor) {
            self::assertStringNotContainsString($hiddenAccessor, $source, $hiddenAccessor);
        }
    }

    public function testPackageContainsOnlyManifestReadmeAndPhpSourceTests(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ($this->allFiles($root) as $path) {
            $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));
            self::assertTrue(in_array($relative, ['composer.json', 'README.md'], true) || 1 === preg_match('#\A(?:src|tests)/.+\.php\z#D', $relative), $relative);
        }
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
