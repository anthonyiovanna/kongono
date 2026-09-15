<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Delivery;

use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\AssetDeliveryMetadata;
use Kongono\AssetContract\Delivery\AssetIntegrity;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Delivery\PreloadHint;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\ContentIdentity;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetDeliveryMetadataTest extends TestCase
{
    public function testIntegrityAlgorithmsDigestLengthsAndMetadataEqualityAreExact(): void
    {
        $content = ContentIdentity::fromBytes('script');
        $integrity = new AssetIntegrity(IntegrityAlgorithm::Sha256, $content->value());
        $metadata = new AssetDeliveryMetadata(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), AssetKind::Script, AssetContentType::TextJavascript, $content, 6, $integrity, PreloadHint::ModulePreload);
        self::assertSame(6, $metadata->byteCount());
        self::assertSame(PreloadHint::ModulePreload, $metadata->preloadHint());
        self::assertTrue($integrity->equals(new AssetIntegrity(IntegrityAlgorithm::Sha256, $content->value())));
        self::assertTrue($metadata->equals(new AssetDeliveryMetadata(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), AssetKind::Script, AssetContentType::TextJavascript, new ContentIdentity($content->value()), 6, new AssetIntegrity(IntegrityAlgorithm::Sha256, $content->value()), PreloadHint::ModulePreload)));
        self::assertSame(str_repeat('a', 96), (new AssetIntegrity(IntegrityAlgorithm::Sha384, str_repeat('a', 96)))->digest());
        self::assertSame(str_repeat('b', 128), (new AssetIntegrity(IntegrityAlgorithm::Sha512, str_repeat('b', 128)))->digest());
    }

    #[DataProvider('invalidMetadataProvider')]
    public function testInvalidDeliveryRelationsAreRejected(callable $factory): void
    {
        $this->expectException(InvalidAssetContractException::class);
        $factory();
    }

    /** @return iterable<string, array{callable(): object}> */
    public static function invalidMetadataProvider(): iterable
    {
        $content = new ContentIdentity(str_repeat('a', 64));
        $base = static fn(AssetKind $kind, AssetContentType $type, int $bytes, ?AssetIntegrity $integrity, PreloadHint $hint): AssetDeliveryMetadata => new AssetDeliveryMetadata(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), $kind, $type, $content, $bytes, $integrity, $hint);
        yield 'digest length' => [static fn() => new AssetIntegrity(IntegrityAlgorithm::Sha384, str_repeat('a', 64))];
        yield 'uppercase digest' => [static fn() => new AssetIntegrity(IntegrityAlgorithm::Sha256, str_repeat('A', 64))];
        yield 'negative bytes' => [static fn() => $base(AssetKind::Script, AssetContentType::TextJavascript, -1, null, PreloadHint::None)];
        yield 'oversized bytes' => [static fn() => $base(AssetKind::Script, AssetContentType::TextJavascript, 67_108_865, null, PreloadHint::None)];
        yield 'wrong content type' => [static fn() => $base(AssetKind::Script, AssetContentType::TextCss, 1, null, PreloadHint::None)];
        yield 'module preload kind' => [static fn() => $base(AssetKind::Style, AssetContentType::TextCss, 1, null, PreloadHint::ModulePreload)];
        yield 'sha256 disagreement' => [static fn() => $base(AssetKind::Script, AssetContentType::TextJavascript, 1, new AssetIntegrity(IntegrityAlgorithm::Sha256, str_repeat('b', 64)), PreloadHint::None)];
    }
}
