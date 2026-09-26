<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Encodes bounded compact fixed-order JSON. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const int MAX_BYTES = 65_536;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($encoded) > self::MAX_BYTES) {
            throw new JsonException('Canonical JSON exceeds its bound.');
        }

        return $encoded;
    }

    /** @return array<string, mixed> */
    public static function decode(string $bytes): array
    {
        if ('' === $bytes || strlen($bytes) > self::MAX_BYTES || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes)) {
            throw new JsonException('Canonical JSON framing is invalid.');
        }
        $decoded = StrictJsonDecoder::decodeObject($bytes, 16);
        if (self::encode($decoded) !== $bytes) {
            throw new JsonException('JSON is not canonical.');
        }

        return $decoded;
    }

    private function __construct() {}
}
