<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditOutcome;

/** Maps one admitted audit record to its exact neutral observation. */
#[Api(ApiStatus::Internal)]
final class SecurityAuditObservationFactory
{
    private function __construct() {}

    public static function create(SecurityAuditEvidence $evidence): Observation
    {
        $attributes = $evidence->attributes()->attributes();
        $attributes[] = self::attribute('audit.outcome', $evidence->outcome()->value);
        $attributes[] = self::attribute('audit.retention_intent', $evidence->definition()->retentionIntent()->value);
        $attributes[] = self::attribute('audit.disclosure', $evidence->disclosure()->value);
        $attributes[] = self::attribute('audit.durability', 'not_guaranteed');

        return new Observation(
            $evidence->context(),
            $evidence->definition()->identity(),
            ObservationKind::SecurityAudit,
            $evidence->outcome() === SecurityAuditOutcome::Succeeded ? ObservationStatus::Ok : ObservationStatus::Error,
            new AttributeSet($attributes),
        );
    }

    private static function attribute(string $key, string $value): SignalAttribute
    {
        return new SignalAttribute(new AttributeKey($key), AttributeValue::text($value));
    }
}
