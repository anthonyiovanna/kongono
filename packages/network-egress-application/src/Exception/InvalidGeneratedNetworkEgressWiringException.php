<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressApplication\Diagnostic\NetworkEgressApplicationDiagnostic;
use Throwable;

/** Reports construction-only generated-byte rejection without echoing bytes. */
#[Api(ApiStatus::Internal)]
final class InvalidGeneratedNetworkEgressWiringException extends NetworkEgressApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['generated_bytes', 'generated_header', 'metadata_json', 'metadata_shape', 'identity_grammar', 'implementation_mismatch', 'template_mismatch', 'profile_mismatch'];

        return new self(NetworkEgressApplicationDiagnostic::InvalidGeneratedWiring, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
