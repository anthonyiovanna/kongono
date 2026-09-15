<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Integration;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
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
use Kongono\AssetResolution\Artifact\AssetResolutionArtifactFactory;
use PHPUnit\Framework\TestCase;

final class NativeAssetResolutionTest extends TestCase
{
    public function testEmptyNativeBuildProducesFirstClassNoAssetArtifact(): void
    {
        $sources = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [], [], []), []);
        $artifact = (new AssetResolutionArtifactFactory())->fromNative($sources, (new NativeAssetBuilder())->build($sources));
        self::assertSame(0, $artifact->entryCount());
        self::assertNull($artifact->entry(new LogicalAssetIdentity('absent')));
    }

    public function testRepeatedNativeNormalizationIsByteIdentical(): void
    {
        $identity = new LogicalAssetIdentity('app');
        $set = new AssetDeclarationSet(ContractVersion::Version1, [new AssetDeclaration(ContractVersion::Version1, $identity, AssetKind::Script, AssetContentType::TextJavascript)], [], [new AssetEntryPoint(ContractVersion::Version1, $identity)]);
        $sources = new AssetSourceSet($set, [new AssetSource($identity, new AssetSourcePath('src/app.js'), 'asset')]);
        $builder = new NativeAssetBuilder();
        $factory = new AssetResolutionArtifactFactory();
        self::assertSame($factory->fromNative($sources, $builder->build($sources))->bytes(), $factory->fromNative($sources, $builder->build($sources))->bytes());
    }
}
