<?php

declare(strict_types=1);

namespace Kongono\ViewInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewInspection\Diagnostic\ViewInspectionDiagnostic;
use Throwable;

/** Reports structural or reconstructed artifact evidence mismatch. */
#[Api(ApiStatus::Experimental)]
final class ViewArtifactEvidenceException extends ViewInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(ViewInspectionDiagnostic::ArtifactEvidenceMismatch, self::safe($component), $previous);
    }
}
