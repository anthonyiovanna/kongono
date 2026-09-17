<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\CacheInspection\Graph\CacheGraphEvidenceProjector;
use Kongono\CacheInspection\Inspection\CacheInspection;
use Kongono\CacheInspection\Role\CacheInspectionDeveloperRole;

/** Derives deterministic evidence without executing or observing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheIntelligenceDeriver
{
    public function derive(
        CacheInspection $inspection,
        CacheInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): CacheIntelligenceView {
        return new CacheIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new CacheGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
