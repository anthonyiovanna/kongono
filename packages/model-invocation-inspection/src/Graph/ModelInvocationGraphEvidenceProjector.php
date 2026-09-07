<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ModelInvocationInspection\Exception\InvalidModelInvocationIntelligenceEvidenceException;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;

/** Copies only an explicit matching-role Phase 11G Safe aggregate view. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationGraphEvidenceProjector
{
    public function project(
        ModelInvocationInspection $inspection,
        ModelInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ModelInvocationGraphEvidence {
        if (null === $graphView) {
            return new ModelInvocationGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidModelInvocationIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidModelInvocationIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new ModelInvocationGraphEvidence(
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
