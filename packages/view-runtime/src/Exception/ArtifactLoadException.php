<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewRuntime\Diagnostic\ViewRuntimeDiagnostic;
use Throwable;

/** Reports failure to load or authenticate the exact generated program. */
#[Api(ApiStatus::Experimental)]
final class ArtifactLoadException extends ViewRuntimeException
{
    private const array VIOLATIONS = ['class_collision', 'program_return_mismatch', 'class_missing', 'callable_surface_mismatch', 'source_identity_mismatch', 'model_identity_mismatch', 'require_failed', 'artifact_changed_after_load'];

    public static function failed(string $violation, ?string $sha256 = null, ?Throwable $previous = null): self
    {
        $context = ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation'];
        if (null !== $sha256 && 1 === preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            $context['sha256'] = $sha256;
        }

        return new self(ViewRuntimeDiagnostic::ArtifactLoadFailed, $context, $previous);
    }
}
