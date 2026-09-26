<?php

declare(strict_types=1);

namespace Kongono\StorageBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageBuild\Diagnostic\StorageBuildDiagnostic;

/** Reports an artifact that is not exact-current for the supplied profile set. */
#[Api(ApiStatus::Experimental)]
final class StaleStorageProfileArtifactException extends StorageBuildException
{
    public static function stale(): self
    {
        return new self(StorageBuildDiagnostic::StaleArtifact, ['violation' => 'stale_artifact']);
    }
}
