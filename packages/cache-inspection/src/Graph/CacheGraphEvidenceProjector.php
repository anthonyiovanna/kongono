<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\CacheInspection\Exception\InvalidCacheIntelligenceEvidenceException;
use Kongono\CacheInspection\Inspection\CacheInspection;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;

/** Copies only matching-role bounded Safe Graph aggregate evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheGraphEvidenceProjector
{
    public function project(
        CacheInspection $inspection,
        CacheInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): CacheGraphEvidence {
        if (null === $graphView) {
            return new CacheGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidCacheIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new CacheGraphEvidence(
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
