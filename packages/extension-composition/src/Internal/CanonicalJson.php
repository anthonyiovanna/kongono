<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;

/** Encodes closed package-owned documents without ambient or lossy inputs. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    /** @param array<string, mixed> $document */
    public static function encode(array $document): string
    {
        try {
            return json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $failure) {
            throw InvalidExtensionGrantPlanException::invalid('canonical_json_failed', previous: $failure);
        }
    }

    private function __construct() {}
}
