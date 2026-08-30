<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphArtifact\Inspection\ApplicationGraphArtifactInspection;
use Kongono\ApplicationGraphContract\Vocabulary\NodeType;
use Kongono\ApplicationGraphContract\Vocabulary\RelationType;
use Kongono\ApplicationGraphIntelligence\Role\DeveloperIntelligenceRole;

/** Derives one role-addressed aggregate view without accessing external state. */
#[Api(ApiStatus::Experimental)]
final readonly class ApplicationGraphIntelligenceDeriver
{
    public function derive(
        ApplicationGraphArtifactInspection $inspection,
        DeveloperIntelligenceRole $role,
    ): ApplicationGraphIntelligenceView {
        $nodeCounts = [];
        foreach (NodeType::cases() as $type) {
            $nodeCounts[$type->value] = $inspection->nodeTypeCount($type);
        }

        $relationCounts = [];
        foreach (RelationType::cases() as $type) {
            $relationCounts[$type->value] = $inspection->relationTypeCount($type);
        }

        return new ApplicationGraphIntelligenceView(
            $role,
            $inspection->artifactSha256(),
            $inspection->graphIdentity(),
            $inspection->contractVersion(),
            $inspection->nodeCount(),
            $inspection->relationCount(),
            $nodeCounts,
            $relationCounts,
        );
    }
}
