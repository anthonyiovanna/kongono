<?php

declare(strict_types=1);

namespace Kongono\ConsoleCommands\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Console Commands diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ConsoleCommandsDiagnostic: string
{
    case InvalidFamily = 'KGN-CONSOLE-COMMANDS-1001';
    case InvalidInvocation = 'KGN-CONSOLE-COMMANDS-1002';
    case InvalidCapabilityState = 'KGN-CONSOLE-COMMANDS-1003';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Use bounded read-only framework commands',
                'Command family and capability inspection invariants must remain exact and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('operation_category', 'Operation category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('violated_invariant', 'Violated invariant', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'console-commands',
                'operation_category' => $this->operationCategory(),
                'violated_invariant' => $this->invariantToken(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidFamily => 'Invalid read-only command family',
            self::InvalidInvocation => 'Invalid read-only command invocation',
            self::InvalidCapabilityState => 'Invalid read-only capability state',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidFamily => 'Supply the exact current Phase 8F command specification set.',
            self::InvalidInvocation => 'Dispatch the exact read-only command with observation intent and no inputs.',
            self::InvalidCapabilityState => 'Supply a valid immutable capability snapshot through typed composition.',
        };
    }

    private function operationCategory(): string
    {
        return match ($this) {
            self::InvalidFamily => 'family_validation',
            self::InvalidInvocation => 'handler_execution',
            self::InvalidCapabilityState => 'capability_inspection',
        };
    }

    private function invariantToken(): string
    {
        return match ($this) {
            self::InvalidFamily => 'exact_definition_set',
            self::InvalidInvocation => 'exact_read_only_invocation',
            self::InvalidCapabilityState => 'validated_capability_snapshot',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::InvalidFamily => 'supply_exact_definitions',
            self::InvalidInvocation => 'correct_invocation',
            self::InvalidCapabilityState => 'supply_valid_snapshot',
        };
    }
}
