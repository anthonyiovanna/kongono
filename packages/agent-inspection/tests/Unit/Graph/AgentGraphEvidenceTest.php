<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Tests\Unit\Graph;

use Kongono\AgentInspection\Exception\InvalidAgentIntelligenceEvidenceException;
use Kongono\AgentInspection\Graph\AgentGraphEvidence;
use Kongono\AgentInspection\Graph\AgentGraphEvidenceProjector;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\AgentInspection\Tests\Support\AgentInspectionFactory;
use PHPUnit\Framework\TestCase;

final class AgentGraphEvidenceTest extends TestCase
{
    public function testAbsentAndSuppliedGraphEvidenceRemainRelationshipNeutral(): void
    {
        $inspection = AgentInspectionFactory::inspection();
        $projector = new AgentGraphEvidenceProjector();
        $absent = $projector->project($inspection, DeveloperIntelligenceRole::Hantei);
        $supplied = $projector->project($inspection, DeveloperIntelligenceRole::Hantei, AgentInspectionFactory::graphView());

        self::assertSame(AgentGraphEvidence::NOT_SUPPLIED, $absent->toArray()['status']);
        self::assertSame([], $absent->toArray()['graph']);
        self::assertSame(AgentGraphEvidence::SUPPLIED, $supplied->toArray()['status']);
        $graph = $supplied->toArray()['graph'];
        self::assertIsArray($graph);
        self::assertSame(4, $graph['node_count']);
        self::assertSame(AgentGraphEvidence::RELATIONSHIP, $supplied->relationship());
    }

    public function testGraphRoleMustMatchRequestedRole(): void
    {
        $this->expectException(InvalidAgentIntelligenceEvidenceException::class);
        (new AgentGraphEvidenceProjector())->project(
            AgentInspectionFactory::inspection(),
            DeveloperIntelligenceRole::Chie,
            AgentInspectionFactory::graphView(),
        );
    }
}
