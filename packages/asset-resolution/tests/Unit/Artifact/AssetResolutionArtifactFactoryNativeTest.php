<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Artifact;

use Kongono\AssetBuild\Build\NativeAssetBuilder;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Dependency\AssetDependency;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifactFactory;
use PHPUnit\Framework\TestCase;

final class AssetResolutionArtifactFactoryNativeTest extends TestCase
{
    public function testNativeEvidenceIsRevalidatedAndDependencyClosureIsComplete(): void
    {
        $sourceSet = self::sourceSet();
        $result = (new NativeAssetBuilder())->build($sourceSet);
        $factory = new AssetResolutionArtifactFactory();
        $left = $factory->fromNative($sourceSet, $result);
        $right = $factory->fromNative(self::sourceSet(), (new NativeAssetBuilder())->build(self::sourceSet()));
        self::assertSame($left->bytes(), $right->bytes());
        self::assertSame('native', $left->producerKind());
        self::assertSame(2, $left->entryCount());
        self::assertSame(1, $left->entry(new LogicalAssetIdentity('app'))?->dependencyCount());
        self::assertStringNotContainsString('src/app.js', $left->bytes());
        self::assertStringNotContainsString('console.log', $left->bytes());
    }

    private static function sourceSet(): AssetSourceSet
    {
        $app = new LogicalAssetIdentity('app');
        $style = new LogicalAssetIdentity('style');
        $set = new AssetDeclarationSet(ContractVersion::Version1, [
            new AssetDeclaration(ContractVersion::Version1, $style, AssetKind::Style, AssetContentType::TextCss),
            new AssetDeclaration(ContractVersion::Version1, $app, AssetKind::Script, AssetContentType::TextJavascript),
        ], [new AssetDependency(ContractVersion::Version1, $app, $style, AssetDependencyKind::Static)], [new AssetEntryPoint(ContractVersion::Version1, $app)]);

        return new AssetSourceSet($set, [new AssetSource($style, new AssetSourcePath('src/app.css'), 'body{}'), new AssetSource($app, new AssetSourcePath('src/app.js'), 'console.log(1)')]);
    }
}
