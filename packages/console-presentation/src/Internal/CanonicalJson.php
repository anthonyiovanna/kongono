<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Internal;

use JsonException;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ConsolePresentation\Exception\InvalidConsolePresentationException;

/** Centralizes compact LF-terminated version-1 presentation JSON. */
#[Api(ApiStatus::Internal)]
final class CanonicalJson
{
    public const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    /** @param array<string, mixed> $value */
    public static function encode(array $value): string
    {
        try {
            return json_encode($value, self::FLAGS) . "\n";
        } catch (JsonException) {
            throw InvalidConsolePresentationException::renderingFailed();
        }
    }

    private function __construct() {}
}
