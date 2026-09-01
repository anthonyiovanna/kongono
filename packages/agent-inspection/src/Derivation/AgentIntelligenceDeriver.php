<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Derivation;

use Kongono\AgentInspection\Graph\AgentGraphEvidenceProjector;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;

/** Derives one deterministic role view without executing any developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentIntelligenceDeriver
{
    public function derive(
        AgentPrincipalInspection $inspection,
        DeveloperIntelligenceRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AgentIntelligenceView {
        return new AgentIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new AgentGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
