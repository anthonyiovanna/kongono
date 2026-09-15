<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\AssetInspection\Exception\InvalidAssetIntelligenceEvidenceException;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;

/** Copies only matching-role bounded Safe Graph aggregate evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetGraphEvidenceProjector
{
    public function project(
        AssetInspection $inspection,
        AssetInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AssetGraphEvidence {
        if (null === $graphView) {
            return new AssetGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidAssetIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidAssetIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new AssetGraphEvidence(
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
