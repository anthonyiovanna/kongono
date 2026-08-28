<?php

declare(strict_types=1);

namespace Kongono\Tracing\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\ObservationStatus;
use Kongono\Tracing\Exception\InvalidTracingValueException;

/** Owns the exact bounded validation shared by tracing runtime values. */
#[Api(ApiStatus::Internal)]
final class TracingValidation
{
    /** @var array<string, true> */
    private const RESERVED_KEYS = [
        'trace.id' => true,
        'trace.span.id' => true,
        'trace.parent_span.id' => true,
        'trace.sampled' => true,
        'trace.start_ns' => true,
        'trace.end_ns' => true,
        'trace.duration_ns' => true,
    ];

    public static function isIdentifier(string $value, int $length): bool
    {
        return strlen($value) === $length
            && 1 === preg_match('/^[0-9a-f]+$/D', $value)
            && $value !== str_repeat('0', $length);
    }

    public static function attributes(AttributeSet $attributes): void
    {
        if ($attributes->count() > 25) {
            throw InvalidTracingValueException::attributes();
        }

        foreach ($attributes->attributes() as $attribute) {
            if (isset(self::RESERVED_KEYS[$attribute->key()->value()])) {
                throw InvalidTracingValueException::attributes();
            }
        }
    }

    public static function startTick(int $tick): void
    {
        if ($tick < 0) {
            throw InvalidTracingValueException::timing();
        }
    }

    public static function endTick(int $start, int $end): void
    {
        if ($end < 0 || $end < $start) {
            throw InvalidTracingValueException::timing();
        }
    }

    public static function status(ObservationStatus $status): void
    {
        if (ObservationStatus::Unset === $status) {
            throw InvalidTracingValueException::status();
        }
    }
}
