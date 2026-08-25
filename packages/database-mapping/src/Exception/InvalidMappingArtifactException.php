<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports malformed, noncanonical, partial, mixed, or tampered artifact bytes. */
#[Api(ApiStatus::Public)]
final class InvalidMappingArtifactException extends MappingException
{
    public static function invalid(MappingFailureCategory $failureCategory): self
    {
        return new self(MappingDiagnostic::InvalidMappingArtifact, $failureCategory);
    }
}
