<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Vite;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildLimits;
use Kongono\AssetAdapterContract\Adapter\AdapterBuildRequest;
use Kongono\AssetAdapterContract\AdapterContractVersion;
use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterVite\Tests\Fixture\FakeBuildProcessRunner;
use Kongono\AssetAdapterVite\Vite\ViteAdapterProfile;
use Kongono\AssetAdapterVite\Vite\ViteAssetBuildAdapter;
use Kongono\AssetAdapterVite\Vite\ViteToolchain;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class ViteAssetBuildAdapterTest extends TestCase
{
    public function testFakeBuildUsesExactCommandAndCleansWorkspace(): void
    {
        $runner = new FakeBuildProcessRunner('{"main.js":{"file":"assets/main.js","src":"main.js","isEntry":true}}', ['assets/main.js' => 'built']);
        $toolchain = $this->toolchain();
        $request = new AdapterBuildRequest(AdapterContractVersion::Version1, $this->sourceSet(), ViteAdapterProfile::ClientEs2022V1->identity(), $toolchain->identity(), new AdapterBuildLimits(5, 100, 100, 10_000, 10, 10_000, 20_000, 10));
        $result = (new ViteAssetBuildAdapter(str_replace('\\', '/', sys_get_temp_dir()), $toolchain, $runner))->build($request);
        self::assertSame(1, $runner->calls);
        self::assertInstanceOf(BuildProcessCommand::class, $runner->lastCommand);
        self::assertSame(['FORCE_COLOR' => '0', 'NODE_ENV' => 'production', 'NO_COLOR' => '1'], $runner->lastCommand->environment());
        self::assertSame('build', $runner->lastCommand->arguments()[2]);
        self::assertDirectoryDoesNotExist($runner->lastCommand->workingDirectory());
        self::assertSame(1, $result->outputSet()->outputCount());
    }private function toolchain(): ViteToolchain
    {
        $path = str_replace('\\', '/', PHP_BINARY);
        $hash = hash_file('sha256', $path);
        self::assertIsString($hash);
        return new ViteToolchain($path, $path, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION, '1.2.3', $hash, $hash);
    }private function sourceSet(): AssetSourceSet
    {
        $logical = new LogicalAssetIdentity('main');
        return new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript)], [], [new AssetEntryPoint(ContractVersion::Version1, $logical)]), [new AssetSource($logical, new AssetSourcePath('main.js'), 'source')]);
    }
}
