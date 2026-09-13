<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ViewInspection\Graph\ViewGraphEvidenceProjector;
use Kongono\ViewInspection\Inspection\ViewInspection;
use Kongono\ViewInspection\Role\ViewInspectionDeveloperRole;

/** Derives one deterministic bounded evidence view without executing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class ViewIntelligenceDeriver
{
    public function derive(
        ViewInspection $inspection,
        ViewInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ViewIntelligenceView {
        return new ViewIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new ViewGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
