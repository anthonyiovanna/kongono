<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Artifact;

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

final class AssetResolutionArtifactFactoryAdapterTest extends TestCase
{
    public function testAdapterEvidenceIsRevalidatedAndCanonical(): void
    {
        $result = self::adapterResult();
        $artifact = (new AssetResolutionArtifactFactory())->fromAdapter($result);
        self::assertSame('adapter', $artifact->producerKind());
        self::assertSame(1, $artifact->entryCount());
        self::assertSame($artifact->bytes(), (new AssetResolutionArtifactFactory())->fromAdapter(self::adapterResult())->bytes());
        self::assertStringNotContainsString('src/app.js', $artifact->bytes());
        self::assertStringNotContainsString('console.log', $artifact->bytes());
    }

    private static function adapterResult(): AdapterBuildResult
    {
        $logical = new LogicalAssetIdentity('app');
        $declarations = new AssetDeclarationSet(ContractVersion::Version1, [new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript)], [], [new AssetEntryPoint(ContractVersion::Version1, $logical)]);
        $source = new AssetSourceSet($declarations, [new AssetSource($logical, new AssetSourcePath('src/app.js'), 'source')]);
        $profile = new AdapterProfileIdentity('vite/client.1');
        $toolchain = ToolchainIdentity::fromEvidence('toolchain');
        $outputId = AdapterOutputIdentity::fromProducerKey($profile, 'app.js');
        $bytes = 'console.log(1)';
        $content = ContentIdentity::fromBytes($bytes);
        $output = new AdaptedAssetOutput($outputId, AssetKind::Script, AssetContentType::TextJavascript, $content, strlen($bytes), new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None, AssetOutputPath::fromContentIdentity($content), $bytes);
        $set = new AdapterOutputSet($source, [$output], [new AdapterEntryBinding($logical, $outputId)], []);
        $artifact = (new AdapterBuildArtifactValidator())->create($source, $profile, $toolchain, 'kongono/asset-adapter-vite', $set);

        return new AdapterBuildResult($artifact, $set, [$output->path()->value() => $bytes]);
    }
}
