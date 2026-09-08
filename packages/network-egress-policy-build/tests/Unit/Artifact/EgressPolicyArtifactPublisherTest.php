<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Tests\Unit\Artifact;

use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifact;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactBuilder;
use Kongono\NetworkEgressPolicyBuild\Artifact\EgressPolicyArtifactPublisher;
use Kongono\NetworkEgressPolicyBuild\Exception\EgressPolicyPublicationException;
use Kongono\NetworkEgressPolicyBuild\Tests\Support\EgressPolicySourceFactory;
use PHPUnit\Framework\TestCase;

final class EgressPolicyArtifactPublisherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/kongono-egress-policy-' . bin2hex(random_bytes(8));
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
                @rmdir($path);
            }
        }
        @rmdir($this->directory);
    }

    public function testFirstAndIdempotentPublicationAreContentAddressedCompleteAndClean(): void
    {
        $artifact = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create());
        $publisher = new EgressPolicyArtifactPublisher();
        $parentBefore = glob(dirname($this->directory) . '/*');
        $path = $publisher->publish($artifact, $this->directory);
        self::assertSame($this->directory . '/kongono-network-egress-policy-v1-' . $artifact->artifactSha256() . '.json', $path);
        self::assertSame($artifact->canonicalBytes(), file_get_contents($path));
        self::assertSame($path, $publisher->publish($artifact, $this->directory));
        self::assertSame([$path], glob($this->directory . '/*'));
        self::assertSame($parentBefore, glob(dirname($this->directory) . '/*'));
    }

    public function testConflictingAndUnsafeTargetsFailWithoutClobberOrResidue(): void
    {
        $artifact = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create());
        $path = $this->directory . '/kongono-network-egress-policy-v1-' . $artifact->artifactSha256() . '.json';
        file_put_contents($path, 'conflict');
        try {
            (new EgressPolicyArtifactPublisher())->publish($artifact, $this->directory);
            self::fail('Expected conflict rejection.');
        } catch (EgressPolicyPublicationException) {
            self::assertSame('conflict', file_get_contents($path));
            self::assertSame([$path], glob($this->directory . '/*'));
        }
        foreach (['', '.', dirname($this->directory) . '/missing', $this->directory . "\0suffix"] as $directory) {
            try {
                (new EgressPolicyArtifactPublisher())->publish($artifact, $directory);
                self::fail('Expected unsafe path rejection.');
            } catch (EgressPolicyPublicationException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testArtifactIsValidatedBeforeFilesystemInputAndEngineErrorsPropagate(): void
    {
        $valid = (new EgressPolicyArtifactBuilder())->build(EgressPolicySourceFactory::create());
        $invalid = new EgressPolicyArtifact($valid->canonicalBytes() . 'x', $valid->artifactSha256(), $valid->sourceSha256(), $valid->policyIdentity());
        try {
            (new EgressPolicyArtifactPublisher())->publish($invalid, 'relative/not-read');
            self::fail('Expected invalid artifact rejection.');
        } catch (EgressPolicyPublicationException $failure) {
            self::assertSame('KGN-EGRESS-POLICY-1515', $failure->diagnosticId());
        }

        $reflection = new \ReflectionClass(\Kongono\NetworkEgressPolicyBuild\Policy\EgressPolicyIdentity::class);
        $this->expectException(\TypeError::class);
        $reflection->newInstanceArgs([[]]);
    }
}
