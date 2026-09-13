<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class ViewBuildCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::removeTree($this->root);
        }
    }

    public function testPlanAndApplyAreExplicitSafeAndIdempotent(): void
    {
        [$root, $source, $output] = $this->application();
        $arguments = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=' . $source];

        [$planStatus, $planOut] = self::execute(array_merge(['--json', 'view:build', '--plan'], $arguments));
        self::assertSame(0, $planStatus);
        self::assertStringContainsString('"name":"execution_intent","kind":"text","value":"plan"', $planOut);
        self::assertSame([], self::entries($output));

        [$applyStatus, $applyOut] = self::execute(array_merge(['--json', 'view:build', '--apply'], $arguments));
        self::assertSame(0, $applyStatus);
        self::assertStringContainsString('"name":"current","kind":"boolean","value":true', $applyOut);
        self::assertStringContainsString('"name":"execution_intent","kind":"text","value":"apply"', $applyOut);
        self::assertStringNotContainsString($root, $applyOut);
        $before = self::identities($output);

        [$repeatStatus, $repeatOut] = self::execute(array_merge(['--json', 'view:build', '--apply'], $arguments));
        self::assertSame(0, $repeatStatus);
        self::assertSame($applyOut, $repeatOut);
        self::assertSame($before, self::identities($output));

        $decoded = json_decode($applyOut, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $data = $decoded['data'] ?? null;
        self::assertIsArray($data);
        $fields = $data['fields'] ?? null;
        self::assertIsArray($fields);
        self::assertSame(['artifact_basename', 'compiled_sha256', 'current', 'execution_intent', 'generated_sha256', 'model_sha256', 'publication_required', 'schema', 'source_sha256', 'version', 'view_count'], array_column($fields, 'name'));
    }

    /** @return array{string, string, string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-view-build-command-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/views', 0700, true));
        self::assertTrue(mkdir($root . '/var/view', 0700, true));
        $source = $root . '/views/home.json';
        $bytes = <<<'JSON'
{"schema":"kongono.view-source-set","version":1,"views":[{"id":"page.home","context":{"kind":"record","fields":[{"name":"message","required":true,"schema":{"kind":"string","max_bytes":64}}]},"body":[{"kind":"value","path":"$.message"}]}]}
JSON;
        $bytes .= "\n";
        self::assertSame(strlen($bytes), file_put_contents($source, $bytes));
        $this->root = $root;

        return [$root, $source, $root . '/var/view'];
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{int, string, string}
     */
    private static function execute(array $arguments): array
    {
        $stdout = self::port();
        $stderr = self::port();
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $stdout, $stderr);

        return [$status, $stdout->bytes, $stderr->bytes];
    }

    /** @return CommandOutputPort&object{bytes: string} */
    private static function port(): CommandOutputPort
    {
        return new class implements CommandOutputPort {
            public string $bytes = '';

            public function write(string $bytes): void
            {
                if ('' !== $this->bytes) {
                    throw new \RuntimeException('duplicate write');
                }
                $this->bytes = $bytes;
            }
        };
    }

    /** @return list<string> */
    private static function entries(string $directory): array
    {
        $entries = glob($directory . '/*');

        return false === $entries ? [] : $entries;
    }

    /** @return array<string, string> */
    private static function identities(string $directory): array
    {
        $identities = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            if ($entry->isFile()) {
                $path = str_replace('\\', '/', $entry->getPathname());
                $identities[substr($path, strlen($directory) + 1)] = (string) hash_file('sha256', $path);
            }
        }
        ksort($identities, SORT_STRING);

        return $identities;
    }

    private static function removeTree(string $root): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}
