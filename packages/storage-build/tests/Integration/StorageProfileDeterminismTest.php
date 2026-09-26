<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Integration;

use Fiber;
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
use PHPUnit\Framework\TestCase;

final class StorageProfileDeterminismTest extends TestCase
{
    public function testReverseRepeatedAndFiberBuildsAreIdenticalAndIsolated(): void
    {
        $alpha = self::profile('alpha');
        $zeta = self::profile('zeta');
        $builder = new StorageProfileBuilder();
        $forward = $builder->build(new StorageProfileSet([$alpha, $zeta]));
        $reverse = $builder->build(new StorageProfileSet([$zeta, $alpha]));
        self::assertTrue($forward->equals($reverse));
        self::assertTrue($forward->equals($builder->build(new StorageProfileSet([$alpha, $zeta]))));

        $fiber = new Fiber(static fn(): string => (new StorageProfileBuilder())->build(new StorageProfileSet([$zeta, $alpha]))->artifact()->bytes());
        $fiber->start();
        self::assertSame($forward->artifact()->bytes(), $fiber->getReturn());
        self::assertSame($forward->artifact()->bytes(), $builder->build(new StorageProfileSet([$alpha, $zeta]))->artifact()->bytes());
    }

    private static function profile(string $name): StorageProfileDeclaration
    {
        return new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName($name),
            new StorageNamespace($name . '-store'),
            new StorageCapabilitySet([StorageCapability::ContentDigest, StorageCapability::Read]),
            StorageDigestAlgorithm::Sha256,
            new StorageProfileLimits(1024, 4096, 25),
        );
    }
}
