<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Diagnostic\CacheInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class CacheInspectionVerificationException extends CacheInspectionException
{
    public static function failed(string $component, ?Throwable $previous = null): self
    {
        return new self(CacheInspectionDiagnostic::VerificationFailure, self::safe($component), $previous);
    }
}
