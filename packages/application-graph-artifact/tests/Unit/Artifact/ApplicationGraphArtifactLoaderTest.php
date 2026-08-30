<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Artifact;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactLoader;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactPublisher;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactLoadException;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphArtifactLoaderTest extends TestCase
{
    public function testExplicitLoadAndCurrentLoadValidateNameBytesAndGraph(): void
    {
        $directory = $this->directory();
        try {
            $graph = (new ApplicationGraphBuilder())->build([], []);
            $artifact = (new ApplicationGraphArtifactBuilder())->build($graph);
            $path = (new ApplicationGraphArtifactPublisher())->publish($artifact, $directory);
            $loader = new ApplicationGraphArtifactLoader();
            self::assertSame($artifact->canonicalBytes(), $loader->load($path)->canonicalBytes());
            self::assertSame($artifact->canonicalBytes(), $loader->loadCurrent($path, $graph)->canonicalBytes());
            $wrong = $directory . DIRECTORY_SEPARATOR . 'wrong.json';
            rename($path, $wrong);
            $this->expectException(ApplicationGraphArtifactLoadException::class);
            $loader->load($wrong);
        } finally {
            $paths = glob($directory . DIRECTORY_SEPARATOR . '*');
            foreach (false === $paths ? [] : $paths as $path) {
                unlink($path);
            }rmdir($directory);
        }
    }

    public function testRelativeAndMissingPathsFailWithoutDiscovery(): void
    {
        $this->expectException(ApplicationGraphArtifactLoadException::class);
        (new ApplicationGraphArtifactLoader())->load('missing.json');
    }

    private function directory(): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-graph-load-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($path));
        return $path;
    }
}
