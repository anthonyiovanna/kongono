<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Security;

use Kongono\AgentInspection\Derivation\AgentIntelligenceDeriver;
use Kongono\AgentInspection\Projection\AgentInspectionObservationProjector;
use Kongono\AgentInspection\Projection\AgentInspectionSecurityAuditProjector;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\AgentInspection\Tests\Support\AgentInspectionFactory;
use Kongono\Observability\ObservabilityContext;
use PHPUnit\Framework\TestCase;

final class AgentInspectionDisclosureTest extends TestCase
{
    public function testPrivateRawCategoriesNeverCrossAnyPresentationNeutralBoundary(): void
    {
        $private = [
            'agent-primary', 'Bounded purpose for calendar.read', 'policy.primary', 'application.primary',
            'proposal.primary', 'receipt.primary', 'KongonoGenerated', 'fixture-resource', 'private-secret-token',
        ];
        $inspection = AgentInspectionFactory::inspection(true);
        $view = (new AgentIntelligenceDeriver())->derive($inspection, DeveloperIntelligenceRole::Hantei, AgentInspectionFactory::graphView());
        $observation = (new AgentInspectionObservationProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $audit = (new AgentInspectionSecurityAuditProjector())->project($inspection, ObservabilityContext::uncorrelated());
        $presentations = [
            json_encode($inspection->toArray(), JSON_THROW_ON_ERROR),
            json_encode($view->toArray(), JSON_THROW_ON_ERROR),
            serialize($observation),
            serialize($audit),
        ];
        foreach ($presentations as $presentation) {
            foreach ($private as $literal) {
                self::assertStringNotContainsString($literal, $presentation);
            }
        }
    }
}
