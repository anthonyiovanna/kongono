<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Dependency;

use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class AssetDependencyTest extends TestCase
{
    public function testDependencyShapeEqualityAndCanonicalKeyAreExact(): void
    {
        $dependency = new AssetDependency(ContractVersion::Version1, new LogicalAssetIdentity('a'), new LogicalAssetIdentity('b'), AssetDependencyKind::Static);
        self::assertSame(ContractVersion::Version1, $dependency->contractVersion());
        self::assertSame('a', $dependency->source()->value());
        self::assertSame('b', $dependency->target()->value());
        self::assertSame(AssetDependencyKind::Static, $dependency->kind());
        self::assertSame("a\0b\0static", $dependency->orderingKey());
        self::assertTrue($dependency->equals(new AssetDependency(ContractVersion::Version1, new LogicalAssetIdentity('a'), new LogicalAssetIdentity('b'), AssetDependencyKind::Static)));
    }

    public function testSelfDependencyIsRejected(): void
    {
        $this->expectException(InvalidAssetContractException::class);
        new AssetDependency(ContractVersion::Version1, new LogicalAssetIdentity('a'), new LogicalAssetIdentity('a'), AssetDependencyKind::Dynamic);
    }
}
