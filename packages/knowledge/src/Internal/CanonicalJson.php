<?php

declare(strict_types=1);

namespace Kongono\Knowledge\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Centralizes the one compact UTF-8 JSON grammar used by knowledge artifacts. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return json_encode($value, self::FLAGS) . "\n";
    }

    /**
     * @param int<1, max> $depth
     *
     * @return array<string, mixed>
     */
    public static function decodeObject(string $bytes, int $depth): array
    {
        try {
            $value = json_decode($bytes, true, $depth, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw $exception;
        }

        if (!is_array($value) || array_is_list($value)) {
            throw new JsonException('The canonical JSON root must be an object.');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new JsonException('The canonical JSON object has a non-string key.');
            }
            $object[$key] = $item;
        }

        return $object;
    }
}
