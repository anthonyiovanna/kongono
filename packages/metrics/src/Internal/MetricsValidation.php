<?php

declare(strict_types=1);

namespace Kongono\Metrics\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Centralizes closed metric value grammar without retaining caller data. */
#[Api(ApiStatus::Internal)]
final class MetricsValidation
{
    /** @var list<string> */
    private const array DENIED_DIMENSION_FAMILIES = [
        'tenant',
        'principal',
        'identity',
        'user',
        'resource',
        'session',
        'credential',
        'secret',
        'token',
        'csrf',
        'policy',
        'request.body',
        'url',
        'query',
        'route.parameter',
        'exception',
    ];

    private function __construct() {}

    public static function isUnit(string $value): bool
    {
        return 1 === preg_match('/\A[A-Za-z0-9%*._\/{\}-]{1,63}\z/D', $value);
    }

    public static function isDimensionKeyAllowed(string $key): bool
    {
        if (str_starts_with($key, 'metric.') || str_starts_with($key, 'trace.')) {
            return false;
        }

        foreach (self::DENIED_DIMENSION_FAMILIES as $family) {
            if ($key === $family || str_starts_with($key, $family . '.')) {
                return false;
            }
        }

        return true;
    }
}
