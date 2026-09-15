<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\EntryPoint;

use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class AssetEntryPointTest extends TestCase
{
    public function testEntryPointIsInertAndStructurallyEqual(): void
    {
        $entryPoint = new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('app.main'));
        self::assertSame(ContractVersion::Version1, $entryPoint->contractVersion());
        self::assertSame('app.main', $entryPoint->identity()->value());
        self::assertTrue($entryPoint->equals(new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('app.main'))));
        self::assertFalse($entryPoint->equals(new AssetEntryPoint(ContractVersion::Version1, new LogicalAssetIdentity('app.alt'))));
    }
}
