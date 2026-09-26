<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Tests\Unit;

use Kongono\ConsoleApplication\KongoApplicationFactory;
use Kongono\ConsoleApplication\Process\NativeKongoEntrypoint;
use PHPUnit\Framework\TestCase;

final class StorageApplicationCommandTest extends TestCase
{
    public function testProcessRoutePlansAppliesAndChecksWithoutDisclosingInputs(): void
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-storage-command-' . bin2hex(random_bytes(8));
        $output = $root . '/profiles';
        self::assertTrue(mkdir($root));
        self::assertTrue(mkdir($output));
        $marker = 'private-storage-marker-' . bin2hex(random_bytes(8));
        $source = '{"schema":"kongono/storage-application-profile-source/1","profiles":[{"name":"' . $marker . '","contract_version":1,"namespace":"private-objects","capabilities":["read"],"digest_algorithm":null,"limits":{"max_chunk_bytes":1024,"max_object_bytes":4096,"max_list_page_size":10}}]}';
        self::assertSame(strlen($source), file_put_contents($root . '/source.json', $source));
        $inputs = ['--application-root=' . $root, '--output-directory=' . $output, '--source-file=source.json', '--no-interaction'];
        try {
            [$planStatus, $plan] = self::executeProcess(array_merge(['--json', 'storage:plan'], $inputs));
            [$buildPlanStatus, $buildPlan] = self::executeProcess(array_merge(['--json', 'storage:build', '--plan'], $inputs));
            self::assertSame(0, $planStatus);
            self::assertSame(0, $buildPlanStatus);
            self::assertStringContainsString('publication_required', $plan);
            self::assertStringContainsString('publication_required', $buildPlan);
            self::assertSame([], array_values(array_diff((array) scandir($output), ['.', '..'])));

            [$checkMissingStatus, $checkMissing] = self::executeProcess(array_merge(['--json', 'storage:check'], $inputs));
            self::assertSame(3, $checkMissingStatus);
            [$applyStatus, $apply] = self::executeProcess(array_merge(['--json', 'storage:build', '--apply'], $inputs));
            [$checkStatus, $check] = self::executeProcess(array_merge(['--json', 'storage:check'], $inputs));
            [$humanStatus, $human] = self::executeProcess(array_merge(['storage:check'], $inputs));
            self::assertSame(0, $applyStatus);
            self::assertSame(0, $checkStatus);
            self::assertSame(0, $humanStatus);
            self::assertStringContainsString('current', $apply);
            self::assertStringContainsString('current', $check);
            self::assertStringContainsString('Current', $human);

            [$missingIntentStatus, $missingIntent] = self::executeProcess(array_merge(['--json', 'storage:build'], $inputs));
            [$extraInputStatus, $extraInput] = self::executeProcess(array_merge(['--json', 'storage:plan', '--unexpected=' . $marker], $inputs));
            self::assertSame(2, $missingIntentStatus);
            self::assertSame(2, $extraInputStatus);
            foreach ([$plan, $buildPlan, $checkMissing, $apply, $check, $human, $missingIntent, $extraInput] as $bytes) {
                self::assertStringNotContainsString($root, $bytes);
                self::assertStringNotContainsString($marker, $bytes);
                self::assertStringNotContainsString('private-objects', $bytes);
            }
        } finally {
            self::removePrivate($root);
        }
    }

    /**
     * @param list<string> $arguments
     * @return array{int,string}
     */
    private static function executeProcess(array $arguments): array
    {
        $stdout = fopen('php://memory', 'w+b');
        $stderr = fopen('php://memory', 'w+b');
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);
        try {
            $status = (new NativeKongoEntrypoint((new KongoApplicationFactory())->create()))->run($arguments, $stdout, $stderr);
            self::assertSame(0, fseek($stdout, 0));
            self::assertSame(0, fseek($stderr, 0));
            return [$status, (string) stream_get_contents($stdout) . (string) stream_get_contents($stderr)];
        } finally {
            fclose($stdout);
            fclose($stderr);
        }
    }

    private static function removePrivate(string $root): void
    {
        $base = realpath($root);
        self::assertIsString($base);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) {
            self::assertInstanceOf(\SplFileInfo::class, $entry);
            $path = realpath($entry->getPathname());
            self::assertIsString($path);
            self::assertStringStartsWith($base . DIRECTORY_SEPARATOR, $path);
            if ($entry->isDir()) {
                self::assertTrue(rmdir($path));
            } else {
                self::assertTrue(unlink($path));
            }
        }
        self::assertTrue(rmdir($base));
    }
}
