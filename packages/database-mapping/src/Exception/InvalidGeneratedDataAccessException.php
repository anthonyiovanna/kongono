<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports malformed, noncanonical, stale, or tampered generated PHP bytes. */
#[Api(ApiStatus::Public)]
final class InvalidGeneratedDataAccessException extends MappingException
{
    public static function invalid(MappingFailureCategory $failureCategory, bool $artifactCurrent = false): self
    {
        return new self(MappingDiagnostic::InvalidGeneratedDataAccess, $failureCategory, artifactCurrent: $artifactCurrent);
    }
}
