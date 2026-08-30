<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Artifact;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactPublisher;
use Kongono\ApplicationGraphArtifact\Exception\ApplicationGraphArtifactPublicationException;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use PHPUnit\Framework\TestCase;

final class ApplicationGraphArtifactPublicationTest extends TestCase
{
    public function testPublishIsContentAddressedIdempotentAndNoClobber(): void
    {
        $directory = $this->directory();
        try {
            $artifact = (new ApplicationGraphArtifactBuilder())->build((new ApplicationGraphBuilder())->build([], []));
            $publisher = new ApplicationGraphArtifactPublisher();
            $path = $publisher->publish($artifact, $directory);
            self::assertSame(str_replace('\\', '/', $directory) . '/kongono-application-graph-v1-' . $artifact->artifactSha256() . '.json', $path);
            self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
            self::assertSame($path, $publisher->publish($artifact, $directory));
            file_put_contents($path, 'conflict');
            $this->expectException(ApplicationGraphArtifactPublicationException::class);
            $publisher->publish($artifact, $directory);
        } finally {
            $paths = glob($directory . DIRECTORY_SEPARATOR . '*');
            foreach (false === $paths ? [] : $paths as $path) {
                unlink($path);
            }rmdir($directory);
        }
    }

    private function directory(): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kongono-graph-artifact-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($path));
        return $path;
    }
}
