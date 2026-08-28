<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservationKind;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Opentelemetry\Proto\Logs\V1\LogRecord;
use Opentelemetry\Proto\Logs\V1\SeverityNumber;

/** Maps ordinary events and audit evidence into distinct exact OTLP log semantics. */
#[Api(ApiStatus::Internal)]
final class OtlpLogMapper
{
    private function __construct() {}

    /**
     * @param list<QueuedObservation> $records
     * @return list<LogRecord>
     */
    public static function map(array $records): array
    {
        $logs = [];
        foreach ($records as $record) {
            $observation = $record->observation();
            $additional = ['kongono.signal.status' => $observation->status()->value];
            if ($observation->kind() === ObservationKind::OperationalEvent) {
                $severity = self::text($observation, 'event.severity');
                $mapping = match ($severity) {
                    'debug' => SeverityNumber::SEVERITY_NUMBER_DEBUG,
                    'info' => SeverityNumber::SEVERITY_NUMBER_INFO,
                    'notice' => SeverityNumber::SEVERITY_NUMBER_INFO2,
                    'warning' => SeverityNumber::SEVERITY_NUMBER_WARN,
                    'error' => SeverityNumber::SEVERITY_NUMBER_ERROR,
                    'critical' => SeverityNumber::SEVERITY_NUMBER_FATAL,
                    default => throw OpenTelemetryMappingException::invalid(signalKind: 'operational_event'),
                };
                self::text($observation, 'event.disclosure');
                $attributes = OtlpAttributeMapper::observation($observation, ['event.severity'], $additional);
            } elseif ($observation->kind() === ObservationKind::SecurityAudit) {
                $outcome = self::text($observation, 'audit.outcome');
                $mapping = match ($outcome) {
                    'succeeded' => SeverityNumber::SEVERITY_NUMBER_INFO,
                    'denied' => SeverityNumber::SEVERITY_NUMBER_WARN,
                    'failed' => SeverityNumber::SEVERITY_NUMBER_ERROR,
                    default => throw OpenTelemetryMappingException::invalid(signalKind: 'security_audit'),
                };
                foreach (['audit.retention_intent', 'audit.disclosure', 'audit.durability'] as $required) {
                    self::text($observation, $required);
                }
                if ('not_guaranteed' !== self::text($observation, 'audit.durability')) {
                    throw OpenTelemetryMappingException::invalid(signalKind: 'security_audit');
                }
                $severity = match ($outcome) {
                    'succeeded' => 'info',
                    'denied' => 'warning',
                    'failed' => 'error',
                };
                $attributes = OtlpAttributeMapper::observation($observation, [], $additional);
            } else {
                throw OpenTelemetryMappingException::invalid(signalKind: $observation->kind()->value);
            }
            $logs[] = (new LogRecord())
                ->setTimeUnixNano($record->epochNanoseconds())
                ->setSeverityNumber($mapping)
                ->setSeverityText($severity)
                ->setEventName($observation->identity()->name())
                ->setAttributes($attributes);
        }

        return $logs;
    }

    private static function text(\Kongono\Observability\Observation $observation, string $key): string
    {
        foreach ($observation->attributes()->attributes() as $attribute) {
            if ($attribute->key()->value() === $key && $attribute->value()->kind() === AttributeValueKind::Text && is_string($attribute->value()->value())) {
                return $attribute->value()->value();
            }
        }

        throw OpenTelemetryMappingException::invalid(signalKind: $observation->kind()->value);
    }
}
