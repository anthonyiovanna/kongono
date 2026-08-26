<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Runtime;

use FilesystemIterator;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\GeneratedPersistenceLoadException;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryPublisher;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStatePublisher;
use Kongono\PersistenceBuild\Runtime\GeneratedPersistenceLoader;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class GeneratedPersistenceLoaderTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        $this->output = self::applicationRoot() . '/var/kongono/loader-test-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->output));
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->output, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }
        rmdir($this->output);
    }

    #[RunInSeparateProcess]
    public function testAdmissionRejectsUnsafeStaleAndCollisionStatesBeforeLoading(): void
    {
        $root = self::applicationRoot();
        $persistenceSource = 'kongono.persistence.json';
        $repositorySource = 'kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $state = (new GeneratedPersistenceStateGenerator())->generate($root, $persistenceSource, $persistence->canonicalBytes());
        $repositories = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
        $statePath = (new GeneratedPersistenceStatePublisher())->publish($state, $this->output);
        $repositoryPath = (new GeneratedAggregateRepositoryPublisher())->publish($repositories, $this->output);
        $loader = new GeneratedPersistenceLoader();

        $wrongDirectory = $this->output . '/wrong';
        self::assertTrue(mkdir($wrongDirectory));
        $wrongState = $wrongDirectory . '/' . basename($statePath);
        self::assertTrue(copy($statePath, $wrongState));
        self::assertSame(1, file_put_contents($wrongState, 'x'));
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $wrongState, $repositoryPath));
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), sys_get_temp_dir() . '/' . basename($statePath), $repositoryPath));

        $loaded = $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $statePath, $repositoryPath);
        self::assertSame($state->generatedFileSha256(), $loaded->generatedStateSha256());
        self::assertSame($repositories->generatedFileSha256(), $loaded->generatedRepositorySha256());
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $statePath, $repositoryPath));
    }

    #[RunInSeparateProcess]
    public function testAdmissionRejectsWrongFilenameMissingAndExecutableTamperWithoutExecution(): void
    {
        $root = self::applicationRoot();
        $persistenceSource = 'kongono.persistence.json';
        $repositorySource = 'kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $state = (new GeneratedPersistenceStateGenerator())->generate($root, $persistenceSource, $persistence->canonicalBytes());
        $repositories = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
        $statePath = (new GeneratedPersistenceStatePublisher())->publish($state, $this->output);
        $repositoryPath = (new GeneratedAggregateRepositoryPublisher())->publish($repositories, $this->output);
        $loader = new GeneratedPersistenceLoader();

        $wrongName = $this->output . '/renamed-state.php';
        self::assertTrue(copy($statePath, $wrongName));
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $wrongName, $repositoryPath));
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $this->output . '/missing-state.php', $repositoryPath));

        $sentinel = $this->output . '/executed.sentinel';
        $payload = "\nfile_put_contents(" . var_export($sentinel, true) . ", 'executed');\n";
        self::assertSame(strlen($payload), file_put_contents($statePath, $payload, FILE_APPEND));
        $this->expectLoadFailure(fn() => $loader->loadCurrent($root, $persistenceSource, $repositorySource, $persistence->canonicalBytes(), $statePath, $repositoryPath));
        self::assertFileDoesNotExist($sentinel);
    }

    /** @param callable(): mixed $operation */
    private function expectLoadFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected generated persistence load rejection.');
        } catch (GeneratedPersistenceLoadException $failure) {
            self::assertSame('KGN-PERSISTENCE-BUILD-1020', $failure->diagnosticId());
        }
    }

    private static function applicationRoot(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 5)) . '/skeletons/persistence-minimal';
    }
}
