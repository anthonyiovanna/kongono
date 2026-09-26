<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterContract\Tests\Fixture;

use Kongono\StorageBuild\Artifact\StorageProfileArtifact;
use Kongono\StorageBuild\Build\StorageProfileBuilder;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Digest\StorageDigestAlgorithm;
use Kongono\StorageContract\Identity\StorageNamespace;

final class StorageFixture
{
    public static function artifact(string $profile = 'local-test', string $namespace = 'objects'): StorageProfileArtifact
    {
        $declaration = new StorageProfileDeclaration(ContractVersion::Version1, new StorageProfileName($profile), new StorageNamespace($namespace), new StorageCapabilitySet(StorageCapability::cases()), StorageDigestAlgorithm::Sha256, new StorageProfileLimits(4, 4096, 2));

        return (new StorageProfileBuilder())->build(new StorageProfileSet([$declaration]))->artifact();
    }
}
