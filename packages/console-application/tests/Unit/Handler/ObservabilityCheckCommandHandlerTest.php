<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\ObservabilityBuild\Workflow\ObservabilityBuildWorkflow;
use PHPUnit\Framework\TestCase;

final class ObservabilityCheckCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::removeTree($this->root);
        }
    }

    public function testCheckIsWriteFreeAndRequiresExactCurrentEvidence(): void
    {
        [$root, $output] = $this->application();
        [$missingStatus, $missingOut, $missingError] = self::execute([
            '--json',
            'observability:check',
            '--application-root=' . $root,
            '--output-directory=' . $output,
        ]);
        self::assertSame(3, $missingStatus);
        self::assertSame('', $missingError);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $missingOut);
        self::assertSame([], self::entries($output));

        $current = (new ObservabilityBuildWorkflow())->build($root, $output);
        $before = self::identities($output);
        [$status, $out, $error] = self::execute([
            '--json',
            'observability:check',
            '--application-root=' . $root,
            '--output-directory=' . $output,
            '--no-interaction',
        ]);
        self::assertSame(0, $status);
        self::assertSame('', $error);
        self::assertStringContainsString($current->artifactSha256(), $out);
        self::assertStringContainsString($current->sourceSha256(), $out);
        self::assertStringContainsString('kongono.opentelemetry.otlp-http-protobuf.v1', $out);
        self::assertStringContainsString('"name":"current","kind":"boolean","value":true', $out);
        self::assertStringNotContainsString($root, $out);
        self::assertStringNotContainsString('sentinel-service', $out);
        self::assertSame($before, self::identities($output));
    }

    public function testTamperedEvidenceRejectsWithoutRepairOrDisclosure(): void
    {
        [$root, $output] = $this->application();
        $current = (new ObservabilityBuildWorkflow())->build($root, $output);
        $artifact = $output . '/' . $current->artifactBasename();
        self::assertSame(9, file_put_contents($artifact, "tampered\n"));
        $before = self::identities($output);

        [$status, $out, $error] = self::execute([
            '--json',
            'observability:check',
            '--application-root=' . $root,
            '--output-directory=' . $output,
        ]);
        self::assertSame(3, $status);
        self::assertSame('', $error);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $out);
        self::assertStringNotContainsString($root, $out);
        self::assertStringNotContainsString('tampered', $out);
        self::assertSame($before, self::identities($output));
        self::assertSame("tampered\n", file_get_contents($artifact));
    }

    /** @return array{string, string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-observability-check-command-' . bin2hex(random_bytes(12));
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

    /** @return array<string, string> */
    private static function identities(string $directory): array
    {
        $identities = [];
        foreach (self::entries($directory) as $path) {
            $sha256 = hash_file('sha256', $path);
            self::assertIsString($sha256);
            $identities[basename($path)] = $sha256;
        }
        ksort($identities);

        return $identities;
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
