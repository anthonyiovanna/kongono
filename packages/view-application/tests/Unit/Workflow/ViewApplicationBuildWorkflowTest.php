<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Tests\Unit\Workflow;

use Kongono\ViewApplication\Definition\ViewApplicationDefinition;
use Kongono\ViewApplication\Exception\ExceptionInterface;
use Kongono\ViewApplication\Exception\UnsafeViewSourceException;
use Kongono\ViewApplication\Exception\ViewApplicationWorkflowException;
use Kongono\ViewApplication\Internal\ViewApplicationPathGuard;
use Kongono\ViewApplication\Tests\Fixture\ViewApplicationFixture;
use Kongono\ViewApplication\Workflow\ViewApplicationBuildWorkflow;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class ViewApplicationBuildWorkflowTest extends TestCase
{
    public function testPlanIsWriteFreeAndBuildIsExactlyCurrent(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $before = $this->tree($fixture['root']);
            $plan = $workflow->plan($fixture['definition']);
            self::assertTrue($plan->publicationRequired());
            self::assertSame($before, $this->tree($fixture['root']));

            $current = $workflow->build($fixture['definition']);
            self::assertSame($plan->compiledViewIdentity()->value(), $current->compiledViewIdentity()->value());
            self::assertFalse($workflow->plan($fixture['definition'])->publicationRequired());
            self::assertSame($current->artifactDirectory(), $workflow->check($fixture['definition'])->artifactDirectory());
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testIdenticalRebuildIsIdempotentAndCheckIsWriteFree(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $first = $workflow->build($fixture['definition']);
            $before = $this->tree($fixture['root']);
            $second = $workflow->build($fixture['definition']);
            self::assertSame($first->compiledViewIdentity()->value(), $second->compiledViewIdentity()->value());
            self::assertSame($before, $this->tree($fixture['root']));
            $workflow->check($fixture['definition']);
            self::assertSame($before, $this->tree($fixture['root']));
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testChangedSourceSelectsOnlyTheNewContentAddressedTarget(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $first = $workflow->build($fixture['definition']);
            $changed = str_replace('"max_bytes":64', '"max_bytes":63', ViewApplicationFixture::sourceBytes());
            self::assertNotSame(ViewApplicationFixture::sourceBytes(), $changed);
            self::assertSame(strlen($changed), file_put_contents($fixture['source'], $changed));

            $plan = $workflow->plan($fixture['definition']);
            self::assertTrue($plan->publicationRequired());
            self::assertNotSame($first->sourceIdentity()->value(), $plan->sourceIdentity()->value());
            self::assertNotSame($first->compiledViewIdentity()->value(), $plan->compiledViewIdentity()->value());
            self::assertNotSame($first->artifactBasename(), $plan->artifactBasename());
            self::assertDirectoryExists($first->artifactDirectory());
            $second = $workflow->build($fixture['definition']);
            self::assertSame($plan->artifactBasename(), $second->artifactBasename());
            self::assertSame($second->artifactDirectory(), $workflow->check($fixture['definition'])->artifactDirectory());
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testMissingPartialTamperedRenamedExtraAndStaleOutputFailClosedWithoutMutation(): void
    {
        foreach (['missing', 'partial', 'tampered', 'renamed', 'extra', 'stale'] as $mode) {
            $fixture = ViewApplicationFixture::create();
            try {
                $workflow = new ViewApplicationBuildWorkflow();
                if ('missing' !== $mode) {
                    $current = $workflow->build($fixture['definition']);
                    if ('partial' === $mode) {
                        unlink($current->artifactDirectory() . '/compiled.php');
                    } elseif ('tampered' === $mode) {
                        file_put_contents($current->artifactDirectory() . '/artifact.json', "tampered\n");
                    } elseif ('renamed' === $mode) {
                        rename($current->artifactDirectory(), $current->artifactDirectory() . '-renamed');
                    } elseif ('extra' === $mode) {
                        file_put_contents($current->artifactDirectory() . '/extra', 'private');
                    } else {
                        file_put_contents($fixture['source'], str_replace('"max_bytes":64', '"max_bytes":63', ViewApplicationFixture::sourceBytes()));
                    }
                }
                clearstatcache(true);
                $before = $this->tree($fixture['root']);
                $this->expectFailure(static fn() => $workflow->check($fixture['definition']), $fixture['root']);
                clearstatcache(true);
                self::assertSame($before, $this->tree($fixture['root']), $mode);
            } finally {
                ViewApplicationFixture::cleanup($fixture);
            }
        }
    }

    public function testMissingMalformedOversizedOutsideAndHardLinkedSourceFailWithoutPublication(): void
    {
        foreach (['missing', 'malformed', 'oversized', 'outside', 'hard_linked'] as $mode) {
            $fixture = ViewApplicationFixture::create();
            $outside = dirname($fixture['root']) . '/kongono-view-outside-' . bin2hex(random_bytes(8)) . '.json';
            try {
                $definition = $fixture['definition'];
                if ('missing' === $mode) {
                    unlink($fixture['source']);
                } elseif ('malformed' === $mode) {
                    file_put_contents($fixture['source'], "{}\n");
                } elseif ('oversized' === $mode) {
                    file_put_contents($fixture['source'], str_repeat('x', ViewApplicationPathGuard::MAX_SOURCE_BYTES + 1));
                } elseif ('outside' === $mode) {
                    file_put_contents($outside, ViewApplicationFixture::sourceBytes());
                } else {
                    $this->replaceWithHardLink($fixture['source'], $outside);
                }
                if ('outside' === $mode) {
                    $definition = new ViewApplicationDefinition($fixture['root'], str_replace('\\', '/', $outside), $fixture['output']);
                }
                clearstatcache(true);
                $before = $this->tree($fixture['root']);
                $this->expectFailure(static fn() => (new ViewApplicationBuildWorkflow())->plan($definition), $fixture['root']);
                clearstatcache(true);
                self::assertSame($before, $this->tree($fixture['root']), $mode);
                self::assertSame([], glob($fixture['output'] . '/*'));
            } finally {
                if (is_file($outside)) {
                    self::assertTrue(unlink($outside));
                }
                ViewApplicationFixture::cleanup($fixture);
            }
        }
    }

    public function testLinkedOutputRootFailsWithoutPublication(): void
    {
        $fixture = ViewApplicationFixture::create();
        $target = $fixture['root'] . '/var/linked-target';
        $linked = $fixture['root'] . '/var/linked-output';
        try {
            self::assertTrue(mkdir($target, 0700));
            self::assertTrue(self::createDirectoryLink($target, $linked));
            $definition = new ViewApplicationDefinition($fixture['root'], $fixture['source'], str_replace('\\', '/', $linked));
            $this->expectFailure(static fn() => (new ViewApplicationBuildWorkflow())->plan($definition), $fixture['root']);
            self::assertSame([], glob($target . '/*'));
        } finally {
            if (is_dir($linked)) {
                self::assertTrue(rmdir($linked));
            }
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testSourceChangingDuringReadFailsClosedAndLeavesNoProcessOrResidue(): void
    {
        $fixture = ViewApplicationFixture::create();
        $marker = $fixture['root'] . '/writer-ready';
        $original = str_repeat(' ', ViewApplicationPathGuard::MAX_SOURCE_BYTES - strlen(ViewApplicationFixture::sourceBytes())) . ViewApplicationFixture::sourceBytes();
        self::assertSame(strlen($original), file_put_contents($fixture['source'], $original));
        $process = $this->changingWriter($fixture['source'], $marker);
        try {
            $this->expectFailure(static fn() => (new ViewApplicationBuildWorkflow())->plan($fixture['definition']), $fixture['root']);
        } finally {
            $this->stopWriter($process);
            if (is_file($marker)) {
                self::assertTrue(unlink($marker));
            }
            self::assertSame([], glob($fixture['output'] . '/*'));
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testOutputChangingDuringReadFailsClosedAndLeavesNoProcessOrTemporaryResidue(): void
    {
        $fixture = ViewApplicationFixture::create();
        $marker = $fixture['root'] . '/writer-ready';
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $current = $workflow->build($fixture['definition']);
            $artifact = $current->artifactDirectory() . '/artifact.json';
            $writer = $this->changingWriter($artifact, $marker);
            try {
                $this->expectFailure(static fn() => $workflow->plan($fixture['definition']), $fixture['root']);
            } finally {
                $this->stopWriter($writer);
                if (is_file($marker)) {
                    self::assertTrue(unlink($marker));
                }
            }
            self::assertCount(2, $this->tree($current->artifactDirectory()));
            self::assertSame([], array_values(array_filter(
                array_keys($this->tree($fixture['output'])),
                static fn(string $path): bool => str_contains(basename($path), '.tmp'),
            )));
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testPlanAndCheckNeverExecuteGeneratedPhp(): void
    {
        $fixture = ViewApplicationFixture::create();
        $marker = $fixture['root'] . '/generated-executed';
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $current = $workflow->build($fixture['definition']);
            $generated = "<?php file_put_contents(" . var_export($marker, true) . ", 'executed');\n";
            self::assertSame(strlen($generated), file_put_contents($current->artifactDirectory() . '/compiled.php', $generated));
            $this->expectFailure(static fn() => $workflow->check($fixture['definition']), $fixture['root']);
            self::assertFileDoesNotExist($marker);
            $this->expectFailure(static fn() => $workflow->plan($fixture['definition']), $fixture['root']);
            self::assertFileDoesNotExist($marker);
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testNoClobberConflictFailsWithoutDisclosure(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            $workflow = new ViewApplicationBuildWorkflow();
            $plan = $workflow->plan($fixture['definition']);
            $directory = $fixture['output'] . '/' . $plan->artifactBasename();
            self::assertTrue(mkdir($directory, 0700));
            self::assertSame(8, file_put_contents($directory . '/compiled.php', 'private!'));
            try {
                $workflow->build($fixture['definition']);
                self::fail('Expected the conflicting artifact to fail closed.');
            } catch (ViewApplicationWorkflowException $failure) {
                self::assertSame('KGN-VIEW-APPLICATION-1852', $failure->diagnosticId());
                self::assertStringNotContainsString($fixture['root'], (string) $failure);
                self::assertStringNotContainsString('private', (string) $failure);
            }
            self::assertSame('private!', file_get_contents($directory . '/compiled.php'));
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    public function testUnsafeSourceFramingFailsClosed(): void
    {
        $fixture = ViewApplicationFixture::create();
        try {
            self::assertSame(2, file_put_contents($fixture['source'], "{}"));
            $this->expectException(UnsafeViewSourceException::class);
            (new ViewApplicationBuildWorkflow())->plan($fixture['definition']);
        } finally {
            ViewApplicationFixture::cleanup($fixture);
        }
    }

    private function expectFailure(callable $operation, string $private): void
    {
        try {
            $operation();
            self::fail('Expected the workflow to fail closed.');
        } catch (ExceptionInterface $failure) {
            self::assertStringStartsWith('KGN-VIEW-APPLICATION-', $failure->diagnosticId());
            self::assertStringNotContainsString($private, (string) $failure);
        }
    }

    private function replaceWithHardLink(string $source, string $outside): bool
    {
        self::assertSame(strlen(ViewApplicationFixture::sourceBytes()), file_put_contents($outside, ViewApplicationFixture::sourceBytes()));
        self::assertTrue(unlink($source));

        return link($outside, $source);
    }

    private static function createDirectoryLink(string $target, string $link): bool
    {
        if ('Windows' !== PHP_OS_FAMILY) {
            return symlink($target, $link);
        }
        $pipes = [];
        $command = "New-Item -ItemType Junction -Path '" . str_replace("'", "''", $link) . "' -Target '" . str_replace("'", "''", $target) . "' | Out-Null";
        $process = proc_open(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command', $command], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return 0 === proc_close($process) && is_dir($link);
    }

    /** @return array{resource, array<int, resource>} */
    private function changingWriter(string $path, string $marker): array
    {
        $script = <<<'PHP'
$path = $argv[1];
$marker = $argv[2];
$original = file_get_contents($path);
if (!is_string($original)) { exit(9); }
file_put_contents($path, "tampered\n");
file_put_contents($marker, "ready\n");
$until = microtime(true) + 2.0;
while (microtime(true) < $until) {
    file_put_contents($path, $original);
    file_put_contents($path, "tampered\n");
}
PHP;
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-r', $script, $path, $marker], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        self::assertTrue(fclose($pipes[0]));
        for ($attempt = 0; $attempt < 200 && !is_file($marker); ++$attempt) {
            usleep(10_000);
        }
        self::assertFileExists($marker);

        return [$process, $pipes];
    }

    /** @param array{resource, array<int, resource>} $writer */
    private function stopWriter(array $writer): void
    {
        [$process, $pipes] = $writer;
        proc_terminate($process);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        self::assertTrue(fclose($pipes[1]));
        self::assertTrue(fclose($pipes[2]));
        proc_close($process);
    }

    /** @return array<string,string> */
    private function tree(string $root): array
    {
        $tree = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                self::fail('Fixture entry must be a file descriptor.');
            }
            if ($entry->isFile()) {
                $path = str_replace('\\', '/', $entry->getPathname());
                $sha256 = hash_file('sha256', $path);
                self::assertIsString($sha256);
                $tree[substr($path, strlen($root) + 1)] = $sha256;
            }
        }
        ksort($tree, SORT_STRING);

        return $tree;
    }
}
