<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Kongono\OpenTelemetry\OpenTelemetryTimeAnchor;
use Opentelemetry\Proto\Trace\V1\Span;
use Opentelemetry\Proto\Trace\V1\Span\SpanKind;
use Opentelemetry\Proto\Trace\V1\Status;
use Opentelemetry\Proto\Trace\V1\Status\StatusCode;

/** Maps the exact neutral trace observation shape into OTLP spans. */
#[Api(ApiStatus::Internal)]
final readonly class OtlpTraceMapper
{
    public function __construct(private OpenTelemetryTimeAnchor $timeAnchor) {}

    /**
     * @param list<QueuedObservation> $records
     * @return list<Span>
     */
    public function map(array $records): array
    {
        $spans = [];
        foreach ($records as $record) {
            $observation = $record->observation();
            if ($observation->kind() !== ObservationKind::Trace) {
                throw OpenTelemetryMappingException::invalid(signalKind: $observation->kind()->value);
            }
            $reserved = $this->reserved($observation);
            $traceId = $this->hex($reserved, 'trace.id', 32);
            $spanId = $this->hex($reserved, 'trace.span.id', 16);
            $parent = array_key_exists('trace.parent_span.id', $reserved) ? $this->hex($reserved, 'trace.parent_span.id', 16) : '';
            $sampled = $this->value($reserved, 'trace.sampled', AttributeValueKind::Boolean);
            $start = $this->integer($reserved, 'trace.start_ns');
            $end = $this->integer($reserved, 'trace.end_ns');
            $duration = $this->integer($reserved, 'trace.duration_ns');
            if (true !== $sampled || $end < $start || $duration < 0 || $end - $start !== $duration) {
                throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
            }
            $allowed = ['trace.id', 'trace.span.id', 'trace.sampled', 'trace.start_ns', 'trace.end_ns', 'trace.duration_ns'];
            if ('' !== $parent) {
                $allowed[] = 'trace.parent_span.id';
            }
            if ([] !== array_diff(array_keys($reserved), $allowed) || [] !== array_diff($allowed, array_keys($reserved))) {
                throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
            }

            $statusCode = match ($observation->status()) {
                ObservationStatus::Unset => StatusCode::STATUS_CODE_UNSET,
                ObservationStatus::Ok => StatusCode::STATUS_CODE_OK,
                ObservationStatus::Error => StatusCode::STATUS_CODE_ERROR,
            };
            $span = (new Span())
                ->setTraceId($traceId)
                ->setSpanId($spanId)
                ->setName($observation->identity()->name())
                ->setKind(SpanKind::SPAN_KIND_INTERNAL)
                ->setStartTimeUnixNano($this->timeAnchor->toEpochNanoseconds($start))
                ->setEndTimeUnixNano($this->timeAnchor->toEpochNanoseconds($end))
                ->setAttributes(OtlpAttributeMapper::observation($observation, ['trace.']))
                ->setStatus((new Status())->setCode($statusCode));
            if ('' !== $parent) {
                $span->setParentSpanId($parent);
            }
            $spans[] = $span;
        }

        return $spans;
    }

    /** @return array<string, AttributeValue> */
    private function reserved(\Kongono\Observability\Observation $observation): array
    {
        $values = [];
        foreach ($observation->attributes()->attributes() as $attribute) {
            if (str_starts_with($attribute->key()->value(), 'trace.')) {
                $values[$attribute->key()->value()] = $attribute->value();
            }
        }

        return $values;
    }

    /** @param array<string, AttributeValue> $values */
    private function value(array $values, string $key, AttributeValueKind $kind): bool|int|float|string
    {
        $value = $values[$key] ?? null;
        if (null === $value || $value->kind() !== $kind) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
        }

        return $value->value();
    }

    /** @param array<string, AttributeValue> $values */
    private function integer(array $values, string $key): int
    {
        $value = $this->value($values, $key, AttributeValueKind::Integer);
        if (!is_int($value) || $value < 0) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
        }

        return $value;
    }

    /** @param array<string, AttributeValue> $values */
    private function hex(array $values, string $key, int $length): string
    {
        $value = $this->value($values, $key, AttributeValueKind::Text);
        if (!is_string($value) || strlen($value) !== $length || 1 !== preg_match('/\A[0-9a-f]+\z/D', $value) || 1 === preg_match('/\A0+\z/D', $value)) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
        }
        $binary = hex2bin($value);
        if (false === $binary) {
            throw OpenTelemetryMappingException::invalid(signalKind: 'trace');
        }

        return $binary;
    }
}
