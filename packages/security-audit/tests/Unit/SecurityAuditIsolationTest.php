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
use Kongono\Observability\ObservationRecorder;
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

final class SecurityAuditIsolationTest extends TestCase
{
    public function testSequentialOverlappingAndReentrantCallsShareNoEvidenceState(): void
    {
        $recorder = new class implements ObservationRecorder {
            public ?SecurityAuditRuntime $runtime = null;
            public ?SecurityAuditEvidence $nested = null;
            /** @var list<string> */
            public array $names = [];
            public function record(Observation $observation): void
            {
                $this->names[] = $observation->identity()->name();
                if (1 === count($this->names)) {
                    $this->runtime?->record($this->nested ?? throw new \LogicException());
                }
            }
        };
        $runtime = new SecurityAuditRuntime($recorder);
        $recorder->runtime = $runtime;
        $recorder->nested = $this->evidence('security.nested');
        $runtime->record($this->evidence('security.outer'));
        $runtime->record($this->evidence('security.after'));
        self::assertSame(['security.outer', 'security.nested', 'security.after'], $recorder->names);
    }

    private function evidence(string $name): SecurityAuditEvidence
    {
        $field = new SecurityAuditField(new AttributeKey('app.action'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('read')]);

        return new SecurityAuditEvidence(ObservabilityContext::uncorrelated(), new SecurityAuditDefinition(new SignalIdentity($name, 1), SecurityAuditRetentionIntent::Standard, new SecurityAuditSchema([$field])), SecurityAuditOutcome::Succeeded, new AttributeSet([new SignalAttribute(new AttributeKey('app.action'), AttributeValue::text('read'))]));
    }
}
