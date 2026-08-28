<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;

/** Defines the exact bounded queue, batch, and transport timeout policy. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenTelemetryBatchPolicy
{
    public function __construct(
        private int $queueCapacity,
        private int $maximumBatchSize,
        private int $transportTimeoutMilliseconds,
    ) {
        if (
            $queueCapacity < 1 || $queueCapacity > 1024
            || $maximumBatchSize < 1 || $maximumBatchSize > 256
            || $maximumBatchSize > $queueCapacity
            || $transportTimeoutMilliseconds < 1 || $transportTimeoutMilliseconds > 30_000
        ) {
            throw InvalidOpenTelemetryConfigurationException::invalid();
        }
    }

    public function queueCapacity(): int
    {
        return $this->queueCapacity;
    }

    public function maximumBatchSize(): int
    {
        return $this->maximumBatchSize;
    }

    public function transportTimeoutMilliseconds(): int
    {
        return $this->transportTimeoutMilliseconds;
    }
}
