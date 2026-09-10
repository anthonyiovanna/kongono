<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\ExternalServiceInspection\Graph\ExternalServiceGraphEvidenceProjector;
use Kongono\ExternalServiceInspection\Inspection\ExternalServiceInspection;
use Kongono\ExternalServiceInspection\Role\ExternalServiceInspectionDeveloperRole;

/** Derives one deterministic bounded evidence view without executing a developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceIntelligenceDeriver
{
    public function derive(
        ExternalServiceInspection $inspection,
        ExternalServiceInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): ExternalServiceIntelligenceView {
        return new ExternalServiceIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new ExternalServiceGraphEvidenceProjector())->project($inspection, $role, $graphView),
            ['inspection-bindings', 'optional-completed-result-evidence', 'graph-aggregates', 'limitations'],
            ExternalServiceIntelligenceView::statementsFor($role),
        );
    }
}
