<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Build;

use Kongono\StorageBuild\Build\StorageProfileBuildIdentity;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfileBuildIdentityTest extends TestCase
{
    public function testIdentityIsExactDeterministicAndDomainSeparated(): void
    {
        $source = self::set()->identity();
        $identity = StorageProfileBuildIdentity::fromSourceIdentity($source);
        $descriptor = json_encode([
            'schema' => 'kongono/storage-profile-artifact/1',
            'source_identity' => $source->value(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        self::assertSame(hash('sha256', "kongono/storage-profile-build/1\0" . $descriptor), $identity->value());
        self::assertNotSame($source->value(), $identity->value());
        self::assertTrue($identity->equals(StorageProfileBuildIdentity::fromSourceIdentity($source)));
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
