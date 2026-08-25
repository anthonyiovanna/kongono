<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports a failed content-addressed generated-file publication attempt. */
#[Api(ApiStatus::Public)]
final class GeneratedDataAccessPublicationException extends MappingException
{
    public static function failed(MappingFailureCategory $failureCategory = MappingFailureCategory::Publication): self
    {
        return new self(MappingDiagnostic::GeneratedPublicationFailed, $failureCategory, artifactCurrent: true, operationKind: 'publish');
    }
}
