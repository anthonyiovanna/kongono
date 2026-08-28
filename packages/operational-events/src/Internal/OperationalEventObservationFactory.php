<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\SignalAttribute;
use Kongono\OperationalEvents\OperationalEvent;

/** Maps one admitted event to its exact neutral observation. */
#[Api(ApiStatus::Internal)]
final class OperationalEventObservationFactory
{
    private function __construct() {}

    public static function create(OperationalEvent $event): Observation
    {
        $attributes = $event->attributes()->attributes();
        $attributes[] = new SignalAttribute(new AttributeKey('event.severity'), AttributeValue::text($event->definition()->severity()->value));
        $attributes[] = new SignalAttribute(new AttributeKey('event.disclosure'), AttributeValue::text($event->disclosure()->value));

        return new Observation(
            $event->context(),
            $event->definition()->identity(),
            ObservationKind::OperationalEvent,
            $event->status(),
            new AttributeSet($attributes),
        );
    }
}
