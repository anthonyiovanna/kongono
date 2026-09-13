<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use Throwable;

/** Reports failure to clear operation-local mutable render state. */
#[Api(ApiStatus::Experimental)]
final class RenderRetirementException extends ViewRuntimeException
{
    private const array KINDS = ['success', 'invalid_request', 'schema_mismatch', 'stale_artifact', 'unsupported_output_context', 'bound_exceeded', 'rendering_failed'];

    public static function failed(string $primaryFailureKind, Throwable $previous): self
    {
        return new self(ViewRuntimeDiagnostic::RetirementFailed, [
            'violation' => 'retirement_failed',
            'primary_failure_kind' => in_array($primaryFailureKind, self::KINDS, true) ? $primaryFailureKind : 'rendering_failed',
        ], $previous);
    }
}
