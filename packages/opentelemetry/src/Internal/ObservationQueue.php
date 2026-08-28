<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\OpenTelemetry\Exception\OpenTelemetryBackpressureException;

/** Owns one bounded FIFO and groups only contiguous exact observation kinds. */
#[Api(ApiStatus::Internal)]
final class ObservationQueue
{
    /** @var list<QueuedObservation> */
    private array $records = [];

    public function __construct(private readonly int $capacity) {}

    public function append(QueuedObservation $record): void
    {
        if (count($this->records) >= $this->capacity) {
            throw OpenTelemetryBackpressureException::full(count($this->records), $this->capacity);
        }

        $this->records[] = $record;
    }

    /** @return list<QueuedObservation> */
    public function batch(int $maximum): array
    {
        if ([] === $this->records) {
            return [];
        }

        $kind = $this->records[0]->observation()->kind();
        $batch = [];
        foreach ($this->records as $record) {
            if (count($batch) >= $maximum || $record->observation()->kind() !== $kind) {
                break;
            }
            $batch[] = $record;
        }

        return $batch;
    }

    public function remove(int $count): void
    {
        $this->records = array_slice($this->records, $count);
    }

    public function clear(): void
    {
        $this->records = [];
    }

    public function count(): int
    {
        return count($this->records);
    }
}
