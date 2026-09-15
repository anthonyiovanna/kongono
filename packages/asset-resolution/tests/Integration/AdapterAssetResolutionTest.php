<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Integration;

use Kongono\AssetAdapterContract\Adapter\AdapterBuildResult;
use Kongono\AssetAdapterContract\Artifact\AdapterBuildArtifactValidator;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Identity\AdapterProfileIdentity;
use Kongono\AssetAdapterContract\Identity\ToolchainIdentity;
use Kongono\AssetAdapterContract\Output\AdaptedAssetOutput;
use Kongono\AssetAdapterContract\Output\AdapterEntryBinding;
use Kongono\AssetAdapterContract\Output\AdapterOutputSet;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetBuild\Source\AssetSource;
use Kongono\AssetBuild\Source\AssetSourcePath;
use Kongono\AssetBuild\Source\AssetSourceSet;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetDeclarationSet;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\EntryPoint\AssetEntryPoint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifactFactory;
use PHPUnit\Framework\TestCase;

final class AdapterAssetResolutionTest extends TestCase
{
    public function testAdapterOutputNormalizesWithoutPrivateBytesOrPaths(): void
    {
        $logical = new LogicalAssetIdentity('app');
        $decl = new AssetDeclarationSet(ContractVersion::Version1, [new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript)], [], [new AssetEntryPoint(ContractVersion::Version1, $logical)]);
        $source = new AssetSourceSet($decl, [new AssetSource($logical, new AssetSourcePath('private/source.js'), 'private-source')]);
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = ToolchainIdentity::fromEvidence('toolchain');
        $outputIdentity = AdapterOutputIdentity::fromProducerKey($profile, 'bundle.js');
        $bytes = 'private-output';
        $content = ContentIdentity::fromBytes($bytes);
        $output = new AdaptedAssetOutput($outputIdentity, AssetKind::Script, AssetContentType::TextJavascript, $content, strlen($bytes), new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None, AssetOutputPath::fromContentIdentity($content), $bytes);
        $set = new AdapterOutputSet($source, [$output], [new AdapterEntryBinding($logical, $outputIdentity)], []);
        $adapterArtifact = (new AdapterBuildArtifactValidator())->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', $set);
        $artifact = (new AssetResolutionArtifactFactory())->fromAdapter(new AdapterBuildResult($adapterArtifact, $set, [$output->path()->value() => $bytes]));
        self::assertStringNotContainsString('private/source.js', $artifact->bytes());
        self::assertStringNotContainsString('private-source', $artifact->bytes());
        self::assertStringNotContainsString('private-output', $artifact->bytes());
    }
}
