<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports a failed content-addressed mapping-artifact publication attempt. */
#[Api(ApiStatus::Public)]
final class MappingArtifactPublicationException extends MappingException
{
    public static function failed(): self
    {
        return new self(
            MappingDiagnostic::GeneratedPublicationFailed,
            MappingFailureCategory::Publication,
            artifactCurrent: true,
            operationKind: 'publish_mapping',
        );
    }
}
