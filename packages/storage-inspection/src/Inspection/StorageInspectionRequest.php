<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Inspection;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageApplication\Workflow\CurrentStorageApplicationBuild;
use Kongono\StorageInspection\RuntimeEvidence\DetachedStorageRuntimeEvidence;

/** Carries exactly one explicit exact-current build and optional detached aggregates. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageInspectionRequest
{
    public function __construct(
        private CurrentStorageApplicationBuild $current,
        private ?DetachedStorageRuntimeEvidence $runtimeEvidence = null,
    ) {}

    public function current(): CurrentStorageApplicationBuild
    {
        return $this->current;
    }

    public function runtimeEvidence(): ?DetachedStorageRuntimeEvidence
    {
        return $this->runtimeEvidence;
    }
}
