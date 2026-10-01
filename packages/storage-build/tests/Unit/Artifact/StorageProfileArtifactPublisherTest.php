<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Artifact;

use Kongono\StorageBuild\Artifact\StorageProfileArtifactPublisher;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Exception\StorageProfilePublicationException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfileArtifactPublisherTest extends TestCase
{
    public function testPublicationIsContainedContentAddressedNoClobberAndExactIdempotent(): void
    {
        $root = self::temporaryRoot('publication');
        try {
            $artifact = (new StorageProfileBuilder())->build(self::set())->artifact();
            $publisher = new StorageProfileArtifactPublisher();
            $first = $publisher->publish($artifact, $root);
            $relative = 'objects/sha256/' . substr($artifact->artifactIdentity()->value(), 0, 2) . '/' . $artifact->artifactIdentity()->value() . '/storage-profile.json';
            self::assertTrue($first->created());
            self::assertSame($root, $first->publicationRoot());
            self::assertSame($relative, $first->relativeArtifactPath());
            self::assertSame(strtolower($root[0]) . substr($root, 1) . '/' . $relative, $first->absoluteArtifactPath());
            self::assertSame($artifact->artifactIdentity()->value(), $first->artifactIdentity()->value());
            self::assertSame(strlen($artifact->bytes()), $first->artifactByteCount());
            self::assertSame($artifact->bytes(), file_get_contents($root . '/' . $relative));
            self::assertFalse($publisher->publish($artifact, $root)->created());

            self::assertSame(strlen('different'), file_put_contents($root . '/' . $relative, 'different'));
            $this->expectException(StorageProfilePublicationException::class);
            $publisher->publish($artifact, $root);
        } finally {
            self::remove($root);
        }
    }

    public function testMalformedTraversalAndSpecialRootsFailClosed(): void
    {
        $root = self::temporaryRoot('roots');
        $ordinaryFile = $root . '/ordinary-file';
        self::assertSame(4, file_put_contents($ordinaryFile, 'data'));
        try {
            foreach (['relative', '/', $root . '/missing', $root . '/../escape', $root . '/*', $root . '/mixed\\separator', $ordinaryFile] as $invalid) {
                try {
                    (new StorageProfileArtifactPublisher())->publish((new StorageProfileBuilder())->build(self::set())->artifact(), $invalid);
                    self::fail('Invalid publication root was accepted.');
                } catch (StorageProfilePublicationException) {
                    self::addToAssertionCount(1);
                }
            }
        } finally {
            self::remove($root);
        }
    }

    public function testSymbolicLinkRootFailsClosedWhenSupported(): void
    {
        $base = self::temporaryRoot('link');
        $target = $base . '/target';
        $link = $base . '/link';
        self::assertTrue(mkdir($target, 0700));
        try {
            if (@symlink($target, $link)) {
                try {
                    (new StorageProfileArtifactPublisher())->publish((new StorageProfileBuilder())->build(self::set())->artifact(), $link);
                    self::fail('Symbolic-link publication root was accepted.');
                } catch (StorageProfilePublicationException) {
                    self::addToAssertionCount(1);
                }
            } else {
                self::assertFalse(is_link($link));
            }
        } finally {
            if (is_link($link)) {
                $removed = 'Windows' === PHP_OS_FAMILY ? rmdir($link) : unlink($link);
                self::assertTrue($removed);
            }
            clearstatcache(true, $link);
            self::assertFalse(is_link($link));
            self::assertFalse(file_exists($link));
            self::remove($base);
            clearstatcache(true, $base);
            self::assertFalse(file_exists($base));
        }
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

    private static function temporaryRoot(string $label): string
    {
        $root = str_replace('\\', '/', sys_get_temp_dir()) . '/kongono-storage-' . $label . '-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root, 0700));

        return $root;
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
