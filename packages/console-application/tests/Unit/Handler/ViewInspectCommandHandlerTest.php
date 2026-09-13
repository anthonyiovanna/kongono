<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class ViewInspectCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::removeTree($this->root);
        }
    }

    public function testInspectIsWriteFreeAndRejectsMutationIntent(): void
    {
        [$root, $source, $output] = $this->application();
        $arguments = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=' . $source];

        [$inspectStatus, $inspectOut] = self::execute(array_merge(['--json', 'view:inspect'], $arguments));
        self::assertSame(0, $inspectStatus);
        self::assertStringContainsString('"name":"publication_required","kind":"boolean","value":true', $inspectOut);
        self::assertStringNotContainsString($root, $inspectOut);
        self::assertSame([], self::entries($output));

        [$invalidStatus, $invalidOut] = self::execute(array_merge(['--json', 'view:inspect', '--apply'], $arguments));
        self::assertSame(2, $invalidStatus);
        self::assertStringNotContainsString($root, $invalidOut);
        self::assertSame([], self::entries($output));
    }

    /** @return array{string, string, string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-view-inspect-command-' . bin2hex(random_bytes(12));
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
