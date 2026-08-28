<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Tests\Unit;

use Google\Protobuf\RepeatedField;
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
use Opentelemetry\Proto\Collector\Logs\V1\ExportLogsServiceRequest;
use Opentelemetry\Proto\Common\V1\InstrumentationScope;
use Opentelemetry\Proto\Logs\V1\LogRecord;
use Opentelemetry\Proto\Logs\V1\ResourceLogs;
use Opentelemetry\Proto\Logs\V1\ScopeLogs;
use Opentelemetry\Proto\Logs\V1\SeverityNumber;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryLogMappingTest extends TestCase
{
    public function testEventAndAuditUseSeparateScopesAndExactSeverity(): void
    {
        $transport = $this->transport();
        $bridge = new OpenTelemetryBridge(new OpenTelemetryResource('logs'), new OpenTelemetryBatchPolicy(8, 8, 100), new OpenTelemetryTimeAnchor(0, 1), new class implements OpenTelemetryClock {
            public function epochNanoseconds(): int
            {
                return 9_000;
            }
        }, $transport);
        $bridge->record($this->event());
        $bridge->record($this->audit());
        $bridge->flush();
        self::assertCount(2, $transport->payloads);

        $event = new ExportLogsServiceRequest();
        $event->mergeFromString($transport->payloads[0]);
        [$eventScope, $eventRecord] = $this->firstLog($event);
        $eventInstrumentationScope = $eventScope->getScope();
        self::assertInstanceOf(InstrumentationScope::class, $eventInstrumentationScope);
        self::assertSame('kongono.operational-events', $eventInstrumentationScope->getName());
        self::assertSame(SeverityNumber::SEVERITY_NUMBER_WARN, $eventRecord->getSeverityNumber());
        self::assertSame('job.delayed', $eventRecord->getEventName());
        self::assertNull($eventRecord->getBody());

        $audit = new ExportLogsServiceRequest();
        $audit->mergeFromString($transport->payloads[1]);
        [$auditScope, $auditRecord] = $this->firstLog($audit);
        $auditInstrumentationScope = $auditScope->getScope();
        self::assertInstanceOf(InstrumentationScope::class, $auditInstrumentationScope);
        self::assertSame('kongono.security-audit', $auditInstrumentationScope->getName());
        self::assertSame(SeverityNumber::SEVERITY_NUMBER_ERROR, $auditRecord->getSeverityNumber());
        self::assertSame('access.failed', $auditRecord->getEventName());
        self::assertSame('', $auditRecord->getTraceId());
    }

    private function event(): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity('job.delayed', 1), ObservationKind::OperationalEvent, ObservationStatus::Unset, new AttributeSet([$this->text('event.severity', 'warning'), $this->text('event.disclosure', 'safe')]));
    }

    private function audit(): Observation
    {
        return new Observation(ObservabilityContext::uncorrelated(), new SignalIdentity('access.failed', 2), ObservationKind::SecurityAudit, ObservationStatus::Error, new AttributeSet([$this->text('audit.outcome', 'failed'), $this->text('audit.retention_intent', 'extended'), $this->text('audit.disclosure', 'protected'), $this->text('audit.durability', 'not_guaranteed')]));
    }

    /** @return array{ScopeLogs, LogRecord} */
    private function firstLog(ExportLogsServiceRequest $request): array
    {
        $resource = $this->firstMessage($request->getResourceLogs(), ResourceLogs::class);
        $scope = $this->firstMessage($resource->getScopeLogs(), ScopeLogs::class);
        $record = $this->firstMessage($scope->getLogRecords(), LogRecord::class);

        return [$scope, $record];
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T
     */
    private function firstMessage(mixed $field, string $type): object
    {
        self::assertInstanceOf(RepeatedField::class, $field);
        foreach ($field as $message) {
            self::assertInstanceOf($type, $message);
            return $message;
        }
        self::fail('Expected one protobuf message.');
    }

    /** @return OtlpHttpTransport&object{payloads: list<string>} */
    private function transport(): object
    {
        return new class implements OtlpHttpTransport {
            /** @var list<string> */ public array $payloads = [];
            public function send(OtlpSignal $signal, string $endpointSuffix, string $contentType, string $payload, int $timeoutMilliseconds): void
            {
                $this->payloads[] = $payload;
            }
            public function flush(int $timeoutMilliseconds): void {}
            public function close(int $timeoutMilliseconds): void {}
        };
    }

    private function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
}
