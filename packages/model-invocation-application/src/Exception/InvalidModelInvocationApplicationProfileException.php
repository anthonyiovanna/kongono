<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Reports construction-only profile rejection without exposing source content. */
#[Api(ApiStatus::Internal)]
final class InvalidModelInvocationApplicationProfileException extends ModelInvocationApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['profile_identity_grammar', 'artifact_mismatch', 'artifact_identity_mismatch', 'adapter_identity_mismatch', 'generated_profile_mismatch'];

        return new self(ModelInvocationApplicationDiagnostic::InvalidProfile, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
