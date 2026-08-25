<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Tests\Unit;

use FilesystemIterator;
use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\DatabaseMapping\Build\MappingArtifactPublisher;
use Kongono\DatabaseMapping\Exception\GeneratedDataAccessLoadException;
use Kongono\DatabaseMapping\Exception\MappingArtifactPublicationException;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessGenerator;
use Kongono\DatabaseMapping\Generated\GeneratedDataAccessPublisher;
use Kongono\DatabaseMapping\Runtime\GeneratedDataAccessLoader;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class MappingRuntimeTest extends TestCase
{
    private const SOURCE = 'packages/database-mapping/tests/Fixture/kongono.data-mapping.json';

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testArtifactPublicationAndExactCurrentLoadingAreContentAddressedAndOneUse(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 4));
        $output = $this->temporaryDirectory($root);
        try {
            $artifact = (new MappingArtifactBuilder())->build($root, self::SOURCE);
            $artifactPublisher = new MappingArtifactPublisher();
            $artifactPath = $artifactPublisher->publish($artifact, $output);
            self::assertSame($artifactPath, $artifactPublisher->publish($artifact, $output));
            self::assertSame(
                $output . '/kongono-data-mapping-v1-' . $artifact->artifactSha256() . '.json',
                $artifactPath,
            );

            $generated = (new GeneratedDataAccessGenerator())->generate($root, self::SOURCE, $artifact->canonicalBytes());
            $generatedPath = (new GeneratedDataAccessPublisher())->publish($generated, $output);
            $loaded = (new GeneratedDataAccessLoader())->loadCurrent(
                $root,
                self::SOURCE,
                $artifact->canonicalBytes(),
                $generatedPath,
            );
            self::assertSame($artifact->artifactSha256(), $loaded->mappingArtifactSha256());
            self::assertSame($generated->generatedFileSha256(), $loaded->generatedFileSha256());
            self::assertSame($generated->classes(), $loaded->classes());

            $this->expectException(GeneratedDataAccessLoadException::class);
            (new GeneratedDataAccessLoader())->loadCurrent($root, self::SOURCE, $artifact->canonicalBytes(), $generatedPath);
        } finally {
            $this->removeDirectory($output);
        }
    }

    public function testArtifactPublicationRejectsConflictingExistingBytes(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 4));
        $output = $this->temporaryDirectory($root);
        try {
            $artifact = (new MappingArtifactBuilder())->build($root, self::SOURCE);
            $publisher = new MappingArtifactPublisher();
            $path = $publisher->publish($artifact, $output);
            self::assertIsInt(file_put_contents($path, "conflict\n"));

            $this->expectException(MappingArtifactPublicationException::class);
            $publisher->publish($artifact, $output);
        } finally {
            $this->removeDirectory($output);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoaderRejectsWrongFilenameBeforeExecution(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 4));
        $output = $this->temporaryDirectory($root);
        try {
            $artifact = (new MappingArtifactBuilder())->build($root, self::SOURCE);
            $generated = (new GeneratedDataAccessGenerator())->generate($root, self::SOURCE, $artifact->canonicalBytes());
            $wrong = $output . '/wrong-name.php';
            self::assertIsInt(file_put_contents($wrong, $generated->canonicalBytes()));

            try {
                (new GeneratedDataAccessLoader())->loadCurrent($root, self::SOURCE, $artifact->canonicalBytes(), $wrong);
                self::fail('A wrong generated filename was loaded.');
            } catch (GeneratedDataAccessLoadException) {
                foreach ($generated->classes() as $class) {
                    self::assertFalse(class_exists($class['mapper'], false));
                    self::assertFalse(class_exists($class['repository'], false));
                }
            }
        } finally {
            $this->removeDirectory($output);
        }
    }

    private function temporaryDirectory(string $root): string
    {
        $path = $root . '/.cache/kongono-mapping-runtime-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($path, 0o700, true));

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (new FilesystemIterator($path) as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile()) {
                @unlink($item->getPathname());
            }
        }
        @rmdir($path);
    }
}
