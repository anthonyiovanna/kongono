<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\OpenTelemetry\Exception\OpenTelemetryExportException;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenTelemetryExportFailureTest extends TestCase
{
    public function testPartialProgressRetainsFailedAndLaterRecordsWithoutRetry(): void
    {
        $failure = new RuntimeException('provider secret');
        $transport = new class ($failure) implements OtlpHttpTransport {
            public int $sends = 0;
            public function __construct(private RuntimeException $failure) {}
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                ++$this->sends;
                if (2 === $this->sends) {
                    throw $this->failure;
                }
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
        $bridge = $this->bridge($transport, 1);
        foreach (['one', 'two', 'three'] as $name) {
            $bridge->record($this->gauge($name));
        }
        try {
            $bridge->flush();
            self::fail('Expected second send failure.');
        } catch (OpenTelemetryExportException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertStringNotContainsString('provider secret', (string) $exception);
        }
        self::assertSame(2, $bridge->queuedCount());
        self::assertSame(2, $transport->sends);
    }

    public function testClosePreservesDrainFlushAndCloseFailuresThenReleasesQueue(): void
    {
        $send = new RuntimeException('send');
        $flush = new RuntimeException('flush');
        $close = new RuntimeException('close');
        $transport = new class ($send, $flush, $close) implements OtlpHttpTransport {
            public int $closes = 0;
            public function __construct(private RuntimeException $sendFailure, private RuntimeException $flushFailure, private RuntimeException $closeFailure) {}
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                throw $this->sendFailure;
            }
            public function flush(int $timeoutMilliseconds): void
            {
                throw $this->flushFailure;
            }
            public function close(int $timeoutMilliseconds): void
            {
                ++$this->closes;
                throw $this->closeFailure;
            }
        };
        $bridge = $this->bridge($transport, 1);
        $bridge->record($this->gauge('one'));
        try {
            $bridge->close();
            self::fail('Expected combined failure.');
        } catch (OpenTelemetryExportException $exception) {
            self::assertSame([$send, $flush, $close], $exception->failures());
        }
        self::assertSame(0, $bridge->queuedCount());
        self::assertSame(1, $transport->closes);
        $bridge->close();
        self::assertSame(1, $transport->closes);
    }

    private function bridge(OtlpHttpTransport $transport, int $batch): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(new OpenTelemetryResource('failure'), new OpenTelemetryBatchPolicy(8, $batch, 100), new OpenTelemetryTimeAnchor(0, 1), new class implements OpenTelemetryClock {
            public function epochNanoseconds(): int
            {
                return 1;
            }
        }, $transport);
    }
    private function gauge(string $name): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity($name, 1), ObservationKind::Metric, ObservationStatus::Unset, new AttributeSet([$this->text('metric.instrument', 'gauge'), $this->text('metric.unit', 'items'), $this->integer('metric.measurement_count', 1), $this->integer('metric.last', 1)]));
    }
    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
    private function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }
}
