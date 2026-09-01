<?php

declare(strict_types=1);

namespace Kongono\AgentDeclarationBuild\Tests\Unit\Artifact;

use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifact;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactBuilder;
use Kongono\AgentDeclarationBuild\Artifact\AgentDeclarationArtifactPublisher;
use Kongono\AgentDeclarationBuild\Exception\AgentDeclarationPublicationException;
use Kongono\AgentDeclarationBuild\Tests\Support\AgentDeclarationFactory;
use PHPUnit\Framework\TestCase;

final class AgentDeclarationArtifactPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-agent-declaration-' . bin2hex(random_bytes(8));
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

    public function testFirstAndIdempotentPublicationUseCompleteArtifactHash(): void
    {
        $artifact = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $publisher = new AgentDeclarationArtifactPublisher();
        $path = $publisher->publish($artifact, $this->directory);
        self::assertSame($this->directory . '/kongono-agent-declaration-v1-' . $artifact->artifactSha256() . '.json', $path);
        self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
        self::assertSame(strlen($artifact->canonicalBytes()), filesize($path));
        self::assertSame($artifact->artifactSha256(), hash_file('sha256', $path));
        self::assertSame($path, $publisher->publish($artifact, $this->directory));
        $paths = glob($this->directory . '/*');
        self::assertIsArray($paths);
        self::assertCount(1, $paths);
    }

    public function testConflictingExistingTargetFailsWithoutOverwrite(): void
    {
        $artifact = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $path = $this->directory . '/kongono-agent-declaration-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($path, 'conflict');
        try {
            (new AgentDeclarationArtifactPublisher())->publish($artifact, $this->directory);
            self::fail('Expected no-clobber failure.');
        } catch (AgentDeclarationPublicationException) {
            self::assertSame('conflict', file_get_contents($path));
            $paths = glob($this->directory . '/*');
            self::assertIsArray($paths);
            self::assertSame([$path], $paths);
        }
    }

    public function testArtifactIsRevalidatedBeforeFilesystemInput(): void
    {
        $valid = (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create());
        $invalid = new AgentDeclarationArtifact(
            $valid->canonicalBytes() . 'x',
            $valid->artifactSha256(),
            $valid->declarationIdentity(),
            $valid->declarationSha256(),
        );
        $this->expectException(AgentDeclarationPublicationException::class);
        (new AgentDeclarationArtifactPublisher())->publish($invalid, 'relative/not-read');
    }

    public function testUnsafeDirectoryInputsFailClosed(): void
    {
        foreach (['', '.', "bad\0path", dirname($this->directory) . '/missing'] as $path) {
            try {
                (new AgentDeclarationArtifactPublisher())->publish(
                    (new AgentDeclarationArtifactBuilder())->build(AgentDeclarationFactory::create()),
                    $path,
                );
                self::fail('Expected unsafe directory rejection.');
            } catch (AgentDeclarationPublicationException $failure) {
                self::assertSame('KGN-AGENT-DECLARATION-1313', $failure->diagnosticId());
            }
        }
    }
}
