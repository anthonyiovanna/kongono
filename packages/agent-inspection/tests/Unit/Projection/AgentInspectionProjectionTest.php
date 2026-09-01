<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Unit\Projection;

use Kongono\AgentInspection\Projection\AgentInspectionObservationProjector;
use Kongono\AgentInspection\Projection\AgentInspectionSecurityAuditProjector;
use Kongono\AgentInspection\Tests\Support\AgentInspectionFactory;
use Kongono\Observability\AttributeKey;
use Kongono\Observability\ObservabilityContext;
use Kongono\Observability\ObservationKind;
use Kongono\Observability\ObservationStatus;
use Kongono\SecurityAudit\SecurityAuditOutcome;
use Kongono\SecurityAudit\SecurityAuditRetentionIntent;
use PHPUnit\Framework\TestCase;

final class AgentInspectionProjectionTest extends TestCase
{
    public function testObservationIsExactNeutralInertEvidence(): void
    {
        $observation = (new AgentInspectionObservationProjector())->project(AgentInspectionFactory::inspection(true), ObservabilityContext::uncorrelated());

        self::assertSame('kongono.agent.inspection', $observation->identity()->name());
        self::assertSame(1, $observation->identity()->schemaVersion());
        self::assertSame(ObservationKind::OperationalEvent, $observation->kind());
        self::assertSame(ObservationStatus::Ok, $observation->status());
        self::assertSame('performed', $observation->attributes()->find(new AttributeKey('inspection.runtime_outcome'))?->value());
    }

    public function testSecurityAuditProjectionUsesFixedSafeDefinition(): void
    {
        $evidence = (new AgentInspectionSecurityAuditProjector())->project(AgentInspectionFactory::inspection(), ObservabilityContext::uncorrelated());

        self::assertSame('kongono.agent.inspection.security', $evidence->definition()->identity()->name());
        self::assertSame(SecurityAuditRetentionIntent::Standard, $evidence->definition()->retentionIntent());
        self::assertSame(SecurityAuditOutcome::Succeeded, $evidence->outcome());
        self::assertSame('succeeded', $evidence->attributes()->find(new AttributeKey('inspection.projection'))?->value());
    }
}
