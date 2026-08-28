<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Tests\Unit;

use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\OperationalEvents\OperationalEvent;
use Kongono\OperationalEvents\OperationalEventDefinition;
use Kongono\OperationalEvents\OperationalEventRuntime;
use Kongono\OperationalEvents\OperationalEventSchema;
use Kongono\OperationalEvents\OperationalEventSeverity;
use PHPUnit\Framework\TestCase;

final class OperationalEventRuntimeTest extends TestCase
{
    public function testExactMappingAndThirtyTwoAttributeCeiling(): void
    {
        $attributes = [];
        for ($index = 0; $index < 30; ++$index) {
            $attributes[] = new SignalAttribute(new AttributeKey(sprintf('app.key.%02d', $index)), AttributeValue::integer($index));
        }
        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('operations.capacity', 1);
        $event = new OperationalEvent($context, new OperationalEventDefinition($identity, OperationalEventSeverity::Critical, new OperationalEventSchema([])), ObservationStatus::Error, new AttributeSet([]));
        $recorder = new class implements ObservationRecorder {
            public ?Observation $observation = null;
            public function record(Observation $observation): void
            {
                $this->observation = $observation;
            }
        };
        (new OperationalEventRuntime($recorder))->record($event);
        self::assertNotNull($recorder->observation);
        self::assertSame($context, $recorder->observation->context());
        self::assertSame($identity, $recorder->observation->identity());
        self::assertSame(ObservationKind::OperationalEvent, $recorder->observation->kind());
        self::assertSame(ObservationStatus::Error, $recorder->observation->status());
        self::assertSame('critical', $recorder->observation->attributes()->find(new AttributeKey('event.severity'))?->value());
        self::assertSame('safe', $recorder->observation->attributes()->find(new AttributeKey('event.disclosure'))?->value());

        $fields = array_map(static fn(SignalAttribute $attribute) => new \Kongono\OperationalEvents\OperationalEventField($attribute->key(), $attribute->value()->kind(), \Kongono\Diagnostics\Context\DiagnosticDisclosure::Safe, true, [$attribute->value()]), $attributes);
        $capacityEvent = new OperationalEvent($context, new OperationalEventDefinition($identity, OperationalEventSeverity::Info, new OperationalEventSchema($fields)), ObservationStatus::Ok, new AttributeSet($attributes));
        (new OperationalEventRuntime($recorder))->record($capacityEvent);
        self::assertSame(32, $recorder->observation->attributes()->count());
    }
}
