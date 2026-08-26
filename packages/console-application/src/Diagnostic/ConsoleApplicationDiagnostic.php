<?php

declare(strict_types=1);

namespace Kongono\ConsoleApplication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Console Application diagnostics. */
#[Api(ApiStatus::Internal)]
enum ConsoleApplicationDiagnostic: string
{
    case InvalidProcessRequest = 'KGN-CONSOLE-APPLICATION-1001';
    case BuildPrecondition = 'KGN-CONSOLE-APPLICATION-1002';
    case ApplicationFailure = 'KGN-CONSOLE-APPLICATION-1003';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Run the supported one-shot kongo application',
                'Process grammar, build authority, and application failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('operation_category', 'Operation category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('violated_invariant', 'Violated invariant', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'console-application',
                'operation_category' => $this->operationCategory(),
                'violated_invariant' => $this->invariantToken(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidProcessRequest => 'Invalid kongo process request',
            self::BuildPrecondition => 'Application build precondition failed',
            self::ApplicationFailure => 'Kongo application infrastructure failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidProcessRequest => 'Use the documented bounded kongo grammar.',
            self::BuildPrecondition => 'Correct the application build precondition and retry explicitly.',
            self::ApplicationFailure => 'Inspect the retained failure only through an authorized boundary.',
        };
    }

    private function operationCategory(): string
    {
        return match ($this) {
            self::InvalidProcessRequest => 'process_validation',
            self::BuildPrecondition => 'application_build',
            self::ApplicationFailure => 'application_infrastructure',
        };
    }

    private function invariantToken(): string
    {
        return match ($this) {
            self::InvalidProcessRequest => 'exact_process_grammar',
            self::BuildPrecondition => 'exact_current_build_state',
            self::ApplicationFailure => 'bounded_application_boundary',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::InvalidProcessRequest => 'correct_process_request',
            self::BuildPrecondition => 'correct_build_precondition',
            self::ApplicationFailure => 'inspect_authorized_failure',
        };
    }
}
