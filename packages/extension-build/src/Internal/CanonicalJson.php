<?php

declare(strict_types=1);

namespace Kongono\ExtensionBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns the package's compact UTF-8 JSON encoding and bounded decoding rules. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * @param positive-int $depth
     * @return array<string, mixed>
     */
    public static function decodeObject(string $bytes, int $depth, int $maximumBytes): array
    {
        if (
            '' === $bytes
            || strlen($bytes) > $maximumBytes
            || str_starts_with($bytes, "\xEF\xBB\xBF")
            || str_contains($bytes, "\r")
            || str_contains($bytes, "\0")
            || 1 !== preg_match('//u', $bytes)
            || !str_ends_with($bytes, "\n")
            || str_ends_with($bytes, "\n\n")
        ) {
            throw new JsonException('Invalid canonical JSON framing.');
        }

        $decoded = json_decode($bytes, true, $depth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('Canonical JSON root must be an object.');
        }

        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new JsonException('Canonical JSON object keys must be strings.');
            }
            $object[$key] = $value;
        }

        return $object;
    }

    private function __construct() {}
}
