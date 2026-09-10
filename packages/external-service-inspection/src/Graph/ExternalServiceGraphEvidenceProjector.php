<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ExternalServiceInspection\Exception\InvalidExternalServiceIntelligenceEvidenceException;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;

/** Copies only an explicit matching-role Phase 11G Safe aggregate view. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceGraphEvidenceProjector
{
    public function project(
        ExternalServiceInspection $inspection,
        ExternalServiceInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ExternalServiceGraphEvidence {
        if (null === $graphView) {
            return new ExternalServiceGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidExternalServiceIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidExternalServiceIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new ExternalServiceGraphEvidence(
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
