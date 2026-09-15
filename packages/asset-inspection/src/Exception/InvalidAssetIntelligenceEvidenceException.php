<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Diagnostic\AssetInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class InvalidAssetIntelligenceEvidenceException extends AssetInspectionException
{
    public static function invalid(string $component, ?Throwable $previous = null): self
    {
        return new self(AssetInspectionDiagnostic::InvalidIntelligenceEvidence, self::safe($component), $previous);
    }
}
