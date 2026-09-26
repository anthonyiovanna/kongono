<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Tests\Unit\Profile;

use Kongono\StorageBuild\Exception\InvalidStorageProfileException;
use Kongono\StorageBuild\Profile\StorageProfileDeclaration;
use Kongono\StorageBuild\Profile\StorageProfileLimits;
use Kongono\StorageBuild\Profile\StorageProfileName;
use Kongono\StorageBuild\Profile\StorageProfileSet;
use Kongono\StorageContract\Capability\StorageCapability;
use Kongono\StorageContract\Capability\StorageCapabilitySet;
use Kongono\StorageContract\ContractVersion;
use Kongono\StorageContract\Identity\StorageNamespace;
use PHPUnit\Framework\TestCase;

final class StorageProfileSetTest extends TestCase
{
    public function testSetIsDetachedOrderedAndInputOrderIndependent(): void
    {
        $alpha = self::profile('alpha', 'alpha-store');
        $zeta = self::profile('zeta', 'zeta-store');
        $input = [$zeta, $alpha];
        $set = new StorageProfileSet($input);
        $input[0] = $alpha;
        self::assertSame(['alpha', 'zeta'], array_map(static fn(StorageProfileDeclaration $profile): string => $profile->name()->value(), $set->profiles()));
        self::assertSame($set->identity()->value(), (new StorageProfileSet([$alpha, $zeta]))->identity()->value());
        self::assertSame($alpha, $set->profile(new StorageProfileName('alpha')));
        self::assertNull($set->profile(new StorageProfileName('missing')));
    }

    public function testEmptySetFailsClosed(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileSet([]);
    }

    public function testDuplicateProfileNameFailsClosed(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileSet([self::profile('same', 'one-store'), self::profile('same', 'two-store')]);
    }

    public function testDuplicateNamespaceFailsClosed(): void
    {
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileSet([self::profile('one', 'shared-store'), self::profile('two', 'shared-store')]);
    }

    public function testMaximumProfileCountIsExact(): void
    {
        $profiles = [];
        for ($position = 0; $position < 256; ++$position) {
            $suffix = str_pad((string) $position, 3, '0', STR_PAD_LEFT);
            $profiles[] = self::profile('profile-' . $suffix, 'store-' . $suffix);
        }
        self::assertSame(256, (new StorageProfileSet($profiles))->profileCount());
        $profiles[] = self::profile('profile-overflow', 'store-overflow');
        $this->expectException(InvalidStorageProfileException::class);
        new StorageProfileSet($profiles);
    }

    private static function profile(string $name, string $namespace): StorageProfileDeclaration
    {
        return new StorageProfileDeclaration(
            ContractVersion::Version1,
            new StorageProfileName($name),
            new StorageNamespace($namespace),
            new StorageCapabilitySet([StorageCapability::Read]),
            null,
            new StorageProfileLimits(1024, 4096, 25),
        );
    }
}
