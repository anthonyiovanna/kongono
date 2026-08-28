<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns bounded control-free UTF-8 and integer validation without normalization. */
#[Api(ApiStatus::Internal)]
final class OpenTelemetryValidation
{
    private function __construct() {}

    public static function boundedText(string $value, int $maximumBytes, bool $emptyAllowed = true): bool
    {
        return ($emptyAllowed || '' !== $value)
            && strlen($value) <= $maximumBytes
            && 1 === preg_match('//u', $value)
            && 0 === preg_match('/[\x00-\x1F\x7F]/', $value);
    }

    public static function optionalText(?string $value, int $maximumBytes): bool
    {
        return null === $value || self::boundedText($value, $maximumBytes, false);
    }
}
