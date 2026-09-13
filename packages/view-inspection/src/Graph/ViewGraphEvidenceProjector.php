<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ViewInspection\Exception\InvalidViewIntelligenceEvidenceException;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;

/** Copies only explicit matching-role Safe aggregate Graph evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewGraphEvidenceProjector
{
    public function project(
        ViewInspection $inspection,
        ViewInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ViewGraphEvidence {
        if (null === $graphView) {
            return new ViewGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidViewIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidViewIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new ViewGraphEvidence(
            $inspection->identity(),
            $role,
            true,
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
