<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\Observation;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationRecorder;
use Kongono\Observability\ObservationStatus;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\TestCase;

final class SecurityAuditRuntimeTest extends TestCase
{
    public function testOutcomeMappingReservedEvidenceAndThirtyTwoAttributeCeiling(): void
    {
        $attributes = [];
        $fields = [];
        for ($index = 0; $index < 28; ++$index) {
            $key = new AttributeKey(sprintf('app.key.%02d', $index));
            $value = AttributeValue::integer($index);
            $attributes[] = new SignalAttribute($key, $value);
            $fields[] = new SecurityAuditField($key, AttributeValueKind::Integer, DiagnosticDisclosure::Safe, true, [$value]);
        }
        $context = ObservabilityContext::uncorrelated();
        $identity = new SignalIdentity('security.capacity', 1);
        $definition = new SecurityAuditDefinition($identity, SecurityAuditRetentionIntent::Extended, new SecurityAuditSchema($fields));
        $recorder = new class implements ObservationRecorder {
            /** @var list<Observation> */
            public array $observations = [];
            public function record(Observation $observation): void
            {
                $this->observations[] = $observation;
            }
        };
        $runtime = new SecurityAuditRuntime($recorder);
        foreach ([SecurityAuditOutcome::Succeeded, SecurityAuditOutcome::Denied, SecurityAuditOutcome::Failed] as $outcome) {
            $runtime->record(new SecurityAuditEvidence($context, $definition, $outcome, new AttributeSet($attributes)));
        }
        self::assertSame([ObservationStatus::Ok, ObservationStatus::Error, ObservationStatus::Error], array_map(static fn(Observation $observation): ObservationStatus => $observation->status(), $recorder->observations));
        $observation = $recorder->observations[0];
        self::assertSame($context, $observation->context());
        self::assertSame($identity, $observation->identity());
        self::assertSame(ObservationKind::SecurityAudit, $observation->kind());
        self::assertSame(32, $observation->attributes()->count());
        self::assertSame('succeeded', $observation->attributes()->find(new AttributeKey('audit.outcome'))?->value());
        self::assertSame('extended', $observation->attributes()->find(new AttributeKey('audit.retention_intent'))?->value());
        self::assertSame('safe', $observation->attributes()->find(new AttributeKey('audit.disclosure'))?->value());
        self::assertSame('not_guaranteed', $observation->attributes()->find(new AttributeKey('audit.durability'))?->value());
    }
}
