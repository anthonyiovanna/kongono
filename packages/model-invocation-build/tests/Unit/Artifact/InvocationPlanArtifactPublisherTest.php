<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Tests\Unit\Artifact;

use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifact;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactBuilder;
use Kongono\ModelInvocationBuild\Artifact\InvocationPlanArtifactPublisher;
use Kongono\ModelInvocationBuild\Exception\InvocationPlanPublicationException;
use Kongono\ModelInvocationBuild\Tests\Support\InvocationPlanSourceFactory;
use PHPUnit\Framework\TestCase;

final class InvocationPlanArtifactPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-model-plan-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0700));
        $resolved = realpath($this->directory);
        self::assertNotFalse($resolved);
        $this->directory = str_replace('\\', '/', $resolved);
    }

    protected function tearDown(): void
    {
        $paths = glob($this->directory . '/*');
        if (is_array($paths)) {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
        @rmdir($this->directory);
    }

    public function testFirstAndIdempotentPublicationAreContentAddressedAndClean(): void
    {
        $artifact = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create());
        $publisher = new InvocationPlanArtifactPublisher();
        $path = $publisher->publish($artifact, $this->directory);
        self::assertSame($this->directory . '/kongono-model-invocation-plan-v1-' . $artifact->artifactSha256() . '.json', $path);
        self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
        self::assertSame($path, $publisher->publish($artifact, $this->directory));
        $paths = glob($this->directory . '/*');
        self::assertIsArray($paths);
        self::assertSame([$path], $paths);
    }

    public function testConflictAndUnsafeDirectoriesFailWithoutClobber(): void
    {
        $artifact = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create());
        $path = $this->directory . '/kongono-model-invocation-plan-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($path, 'conflict');
        try {
            (new InvocationPlanArtifactPublisher())->publish($artifact, $this->directory);
            self::fail('Expected conflict rejection.');
        } catch (InvocationPlanPublicationException) {
            self::assertSame('conflict', file_get_contents($path));
        }
        foreach (['', '.', dirname($this->directory) . '/missing'] as $directory) {
            try {
                (new InvocationPlanArtifactPublisher())->publish($artifact, $directory);
                self::fail('Expected directory rejection.');
            } catch (InvocationPlanPublicationException $failure) {
                self::assertSame('KGN-MODEL-BUILD-1416', $failure->diagnosticId());
            }
        }
    }

    public function testArtifactIsRevalidatedBeforeFilesystemInput(): void
    {
        $valid = (new InvocationPlanArtifactBuilder())->build(InvocationPlanSourceFactory::create());
        $invalid = new InvocationPlanArtifact($valid->canonicalBytes() . 'x', $valid->artifactSha256(), $valid->sourceSha256(), $valid->requestSha256(), $valid->planIdentity(), $valid->adapterIdentity(), $valid->invocationIdentity());
        $this->expectException(InvocationPlanPublicationException::class);
        (new InvocationPlanArtifactPublisher())->publish($invalid, 'relative/not-read');
    }
}
