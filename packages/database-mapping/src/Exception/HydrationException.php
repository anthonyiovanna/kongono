<?php

declare(strict_types=1);

namespace Kongono\DatabaseMapping\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMapping\Diagnostic\MappingDiagnostic;
use Kongono\DatabaseMapping\Internal\MappingFailureCategory;

/** Reports a non-disclosing generated mapper row-shape or scalar mismatch. */
#[Api(ApiStatus::Public)]
final class HydrationException extends MappingException
{
    public static function shape(int $ordinal, int $count): self
    {
        return new self(MappingDiagnostic::HydrationFailed, MappingFailureCategory::HydrationShape, $ordinal, $count, true, 'find');
    }

    public static function kind(int $ordinal): self
    {
        return new self(MappingDiagnostic::HydrationFailed, MappingFailureCategory::HydrationKind, $ordinal, artifactCurrent: true, operationKind: 'find');
    }

    public static function value(int $ordinal): self
    {
        return new self(MappingDiagnostic::HydrationFailed, MappingFailureCategory::HydrationValue, $ordinal, artifactCurrent: true, operationKind: 'find');
    }
}
