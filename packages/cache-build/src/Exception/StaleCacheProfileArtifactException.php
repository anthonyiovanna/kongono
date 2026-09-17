<?php

declare(strict_types=1);

namespace Kongono\CacheBuild\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheBuild\Diagnostic\CacheBuildDiagnostic;

/** Reports an artifact that is not exact-current for the supplied profile set. */
#[Api(ApiStatus::Experimental)]
final class StaleCacheProfileArtifactException extends CacheBuildException
{
    public static function stale(): self
    {
        return new self(CacheBuildDiagnostic::StaleArtifact, ['violation' => 'stale_artifact']);
    }
}
