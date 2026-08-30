<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Centralizes compact UTF-8 canonical JSON encoding. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    private const int FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return json_encode($value, self::FLAGS) . "\n";
    }

    /** @return array<string, mixed> */
    public static function decodeObject(string $bytes, int $depth): array
    {
        $value = (new StrictJsonDecoder($bytes, $depth))->decode();
        if (!is_array($value) || array_is_list($value)) {
            throw new JsonException('The JSON root must be an object.');
        }
        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new JsonException('The JSON object key must be text.');
            }
            $object[$key] = $item;
        }
        return $object;
    }
}
