<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Asset;

use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Dependency\AssetDependencyKind;
use PHPUnit\Framework\TestCase;

final class AssetVocabularyTest extends TestCase
{
    public function testClosedEnumInventoriesAreExact(): void
    {
        self::assertSame(['script', 'style', 'image', 'font', 'media', 'web_assembly', 'data'], array_column(AssetKind::cases(), 'value'));
        self::assertSame([
            'text/css', 'text/javascript', 'text/plain', 'application/json', 'application/wasm', 'application/octet-stream',
            'image/avif', 'image/gif', 'image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'font/woff2',
            'audio/mpeg', 'audio/ogg', 'video/mp4', 'video/webm',
        ], array_column(AssetContentType::cases(), 'value'));
        self::assertSame(['static', 'dynamic', 'resource'], array_column(AssetDependencyKind::cases(), 'value'));
        self::assertSame(['sha256', 'sha384', 'sha512'], array_column(IntegrityAlgorithm::cases(), 'value'));
        self::assertSame(['none', 'preload', 'module_preload'], array_column(PreloadHint::cases(), 'value'));
    }
}
