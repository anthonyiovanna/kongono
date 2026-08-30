<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphComposition\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns the exact compact JSON form admitted from the in-memory artifact. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const int FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /** @return array<string, mixed> */
    public static function decodeObject(string $bytes): array
    {
        $value = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($value) || array_is_list($value)) {
            throw new JsonException('The JSON root must be an object.');
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        return json_encode($value, self::FLAGS) . "\n";
    }
}
