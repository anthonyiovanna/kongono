<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Output;

use Kongono\AssetAdapterContract\Exception\InvalidAssetAdapterContractException;
use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
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
use PHPUnit\Framework\TestCase;

final class AdapterOutputSetTest extends TestCase
{
    public function testEveryEntryBindsAndEveryOutputIsReachable(): void
    {
        [$sourceSet, $logical] = $this->sourceSet();
        $bytes = 'compiled';
        $content = ContentIdentity::fromBytes($bytes);
        $id = new AdapterOutputIdentity(str_repeat('a', 64));
        $output = new AdaptedAssetOutput($id, AssetKind::Script, AssetContentType::TextJavascript, $content, strlen($bytes), new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None, AssetOutputPath::fromContentIdentity($content), $bytes);
        $set = new AdapterOutputSet($sourceSet, [$output], [new AdapterEntryBinding($logical, $id)], []);
        self::assertSame(1, $set->outputCount());
        $this->expectException(InvalidAssetAdapterContractException::class);
        new AdapterOutputSet($sourceSet, [$output], [], []);
    }

    /** @return array{0: AssetSourceSet, 1: LogicalAssetIdentity} */
    private function sourceSet(): array
    {
        $logical = new LogicalAssetIdentity('app');
        $declaration = new AssetDeclaration(ContractVersion::Version1, $logical, AssetKind::Script, AssetContentType::TextJavascript);
        $set = new AssetDeclarationSet(ContractVersion::Version1, [$declaration], [], [new AssetEntryPoint(ContractVersion::Version1, $logical)]);
        return [new AssetSourceSet($set, [new AssetSource($logical, new AssetSourcePath('app.js'), 'source')]), $logical];
    }
}
