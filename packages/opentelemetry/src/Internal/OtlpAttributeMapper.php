<?php

declare(strict_types=1);

namespace Kongono\OpenTelemetry\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\Observation;
use Kongono\OpenTelemetry\Exception\OpenTelemetryMappingException;
use Opentelemetry\Proto\Common\V1\AnyValue;
use Opentelemetry\Proto\Common\V1\KeyValue;

/** Maps exact neutral scalar values into canonical OTLP attributes. */
#[Api(ApiStatus::Internal)]
final class OtlpAttributeMapper
{
    private function __construct() {}

    /**
     * @param list<string> $reservedPrefixes
     * @param array<string, bool|int|float|string> $additional
     * @return list<KeyValue>
     */
    public static function observation(Observation $observation, array $reservedPrefixes, array $additional = []): array
    {
        $values = [];
        foreach ($observation->attributes()->attributes() as $attribute) {
            $key = $attribute->key()->value();
            $reserved = false;
            foreach ($reservedPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $reserved = true;
                    break;
                }
            }
            if (!$reserved) {
                $values[$key] = $attribute->value();
            }
        }

        $values['kongono.signal.schema_version'] = $observation->identity()->schemaVersion();
        $values['kongono.signal.kind'] = $observation->kind()->value;
        $correlationId = $observation->context()->correlationId();
        if (null !== $correlationId) {
            $values['kongono.correlation.id'] = $correlationId->value();
        }
        foreach ($additional as $key => $value) {
            if (array_key_exists($key, $values)) {
                throw OpenTelemetryMappingException::invalid(signalKind: $observation->kind()->value);
            }
            $values[$key] = $value;
        }
        ksort($values, SORT_STRING);

        $mapped = [];
        foreach ($values as $key => $value) {
            $mapped[] = self::keyValue($key, $value);
        }

        return $mapped;
    }

    /** @param bool|int|float|string|AttributeValue $value */
    public static function keyValue(string $key, bool|int|float|string|AttributeValue $value): KeyValue
    {
        $any = new AnyValue();
        if ($value instanceof AttributeValue) {
            $raw = $value->value();
            match ($value->kind()) {
                AttributeValueKind::Boolean => is_bool($raw) ? $any->setBoolValue($raw) : throw OpenTelemetryMappingException::invalid(),
                AttributeValueKind::Integer => is_int($raw) ? $any->setIntValue($raw) : throw OpenTelemetryMappingException::invalid(),
                AttributeValueKind::FloatingPoint => is_float($raw) && is_finite($raw) ? $any->setDoubleValue($raw) : throw OpenTelemetryMappingException::invalid(),
                AttributeValueKind::Text => is_string($raw) ? $any->setStringValue($raw) : throw OpenTelemetryMappingException::invalid(),
            };
        } elseif (is_bool($value)) {
            $any->setBoolValue($value);
        } elseif (is_int($value)) {
            $any->setIntValue($value);
        } elseif (is_float($value)) {
            if (!is_finite($value)) {
                throw OpenTelemetryMappingException::invalid();
            }
            $any->setDoubleValue($value);
        } else {
            $any->setStringValue($value);
        }

        return (new KeyValue())->setKey($key)->setValue($any);
    }
}
