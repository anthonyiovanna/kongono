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
use Kongono\OpenTelemetry\Exception\OpenTelemetryLifecycleException;
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryBridgeState;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryLifecycleTest extends TestCase
{
    public function testEmptyFlushAndIdempotentCloseHaveExactCalls(): void
    {
        $transport = $this->transport();
        $bridge = $this->bridge($transport);
        $bridge->flush();
        self::assertSame(1, $transport->flushes);
        self::assertSame(0, $transport->sends);
        $bridge->close();
        self::assertSame(OpenTelemetryBridgeState::Closed, $bridge->state());
        self::assertSame(2, $transport->flushes);
        self::assertSame(1, $transport->closes);
        $bridge->close();
        self::assertSame(1, $transport->closes);
        $this->expectException(OpenTelemetryLifecycleException::class);
        $bridge->record($this->gauge());
    }

    public function testTransportReentryFailsBeforeQueueMutation(): void
    {
        $holder = new class {
            public ?OpenTelemetryBridge $bridge = null;
        };
        $reentryFailure = null;
        $transport = new class (static function () use ($holder, &$reentryFailure): void {
            if (null === $holder->bridge) {
                return;
            }
            try {
                $holder->bridge->flush();
            } catch (\Throwable $failure) {
                $reentryFailure = $failure;
            }
        }) implements OtlpHttpTransport {
            public function __construct(private readonly \Closure $onSend) {}
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                ($this->onSend)();
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
        $bridge = $this->bridge($transport);
        $holder->bridge = $bridge;
        $bridge->record($this->gauge());
        $bridge->flush();
        self::assertInstanceOf(OpenTelemetryLifecycleException::class, $reentryFailure);
        self::assertSame(0, $bridge->queuedCount());
    }

    private function bridge(OtlpHttpTransport $transport): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(new OpenTelemetryResource('lifecycle'), new OpenTelemetryBatchPolicy(4, 4, 100), new OpenTelemetryTimeAnchor(0, 1), new class implements OpenTelemetryClock {
            public function epochNanoseconds(): int
            {
                return 1;
            }
        }, $transport);
    }
    private function gauge(): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity('gauge', 1), ObservationKind::Metric, ObservationStatus::Unset, new AttributeSet([$this->text('metric.instrument', 'gauge'), $this->text('metric.unit', 'items'), $this->integer('metric.measurement_count', 1), $this->integer('metric.last', 1)]));
    }

    /** @return OtlpHttpTransport&object{sends: int, flushes: int, closes: int} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport {
            public int $sends = 0;
            public int $flushes = 0;
            public int $closes = 0;
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                ++$this->sends;
            }
            public function flush(int $timeoutMilliseconds): void
            {
                ++$this->flushes;
            }
            public function close(int $timeoutMilliseconds): void
            {
                ++$this->closes;
            }
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
