<?php

declare(strict_types=1);

namespace Kongono\ModelProviderOpenAiResponsesInspection\Graph;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ModelProviderOpenAiResponsesInspection\Exception\InvalidOpenAiResponsesIntelligenceEvidenceException;
use Kongono\ModelProviderOpenAiResponsesInspection\Inspection\OpenAiResponsesInspection;
use Kongono\ModelProviderOpenAiResponsesInspection\Role\OpenAiResponsesInspectionDeveloperRole;

/** Copies only an explicit matching-role Phase 11G Safe aggregate view. */
#[Api(ApiStatus::Experimental)]
final readonly class OpenAiResponsesGraphEvidenceProjector
{
    public function project(
        OpenAiResponsesInspection $inspection,
        OpenAiResponsesInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): OpenAiResponsesGraphEvidence {
        if (null === $graphView) {
            return new OpenAiResponsesGraphEvidence($inspection->identity(), $role, false);
        }
        if ($graphView->role()->value !== $role->value) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('graph_role');
        }
        $graph = $graphView->toArray();
        $nodes = $graph['node_type_counts'] ?? null;
        $relations = $graph['relation_type_counts'] ?? null;
        if (!is_array($nodes) || !is_array($relations)) {
            throw InvalidOpenAiResponsesIntelligenceEvidenceException::invalid('graph_evidence');
        }

        return new OpenAiResponsesGraphEvidence(
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
