<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Asset\AssetContentType;
use Kongono\AssetContract\Asset\AssetKind;
use Kongono\AssetContract\ContractVersion;
use Kongono\AssetContract\Delivery\IntegrityAlgorithm;
use Kongono\AssetContract\Exception\InvalidAssetContractException;

/** Centralizes exact local validation without exposing a generic validator. */
#[Api(ApiStatus::Internal)]
final class ContractGuard
{
    public const int MAX_CONTENT_BYTES = 67_108_864;

    public static function sameContractVersion(ContractVersion $left, ContractVersion $right): bool
    {
        return match ($left) {
            ContractVersion::Version1 => match ($right) {
                ContractVersion::Version1 => true,
            },
        };
    }

    public static function logicalIdentity(string $value): string
    {
        if (strlen($value) < 1 || strlen($value) > 128 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidAssetContractException::invalidIdentity('logical_asset_identity_grammar');
        }

        return $value;
    }

    public static function contentIdentity(string $value): string
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $value)) {
            throw InvalidAssetContractException::invalidIdentity('content_identity_grammar');
        }

        return $value;
    }

    public static function contentBytes(string $bytes): void
    {
        if (strlen($bytes) > self::MAX_CONTENT_BYTES) {
            throw InvalidAssetContractException::contractBoundExceeded('content_bytes', self::MAX_CONTENT_BYTES, strlen($bytes));
        }
    }

    public static function kindContentType(AssetKind $kind, AssetContentType $contentType, bool $delivery = false): void
    {
        $valid = match ($kind) {
            AssetKind::Script => AssetContentType::TextJavascript === $contentType,
            AssetKind::Style => AssetContentType::TextCss === $contentType,
            AssetKind::Image => in_array($contentType, [AssetContentType::ImageAvif, AssetContentType::ImageGif, AssetContentType::ImageJpeg, AssetContentType::ImagePng, AssetContentType::ImageSvgXml, AssetContentType::ImageWebp], true),
            AssetKind::Font => AssetContentType::FontWoff2 === $contentType,
            AssetKind::Media => in_array($contentType, [AssetContentType::AudioMpeg, AssetContentType::AudioOgg, AssetContentType::VideoMp4, AssetContentType::VideoWebm], true),
            AssetKind::WebAssembly => AssetContentType::ApplicationWasm === $contentType,
            AssetKind::Data => in_array($contentType, [AssetContentType::TextPlain, AssetContentType::ApplicationJson, AssetContentType::ApplicationOctetStream], true),
        };
        if (!$valid) {
            if ($delivery) {
                throw InvalidAssetContractException::invalidDeliveryMetadata('kind_content_type_relation', assetKind: $kind->value);
            }
            throw InvalidAssetContractException::invalidDeclaration('kind_content_type_relation', assetKind: $kind->value);
        }
    }

    public static function integrityDigest(IntegrityAlgorithm $algorithm, string $digest): string
    {
        $length = match ($algorithm) {
            IntegrityAlgorithm::Sha256 => 64,
            IntegrityAlgorithm::Sha384 => 96,
            IntegrityAlgorithm::Sha512 => 128,
        };
        if (1 !== preg_match('/\A[a-f0-9]{' . $length . '}\z/D', $digest)) {
            throw InvalidAssetContractException::invalidDeliveryMetadata('integrity_digest_grammar', algorithm: $algorithm->value);
        }

        return $digest;
    }
}
