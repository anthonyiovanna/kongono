<?php

declare(strict_types=1);

namespace Kongono\SecurityPolicyBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Encodes the package's fixed-order canonical JSON documents. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

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
                throw new JsonException('The JSON root must use string keys.');
            }
            $object[$key] = $item;
        }

        return $object;
    }
}
