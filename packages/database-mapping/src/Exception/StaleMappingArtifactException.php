<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports structurally valid mapping evidence that no longer matches current state. */
#[Api(ApiStatus::Public)]
final class StaleMappingArtifactException extends MappingException
{
    public static function currentMismatch(): self
    {
        return new self(MappingDiagnostic::StaleMappingArtifact, MappingFailureCategory::CurrentState, artifactCurrent: false);
    }
}
