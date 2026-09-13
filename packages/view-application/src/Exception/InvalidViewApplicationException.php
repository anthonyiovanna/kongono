<?php

declare(strict_types=1);

namespace Kongono\ViewApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ViewApplication\Diagnostic\ViewApplicationDiagnostic;
use Throwable;

/** Reports invalid definition or profile evidence without exposing paths. */
#[Api(ApiStatus::Internal)]
final class InvalidViewApplicationException extends ViewApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['empty_value', 'nul_byte', 'invalid_root', 'invalid_source', 'invalid_output', 'outside_root', 'identity_grammar', 'profile_mismatch'];

        return new self(ViewApplicationDiagnostic::InvalidDefinition, in_array($violation, $allowed, true) ? $violation : 'unsupported_violation', $previous);
    }
}
