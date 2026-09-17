<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheApplication\Workflow\CurrentCacheApplicationBuild;
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;

/** Carries exactly one explicit exact-current build and optional detached aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheInspectionRequest
{
    public function __construct(
        private CurrentCacheApplicationBuild $current,
        private ?DetachedCacheRuntimeEvidence $runtimeEvidence = null,
    ) {}

    public function current(): CurrentCacheApplicationBuild
    {
        return $this->current;
    }

    public function runtimeEvidence(): ?DetachedCacheRuntimeEvidence
    {
        return $this->runtimeEvidence;
    }
}
