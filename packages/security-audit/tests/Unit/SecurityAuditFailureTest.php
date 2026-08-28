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
use Kongono\SecurityAudit\Exception\InvalidSecurityAuditValueException;
use Kongono\SecurityAudit\Exception\SecurityAuditRecordingException;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditRuntime;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SecurityAuditFailureTest extends TestCase
{
    public function testValidationPrecedesRecorderAndRecorderFailureIsFinal(): void
    {
        $failure = new RuntimeException('protected-audit-message');
        $recorder = new class ($failure) implements ObservationRecorder {
            public int $calls = 0;
            public function __construct(private RuntimeException $failure) {}
            public function record(Observation $observation): void
            {
                ++$this->calls;
                throw $this->failure;
            }
        };
        $definition = new SecurityAuditDefinition(new SignalIdentity('security.failure', 1), SecurityAuditRetentionIntent::Short, new SecurityAuditSchema([new SecurityAuditField(new AttributeKey('app.action'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('read')])]));
        try {
            new SecurityAuditEvidence(ObservabilityContext::uncorrelated(), $definition, SecurityAuditOutcome::Failed, new AttributeSet([]));
            self::fail('Expected validation failure.');
        } catch (InvalidSecurityAuditValueException) {
            self::assertSame(0, $recorder->calls);
        }
        $evidence = new SecurityAuditEvidence(ObservabilityContext::uncorrelated(), $definition, SecurityAuditOutcome::Failed, new AttributeSet([new SignalAttribute(new AttributeKey('app.action'), AttributeValue::text('read'))]));
        try {
            (new SecurityAuditRuntime($recorder))->record($evidence);
            self::fail('Expected recording failure.');
        } catch (SecurityAuditRecordingException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertStringNotContainsString('protected-audit-message', (string) $exception);
        }
        self::assertSame(1, $recorder->calls);
    }
}
