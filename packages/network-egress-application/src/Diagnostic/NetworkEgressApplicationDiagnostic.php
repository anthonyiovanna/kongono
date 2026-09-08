<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed nondisclosing Phase 15F diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressApplicationDiagnostic: string
{
    case InvalidProfile = 'KGN-NET-1540';
    case InvalidGeneratedWiring = 'KGN-NET-1541';
    case PublicationFailed = 'KGN-NET-1542';
    case LoadFailed = 'KGN-NET-1543';
    case WorkflowFailed = 'KGN-NET-1544';
    case LifecycleFailed = 'KGN-NET-1545';
    case RuntimeRequestFailed = 'KGN-NET-1546';
    case OperationFailed = 'KGN-NET-1547';
    case InternalInvariantFailed = 'KGN-NET-1548';

    public function contextSchema(): DiagnosticContextSchema
    {
        $field = match ($this) {
            self::InvalidProfile, self::InvalidGeneratedWiring => new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            self::PublicationFailed, self::LoadFailed, self::WorkflowFailed, self::LifecycleFailed,
            self::RuntimeRequestFailed, self::OperationFailed, self::InternalInvariantFailed => new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
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
            self::InvalidProfile => 'Network Egress Application profile is invalid',
            self::InvalidGeneratedWiring => 'Network Egress Application generated wiring is invalid',
            self::PublicationFailed => 'Network Egress Application wiring publication failed',
            self::LoadFailed => 'Network Egress Application wiring load failed',
            self::WorkflowFailed => 'Network Egress Application build workflow failed',
            self::LifecycleFailed => 'Network Egress Application lifecycle failed',
            self::RuntimeRequestFailed => 'Network Egress Runtime request construction failed',
            self::OperationFailed => 'Network Egress Application operation failed',
            self::InternalInvariantFailed => 'Network Egress Application internal invariant failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Supply one exact current policy source and its exact artifact bytes',
            self::InvalidGeneratedWiring => 'Regenerate canonical wiring from the exact current application profile',
            self::PublicationFailed => 'Use one contained existing application-owned output directory and preserve any conflicting target',
            self::LoadFailed => 'Load one exact current content-addressed generated file in a fresh process',
            self::WorkflowFailed => 'Rebuild from unchanged explicit inputs after inspecting the preserved failure',
            self::LifecycleFailed => 'Create a fresh application instance with a compatible explicit transport',
            self::RuntimeRequestFailed => 'Supply one complete operation input to a fresh application instance',
            self::OperationFailed => 'Inspect the preserved lower-layer failure and its bounded runtime evidence',
            self::InternalInvariantFailed => 'Retire the affected instance and report the invariant failure',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Bind Network Egress Application profile',
            self::InvalidGeneratedWiring => 'Validate Network Egress Application wiring',
            self::PublicationFailed => 'Publish Network Egress Application wiring',
            self::LoadFailed => 'Load Network Egress Application wiring',
            self::WorkflowFailed => 'Build Network Egress Application workflow',
            self::LifecycleFailed => 'Start or run Network Egress Application lifecycle',
            self::RuntimeRequestFailed => 'Construct Network Egress Runtime request',
            self::OperationFailed => 'Run Network Egress Runtime operation',
            self::InternalInvariantFailed => 'Enforce Network Egress Application invariant',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidProfile => 'Profile evidence must bind one exact source and exact-current artifact',
            self::InvalidGeneratedWiring => 'Generated bytes must be canonical, bounded, inert, and exact-current',
            self::PublicationFailed => 'Publication must be contained, atomic, initial-only, and no-clobber',
            self::LoadFailed => 'Loading must use one explicit unchanged exact-current path and fixed boundary shape',
            self::WorkflowFailed => 'Build must preserve exact input and output evidence',
            self::LifecycleFailed => 'One application instance may perform at most one terminal synchronous operation',
            self::RuntimeRequestFailed => 'Runtime request construction must preserve exact bound evidence and collaborators',
            self::OperationFailed => 'Non-Error runtime failures must remain attributable without disclosure',
            self::InternalInvariantFailed => 'Impossible package-owned state must fail closed',
        };
    }
}
