<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ModelInvocationInspection\Graph\ModelInvocationGraphEvidenceProjector;
use Kongono\ModelInvocationInspection\Inspection\ModelInvocationInspection;
use Kongono\ModelInvocationInspection\Role\ModelInspectionDeveloperRole;

/** Derives one deterministic evidence view without executing a developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelInvocationIntelligenceDeriver
{
    public function derive(
        ModelInvocationInspection $inspection,
        ModelInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ModelInvocationIntelligenceView {
        return new ModelInvocationIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new ModelInvocationGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
