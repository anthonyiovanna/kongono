<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationAgentBridge\Diagnostic\ModelInvocationAgentBridgeDiagnostic;
use Throwable;

/** Reports a closed bridge construction or internal invariant failure. */
#[Api(ApiStatus::Internal)]
final class InvalidModelInvocationAgentBridgeException extends ModelInvocationAgentBridgeException
{
    private const array VIOLATIONS = ['action_suggestion_required', 'output_byte_overflow', 'unsupported_visibility'];
    private const array STAGES = ['bridge', 'output_bytes', 'visibility', 'evidence', 'proposal'];

    public static function invalid(string $violation, string $stage, ?Throwable $previous = null): self
    {
        return new self(
            ModelInvocationAgentBridgeDiagnostic::InvalidBridge,
            ['violation' => self::safe($violation, self::VIOLATIONS), 'stage' => self::safe($stage, self::STAGES)],
            $previous,
        );
    }

    /** @param list<string> $allowed */
    private static function safe(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'unsupported_violation';
    }
}
