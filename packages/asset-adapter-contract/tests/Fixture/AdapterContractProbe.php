<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Fixture;

use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\ContractVersion;

final class AdapterContractProbe
{
    public static function emptySourceSet(): AssetSourceSet
    {
        return new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
    }
    private function __construct() {}
}
