<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Observation;

/** Retains one validated observation and its explicit enqueue epoch. */
#[Api(ApiStatus::Internal)]
final readonly class QueuedObservation
{
    public function __construct(private Observation $observation, private int $epochNanoseconds) {}

    public function observation(): Observation
    {
        return $this->observation;
    }

    public function epochNanoseconds(): int
    {
        return $this->epochNanoseconds;
    }
}
