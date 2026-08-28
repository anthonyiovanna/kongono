<?php

declare(strict_types=1);

namespace Kongono\Observability\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Owns bounded validation shared by neutral observability values. */
#[Api(ApiStatus::Internal)]
final class ObservabilityValidation
{
    public static function isAttributeKey(string $value): bool
    {
        return '' !== $value
            && strlen($value) <= 128
            && 1 === preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*$/D', $value);
    }

    public static function isBoundedText(string $value, int $maximumBytes): bool
    {
        return strlen($value) <= $maximumBytes
            && 1 === preg_match('//u', $value)
            && 0 === preg_match('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]/u', $value);
    }
}
