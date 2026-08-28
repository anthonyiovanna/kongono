<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeSet;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\SignalAttribute;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditEvidence;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\TestCase;

final class SecurityAuditValueTest extends TestCase
{
    public function testEvidenceRetainsExactObjectsAndDerivesDisclosure(): void
    {
        self::assertSame(['succeeded', 'denied', 'failed'], array_column(SecurityAuditOutcome::cases(), 'value'));
        $safe = new SecurityAuditField(new AttributeKey('app.action'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('read')]);
        $protected = new SecurityAuditField(new AttributeKey('app.scope'), AttributeValueKind::Text, DiagnosticDisclosure::Protected, false, [AttributeValue::text('internal')]);
        $definition = new SecurityAuditDefinition(new SignalIdentity('security.read', 1), SecurityAuditRetentionIntent::Standard, new SecurityAuditSchema([$safe, $protected]));
        $context = ObservabilityContext::uncorrelated();
        $attributes = new AttributeSet([new SignalAttribute(new AttributeKey('app.action'), AttributeValue::text('read'))]);
        $evidence = new SecurityAuditEvidence($context, $definition, SecurityAuditOutcome::Succeeded, $attributes);
        self::assertSame($context, $evidence->context());
        self::assertSame($definition, $evidence->definition());
        self::assertSame(SecurityAuditOutcome::Succeeded, $evidence->outcome());
        self::assertSame($attributes, $evidence->attributes());
        self::assertSame(DiagnosticDisclosure::Safe, $evidence->disclosure());
        $protectedEvidence = new SecurityAuditEvidence($context, $definition, SecurityAuditOutcome::Denied, new AttributeSet([
            new SignalAttribute(new AttributeKey('app.action'), AttributeValue::text('read')),
            new SignalAttribute(new AttributeKey('app.scope'), AttributeValue::text('internal')),
        ]));
        self::assertSame(DiagnosticDisclosure::Protected, $protectedEvidence->disclosure());
    }
}
