<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Reports profile rejection without exposing source or artifact content. */
#[Api(ApiStatus::Experimental)]
final class InvalidNetworkEgressApplicationProfileException extends NetworkEgressApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['profile_identity_grammar', 'artifact_mismatch', 'artifact_identity_mismatch', 'generated_profile_mismatch'];

        return new self(NetworkEgressApplicationDiagnostic::InvalidProfile, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
