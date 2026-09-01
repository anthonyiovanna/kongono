<?php

declare(strict_types=1);

namespace Kongono\AgentApplication\Exception;

use Kongono\AgentApplication\Diagnostic\AgentApplicationDiagnostic;
use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Throwable;

/** Reports a canonical generated-byte violation without echoing input bytes. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedAgentWiringException extends AgentApplicationException
{
    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        $allowed = ['generated_bytes', 'generated_header', 'metadata_json', 'metadata_shape', 'identity_grammar', 'implementation_mismatch', 'template_mismatch', 'profile_mismatch'];

        return new self(AgentApplicationDiagnostic::InvalidGeneratedWiring, ['violation' => in_array($violation, $allowed, true) ? $violation : 'unsupported_violation'], $previous);
    }
}
