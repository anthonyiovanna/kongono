<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\NetworkEgressInspection\Graph\NetworkEgressGraphEvidenceProjector;
use Kongono\NetworkEgressInspection\Inspection\NetworkEgressInspection;
use Kongono\NetworkEgressInspection\Role\NetworkEgressInspectionDeveloperRole;

/** Derives one deterministic bounded evidence view without executing a developer role. */
#[Api(ApiStatus::Experimental)]
final readonly class NetworkEgressIntelligenceDeriver
{
    public function derive(
        NetworkEgressInspection $inspection,
        NetworkEgressInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): NetworkEgressIntelligenceView {
        return new NetworkEgressIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new NetworkEgressGraphEvidenceProjector())->project($inspection, $role, $graphView),
            ['inspection-bindings', 'optional-runtime-evidence', 'graph-aggregates', 'limitations'],
            NetworkEgressIntelligenceView::statementsFor($role),
        );
    }
}
