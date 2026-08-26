<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Unit\Workflow;

use FilesystemIterator;
use Kongono\DatabaseApplication\Exception\DataApplicationWorkflowException;
use Kongono\DatabaseApplication\Workflow\DataAccessBuildWorkflow;
use Kongono\DatabaseApplication\Workflow\PreparedDataAccessBuild;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DataAccessBuildWorkflowTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = self::root() . '/skeletons/persistence-minimal/var/kongono/data-workflow-test-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->output));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->output);
    }

    #[RunInSeparateProcess]
    public function testPreparationIsNonExecutingAndLoadPreservesCurrentBehavior(): void
    {
        $root = self::root() . '/skeletons/persistence-minimal';
        $repository = 'KongonoPersistenceMinimal\\GeneratedData\\AccountRepository';
        self::assertNotContains($repository, get_declared_classes());
        $workflow = new DataAccessBuildWorkflow();
        $prepared = $workflow->prepareBuild($root, 'kongono.data-mapping.json', $this->output);
        self::assertNotContains($repository, get_declared_classes());
        $before = $this->manifest($this->output);
        $checked = $workflow->prepareCheck($root, 'kongono.data-mapping.json', $this->output);
        self::assertSame($before, $this->manifest($this->output));
        self::assertNotContains($repository, get_declared_classes());
        self::assertSame($prepared->mappingArtifactSha256(), $checked->mappingArtifactSha256());
        self::assertSame($prepared->generatedDataAccessSha256(), $checked->generatedDataAccessSha256());

        $forged = new PreparedDataAccessBuild(
            $checked->applicationRoot(),
            $checked->mappingSourceRelativePath(),
            str_repeat('0', 64),
            $checked->generatedDataAccessSha256(),
            $checked->mappingSnapshot(),
            $checked->generatedSnapshot(),
        );
        try {
            $workflow->loadPrepared($forged);
            self::fail('Expected forged evidence rejection.');
        } catch (DataApplicationWorkflowException) {
            self::assertNotContains($repository, get_declared_classes());
        }

        $current = $workflow->loadPrepared($checked);
        self::assertContains($repository, get_declared_classes());
        self::assertSame($checked->mappingArtifactSha256(), $current->mappingArtifactSha256());
        self::assertSame($checked->generatedDataAccessSha256(), $current->generatedDataAccessSha256());
        $workflow->reverify($current);
    }

    #[RunInSeparateProcess]
    public function testPreparedSnapshotsRejectPathEscapeAndTamper(): void
    {
        $workflow = new DataAccessBuildWorkflow();
        $prepared = $workflow->prepareBuild(self::applicationRoot(), 'kongono.data-mapping.json', $this->output);
        $escaped = $prepared->generatedSnapshot();
        $escaped['absolute'] = sys_get_temp_dir() . '/escaped-generated.php';
        $this->expectWorkflowFailure(fn() => $workflow->loadPrepared(new PreparedDataAccessBuild(
            $prepared->applicationRoot(),
            $prepared->mappingSourceRelativePath(),
            $prepared->mappingArtifactSha256(),
            $prepared->generatedDataAccessSha256(),
            $prepared->mappingSnapshot(),
            $escaped,
        )));

        $generated = $prepared->generatedSnapshot();
        self::assertIsInt(file_put_contents($generated['absolute'], "\n// tampered\n", FILE_APPEND));
        $this->expectWorkflowFailure(fn() => $workflow->loadPrepared($prepared));
    }

    #[RunInSeparateProcess]
    public function testPreparedSnapshotsRejectStaleAndChangedEvidence(): void
    {
        $root = self::applicationRoot();
        $sourceDirectory = $this->output . '/stale';
        self::assertTrue(mkdir($sourceDirectory));
        $source = $sourceDirectory . '/kongono.data-mapping.json';
        $sourceBytes = file_get_contents($root . '/kongono.data-mapping.json');
        self::assertIsString($sourceBytes);
        self::assertSame(strlen($sourceBytes), file_put_contents($source, $sourceBytes));
        $relative = str_replace($root . '/', '', str_replace('\\', '/', $source));
        $workflow = new DataAccessBuildWorkflow();
        $stale = $workflow->prepareBuild($root, $relative, $this->output);
        self::assertSame(strlen($sourceBytes) + 1, file_put_contents($source, $sourceBytes . "\n"));
        $this->expectWorkflowFailure(fn() => $workflow->loadPrepared($stale));

        self::assertSame(strlen($sourceBytes), file_put_contents($source, $sourceBytes));
        $changed = $workflow->prepareBuild($root, $relative, $this->output);
        $generated = $changed->generatedSnapshot();
        self::assertTrue(touch($generated['absolute'], time() + 10));
        clearstatcache(true, $generated['absolute']);
        $this->expectWorkflowFailure(fn() => $workflow->loadPrepared($changed));
    }

    #[RunInSeparateProcess]
    public function testPreparedSnapshotsRejectMixedCurrentEvidence(): void
    {
        $root = self::applicationRoot();
        $workflow = new DataAccessBuildWorkflow();
        $current = $workflow->prepareBuild($root, 'kongono.data-mapping.json', $this->output);
        $sourceBytes = file_get_contents($root . '/kongono.data-mapping.json');
        self::assertIsString($sourceBytes);
        $peerBytes = str_replace('"AccountMapper"', '"AccountMapperPeer"', $sourceBytes, $replacements);
        self::assertSame(1, $replacements);
        $peerDirectory = $this->output . '/peer';
        self::assertTrue(mkdir($peerDirectory));
        $peerSource = $peerDirectory . '/kongono.data-mapping.json';
        self::assertSame(strlen($peerBytes), file_put_contents($peerSource, $peerBytes));
        $relative = str_replace($root . '/', '', str_replace('\\', '/', $peerSource));
        $peer = $workflow->prepareBuild($root, $relative, $this->output);
        $mixed = new PreparedDataAccessBuild(
            $current->applicationRoot(),
            $current->mappingSourceRelativePath(),
            $current->mappingArtifactSha256(),
            $peer->generatedDataAccessSha256(),
            $current->mappingSnapshot(),
            $peer->generatedSnapshot(),
        );
        $this->expectWorkflowFailure(fn() => $workflow->loadPrepared($mixed));
    }

    /** @param callable(): mixed $operation */
    private function expectWorkflowFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected prepared data-access rejection.');
        } catch (DataApplicationWorkflowException $failure) {
            self::assertSame('KGN-DATABASE-APPLICATION-1003', $failure->diagnosticId());
        }
    }

    /** @return array<string, string> */
    private function manifest(string $directory): array
    {
        $manifest = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
            if ($item instanceof SplFileInfo && $item->isFile()) {
                $sha256 = hash_file('sha256', $item->getPathname());
                self::assertIsString($sha256);
                $manifest[str_replace('\\', '/', $item->getPathname())] = $sha256;
            }
        }
        ksort($manifest, SORT_STRING);

        return $manifest;
    }

    private function removeTree(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }

    private static function root(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 5));
    }

    private static function applicationRoot(): string
    {
        return self::root() . '/skeletons/persistence-minimal';
    }
}
