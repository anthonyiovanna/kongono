<?php

declare(strict_types=1);

namespace Kongono\Tests\Integration\Phase8G;

use PHPUnit\Framework\TestCase;

final class KongoExecutableTest extends TestCase
{
    public function testDirectSourceAndComposerProxyPlanApplyWithoutRepositoryMutation(): void
    {
        $repository = dirname(__DIR__, 3);
        $root = self::temporaryDirectory();
        $output = $root . '/var/build';
        self::assertTrue(mkdir($root . '/src', 0700, true));
        self::assertTrue(mkdir($output, 0700, true));
        self::write($root . '/src/Input.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Phase8GExecutableFixture;\n\nfinal readonly class Input {}\n");
        self::write($root . '/src/Root.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace Phase8GExecutableFixture;\n\nfinal readonly class Root { public function __construct(public Input \$input) {} }\n");
        self::write($root . '/composer.json', "{\"name\":\"example/phase8g-executable\"}\n");
        self::write($root . '/composer.lock', "{\"packages\":[]}\n");
        self::write($root . '/kongono.composition.json', "{\"schema\":\"kongono.composition\",\"version\":1,\"inputs\":[{\"contract\":\"Phase8GExecutableFixture\\\\Input\"}],\"services\":[{\"class\":\"Phase8GExecutableFixture\\\\Root\",\"lifetime\":\"application\",\"arguments\":[{\"parameter\":\"input\",\"input\":\"Phase8GExecutableFixture\\\\Input\"}],\"cleanup\":null}],\"bindings\":[],\"roots\":[{\"kind\":\"application\",\"contract\":\"Phase8GExecutableFixture\\\\Root\"}]}\n");
        $autoload = str_replace('\\', '/', $repository . '/vendor/autoload.php');
        self::write($root . '/preload.php', "<?php\n\ndeclare(strict_types=1);\n\n\$GLOBALS['_composer_autoload_path'] = '" . addslashes($autoload) . "';\nrequire '" . addslashes($autoload) . "';\nrequire __DIR__ . '/src/Input.php';\nrequire __DIR__ . '/src/Root.php';\n");

        try {
            $before = self::gitStatus($repository);
            $source = $repository . '/packages/console-application/bin/kongo';
            $proxy = $repository . '/vendor/bin/kongo';
            self::assertFileExists($source);
            self::assertFileExists($proxy);

            $plan = self::process([PHP_BINARY, '-d', 'auto_prepend_file=' . $root . '/preload.php', $source, '--json', 'application:build', '--plan', '--application-root=' . $root, '--output-directory=' . $output]);
            self::assertSame(0, $plan['exit']);
            self::assertSame('', $plan['stderr']);
            self::assertSame([], self::outputFiles($output));
            $planDocument = json_decode($plan['stdout'], true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($planDocument)) {
                self::fail('Plan output must decode to an object.');
            }
            self::assertTrue(self::field($planDocument, 'publication_required'));

            $humanPlan = self::process([PHP_BINARY, '-d', 'auto_prepend_file=' . $root . '/preload.php', $source, 'application:build', '--plan', '--application-root=' . $root, '--output-directory=' . $output]);
            self::assertSame(0, $humanPlan['exit']);
            self::assertSame('', $humanPlan['stderr']);
            $artifactSha256 = self::field($planDocument, 'artifact_sha256');
            $wiringSha256 = self::field($planDocument, 'generated_wiring_sha256');
            self::assertIsString($artifactSha256);
            self::assertIsString($wiringSha256);
            self::assertStringContainsString($artifactSha256, $humanPlan['stdout']);
            self::assertStringContainsString($wiringSha256, $humanPlan['stdout']);
            self::assertSame([], self::outputFiles($output));

            $apply = self::process([PHP_BINARY, '-d', 'auto_prepend_file=' . $root . '/preload.php', $proxy, '--json', 'application:build', '--apply', '--application-root=' . $root, '--output-directory=' . $output, '--no-interaction']);
            self::assertSame(0, $apply['exit']);
            self::assertSame('', $apply['stderr']);
            self::assertCount(2, self::outputFiles($output));
            $applyDocument = json_decode($apply['stdout'], true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($applyDocument)) {
                self::fail('Apply output must decode to an object.');
            }
            self::assertFalse(self::field($applyDocument, 'publication_required'));
            self::assertSame(self::field($planDocument, 'artifact_sha256'), self::field($applyDocument, 'artifact_sha256'));
            self::assertSame(self::field($planDocument, 'generated_wiring_sha256'), self::field($applyDocument, 'generated_wiring_sha256'));

            $human = self::process([PHP_BINARY, $proxy, '--help']);
            self::assertSame(0, $human['exit']);
            self::assertStringContainsString('Command: application:build', $human['stdout']);
            self::assertSame('', $human['stderr']);
            $allOutput = $plan['stdout'] . $humanPlan['stdout'] . $apply['stdout'] . $human['stdout'];
            self::assertStringNotContainsString($root, $allOutput);
            self::assertStringNotContainsString('Phase8GExecutableFixture', $allOutput);
            self::assertStringNotContainsString("\033", $allOutput);
            self::assertStringNotContainsStringIgnoringCase('debug', $allOutput);
            self::assertSame($before, self::gitStatus($repository));
        } finally {
            self::removeTree($root);
        }
    }

    /** @param array<mixed, mixed> $document */
    private static function field(array $document, string $name): mixed
    {
        $data = $document['data'] ?? null;
        $fields = is_array($data) ? ($data['fields'] ?? null) : null;
        if (!is_array($fields)) {
            self::fail('Result data fields are missing.');
        }
        foreach ($fields as $field) {
            if (is_array($field) && $name === ($field['name'] ?? null)) {
                return $field['value'] ?? null;
            }
        }
        self::fail('Required field missing: ' . $name);
    }

    /**
     * @param list<string> $command
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private static function process(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        self::assertTrue(fclose($pipes[0]));
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        if (!is_string($stdout) || !is_string($stderr)) {
            self::fail('Process output streams must be readable.');
        }
        self::assertTrue(fclose($pipes[1]));
        self::assertTrue(fclose($pipes[2]));

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private static function gitStatus(string $repository): string
    {
        $result = self::process(['git', '-c', 'core.excludesfile=', '-C', $repository, 'status', '--porcelain=v1', '--untracked-files=all']);
        self::assertSame(0, $result['exit']);
        self::assertSame('', $result['stderr']);

        return $result['stdout'];
    }

    /** @return list<string> */
    private static function outputFiles(string $directory): array
    {
        $files = glob($directory . '/*');

        return false === $files ? [] : $files;
    }

    private static function temporaryDirectory(): string
    {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-phase8g-executable-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($path, 0700, true));

        return $path;
    }

    private static function write(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue;
            }
            $entry->isDir() ? self::assertTrue(rmdir($entry->getPathname())) : self::assertTrue(unlink($entry->getPathname()));
        }
        self::assertTrue(rmdir($directory));
    }
}
