<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Profile;

use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfileDeclarationTest extends TestCase
{
    public function testDeclarationKeepsEveryDomainDistinct(): void
    {
        $profile = self::profile(new StorageCapabilitySet([
            StorageCapability::ContentDigest,
            StorageCapability::Read,
        ]), StorageDigestAlgorithm::Sha256);
        self::assertSame(ContractVersion::Version1, $profile->contractVersion());
        self::assertSame('documents', $profile->name()->value());
        self::assertSame('document-store', $profile->namespace()->value());
        self::assertSame([StorageCapability::Read, StorageCapability::ContentDigest], $profile->capabilities()->capabilities());
        self::assertSame(StorageDigestAlgorithm::Sha256, $profile->digestAlgorithm());
        self::assertSame(4096, $profile->limits()->maxObjectBytes());
        self::assertTrue($profile->equals(self::profile(new StorageCapabilitySet([
            StorageCapability::Read,
            StorageCapability::ContentDigest,
        ]), StorageDigestAlgorithm::Sha256)));
    }

    public function testEmptyCapabilitiesFailClosed(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        self::profile(new StorageCapabilitySet([]), null);
    }

    public function testDigestCapabilityRequiresAlgorithm(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        self::profile(new StorageCapabilitySet([StorageCapability::ContentDigest]), null);
    }

    public function testAlgorithmRequiresDigestCapability(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        self::profile(new StorageCapabilitySet([StorageCapability::Read]), StorageDigestAlgorithm::Sha256);
    }

    private static function profile(StorageCapabilitySet $capabilities, ?StorageDigestAlgorithm $algorithm): StorageProfileDeclaration
    {
        return new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName('documents'),
            new StorageNamespace('document-store'),
            $capabilities,
            $algorithm,
            new StorageProfileLimits(1024, 4096, 25),
        );
    }
}
