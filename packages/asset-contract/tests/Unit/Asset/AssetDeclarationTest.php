<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Tests\Unit\Asset;

use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetDeclaration;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Exception\InvalidAssetContractException;
use Kongono\AssetContract\Identity\LogicalAssetIdentity;
use PHPUnit\Framework\TestCase;

final class AssetDeclarationTest extends TestCase
{
    public function testDeclarationExposesExactValuesAndStructuralEquality(): void
    {
        $declaration = new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), AssetKind::Script, AssetContentType::TextJavascript);
        self::assertSame(ContractVersion::Version1, $declaration->contractVersion());
        self::assertSame('app.main', $declaration->identity()->value());
        self::assertSame(AssetKind::Script, $declaration->kind());
        self::assertSame(AssetContentType::TextJavascript, $declaration->contentType());
        self::assertTrue($declaration->equals(new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), AssetKind::Script, AssetContentType::TextJavascript)));
        self::assertFalse($declaration->equals(new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity('app.alt'), AssetKind::Script, AssetContentType::TextJavascript)));
    }

    public function testInvalidKindContentRelationIsRejected(): void
    {
        $this->expectException(InvalidAssetContractException::class);
        new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity('app.main'), AssetKind::Script, AssetContentType::TextCss);
    }

    public function testEveryAllowedKindContentRelationIsAccepted(): void
    {
        $allowed = [
            AssetKind::Script->value => [AssetContentType::TextJavascript],
            AssetKind::Style->value => [AssetContentType::TextCss],
            AssetKind::Image->value => [AssetContentType::ImageAvif, AssetContentType::ImageGif, AssetContentType::ImageJpeg, AssetContentType::ImagePng, AssetContentType::ImageSvgXml, AssetContentType::ImageWebp],
            AssetKind::Font->value => [AssetContentType::FontWoff2],
            AssetKind::Media->value => [AssetContentType::AudioMpeg, AssetContentType::AudioOgg, AssetContentType::VideoMp4, AssetContentType::VideoWebm],
            AssetKind::WebAssembly->value => [AssetContentType::ApplicationWasm],
            AssetKind::Data->value => [AssetContentType::TextPlain, AssetContentType::ApplicationJson, AssetContentType::ApplicationOctetStream],
        ];
        foreach (AssetKind::cases() as $kind) {
            foreach ($allowed[$kind->value] as $contentType) {
                self::assertSame($contentType, (new AssetDeclaration(ContractVersion::Version1, new LogicalAssetIdentity('asset.' . str_replace('_', '-', $kind->value)), $kind, $contentType))->contentType());
            }
        }
    }
}
