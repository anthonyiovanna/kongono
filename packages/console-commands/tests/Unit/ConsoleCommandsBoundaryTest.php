<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ConsoleCommandsBoundaryTest extends TestCase
{
    public function testManifestDependencyDirectionAndInventoryAreExact(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertSame('kongono/console-commands', $manifest['name']);
        self::assertSame([
            'kongono/api-metadata' => '*',
            'kongono/application-graph' => '*',
            'kongono/composition' => '*',
            'kongono/console' => '*',
            'kongono/console-runtime' => '*',
            'kongono/diagnostics' => '*',
            'kongono/hantei' => '*',
            'php' => '>=8.5.0 <8.6.0',
        ], $manifest['require']);
        self::assertSame(['kongono/console-build' => '*'], $manifest['require-dev']);
        self::assertCount(10, self::phpFiles($root . '/src'));
        self::assertCount(8, self::phpFiles($root . '/tests'));
        foreach (['bin', 'public', 'scripts'] as $absent) {
            self::assertDirectoryDoesNotExist($root . '/' . $absent);
        }

        $repository = dirname($root, 2);
        $manifests = glob($repository . '/packages/*/composer.json');
        foreach (false === $manifests ? [] : $manifests as $path) {
            if ($path === $root . '/composer.json') {
                continue;
            }
            $other = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            self::assertIsArray($other);
            self::assertIsArray($other['require']);
            self::assertArrayNotHasKey('kongono/console-commands', $other['require']);
        }
    }

    public function testProductionContainsNoAmbientOrMutatingMechanism(): void
    {
        $bytes = '';
        foreach (self::phpFiles(dirname(__DIR__, 2) . '/src') as $file) {
            $bytes .= (string) file_get_contents($file);
        }
        foreach ([
            '$argv', '$_SERVER', '$GLOBALS', 'getenv(', 'putenv(', 'STDIN', 'STDOUT', 'STDERR', 'exit(', 'die(',
            'proc_open(', 'shell_exec(', 'exec(', 'system(', 'passthru(', 'file_get_contents(', 'file_put_contents(',
            'ReflectionClass', 'scandir(', 'glob(', 'service locator', 'container->', 'eval(', 'curl_', 'socket_',
            'beginTransaction(', 'commit(', 'rollBack(',
        ] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $bytes);
        }
    }

    /** @return list<string> */
    private static function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }
}
