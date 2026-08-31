<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionInspection\Exception\InvalidExtensionInspectionValueException;

/** Encodes or decodes only package-owned deterministic language-neutral values. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @return array<string, mixed> */
    public static function decodeObject(string $bytes): array
    {
        try {
            $value = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $failure) {
            throw InvalidExtensionInspectionValueException::aggregate('serialization', $failure);
        }
        if (!is_array($value) || array_is_list($value)) {
            throw InvalidExtensionInspectionValueException::aggregate('serialization');
        }
        $object = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                throw InvalidExtensionInspectionValueException::aggregate('serialization');
            }
            $object[$key] = $entry;
        }
        return $object;
    }

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidExtensionInspectionValueException::value('serialization', $failure);
        }
    }

    private function __construct() {}
}
