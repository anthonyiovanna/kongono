<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationRecorder;
use Kongono\OpenTelemetry\Exception\InvalidOpenTelemetryConfigurationException;
use Kongono\OpenTelemetry\Exception\OpenTelemetryExportException;
use Kongono\OpenTelemetry\Exception\OpenTelemetryLifecycleException;
use Kongono\OpenTelemetry\Internal\ObservationQueue;
use Kongono\OpenTelemetry\Internal\OtlpRequestFactory;
use Kongono\OpenTelemetry\Internal\QueuedObservation;
use Throwable;

/** Owns one explicit bounded synchronous OTLP export lifecycle. */
#[Api(ApiStatus::Experimental)]
final class OpenTelemetryBridge implements ObservationRecorder
{
    private OpenTelemetryBridgeState $state = OpenTelemetryBridgeState::Open;
    private bool $recording = false;
    private readonly ObservationQueue $queue;
    private readonly OtlpRequestFactory $requestFactory;

    public function __construct(
        OpenTelemetryResource $resource,
        private readonly OpenTelemetryBatchPolicy $batchPolicy,
        OpenTelemetryTimeAnchor $timeAnchor,
        private readonly OpenTelemetryClock $clock,
        private readonly OtlpHttpTransport $transport,
    ) {
        $this->queue = new ObservationQueue($batchPolicy->queueCapacity());
        $this->requestFactory = new OtlpRequestFactory($resource, $timeAnchor);
    }

    public function record(Observation $observation): void
    {
        if ($this->state !== OpenTelemetryBridgeState::Open || $this->recording) {
            throw OpenTelemetryLifecycleException::transition($this->state);
        }
        $this->recording = true;
        try {
            try {
                $epochNanoseconds = $this->clock->epochNanoseconds();
            } catch (Throwable $exception) {
                throw InvalidOpenTelemetryConfigurationException::invalid($exception);
            }
            if ($epochNanoseconds < 1) {
                throw InvalidOpenTelemetryConfigurationException::invalid();
            }
            $record = new QueuedObservation($observation, $epochNanoseconds);
            $this->requestFactory->create([$record]);
            $this->queue->append($record);
        } finally {
            $this->recording = false;
        }
    }

    public function flush(): void
    {
        if ($this->state !== OpenTelemetryBridgeState::Open || $this->recording) {
            throw OpenTelemetryLifecycleException::transition($this->state);
        }
        $this->state = OpenTelemetryBridgeState::Flushing;
        try {
            $this->drain();
            try {
                $this->transport->flush($this->batchPolicy->transportTimeoutMilliseconds());
            } catch (Throwable $exception) {
                throw OpenTelemetryExportException::failed($exception, null, $this->queue->count(), $this->batchPolicy->queueCapacity(), 'flush');
            }
        } finally {
            $this->state = OpenTelemetryBridgeState::Open;
        }
    }

    public function close(): void
    {
        if ($this->state === OpenTelemetryBridgeState::Closed) {
            return;
        }
        if ($this->state !== OpenTelemetryBridgeState::Open || $this->recording) {
            throw OpenTelemetryLifecycleException::transition($this->state);
        }
        $this->state = OpenTelemetryBridgeState::Closing;
        $failures = [];
        try {
            try {
                $this->drain();
            } catch (Throwable $exception) {
                $failures[] = $exception->getPrevious() ?? $exception;
            }
            try {
                $this->transport->flush($this->batchPolicy->transportTimeoutMilliseconds());
            } catch (Throwable $exception) {
                $failures[] = $exception;
            }
            try {
                $this->transport->close($this->batchPolicy->transportTimeoutMilliseconds());
            } catch (Throwable $exception) {
                $failures[] = $exception;
            }
        } finally {
            $count = $this->queue->count();
            $this->queue->clear();
            $this->state = OpenTelemetryBridgeState::Closed;
        }
        if ([] !== $failures) {
            throw OpenTelemetryExportException::combined($failures, $count, $this->batchPolicy->queueCapacity());
        }
    }

    public function state(): OpenTelemetryBridgeState
    {
        return $this->state;
    }

    public function queuedCount(): int
    {
        return $this->queue->count();
    }

    private function drain(): void
    {
        while ($this->queue->count() > 0) {
            $batch = $this->queue->batch($this->batchPolicy->maximumBatchSize());
            if ([] === $batch) {
                throw OpenTelemetryLifecycleException::transition($this->state);
            }
            [$signal, $payload] = $this->requestFactory->create($batch);
            try {
                $this->transport->send(
                    $signal,
                    $signal->endpointSuffix(),
                    OtlpRequestFactory::CONTENT_TYPE,
                    $payload,
                    $this->batchPolicy->transportTimeoutMilliseconds(),
                );
            } catch (Throwable $exception) {
                throw OpenTelemetryExportException::failed(
                    $exception,
                    $batch[0]->observation()->kind()->value,
                    $this->queue->count(),
                    $this->batchPolicy->queueCapacity(),
                    'send',
                );
            }
            $this->queue->remove(count($batch));
        }
    }
}
