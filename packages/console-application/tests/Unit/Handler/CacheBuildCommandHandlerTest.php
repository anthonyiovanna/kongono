<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class CacheBuildCommandHandlerTest extends TestCase
{
    private ?string $root = null;
    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testExplicitPlanApplyAndIdempotentApply(): void
    {
        [$root, $output] = $this->application();
        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=cache.json'];
        [$planStatus, $plan] = self::execute(array_merge(['--json', 'cache:build', '--plan'], $inputs));
        self::assertSame(0, $planStatus);
        self::assertStringContainsString('"value":"plan"', $plan);
        self::assertSame([], array_values(array_diff((array) scandir($output), ['.', '..'])));
        [$applyStatus, $apply] = self::execute(array_merge(['--json', 'cache:build', '--apply'], $inputs));
        [$repeatStatus, $repeat] = self::execute(array_merge(['--json', 'cache:build', '--apply'], $inputs));
        self::assertSame(0, $applyStatus);
        self::assertSame(0, $repeatStatus);
        self::assertSame($apply, $repeat);
        self::assertStringContainsString('"current"', $apply);
        self::assertStringNotContainsString($root, $apply);
        [$invalidStatus, $invalid] = self::execute(['--json', 'cache:build', '--apply']);
        self::assertSame(2, $invalidStatus);
        self::assertStringContainsString('KGN-CONSOLE-', $invalid);
    }

    /** @return array{string,string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-build-command-' . bin2hex(random_bytes(8));
        mkdir($root . '/var/cache', 0777, true);
        file_put_contents($root . '/cache.json', self::source());
        $this->root = $root;
        return [$root, $root . '/var/cache'];
    }
    private static function source(): string
    {
        return "{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[{\"name\":\"application\",\"contract_version\":1,\"namespace\":\"application\",\"codec\":{\"name\":\"application-json\",\"version\":1},\"lifetime_microseconds\":1000000,\"max_value_bytes\":1024}]}\n";
    }
    /**
     * @param list<string> $arguments
     * @return array{int,string}
     */
    private static function execute(array $arguments): array
    {
        $out = self::port();
        $err = self::port();
        $status = (new KongoApplicationFactory())->create()->run(new KongoProcessRequest($arguments), $out, $err);
        return [$status, $out->bytes . $err->bytes];
    }
    /** @return CommandOutputPort&object{bytes:string} */
    private static function port(): CommandOutputPort
    {
        return new class implements CommandOutputPort {
            public string $bytes = '';
            public function write(string $bytes): void
            {
                $this->bytes .= $bytes;
            }
        };
    }
    private static function remove(string $root): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                throw new \RuntimeException('Invalid inventory.');
            } $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        } rmdir($root);
    }
}
