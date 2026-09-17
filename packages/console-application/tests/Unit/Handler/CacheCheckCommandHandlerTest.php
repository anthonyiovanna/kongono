<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit\Handler;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\KongoProcessRequest;
use Kongono\ConsolePresentation\CommandOutputPort;
use PHPUnit\Framework\TestCase;

final class CacheCheckCommandHandlerTest extends TestCase
{
    private ?string $root = null;
    protected function tearDown(): void
    {
        if (null !== $this->root) {
            self::remove($this->root);
        }
    }

    public function testCheckRequiresExactCurrentEvidenceAndIsReadOnly(): void
    {
        [$root, $output] = $this->application();
        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=cache.json'];
        [$missingStatus, $missing] = self::execute(array_merge(['--json', 'cache:check'], $inputs));
        self::assertSame(3, $missingStatus);
        self::assertStringContainsString('KGN-CONSOLE-APPLICATION-1002', $missing);
        [$buildStatus] = self::execute(array_merge(['--json', 'cache:build', '--apply'], $inputs));
        self::assertSame(0, $buildStatus);
        $before = self::inventory($output);
        [$jsonStatus, $json] = self::execute(array_merge(['--json', 'cache:check'], $inputs));
        [$humanStatus, $human] = self::execute(array_merge(['cache:check'], $inputs));
        self::assertSame(0, $jsonStatus);
        self::assertSame(0, $humanStatus);
        self::assertSame($before, self::inventory($output));
        self::assertStringContainsString('"current"', $json);
        self::assertStringContainsString('Current: true', $human);
        self::assertStringNotContainsString($root, $json . $human);
    }

    /** @return array{string,string} */
    private function application(): array
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-cache-check-command-' . bin2hex(random_bytes(8));
        mkdir($root . '/var/cache', 0777, true);
        file_put_contents($root . '/cache.json', self::source());
        $this->root = $root;
        return [$root, $root . '/var/cache'];
    }
    private static function source(): string
    {
        return "{\"schema\":\"kongono/cache-application-profile-source/1\",\"profiles\":[{\"name\":\"application\",\"contract_version\":1,\"namespace\":\"application\",\"codec\":{\"name\":\"application-json\",\"version\":1},\"lifetime_microseconds\":1000000,\"max_value_bytes\":1024}]}\n";
    }
    /** @return list<string> */
    private static function inventory(string $root): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $entry) {
            if ($entry instanceof \SplFileInfo && $entry->isFile()) {
                $files[] = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1)) . '|' . hash_file('sha256', $entry->getPathname());
            }
        } sort($files, SORT_STRING);
        return $files;
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
