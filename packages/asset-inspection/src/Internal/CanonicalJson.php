<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Exception\InvalidAssetInspectionException;

/** Encodes package-owned ordered evidence with one LF terminator. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        } catch (JsonException $failure) {
            throw InvalidAssetInspectionException::invalid('serialization', $failure);
        }
    }

    private function __construct() {}
}
