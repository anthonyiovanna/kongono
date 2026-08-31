<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionInspection\Diagnostic\ExtensionInspectionDiagnostic;
use Throwable;

/** Reports fixed safe loading, structural, or identity-binding failures. */
#[Api(ApiStatus::Experimental)]
final class ExtensionInspectionLoadException extends ExtensionInspectionException
{
    public static function declaration(string $component = 'declaration_artifact', ?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::DeclarationArtifactUnavailable, self::safe($component), $previous);
    }

    public static function wiring(string $component = 'generated_wiring', ?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::GeneratedWiringUnavailable, self::safe($component), $previous);
    }

    public static function structural(?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::StructurallyInvalidInput, 'structural_validation', $previous);
    }

    public static function identityMismatch(): self
    {
        return new self(ExtensionInspectionDiagnostic::DeclarationSetMismatch, 'declaration_set_identity');
    }

    private static function safe(string $component): string
    {
        return in_array($component, ['declaration_artifact', 'generated_wiring', 'path', 'ordinary_file', 'bounded_read', 'stable_read'], true) ? $component : 'path';
    }
}
