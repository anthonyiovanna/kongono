<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns compact fixed-order UTF-8 JSON encoding and exact artifact framing. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const int MAX_BYTES = 1_048_576;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        self::rejectUnsupported($value);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /** @return array<string, mixed> */
    public static function decode(string $bytes): array
    {
        if ('' === $bytes || strlen($bytes) > self::MAX_BYTES || str_starts_with($bytes, "\xEF\xBB\xBF") || str_contains($bytes, "\r") || str_contains($bytes, "\0") || 1 !== preg_match('//u', $bytes) || !str_ends_with($bytes, "\n") || str_ends_with($bytes, "\n\n")) {
            throw new JsonException('Canonical JSON framing is invalid.');
        }
        $decoded = StrictJsonDecoder::decodeObject(substr($bytes, 0, -1), 8);
        self::rejectUnsupported($decoded);

        return $decoded;
    }

    private static function rejectUnsupported(mixed $value): void
    {
        if (is_float($value) || is_object($value) || is_resource($value)) {
            throw new JsonException('Canonical JSON value is unsupported.');
        }
        if (is_array($value)) {
            foreach ($value as $entry) {
                self::rejectUnsupported($entry);
            }
        }
    }

    private function __construct() {}
}
