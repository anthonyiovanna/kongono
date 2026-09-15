<?php

declare(strict_types=1);

namespace Kongono\AssetResolution\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/** Selects whether validated artifact SHA-384 becomes browser metadata. */
#[Api(ApiStatus::Experimental)]
enum AssetIntegrityPolicy: string
{
    case Omit = 'omit';
    case RequireArtifactSha384 = 'require_artifact_sha384';
}
