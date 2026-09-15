<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Adapter;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use PHPUnit\Framework\TestCase;

final class AdapterBuildRequestTest extends TestCase
{
    public function testRequestCarriesExactTypedEvidence(): void
    {
        $set = AdapterContractProbe::emptySourceSet();
        $request = new AdapterBuildRequest(AdapterContractVersion::Version1, $set, new AdapterProfileIdentity('tool/profile.1'), new ToolchainIdentity(str_repeat('a', 64)), new AdapterBuildLimits(1, 0, 0, 1, 0, 0, 0, 0));
        self::assertSame($set, $request->sourceSet());
        self::assertSame('tool/profile.1', $request->profileIdentity()->value());
    }
}
