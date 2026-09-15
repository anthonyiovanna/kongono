<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Tests\Unit\Http;

use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetResolution\Http\AssetHttpMetadata;
use Kongono\AssetResolution\Policy\AssetCachePolicy;
use PHPUnit\Framework\TestCase;

final class AssetHttpMetadataTest extends TestCase
{
    public function testMetadataIsTypedAndCallerSelected(): void
    {
        $identity = ContentIdentity::fromBytes('asset');
        $metadata = new AssetHttpMetadata(AssetContentType::TextJavascript, 5, $identity, AssetCachePolicy::PublicImmutable);
        self::assertSame(AssetContentType::TextJavascript, $metadata->contentType());
        self::assertSame(5, $metadata->contentLength());
        self::assertSame('"sha256-' . $identity->value() . '"', $metadata->etag());
        self::assertSame('public, max-age=31536000, immutable', $metadata->cacheControl());
    }
}
