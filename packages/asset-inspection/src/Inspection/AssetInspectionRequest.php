<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetApplication\Workflow\CurrentAssetApplicationBuild;

/** Carries exactly one explicit already exact-current asset result. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetInspectionRequest
{
    public function __construct(private CurrentAssetApplicationBuild $current) {}

    public function current(): CurrentAssetApplicationBuild
    {
        return $this->current;
    }
}
