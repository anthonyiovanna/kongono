<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Artifact;

use Kongono\ViewContract\Identity\CompiledViewIdentity;
use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Exception\ArtifactLoadException;
use Kongono\ViewRuntime\Exception\InvalidRuntimeArtifactException;
use Kongono\ViewRuntime\Exception\StaleRuntimeArtifactException;
use Kongono\ViewRuntime\Tests\Fixture\RuntimeProbe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompiledViewArtifactLoaderTest extends TestCase
{
    public function testLoadsExactArtifactAndReusesItsImmutableGeneratedClass(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $loader = new CompiledViewArtifactLoader();
            $first = $loader->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            $second = $loader->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            self::assertTrue($first->compiledViewIdentity()->equals($second->compiledViewIdentity()));
            self::assertSame($fixture['artifact']->generatedClass(), $first->generatedClass());
            self::assertSame(2, $first->viewCount());
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testRejectsAnAlreadyLoadedClassWithTheExpectedNameButWrongSurface(): void
    {
        $source = str_replace('page.alpha', 'page.collision', RuntimeProbe::sourceBytes());
        $fixture = RuntimeProbe::publishSource($source);
        try {
            $unrelated = new class {};
            self::assertTrue(class_alias($unrelated::class, $fixture['artifact']->generatedClass()));
            $this->expectException(ArtifactLoadException::class);
            (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    #[DataProvider('unsafePathProvider')]
    public function testRejectsNoncanonicalPathForms(string $kind): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            [$root, $directory] = match ($kind) {
                'relative' => ['relative', $fixture['directory']],
                'dot' => [$fixture['root'] . '/.', $fixture['directory']],
                'mixed' => [str_replace('/', '\\', $fixture['root']), $fixture['directory']],
                'trailing' => [$fixture['root'] . '/', $fixture['directory']],
                'parent' => [$fixture['root'], $fixture['root']],
                'sibling' => [$fixture['directory'], $fixture['directory']],
                default => [$fixture['root'], $fixture['directory'] . '-wrong'],
            };
            $this->expectException(InvalidRuntimeArtifactException::class);
            (new CompiledViewArtifactLoader())->load($root, $directory, $fixture['artifact']->compiledViewIdentity());
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function unsafePathProvider(): iterable
    {
        foreach (['relative', 'dot', 'mixed', 'trailing', 'parent', 'sibling', 'wrong'] as $kind) {
            yield $kind => [$kind];
        }
    }

    public function testRejectsMissingDirectoryAndFixedFileWithoutFallbackDiscovery(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/fallback.php', '<?php return true;'));
            self::assertTrue(unlink($fixture['directory'] . '/compiled.php'));
            $this->expectException(InvalidRuntimeArtifactException::class);
            (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
        } finally {
            @unlink($fixture['directory'] . '/fallback.php');
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testRejectsCallerCurrentnessMismatchBeforeMetadataInterpretation(): void
    {
        $fixture = RuntimeProbe::publish();
        $changed = new CompiledViewIdentity(('0' === $fixture['artifact']->compiledViewIdentity()->value()[0] ? '1' : '0') . substr($fixture['artifact']->compiledViewIdentity()->value(), 1));
        $renamed = $fixture['root'] . '/kongono-view-v1-' . $changed->value();
        try {
            self::assertTrue(rename($fixture['directory'], $renamed));
            $fixture['directory'] = $renamed;
            $this->expectException(StaleRuntimeArtifactException::class);
            (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $changed);
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testRejectsMalformedOversizedDirectoryAndLockedFileEvidence(): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            self::assertTrue(unlink($fixture['directory'] . '/artifact.json'));
            self::assertTrue(mkdir($fixture['directory'] . '/artifact.json'));
            try {
                (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
                self::fail('Expected directory-as-file rejection.');
            } catch (InvalidRuntimeArtifactException) {
                self::addToAssertionCount(1);
            }
            self::assertTrue(rmdir($fixture['directory'] . '/artifact.json'));
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/artifact.json', str_repeat('x', 2_097_153)));
            try {
                (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
                self::fail('Expected oversized rejection.');
            } catch (InvalidRuntimeArtifactException) {
                self::addToAssertionCount(1);
            }
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/artifact.json', $fixture['artifact']->metadataBytes()));
            $handle = fopen($fixture['directory'] . '/artifact.json', 'rb');
            self::assertIsResource($handle);
            self::assertTrue(flock($handle, LOCK_EX | LOCK_NB));
            try {
                (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
                self::fail('Expected locked-file rejection.');
            } catch (InvalidRuntimeArtifactException) {
                self::addToAssertionCount(1);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    public function testRejectsHardLinkedFixedFileEvidence(): void
    {
        $fixture = RuntimeProbe::publish();
        $original = $fixture['directory'] . '/artifact.original';
        try {
            self::assertTrue(rename($fixture['directory'] . '/artifact.json', $original));
            self::assertTrue(link($original, $fixture['directory'] . '/artifact.json'));
            $this->expectException(InvalidRuntimeArtifactException::class);
            (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
        } finally {
            @unlink($original);
            RuntimeProbe::cleanup($fixture);
        }
    }

    /** @param class-string<\Throwable> $expectedException */
    #[DataProvider('tamperedArtifactProvider')]
    public function testRejectsTamperedArtifactIdentityRelations(string $kind, string $expectedException): void
    {
        $fixture = RuntimeProbe::publish();
        try {
            $metadata = json_decode(substr($fixture['artifact']->metadataBytes(), 0, -1), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($metadata)) {
                self::fail('Expected fixture metadata object.');
            }
            $generatedEnvelope = $metadata['generated_php'] ?? null;
            if (!is_array($generatedEnvelope)) {
                self::fail('Expected fixture generated envelope.');
            }
            $generated = $fixture['artifact']->generatedPhpBytes();
            if ('source' === $kind) {
                $metadata['source_sha256'] = str_repeat('0', 64);
            } elseif ('model' === $kind) {
                $metadata['model_sha256'] = str_repeat('0', 64);
            } elseif ('generated' === $kind) {
                $generatedEnvelope['sha256'] = str_repeat('0', 64);
            } elseif ('class' === $kind) {
                $generatedEnvelope['class'] = 'Kongono\\Generated\\View\\CompiledViewSet_' . str_repeat('0', 64);
            } elseif ('return' === $kind) {
                $generated = str_replace("return '" . str_replace('\\', '\\\\', $fixture['artifact']->generatedClass()) . "';\n", "return 'Kongono\\\\Generated\\\\View\\\\Wrong';\n", $generated);
                $generatedEnvelope['sha256'] = hash('sha256', $generated);
                self::assertNotFalse(file_put_contents($fixture['directory'] . '/compiled.php', $generated));
            }
            $metadata['generated_php'] = $generatedEnvelope;
            $bytes = 'malformed' === $kind
                ? "{]\n"
                : ('noncanonical' === $kind
                    ? json_encode($metadata, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
                    : json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
            self::assertNotFalse(file_put_contents($fixture['directory'] . '/artifact.json', $bytes));
            $identity = new CompiledViewIdentity(hash('sha256', $bytes));
            $renamed = $fixture['root'] . '/kongono-view-v1-' . $identity->value();
            self::assertTrue(rename($fixture['directory'], $renamed));
            $fixture['directory'] = $renamed;
            $this->expectException($expectedException);
            (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $identity);
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }

    /** @return iterable<string, array{string, class-string<\Throwable>}> */
    public static function tamperedArtifactProvider(): iterable
    {
        yield 'malformed envelope' => ['malformed', InvalidRuntimeArtifactException::class];
        yield 'noncanonical envelope' => ['noncanonical', InvalidRuntimeArtifactException::class];
        yield 'source identity' => ['source', ArtifactLoadException::class];
        yield 'model identity' => ['model', InvalidRuntimeArtifactException::class];
        yield 'generated identity' => ['generated', InvalidRuntimeArtifactException::class];
        yield 'generated class' => ['class', InvalidRuntimeArtifactException::class];
        yield 'generated return' => ['return', InvalidRuntimeArtifactException::class];
    }
}
