<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Strictly decodes one bounded JSON object without interpreting its values. */
#[Api(ApiStatus::Internal)]
final class StrictJsonDecoder
{
    /** @return array<string, mixed> */
    public static function decodeObject(string $json, int $depth = 32): array
    {
        if ($depth < 1 || $depth > 32) {
            throw new JsonException('Canonical JSON depth must be within the fixed bound.');
        }
        $decoded = json_decode($json, true, $depth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('Canonical JSON root must be an object.');
        }
        $object = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new JsonException('Canonical JSON keys must be strings.');
            }
            $object[$key] = $value;
        }

        return $object;
    }

    private function __construct() {}
}
