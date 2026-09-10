<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns the package's compact fixed-order UTF-8 JSON encoding and framing. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const int MAX_ARTIFACT_BYTES = 262_144;
    public const int MAX_GENERATED_BYTES = 524_288;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return self::encodeInline($value) . "\n";
    }

    /** @param array<string, mixed> $value */
    public static function encodeInline(array $value): string
    {
        self::rejectUnsupported($value);

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public static function decodeObject(string $bytes): array
    {
        if (
            '' === $bytes
            || strlen($bytes) > self::MAX_ARTIFACT_BYTES
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            throw new JsonException('Invalid canonical JSON framing.');
        }

        $decoded = StrictJsonDecoder::decodeObject(substr($bytes, 0, -1));
        self::rejectUnsupported($decoded);

        return $decoded;
    }

    /** @return array<string, mixed> */
    public static function decodeInlineObject(string $json): array
    {
        if ('' === $json || str_contains($json, "\r") || str_contains($json, "\n") || str_contains($json, "\0") || 1 !== preg_match('//u', $json)) {
            throw new JsonException('Invalid canonical inline JSON.');
        }
        $decoded = StrictJsonDecoder::decodeObject($json);
        self::rejectUnsupported($decoded);

        return $decoded;
    }

    private static function rejectUnsupported(mixed $value): void
    {
        if (is_float($value) || is_object($value) || is_resource($value)) {
            throw new JsonException('Unsupported canonical JSON value.');
        }
        if (is_array($value)) {
            foreach ($value as $entry) {
                self::rejectUnsupported($entry);
            }
        }
    }

    private function __construct() {}
}
