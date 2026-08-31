<?php

declare(strict_types=1);

namespace Kongono\ExtensionHost\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionHost\Diagnostic\ExtensionHostDiagnostic;
use Throwable;

/** Reports one fixed nondisclosing exact generated-host admission failure. */
#[Api(ApiStatus::Experimental)]
final class InvalidGeneratedExtensionHostException extends ExtensionHostException
{
    private const array VIOLATIONS = [
        'artifact_invalid',
        'plan_identity_mismatch',
        'generated_path_invalid',
        'generated_file_name',
        'generated_file_mismatch',
        'generated_file_changed',
        'class_collision',
        'require_failed',
        'generated_shape',
        'implementation_mismatch',
        'lifecycle_mismatch',
        'provider_count',
        'provider_type',
        'wiring_already_started',
        'start_result',
    ];

    public static function invalid(string $violation, ?Throwable $previous = null): self
    {
        return new self(
            ExtensionHostDiagnostic::InvalidGeneratedHost,
            ['violation' => in_array($violation, self::VIOLATIONS, true) ? $violation : 'artifact_invalid'],
            $previous,
        );
    }
}
