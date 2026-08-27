<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use Kongono\SecurityPolicyBuild\Workflow\SecurityPolicyBuildWorkflow;
use PHPUnit\Framework\TestCase;

final class SecurityPolicyCommandHandlerTest extends TestCase
{
    private ?string $root = null;

    protected function tearDown(): void
    {
        if (null === $this->root) {
            return;
        }
        $artifacts = glob($this->root . '/var/build/*.json');
        self::assertIsArray($artifacts);
        foreach ($artifacts as $path) {
            unlink($path);
        }
        rmdir($this->root . '/var/build');
        rmdir($this->root . '/var');
        unlink($this->root . '/kongono.security-policy.json');
        rmdir($this->root);
    }

    public function testInspectIsReadOnlyAndCheckRequiresExactCurrentArtifact(): void
    {
        [$root, $output] = $this->application();
        [$inspectStatus, $inspectOut, $inspectError] = self::execute([
            '--json',
            'security:inspect',
            '--application-root=' . $root,
            '--output-directory=' . $output,
            '--no-interaction',
        ]);

        self::assertSame(0, $inspectStatus);
        self::assertSame('', $inspectError);
        $inspect = json_decode($inspectOut, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($inspect);
        self::assertSame('succeeded', $inspect['outcome']);
        self::assertStringContainsString('publication_required', $inspectOut);
        self::assertSame([], glob($output . '/*.json'));

        [$missingStatus, $missingOut, $missingError] = self::execute([
            '--json',
            'security:check',
            '--application-root=' . $root,
            '--output-directory=' . $output,
        ]);
        self::assertSame(3, $missingStatus);
        self::assertSame('', $missingError);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $missingOut);

        $current = (new SecurityPolicyBuildWorkflow())->build($root, $output);
        [$checkStatus, $checkOut, $checkError] = self::execute([
            '--json',
            'security:check',
            '--application-root=' . $root,
            '--output-directory=' . $output,
        ]);
        self::assertSame(0, $checkStatus);
        self::assertSame('', $checkError);
        self::assertStringContainsString($current->artifactSha256(), $checkOut);
        self::assertStringContainsString('"name":"current","kind":"boolean","value":true', $checkOut);
        self::assertStringNotContainsString($root, $checkOut);
        self::assertStringNotContainsString('example.public', $checkOut);
    }

    public function testSecurityCommandsRejectMutationOptionsAsInvalidInput(): void
    {
        [$root, $output] = $this->application();
        foreach (['security:inspect', 'security:check'] as $command) {
            [$status, $out, $error] = self::execute([
                '--json',
                $command,
                '--plan',
                '--application-root=' . $root,
                '--output-directory=' . $output,
            ]);
            self::assertSame(2, $status);
            self::assertSame('', $error);
            self::assertStringContainsString('KGN-CONSOLE-', $out);
            self::assertSame([], glob($output . '/*.json'));
        }
    }

    /** @return array{string, string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-security-command-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($root . '/var/build', 0700, true));
        $source = '{"schema":"kongono.security-policy","version":1,"policy":{"name":"example.public","version":"v1","provenance":"application-owned"}}' . "\n";
        self::assertSame(strlen($source), file_put_contents($root . '/kongono.security-policy.json', $source));
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
}
