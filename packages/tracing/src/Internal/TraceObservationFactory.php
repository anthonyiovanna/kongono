<?php

declare(strict_types=1);

namespace Kongono\Tracing\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\Tracing\SpanId;
use Kongono\Tracing\TraceId;

/** Constructs the one canonical reserved-attribute trace observation shape. */
#[Api(ApiStatus::Internal)]
final class TraceObservationFactory
{
    public static function create(
        ObservabilityContext $context,
        SignalIdentity $identity,
        AttributeSet $callerAttributes,
        TraceId $traceId,
        SpanId $spanId,
        ?SpanId $parentSpanId,
        int $start,
        int $end,
        ObservationStatus $status,
    ): Observation {
        $attributes = $callerAttributes->attributes();
        $attributes[] = self::text('trace.id', $traceId->value());
        $attributes[] = self::text('trace.span.id', $spanId->value());
        if (null !== $parentSpanId) {
            $attributes[] = self::text('trace.parent_span.id', $parentSpanId->value());
        }
        $attributes[] = new SignalAttribute(new AttributeKey('trace.sampled'), AttributeValue::boolean(true));
        $attributes[] = self::integer('trace.start_ns', $start);
        $attributes[] = self::integer('trace.end_ns', $end);
        $attributes[] = self::integer('trace.duration_ns', $end - $start);

        return new Observation($context, $identity, ObservationKind::Trace, $status, new AttributeSet($attributes));
    }

    private static function text(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }

    private static function integer(string $key, int $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::integer($value));
    }
}
