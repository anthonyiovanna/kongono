<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphIntelligence\Diagnostic\ApplicationGraphIntelligenceDiagnostic;
use Throwable;

/** Reports one bounded invalid derived-view or verification value. */
#[Api(ApiStatus::Experimental)]
final class InvalidApplicationGraphIntelligenceValueException extends ApplicationGraphIntelligenceException
{
    private const array VIEW_COMPONENTS = [
        'artifact_digest',
        'negative_total',
        'node_vocabulary',
        'relation_vocabulary',
        'node_total',
        'relation_total',
        'semantic_token',
        'uncertainty_order',
        'serialization',
        'negative_count',
    ];

    private const array VERIFICATION_COMPONENTS = [
        'artifact_digest',
        'check_order',
        'check_value',
        'failure_order',
        'verdict',
    ];

    public static function view(string $component, ?Throwable $previous = null): self
    {
        return new self(
            ApplicationGraphIntelligenceDiagnostic::InvalidDerivedValue,
            self::safeComponent($component, self::VIEW_COMPONENTS),
            $previous,
        );
    }

    public static function verification(string $component, ?Throwable $previous = null): self
    {
        return new self(
            ApplicationGraphIntelligenceDiagnostic::InvalidVerificationValue,
            self::safeComponent($component, self::VERIFICATION_COMPONENTS),
            $previous,
        );
    }

    /** @param list<string> $allowed */
    private static function safeComponent(string $component, array $allowed): string
    {
        return in_array($component, $allowed, true) ? $component : 'unsupported_component';
    }
}
