<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns compact UTF-8 JSON encoding and strict bounded object decoding. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    private const int MAX_ARTIFACT_BYTES = 65_536;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        self::rejectUnsupported($value);

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
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
        $decoded = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('Canonical JSON root must be an object.');
        }
        self::rejectUnsupported($decoded);
        $object = [];
        foreach ($decoded as $key => $entry) {
            if (!is_string($key)) {
                throw new JsonException('Canonical JSON object keys must be strings.');
            }
            $object[$key] = $entry;
        }

        return $object;
    }

    private static function rejectUnsupported(mixed $value): void
    {
        if (is_float($value) || is_object($value) || is_resource($value)) {
            throw new JsonException('Unsupported canonical JSON value.');
        }
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $entry) {
            self::rejectUnsupported($entry);
        }
    }

    private function __construct() {}
}
