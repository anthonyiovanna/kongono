<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Tests\Unit;

use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\AttributeValue;
use Kongono\Observability\AttributeValueKind;
use Kongono\Observability\SignalIdentity;
use Kongono\SecurityAudit\SecurityAuditDefinition;
use Kongono\SecurityAudit\SecurityAuditField;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use Kongono\SecurityAudit\SecurityAuditSchema;
use PHPUnit\Framework\TestCase;

final class SecurityAuditDefinitionTest extends TestCase
{
    public function testDefinitionAndRetentionIntentAreExactLabels(): void
    {
        self::assertSame(['short', 'standard', 'extended'], array_column(SecurityAuditRetentionIntent::cases(), 'value'));
        $identity = new SignalIdentity('security.access', 3);
        $schema = new SecurityAuditSchema([new SecurityAuditField(new AttributeKey('app.action'), AttributeValueKind::Text, DiagnosticDisclosure::Safe, true, [AttributeValue::text('read')])]);
        $definition = new SecurityAuditDefinition($identity, SecurityAuditRetentionIntent::Extended, $schema);
        self::assertSame($identity, $definition->identity());
        self::assertSame(SecurityAuditRetentionIntent::Extended, $definition->retentionIntent());
        self::assertSame($schema, $definition->schema());
    }
}
