<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports rejection of an explicit source, mapping, record, or provenance contract. */
#[Api(ApiStatus::Public)]
final class InvalidMappingSourceException extends MappingException
{
    public static function invalid(MappingFailureCategory $failureCategory): self
    {
        return new self(MappingDiagnostic::InvalidMappingSource, $failureCategory);
    }
}
