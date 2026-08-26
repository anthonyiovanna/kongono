<?php

declare(strict_types=1);

namespace Kongono\ConsoleBuild\Tests\Unit;

use Kongono\ConsoleBuild\Internal\ConsoleModelCompiler;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ConsoleBuildBoundaryTest extends TestCase
{
    public function testManifestDependencyDirectionAndInventoryAreExact(): void
    {
        $root = dirname(__DIR__, 4);
        $manifest = json_decode((string) file_get_contents($root . '/packages/console-build/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console-build', $manifest['name']);
        self::assertSame([
            'ext-hash' => '*',
            'ext-json' => '*',
            'kongono/api-metadata' => '*',
            'kongono/console' => '*',
            'kongono/diagnostics' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        $autoload = $manifest['autoload'];
        $autoloadDev = $manifest['autoload-dev'];
        self::assertIsArray($autoload);
        self::assertIsArray($autoloadDev);
        self::assertSame(['Kongono\\ConsoleBuild\\' => 'src/'], $autoload['psr-4']);
        self::assertSame(['Kongono\\ConsoleBuild\\Tests\\' => 'tests/'], $autoloadDev['psr-4']);
        self::assertCount(16, $this->phpFiles($root . '/packages/console-build/src'));
        self::assertCount(10, $this->phpFiles($root . '/packages/console-build/tests'));
    }

    public function testProductionBoundaryContainsNoRuntimeExecutionOrLaterPhaseCapability(): void
    {
        $root = dirname(__DIR__, 4);
        $source = '';
        foreach ($this->phpFiles($root . '/packages/console-build/src') as $file) {
            $bytes = file_get_contents($file);
            self::assertIsString($bytes);
            $source .= $bytes;
        }
        foreach ([
            'Kongono\\Kernel\\',
            'Kongono\\Composition\\',
            'Kongono\\Database\\',
            'Kongono\\Persistence\\',
            'Symfony\\Component\\Console',
            'ReflectionClass',
            'function execute(',
            'function publish(',
            'function load(',
            'file_put_contents(',
            'mkdir(',
            'rename(',
            'eval(',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        foreach (['bin', 'src/Execution', 'src/Presentation', 'src/Runtime', 'src/Registry'] as $absent) {
            self::assertDirectoryDoesNotExist($root . '/packages/console-build/' . $absent);
        }
    }

    public function testCompilationProducesNoOutputOrExecution(): void
    {
        ob_start();
        $model = (new ConsoleModelCompiler())->compile([
            'schema' => 'kongono.command-source',
            'version' => 1,
            'commands' => [['identifier' => 'status', 'summary' => 'Show status.', 'interaction' => 'none', 'mutation' => 'read_only', 'inputs' => []]],
        ]);
        $output = ob_get_clean();

        self::assertSame('', $output);
        self::assertSame('status', $model->specifications()->commands()[0]->definition()->identifier()->value());
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
