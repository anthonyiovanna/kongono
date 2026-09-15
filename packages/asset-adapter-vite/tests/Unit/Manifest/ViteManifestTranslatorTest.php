<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Manifest;

use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterVite\Manifest\ViteManifestDecoder;
use Kongono\AssetAdapterVite\Manifest\ViteManifestTranslator;
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

final class ViteManifestTranslatorTest extends TestCase
{
    public function testEntryAndStaticDynamicResourceRelationsTranslate(): void
    {
        $logical = new LogicalAssetIdentity('main');
        $declaration = new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript);
        $sourceSet = new AssetSourceSet(new AssetDeclarationSet(ContractVersion::Version1, [$declaration], [], [new AssetEntryPoint(ContractVersion::Version1, $logical)]), [new AssetSource($logical, new AssetSourcePath('main.js'), 'source')]);
        $manifest = (new ViteManifestDecoder())->decode('{"main.js":{"file":"assets/main.js","src":"main.js","isEntry":true,"imports":["shared"],"dynamicImports":["lazy"],"css":["assets/main.css"]},"shared":{"file":"assets/shared.js"},"lazy":{"file":"assets/lazy.js"}}');
        $set = (new ViteManifestTranslator())->translate($sourceSet, new AdapterProfileIdentity('vite-client-es2022/1'), $manifest, ['assets/lazy.js' => 'l','assets/main.css' => 'c','assets/main.js' => 'm','assets/shared.js' => 's']);
        self::assertSame(4, $set->outputCount());
        self::assertSame(3, $set->dependencyCount());
        self::assertSame(1, $set->bindingCount());
    }
}
