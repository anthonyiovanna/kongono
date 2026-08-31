<?php

declare(strict_types=1);

namespace Kongono\ExtensionInspection\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionInspection\Diagnostic\ExtensionInspectionDiagnostic;
use Throwable;

/** Reports one bounded invalid inspection, view, or verification value. */
#[Api(ApiStatus::Experimental)]
final class InvalidExtensionInspectionValueException extends ExtensionInspectionException
{
    public static function value(string $component, ?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::InvalidBoundedValue, self::safe($component), $previous);
    }

    public static function aggregate(string $component, ?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::InconsistentAggregate, self::safe($component), $previous);
    }

    public static function verification(string $component, ?Throwable $previous = null): self
    {
        return new self(ExtensionInspectionDiagnostic::IndependentVerificationFailed, self::safe($component), $previous);
    }

    private static function safe(string $component): string
    {
        return in_array($component, ['digest', 'identity', 'count', 'category_counts', 'lifecycle_counts', 'capability_counts', 'contribution_counts', 'visibility_counts', 'semantic_tokens', 'serialization', 'check_order', 'check_value', 'failure_order', 'verdict'], true) ? $component : 'unsupported_component';
    }
}
