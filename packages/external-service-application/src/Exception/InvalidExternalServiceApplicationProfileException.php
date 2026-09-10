<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceApplication\Diagnostic\ExternalServiceApplicationDiagnostic;
use Throwable;

/** Reports exact-current profile rejection without exposing binding content. */
#[Api(ApiStatus::Experimental)]
final class InvalidExternalServiceApplicationProfileException extends ExternalServiceApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = [
            'profile_identity_grammar',
            'binding_artifact_mismatch',
            'binding_selection',
            'binding_origin',
            'credential_mode',
            'required_capability',
            'network_profile_mismatch',
            'credential_source_identity',
            'loaded_profile_mismatch',
        ];

        return new self(
            ExternalServiceApplicationDiagnostic::InvalidProfile,
            ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'],
            $previous,
        );
    }
}
