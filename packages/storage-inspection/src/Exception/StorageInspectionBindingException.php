<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageInspection\Diagnostic\StorageInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class StorageInspectionBindingException extends StorageInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(StorageInspectionDiagnostic::ArtifactBindingFailure, self::safe($component), $previous);
    }
}
