<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class ObservabilityInspectCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::removeTree($this->root);
        }
    }

    public function testInspectIsReadOnlyAndHumanMachineFormsShareBoundedEvidence(): void
    {
        [$root, $output] = $this->application();
        $before = self::entries($output);
        [$machineStatus, $machineOut, $machineError] = self::execute([
            '--json',
            'observability:inspect',
            '--application-root=' . $root,
            '--output-directory=' . $output,
            '--no-interaction',
        ]);

        self::assertSame(0, $machineStatus);
        self::assertSame('', $machineError);
        self::assertSame($before, self::entries($output));
        $machine = json_decode($machineOut, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($machine);
        self::assertSame('succeeded', $machine['outcome']);
        self::assertStringContainsString('kongono.opentelemetry.otlp-http-protobuf.v1', $machineOut);
        self::assertStringContainsString('publication_required', $machineOut);

        [$humanStatus, $humanOut, $humanError] = self::execute([
            'observability:inspect',
            '--application-root=' . $root,
            '--output-directory=' . $output,
        ]);
        self::assertSame(0, $humanStatus);
        self::assertSame('', $humanError);
        self::assertStringContainsString('kongono.opentelemetry.otlp-http-protobuf.v1', $humanOut);
        self::assertSame($before, self::entries($output));
        foreach ([$machineOut, $humanOut] as $document) {
            self::assertStringNotContainsString($root, $document);
            self::assertStringNotContainsString('sentinel-service', $document);
            self::assertStringNotContainsString('queue_capacity', $document);
            self::assertStringNotContainsString('maximum_batch_size', $document);
        }
    }

    public function testInspectRejectsMutationIntentWithoutWriting(): void
    {
        [$root, $output] = $this->application();
        foreach (['--plan', '--apply'] as $intent) {
            [$status, $out, $error] = self::execute([
                '--json',
                'observability:inspect',
                $intent,
                '--application-root=' . $root,
                '--output-directory=' . $output,
            ]);
            self::assertSame(2, $status);
            self::assertSame('', $error);
            self::assertStringContainsString('KGN-CONSOLE-', $out);
            self::assertSame([], self::entries($output));
        }
    }

    /** @return array{string, string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-observability-inspect-command-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/var/build', 0700, true));
        $source = <<<'JSON'
{"schema":"kongono.observability","version":1,"profile":"kongono.opentelemetry.otlp-http-protobuf.v1","resource":{"service_name":"sentinel-service","service_version":null,"deployment_environment_name":null},"batch":{"queue_capacity":256,"maximum_batch_size":64,"transport_timeout_milliseconds":1000}}
JSON;
        $source .= "\n";
        self::assertSame(strlen($source), file_put_contents($root . '/kongono.observability.json', $source));
        $this->root = $root;

        return [$root, $root . '/var/build'];
    }

    /** @param list<string> $arguments
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
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }
        rmdir($root);
    }
}
