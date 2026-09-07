<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationAgentBridge\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing diagnostic vocabulary for the bridge. */
#[Api(ApiStatus::Internal)]
enum ModelInvocationAgentBridgeDiagnostic: string
{
    case InvalidBridge = 'KGN-MODEL-BRIDGE-1425';
    case IneligibleResult = 'KGN-MODEL-BRIDGE-1426';
    case MappingRejected = 'KGN-MODEL-BRIDGE-1427';
    case ProjectionFailed = 'KGN-MODEL-BRIDGE-1428';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('stage', 'Stage', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
        ];
        if (self::IneligibleResult === $this) {
            $fields[] = new DiagnosticFieldSchema('mode', 'Mode', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false);
            $fields[] = new DiagnosticFieldSchema('outcome', 'Outcome', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false);
            $fields[] = new DiagnosticFieldSchema('attempt_count', 'Attempt count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
        }
        if (self::MappingRejected === $this) {
            $fields[] = new DiagnosticFieldSchema('permitted_count', 'Permitted count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
        }
        if (self::ProjectionFailed === $this) {
            $fields[] = new DiagnosticFieldSchema('usage_count', 'Usage count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
        }

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidBridge => 'Model invocation Agent bridge input is invalid',
            self::IneligibleResult => 'Model invocation result is ineligible for proposal projection',
            self::MappingRejected => 'Model action mapping is not eligible for the Agent request',
            self::ProjectionFailed => 'Model proposal evidence projection failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBridge => 'Supply exact typed bridge inputs that satisfy the closed Phase 14E invariant',
            self::IneligibleResult => 'Supply one successful retired cleanup-complete one-attempt action-suggestion result',
            self::MappingRejected => 'Supply an exact action mapping whose capability is explicitly permitted',
            self::ProjectionFailed => 'Supply one exact usage quantity and canonically encodable bounded evidence',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidBridge => 'Validate model invocation Agent bridge input',
            self::IneligibleResult => 'Validate model invocation result eligibility',
            self::MappingRejected => 'Validate action-to-capability mapping eligibility',
            self::ProjectionFailed => 'Project deterministic model proposal evidence',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidBridge => 'Bridge inputs and internal relations must remain exact and typed',
            self::IneligibleResult => 'Only successful retired one-attempt action-suggestion evidence may be bridged',
            self::MappingRejected => 'Action and capability matching is exact and denied by default',
            self::ProjectionFailed => 'Usage and canonical evidence projection must be exact and non-disclosing',
        };
    }
}
