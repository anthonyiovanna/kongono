<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;

/** Converts admitted monotonic trace ticks through one explicit epoch anchor. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenTelemetryTimeAnchor
{
    public function __construct(private int $monotonicNanoseconds, private int $epochNanoseconds)
    {
        if ($monotonicNanoseconds < 0 || $epochNanoseconds < 1) {
            throw InvalidOpenTelemetryConfigurationException::invalid();
        }
    }

    public function monotonicNanoseconds(): int
    {
        return $this->monotonicNanoseconds;
    }

    public function epochNanoseconds(): int
    {
        return $this->epochNanoseconds;
    }

    public function toEpochNanoseconds(int $monotonicNanoseconds): int
    {
        if ($monotonicNanoseconds < $this->monotonicNanoseconds) {
            throw OpenTelemetryMappingException::invalid();
        }

        $delta = $monotonicNanoseconds - $this->monotonicNanoseconds;
        if ($delta > PHP_INT_MAX - $this->epochNanoseconds) {
            throw OpenTelemetryMappingException::invalid();
        }

        return $this->epochNanoseconds + $delta;
    }
}
