<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Derivation;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Derivation\ApplicationGraphIntelligenceView;
use Kongono\StorageInspection\Graph\StorageGraphEvidenceProjector;
use Kongono\StorageInspection\Inspection\StorageInspection;
use Kongono\StorageInspection\Role\StorageInspectionDeveloperRole;

/** Derives deterministic evidence without executing or observing a role. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageIntelligenceDeriver
{
    public function derive(
        StorageInspection $inspection,
        StorageInspectionDeveloperRole $role,
        ?ApplicationGraphIntelligenceView $graphView = null,
    ): StorageIntelligenceView {
        return new StorageIntelligenceView(
            $role,
            $role->purpose(),
            $inspection,
            (new StorageGraphEvidenceProjector())->project($inspection, $role, $graphView),
        );
    }
}
