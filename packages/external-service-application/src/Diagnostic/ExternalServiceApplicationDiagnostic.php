<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Phase 16F diagnostic vocabulary. */
#[Api(ApiStatus::Experimental)]
enum ExternalServiceApplicationDiagnostic: string
{
    case InvalidProfile = 'KGN-EXTERNAL-SERVICE-APPLICATION-1624';
    case LifecycleFailed = 'KGN-EXTERNAL-SERVICE-APPLICATION-1625';
    case OperationFailed = 'KGN-EXTERNAL-SERVICE-APPLICATION-1626';
    case InternalInvariantFailed = 'KGN-EXTERNAL-SERVICE-APPLICATION-1627';

    public function contextSchema(): DiagnosticContextSchema
    {
        $field = match ($this) {
            self::InvalidProfile => new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            self::LifecycleFailed, self::OperationFailed, self::InternalInvariantFailed => new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
        };

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [$field]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidProfile => 'External Service Application profile is invalid',
            self::LifecycleFailed => 'External Service Application lifecycle failed',
            self::OperationFailed => 'External Service Application operation failed',
            self::InternalInvariantFailed => 'External Service Application internal invariant failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Supply one exact current binding artifact and matching exact current egress profile',
            self::LifecycleFailed => 'Create a fresh application instance with the exact explicit collaborators',
            self::OperationFailed => 'Inspect the preserved typed lower-layer failure without rendering foreign content',
            self::InternalInvariantFailed => 'Retire the affected instance and report the closed invariant stage',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Bind External Service Application profile',
            self::LifecycleFailed => 'Start or run External Service Application lifecycle',
            self::OperationFailed => 'Run one External Service Application operation',
            self::InternalInvariantFailed => 'Enforce External Service Application invariant',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidProfile => 'One selected binding and egress profile must remain exact-current and mutually consistent',
            self::LifecycleFailed => 'One application instance may perform at most one terminal synchronous operation',
            self::OperationFailed => 'Credential and network failures remain attributable without disclosure or replay',
            self::InternalInvariantFailed => 'Impossible package-owned state must fail closed',
        };
    }
}
