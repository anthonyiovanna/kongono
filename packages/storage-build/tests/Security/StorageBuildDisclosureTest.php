<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Security;

use Kongono\StorageBuild\Artifact\StorageProfileArtifactPublisher;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class StorageBuildDisclosureTest extends TestCase
{
    public function testRejectedNamesAndRootsAreNotDisclosed(): void
    {
        $secret = 'SECRET-profile/C:\\private\\token';
        try {
            new StorageProfileName($secret);
            self::fail('Invalid profile name was accepted.');
        } catch (InvalidStorageProfileException $failure) {
            self::assertStringNotContainsString($secret, (string) $failure);
            self::assertSame('KGN-STORAGE-BUILD-2201', $failure->diagnosticId());
        }

        try {
            (new StorageProfileArtifactPublisher())->publish((new StorageProfileBuilder())->build(self::set())->artifact(), $secret);
            self::fail('Invalid publication root was accepted.');
        } catch (StorageProfilePublicationException $failure) {
            self::assertStringNotContainsString($secret, (string) $failure);
            self::assertSame('KGN-STORAGE-BUILD-2204', $failure->diagnosticId());
        }
    }

    public function testProductionContainsNoAmbientProviderRuntimeOrCommandAuthority(): void
    {
        $source = '';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . '/src')) as $entry) {
            if ($entry instanceof SplFileInfo && $entry->isFile() && 'php' === $entry->getExtension()) {
                $source .= (string) file_get_contents($entry->getPathname());
            }
        }
        foreach (['getenv(', '$_ENV', '$_SERVER', 'serialize(', 'unserialize(', 'proc_open(', 'curl_', 'eval(', 'ReflectionClass', 'Psr\\Http', 'storage:plan', 'storage:build', 'storage:check', 'Aws\\', 'Azure\\', 'Google\\Cloud', 'credential', 'bucket', 'endpoint'] as $denied) {
            self::assertStringNotContainsString($denied, $source, $denied);
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
}
