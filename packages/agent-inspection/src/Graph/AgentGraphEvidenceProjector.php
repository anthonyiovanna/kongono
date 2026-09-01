<?php

declare(strict_types=1);

namespace Kongono\AgentInspection\Graph;

use Kongono\AgentInspection\Exception\InvalidAgentIntelligenceEvidenceException;
use Kongono\AgentInspection\Inspection\AgentPrincipalInspection;
use Kongono\AgentInspection\Role\DeveloperIntelligenceRole;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;

/** Copies only the separately supplied Phase 11G Safe Graph aggregate view. */
#[Api(ApiStatus::Experimental)]
final readonly class AgentGraphEvidenceProjector
{
    public function project(
        AgentPrincipalInspection $inspection,
        DeveloperIntelligenceRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AgentGraphEvidence {
        if (null === $graphView) {
            return new AgentGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodeTypeCounts = $graph['node_type_counts'] ?? null;
        $relationTypeCounts = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodeTypeCounts) || !is_array($relationTypeCounts)) {
            throw InvalidAgentIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new AgentGraphEvidence(
            $inspection->identity(),
            $role,
            true,
            $graphView->artifactSha256(),
            $graphView->graphIdentity()->value(),
            $graphView->contractVersion()->value,
            $graphView->nodeCount(),
            $graphView->relationCount(),
            $nodeTypeCounts,
            $relationTypeCounts,
            $graphView->scope(),
            $graphView->visibility(),
            $graphView->currentness(),
            $graphView->authority(),
            $graphView->uncertainties(),
        );
    }
}
