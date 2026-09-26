<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Artifact;

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

final class StorageProfileArtifactTest extends TestCase
{
    public function testArtifactAndBuildResultExposeDetachedExactEvidence(): void
    {
        $set = self::set();
        $result = (new StorageProfileBuilder())->build($set);
        $artifact = $result->artifact();
        self::assertSame(strlen($artifact->bytes()), $artifact->byteCount());
        self::assertSame(1, $artifact->profileCount());
        self::assertTrue($artifact->profiles()->equals($set));
        self::assertTrue($artifact->equals($artifact));
        self::assertSame($set->identity()->value(), $result->sourceIdentity()->value());
        self::assertSame($artifact->artifactIdentity()->value(), $result->artifactIdentity()->value());
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $artifact->buildIdentity()->value());
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
