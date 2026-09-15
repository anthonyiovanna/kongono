<?php

declare(strict_types=1);

namespace Kongono\AssetInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetInspection\Diagnostic\AssetInspectionDiagnostic;
use Throwable;

#[Api(ApiStatus::Experimental)]
final class AssetInspectionBindingException extends AssetInspectionException
{
    public static function mismatch(string $component, ?Throwable $previous = null): self
    {
        return new self(AssetInspectionDiagnostic::ArtifactBindingFailure, self::safe($component), $previous);
    }
}
