<?php

declare(strict_types=1);

namespace Kongono\StorageAdapterLocal\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Strictly decodes one bounded JSON object. */
#[Api(ApiStatus::Internal)]
final class StrictJsonDecoder
{
    /** @return array<string, mixed> */
    public static function decodeObject(string $json, int $maximumDepth): array
    {
        if (1 === preg_match('/"(?:[^"\\\\]|\\\\.)*"\s*:\s*[^,{}]+\s*,\s*"(?:[^"\\\\]|\\\\.)*"\s*:/s', $json) && self::hasDuplicateTopLevelKey($json)) {
            throw new JsonException('JSON object contains a duplicate member.');
        }
        if ($maximumDepth < 1) {
            throw new JsonException('JSON depth is outside the fixed bound.');
        }
        $decoded = json_decode($json, true, $maximumDepth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('JSON root must be an object.');
        }

        $copy = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new JsonException('JSON object key is invalid.');
            }
            $copy[$key] = $value;
        }

        return $copy;
    }

    private static function hasDuplicateTopLevelKey(string $json): bool
    {
        preg_match_all('/(?:\{|,)\s*"((?:[^"\\\\]|\\\\.)*)"\s*:/s', $json, $matches);
        $keys = [];
        $captures = self::stringList($matches[1]);
        foreach ($captures as $key) {
            $decoded = json_decode('"' . $key . '"', true, 2, JSON_THROW_ON_ERROR);
            if (!is_string($decoded)) {
                throw new JsonException('JSON object key is invalid.');
            }
            if (isset($keys[$decoded])) {
                return true;
            }
            $keys[$decoded] = true;
        }

        return false;
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            throw new JsonException('JSON object key capture is invalid.');
        }

        $strings = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new JsonException('JSON object key capture is invalid.');
            }
            $strings[] = $item;
        }

        return $strings;
    }

    private function __construct() {}
}
