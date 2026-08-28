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
use Kongono\OpenTelemetry\OpenTelemetryBatchPolicy;
use Kongono\OpenTelemetry\OpenTelemetryBridge;
use Kongono\OpenTelemetry\OpenTelemetryClock;
use Kongono\OpenTelemetry\OpenTelemetryResource;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Kongono\OpenTelemetry\OtlpHttpTransport;
use Kongono\OpenTelemetry\OtlpSignal;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryIsolationTest extends TestCase
{
    public function testSeparateBridgesCannotObserveOrCorruptEachOther(): void
    {
        $leftTransport = $this->transport();
        $rightTransport = $this->transport();
        $left = $this->bridge('left', 11, $leftTransport);
        $right = $this->bridge('right', 22, $rightTransport);
        $left->record($this->gauge('left.metric'));
        $right->record($this->gauge('right.metric'));
        $left->flush();
        self::assertSame(0, $left->queuedCount());
        self::assertSame(1, $right->queuedCount());
        self::assertCount(1, $leftTransport->payloads);
        self::assertSame([], $rightTransport->payloads);
        $right->flush();
        self::assertCount(1, $rightTransport->payloads);
        self::assertNotSame($this->firstPayload($leftTransport->payloads), $this->firstPayload($rightTransport->payloads));
        $left->record($this->gauge('left.second'));
        $left->flush();
        self::assertCount(2, $leftTransport->payloads);
    }

    private function bridge(string $service, int $epoch, OtlpHttpTransport $transport): OpenTelemetryBridge
    {
        return new OpenTelemetryBridge(new OpenTelemetryResource($service), new OpenTelemetryBatchPolicy(4, 4, 100), new OpenTelemetryTimeAnchor(0, 1), new class ($epoch) implements OpenTelemetryClock {
            public function __construct(private int $epoch) {} public function epochNanoseconds(): int
            {
                return $this->epoch;
            }
        }, $transport);
    }
    private function gauge(string $name): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity($name, 1), ObservationKind::Metric, ObservationStatus::Unset, new AttributeSet([$this->text('metric.instrument', 'gauge'), $this->text('metric.unit', 'items'), $this->integer('metric.measurement_count', 1), $this->integer('metric.last', 1)]));
    }
    /** @return OtlpHttpTransport&object{payloads: list<string>} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport { /** @var list<string> */ public array $payloads = [];
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                $this->payloads[] = $payload;
            } public function flush(int $timeoutMilliseconds): void {} public function close(int $timeoutMilliseconds): void {}
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

    private function firstPayload(mixed $payloads): string
    {
        self::assertIsArray($payloads);
        foreach ($payloads as $payload) {
            self::assertIsString($payload);
            return $payload;
        }
        self::fail('Expected one payload.');
    }
}
