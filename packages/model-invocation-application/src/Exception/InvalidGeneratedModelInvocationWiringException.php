<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationApplication\Diagnostic\ModelInvocationApplicationDiagnostic;
use Throwable;

/** Reports construction-only generated-byte rejection without echoing bytes. */
#[Api(ApiStatus::Internal)]
final class InvalidGeneratedModelInvocationWiringException extends ModelInvocationApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['generated_bytes', 'generated_header', 'metadata_json', 'metadata_shape', 'identity_grammar', 'implementation_mismatch', 'template_mismatch', 'profile_mismatch'];

        return new self(ModelInvocationApplicationDiagnostic::InvalidGeneratedWiring, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
