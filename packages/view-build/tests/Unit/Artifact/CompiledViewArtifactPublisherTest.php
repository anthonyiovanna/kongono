<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Artifact;

use Kongono\ViewBuild\Artifact\CompiledViewArtifactPublisher;
use Kongono\ViewBuild\Exception\ViewArtifactPublicationException;
use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewArtifactPublisherTest extends TestCase
{
    public function testPublishesExactTwoFilesAndAcceptsOnlyIdenticalExistingTarget(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 5)) . '/.kongono-view-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $target = null;
        try {
            $artifact = BuildProbe::artifact();
            $publisher = new CompiledViewArtifactPublisher();
            $target = $publisher->publish($artifact, $root);
            self::assertSame($target, $publisher->publish($artifact, $root));
            self::assertSame(['.', '..', 'artifact.json', 'compiled.php'], scandir($target));
            self::assertSame($artifact->metadataBytes(), file_get_contents($target . '/artifact.json'));
            self::assertSame($artifact->generatedPhpBytes(), file_get_contents($target . '/compiled.php'));
            self::assertSame([], glob($root . '/*.tmp'));
        } finally {
            if (null !== $target) {
                @unlink($target . '/artifact.json');
                @unlink($target . '/compiled.php');
                @rmdir($target);
            }
            @rmdir($root);
        }
    }

    public function testConflictingExistingTargetFailsWithoutRepair(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 5)) . '/.kongono-view-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $artifact = BuildProbe::artifact();
        $target = $root . '/kongono-view-v1-' . $artifact->compiledViewIdentity()->value();
        self::assertTrue(mkdir($target));
        self::assertSame(1, file_put_contents($target . '/extra', 'x'));
        try {
            (new CompiledViewArtifactPublisher())->publish($artifact, $root);
            self::fail('Expected conflicting target rejection.');
        } catch (ViewArtifactPublicationException) {
            self::assertSame('x', file_get_contents($target . '/extra'));
        } finally {
            @unlink($target . '/extra');
            @rmdir($target);
            @rmdir($root);
        }
    }

    public function testPartialExistingTargetFailsWithoutRepairOrTemporaryResidue(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 5)) . '/.kongono-view-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $artifact = BuildProbe::artifact();
        $target = $root . '/kongono-view-v1-' . $artifact->compiledViewIdentity()->value();
        self::assertTrue(mkdir($target));
        self::assertSame(strlen($artifact->metadataBytes()), file_put_contents($target . '/artifact.json', $artifact->metadataBytes()));
        try {
            (new CompiledViewArtifactPublisher())->publish($artifact, $root);
            self::fail('Expected partial target rejection.');
        } catch (ViewArtifactPublicationException) {
            self::assertFileDoesNotExist($target . '/compiled.php');
            self::assertSame($artifact->metadataBytes(), file_get_contents($target . '/artifact.json'));
            self::assertSame([], glob($root . '/*.tmp'));
        } finally {
            @unlink($target . '/artifact.json');
            @rmdir($target);
            @rmdir($root);
        }
    }

    public function testRejectsNoncanonicalAndUncontainedOutputPathsBeforePublication(): void
    {
        $root = str_replace('\\', '/', dirname(__DIR__, 5)) . '/.kongono-view-build-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        try {
            foreach (['relative/path', $root . '/.', $root . '\\mixed'] as $path) {
                try {
                    (new CompiledViewArtifactPublisher())->publish(BuildProbe::artifact(), $path);
                    self::fail('Expected path rejection.');
                } catch (ViewArtifactPublicationException) {
                    self::addToAssertionCount(1);
                }
            }
            self::assertSame(['.', '..'], scandir($root));
        } finally {
            @rmdir($root);
        }
    }
}
