<?php

declare(strict_types=1);

namespace Kongono\CacheInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheInspection\Diagnostic\CacheInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class InvalidCacheIntelligenceEvidenceException extends CacheInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(CacheInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
