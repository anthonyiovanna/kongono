<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\NetworkEgressInspection\Exception\InvalidNetworkEgressIntelligenceEvidenceException;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspection;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;

/** Copies only an explicit matching-role Phase 11G Safe aggregate view. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressGraphEvidenceProjector
{
    public function project(
        NetworkEgressInspection $inspection,
        NetworkEgressInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): NetworkEgressGraphEvidence {
        if (null === $graphView) {
            return new NetworkEgressGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidNetworkEgressIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidNetworkEgressIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new NetworkEgressGraphEvidence(
            $inspection->identity(),
            $role,
            true,
            $graphView->artifactSha256(),
            $graphView->graphIdentity()->value(),
            $graphView->contractVersion()->value,
            $graphView->nodeCount(),
            $graphView->relationCount(),
            $nodes,
            $relations,
            $graphView->scope(),
            $graphView->visibility(),
            $graphView->currentness(),
            $graphView->authority(),
            $graphView->uncertainties(),
        );
    }
}
