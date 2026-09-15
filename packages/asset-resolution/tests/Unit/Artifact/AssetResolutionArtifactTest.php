<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Artifact;

use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use Kongono\AssetResolution\Artifact\AssetResolutionArtifact;
use Kongono\AssetResolution\Artifact\AssetResolutionEntry;
use Kongono\AssetResolution\Artifact\AssetResolutionObject;
use Kongono\AssetResolution\ContractVersion;
use PHPUnit\Framework\TestCase;

final class AssetResolutionArtifactTest extends TestCase
{
    public function testArtifactSortsAndDetachesEntriesAndCanonicalizesBytes(): void
    {
        $left = self::entry('z');
        $right = self::entry('a');
        $input = [$left, $right];
        $artifact = new AssetResolutionArtifact(ContractVersion::Version1, 'native', str_repeat('1', 64), str_repeat('2', 64), str_repeat('3', 64), $input);
        $input = [];
        self::assertSame(['a', 'z'], array_map(static fn(AssetResolutionEntry $entry): string => $entry->identity()->value(), $artifact->entries()));
        self::assertStringContainsString('"schema":"kongono.asset-resolution-artifact"', $artifact->bytes());
        self::assertSame(hash('sha256', $artifact->bytes()), $artifact->identity()->value());
        self::assertCount(2, $artifact->entries());
    }

    private static function entry(string $logical): AssetResolutionEntry
    {
        $bytes = $logical;
        $content = ContentIdentity::fromBytes($bytes);
        $object = new AssetResolutionObject('objects/sha256/' . substr($content->value(), 0, 2) . '/' . $content->value(), AssetKind::Script, AssetContentType::TextJavascript, $content, strlen($bytes), new AssetIntegrity(IntegrityAlgorithm::Sha384, hash('sha384', $bytes)), PreloadHint::None);

        return new AssetResolutionEntry(new LogicalAssetIdentity($logical), $object, []);
    }
}
