<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Integration;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterContract\Tests\Fixture\AdapterContractProbe;
use Kongono\AssetAdapterVite\Tests\Fixture\FakeBuildProcessRunner;
use Kongono\AssetAdapterVite\Vite\ViteAdapterProfile;
use Kongono\AssetAdapterVite\Vite\ViteAssetBuildAdapter;
use Kongono\AssetAdapterVite\Vite\ViteToolchain;
use PHPUnit\Framework\TestCase;

final class ViteAdapterDeterminismTest extends TestCase
{
    public function testEmptyBuildShortCircuitsWithoutWorkspaceOrProcessAndRepeatsExactly(): void
    {
        $runner = new FakeBuildProcessRunner();
        $path = str_replace('\\', '/', PHP_BINARY);
        $hash = hash_file('sha256', $path);
        self::assertIsString($hash);
        $toolchain = new ViteToolchain($path, $path, '8.5.9', '1.2.3', $hash, $hash);
        $request = new AdapterBuildRequest(AdapterContractVersion::Version1, AdapterContractProbe::emptySourceSet(), ViteAdapterProfile::ClientEs2022V1->identity(), $toolchain->identity(), new AdapterBuildLimits(1, 0, 0, 1, 0, 0, 0, 0));
        $adapter = new ViteAssetBuildAdapter(str_replace('\\', '/', sys_get_temp_dir()), $toolchain, $runner);
        $left = $adapter->build($request);
        $right = $adapter->build($request);
        self::assertSame(0, $runner->calls);
        self::assertSame($left->artifact()->bytes(), $right->artifact()->bytes());
    }
}
