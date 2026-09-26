<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Integration;

use Kongono\StorageBuild\Artifact\StorageProfileArtifactPublisher;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfilePublicationIsolationTest extends TestCase
{
    public function testSequentialRootsDoNotShareStateAndCleanupIsComplete(): void
    {
        $base = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-storage-isolation-' . bin2hex(random_bytes(8));
        $left = $base . '/left';
        $right = $base . '/right';
        self::assertTrue(mkdir($left, 0700, true));
        self::assertTrue(mkdir($right, 0700, true));
        try {
            $artifact = (new StorageProfileBuilder())->build(self::set())->artifact();
            $publisher = new StorageProfileArtifactPublisher();
            $leftEvidence = $publisher->publish($artifact, $left);
            $rightEvidence = $publisher->publish($artifact, $right);
            self::assertTrue($leftEvidence->created());
            self::assertTrue($rightEvidence->created());
            self::assertSame($leftEvidence->relativeArtifactPath(), $rightEvidence->relativeArtifactPath());
            self::assertNotSame($leftEvidence->absoluteArtifactPath(), $rightEvidence->absoluteArtifactPath());
            self::assertFileDoesNotExist($left . '/current');
            self::assertFileDoesNotExist($left . '/latest');
        } finally {
            self::remove($base);
        }
        self::assertDirectoryDoesNotExist($base);
    }

    private static function set(): StorageProfileSet
    {
        return new StorageProfileSet([new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName('primary'),
            new StorageNamespace('primary-store'),
            new StorageCapabilitySet([StorageCapability::Read]),
            null,
            new StorageProfileLimits(1024, 4096, 25),
        )]);
    }

    private static function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        $entries = scandir($path);
        foreach (false === $entries ? [] : $entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            self::remove($path . '/' . $entry);
        }
        rmdir($path);
    }
}
