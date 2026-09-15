<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\AssetInspection\Graph\AssetGraphEvidenceProjector;
use Kongono\AssetInspection\Inspection\AssetInspection;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;

/** Derives deterministic evidence without executing or observing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetIntelligenceDeriver
{
    public function derive(
        AssetInspection $inspection,
        AssetInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): AssetIntelligenceView {
        return new AssetIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new AssetGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
