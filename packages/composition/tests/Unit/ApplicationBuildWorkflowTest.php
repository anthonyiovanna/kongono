<?php

declare(strict_types=1);

namespace Kongono\Composition\Tests\Unit;

use Kongono\Composition\Exception\ApplicationWorkflowException;
use Kongono\Composition\Workflow\ApplicationBuildWorkflow;
use Kongono\Composition\Workflow\CurrentApplicationBuild;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApplicationBuildWorkflowTest extends TestCase
{
    private static string $root;
    private static string $output;

    public static function setUpBeforeClass(): void
    {
        self::$root = self::temporaryDirectory();
        self::$output = self::$root . '/var/kongono/build';
        self::assertTrue(mkdir(self::$root . '/src', 0700, true));
        self::assertTrue(mkdir(self::$output, 0700, true));
        foreach (['WorkflowInput.php', 'WorkflowRoot.php'] as $name) {
            $target = self::$root . '/src/' . $name;
            self::assertTrue(copy(dirname(__DIR__) . '/Fixture/' . $name, $target));
            require $target;
        }
        self::write(self::$root . '/composer.json', "{\"name\":\"example/workflow-test\"}\n");
        self::write(self::$root . '/composer.lock', "{\"packages\":[]}\n");
        self::writeSource(false);
    }

    public static function tearDownAfterClass(): void
    {
        self::removeTree(self::$root);
    }

    protected function tearDown(): void
    {
        self::writeSource(false);
        foreach (self::files(self::$output) as $path) {
            self::assertTrue(unlink($path));
        }
    }

    public function testPublicSurfaceIsExactAndImmutable(): void
    {
        $workflow = new ReflectionClass(ApplicationBuildWorkflow::class);
        $methods = array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            array_filter($workflow->getMethods(\ReflectionMethod::IS_PUBLIC), static fn(\ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === ApplicationBuildWorkflow::class),
        );
        sort($methods, SORT_STRING);
        self::assertSame(['build', 'check'], $methods);
        self::assertTrue($workflow->isFinal());

        $result = new ReflectionClass(CurrentApplicationBuild::class);
        self::assertTrue($result->isFinal());
        self::assertTrue($result->isReadOnly());
        $resultMethods = array_values(array_filter(array_map(
            static fn(\ReflectionMethod $method): string => $method->getName(),
            $result->getMethods(\ReflectionMethod::IS_PUBLIC),
        ), static fn(string $name): bool => '__construct' !== $name));
        sort($resultMethods, SORT_STRING);
        self::assertSame(
            ['artifactSha256', 'compositionArtifactBytes', 'compositionArtifactPath', 'generatedPhpPath', 'generatedWiringSha256'],
            $resultMethods,
        );
    }

    public function testBuildCheckAndIdenticalRebuildReturnTheSameCurrentResult(): void
    {
        $workflow = new ApplicationBuildWorkflow();
        $first = $workflow->build(self::$root, self::$output);
        $check = $workflow->check(self::$root, self::$output);
        $second = $workflow->build(self::$root, self::$output);

        self::assertSame(self::resultIdentity($first), self::resultIdentity($check));
        self::assertSame(self::resultIdentity($first), self::resultIdentity($second));
        self::assertCount(2, self::files(self::$output));
        self::assertSame($first->compositionArtifactBytes(), file_get_contents($first->compositionArtifactPath()));
        self::assertStringEndsWith($first->artifactSha256() . '.json', $first->compositionArtifactPath());
        self::assertStringEndsWith($first->generatedWiringSha256() . '.php', $first->generatedPhpPath());
    }

    public function testCheckPerformsNoFilesystemMutation(): void
    {
        $workflow = new ApplicationBuildWorkflow();
        $workflow->build(self::$root, self::$output);
        clearstatcache(true);
        $before = self::treeIdentity(self::$root);
        $workflow->check(self::$root, self::$output);
        clearstatcache(true);

        self::assertSame($before, self::treeIdentity(self::$root));
    }

    public function testChangedAdmittedBytesPublishNewIdentitiesWithoutSelectingOldOutput(): void
    {
        $workflow = new ApplicationBuildWorkflow();
        $first = $workflow->build(self::$root, self::$output);
        self::writeSource(true);
        $second = $workflow->build(self::$root, self::$output);

        self::assertNotSame($first->artifactSha256(), $second->artifactSha256());
        self::assertNotSame($first->generatedWiringSha256(), $second->generatedWiringSha256());
        self::assertFileExists($first->compositionArtifactPath());
        self::assertFileExists($first->generatedPhpPath());
        self::assertCount(4, self::files(self::$output));
        self::assertSame(self::resultIdentity($second), self::resultIdentity($workflow->check(self::$root, self::$output)));
    }

    public function testMissingPartialTamperedAndOutsideOutputFailClosed(): void
    {
        $workflow = new ApplicationBuildWorkflow();
        $current = $workflow->build(self::$root, self::$output);
        self::assertTrue(unlink($current->generatedPhpPath()));
        $this->expectWorkflowFailure(static fn(): CurrentApplicationBuild => $workflow->check(self::$root, self::$output));

        $current = $workflow->build(self::$root, self::$output);
        self::write($current->compositionArtifactPath(), "tampered\n");
        $this->expectWorkflowFailure(static fn(): CurrentApplicationBuild => $workflow->check(self::$root, self::$output));

        $this->expectWorkflowFailure(static fn(): CurrentApplicationBuild => $workflow->check(self::$root, dirname(self::$root)));
    }

    public function testDiagnosticIsBoundedAndProductionDoesNotScanOrMaintainPointers(): void
    {
        $exception = new ApplicationWorkflowException('sentinel-stage', 'sentinel-violation', new \RuntimeException('native-secret'));
        self::assertSame('KGN-COMPOSITION-1018', $exception->diagnosticId());
        self::assertSame('Application workflow failed', $exception->diagnosticTitle());
        self::assertStringContainsString('app:build', $exception->diagnosticRemediation());
        $serialized = json_encode($exception->diagnosticContext(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('sentinel', $serialized);
        self::assertStringNotContainsString('native-secret', (string) $exception);

        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Workflow/ApplicationBuildWorkflow.php');
        foreach (['scandir', 'glob(', 'DirectoryIterator', 'latest', 'symlink(', 'unlink(', 'rmdir('] as $forbidden) {
            self::assertStringNotContainsStringIgnoringCase($forbidden, $source);
        }
    }

    /** @return array<string, string> */
    private static function resultIdentity(CurrentApplicationBuild $result): array
    {
        return [
            'artifact_path' => $result->compositionArtifactPath(),
            'artifact_bytes' => $result->compositionArtifactBytes(),
            'artifact_sha256' => $result->artifactSha256(),
            'wiring_sha256' => $result->generatedWiringSha256(),
            'generated_path' => $result->generatedPhpPath(),
        ];
    }

    private function expectWorkflowFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Workflow operation must fail closed.');
        } catch (ApplicationWorkflowException $failure) {
            self::assertSame('KGN-COMPOSITION-1018', $failure->diagnosticId());
        }
    }

    private static function writeSource(bool $alternate): void
    {
        $source = [
            'schema' => 'kongono.composition',
            'version' => 1,
            'inputs' => [['contract' => 'Kongono\\Composition\\Tests\\Fixture\\WorkflowInput']],
            'services' => [[
                'class' => 'Kongono\\Composition\\Tests\\Fixture\\WorkflowRoot',
                'lifetime' => 'application',
                'arguments' => [['parameter' => 'input', 'input' => 'Kongono\\Composition\\Tests\\Fixture\\WorkflowInput']],
                'cleanup' => null,
            ]],
            'bindings' => [],
            'roots' => [['kind' => 'application', 'contract' => 'Kongono\\Composition\\Tests\\Fixture\\WorkflowRoot']],
        ];
        $bytes = json_encode($source, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        self::write(self::$root . '/kongono.composition.json', $bytes . ($alternate ? " \n" : "\n"));
    }

    /** @return list<string> */
    private static function files(string $directory): array
    {
        $entries = scandir($directory);
        self::assertIsArray($entries);
        $files = [];
        foreach ($entries as $entry) {
            if ('.' !== $entry && '..' !== $entry) {
                $files[] = $directory . '/' . $entry;
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /** @return array<string, array{bytes: int, sha256: string, mtime: int, ctime: int}> */
    private static function treeIdentity(string $directory): array
    {
        $identity = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            $bytes = file_get_contents($path);
            $stat = stat($path);
            self::assertIsString($bytes);
            self::assertIsArray($stat);
            $identity[substr($path, strlen($directory) + 1)] = [
                'bytes' => strlen($bytes),
                'sha256' => hash('sha256', $bytes),
                'mtime' => $stat['mtime'],
                'ctime' => $stat['ctime'],
            ];
        }
        ksort($identity, SORT_STRING);

        return $identity;
    }

    private static function temporaryDirectory(): string
    {
        $path = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-workflow-' . bin2hex(random_bytes(12));
        self::assertTrue(mkdir($path, 0700, true));

        return $path;
    }

    private static function write(string $path, string $bytes): void
    {
        self::assertSame(strlen($bytes), file_put_contents($path, $bytes));
    }

    private static function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $entries = scandir($directory);
        self::assertIsArray($entries);
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path) && !is_link($path)) {
                self::removeTree($path);
            } else {
                self::assertTrue(unlink($path));
            }
        }
        self::assertTrue(rmdir($directory));
    }
}
