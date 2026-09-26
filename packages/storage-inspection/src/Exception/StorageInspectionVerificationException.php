<?php

declare(strict_types=1);

namespace Kongono\StorageInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageInspection\Diagnostic\StorageInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class StorageInspectionVerificationException extends StorageInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(StorageInspectionDiagnostic::VerificationFailure, self::safe($component), $previous);
    }
}
