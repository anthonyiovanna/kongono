<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Tests\Unit\Output;

use Kongono\AssetAdapterContract\Identity\AdapterOutputIdentity;
use Kongono\AssetAdapterContract\Output\AdaptedAssetOutput;
use Kongono\AssetBuild\Build\AssetOutputPath;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;
use PHPUnit\Framework\TestCase;

final class AdaptedAssetOutputTest extends TestCase
{
    public function testOutputValidatesAllByteRelations(): void
    {
        $bytes = 'asset';
        $content = ContentIdentity::fromBytes($bytes);
        $output = new AdaptedAssetOutput(new AdapterOutputIdentity(str_repeat('a', 64)), AssetKind::Script, AssetContentType::TextJavascript, $content, 5, new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None, AssetOutputPath::fromContentIdentity($content), $bytes);
        self::assertSame($bytes, $output->bytes());
        self::assertSame('objects/sha256/' . substr($content->value(), 0, 2) . '/' . $content->value(), $output->path()->value());
    }
}
