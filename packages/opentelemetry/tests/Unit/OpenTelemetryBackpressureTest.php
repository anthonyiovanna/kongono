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
use Kongono\OpenTelemetry\Exception\OpenTelemetryBackpressureException;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryBackpressureTest extends TestCase
{
    public function testCapacityOneRejectsWithoutDropOrImplicitFlush(): void
    {
        $transport = $this->transport();
        $bridge = $this->bridge($transport, 1, 1);
        $bridge->record($this->gauge('one'));
        try {
            $bridge->record($this->gauge('two'));
            self::fail('Expected visible backpressure.');
        } catch (OpenTelemetryBackpressureException $exception) {
            self::assertSame('KGN-OTEL-1003', $exception->diagnosticId());
        }
        self::assertSame(1, $bridge->queuedCount());
        self::assertSame(0, $transport->sendCount);
        self::assertSame(0, $transport->flushCount);
        $bridge->flush();
        self::assertSame(1, $transport->sendCount);
        self::assertSame(0, $bridge->queuedCount());
    }

    public function testMaximumCapacityAndBatchBoundaryAreAdmitted(): void
    {
        $transport = $this->transport();
        $bridge = $this->bridge($transport, 1024, 256);
        for ($index = 0; $index < 1024; ++$index) {
            $bridge->record($this->gauge('metric' . $index));
        }
        $bridge->flush();
        self::assertSame(4, $transport->sendCount);
    }

    private function bridge(OtlpHttpTransport $transport, int $capacity, int $batch): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(new OpenTelemetryResource('backpressure'), new OpenTelemetryBatchPolicy($capacity, $batch, 100), new OpenTelemetryTimeAnchor(0, 1), new class implements OpenTelemetryClock {
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

    /** @return OtlpHttpTransport&object{sendCount: int, flushCount: int} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport {
            public int $sendCount = 0;
            public int $flushCount = 0;
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                ++$this->sendCount;
            }
            public function flush(int $timeoutMilliseconds): void
            {
                ++$this->flushCount;
            }
            public function close(int $timeoutMilliseconds): void {}
        };
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
