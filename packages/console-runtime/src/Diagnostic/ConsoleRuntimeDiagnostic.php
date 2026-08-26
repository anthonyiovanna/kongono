<?php

declare(strict_types=1);

namespace Kongono\ConsoleRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Console Runtime diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ConsoleRuntimeDiagnostic: string
{
    case InvalidBinding = 'KGN-CONSOLE-RUNTIME-1001';
    case HandlerUnavailable = 'KGN-CONSOLE-RUNTIME-1002';
    case CancelledBeforeExecution = 'KGN-CONSOLE-RUNTIME-1003';
    case ExecutionFailed = 'KGN-CONSOLE-RUNTIME-1004';
    case CleanupFailed = 'KGN-CONSOLE-RUNTIME-1005';
    case ExecutionAndCleanupFailed = 'KGN-CONSOLE-RUNTIME-1006';

    public function context(bool $primaryFailure, int $cleanupFailureCount): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Execute one command operation',
                'Command execution and cleanup evidence must remain bounded and explicit',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('primary_failure', 'Primary failure', DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('cleanup_failure_count', 'Cleanup failure count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'console-runtime',
                'failure_category' => $this->category(),
                'primary_failure' => $primaryFailure,
                'cleanup_failure_count' => $cleanupFailureCount,
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Invalid Console Runtime value or lifecycle',
            self::HandlerUnavailable => 'Command handler unavailable',
            self::CancelledBeforeExecution => 'Command cancelled before execution',
            self::ExecutionFailed => 'Command execution failed',
            self::CleanupFailed => 'Command cleanup failed',
            self::ExecutionAndCleanupFailed => 'Command execution and cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidBinding => 'Provide valid explicit bindings and respect the command operation lifecycle.',
            self::HandlerUnavailable => 'Supply one exact explicit binding for the command before dispatch.',
            self::CancelledBeforeExecution => 'Retry only when caller-owned cancellation evidence permits execution.',
            self::ExecutionFailed => 'Inspect the retained primary failure through an authorized diagnostic boundary.',
            self::CleanupFailed => 'Inspect the retained cleanup failures and retire affected external resources.',
            self::ExecutionAndCleanupFailed => 'Preserve the primary and cleanup failures and retire affected external resources.',
        };
    }

    private function category(): string
    {
        return match ($this) {
            self::InvalidBinding => 'invalid_runtime_value',
            self::HandlerUnavailable => 'handler_unavailable',
            self::CancelledBeforeExecution => 'cancelled_before_execution',
            self::ExecutionFailed => 'execution',
            self::CleanupFailed => 'cleanup',
            self::ExecutionAndCleanupFailed => 'execution_and_cleanup',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::InvalidBinding => 'correct_runtime_value',
            self::HandlerUnavailable => 'bind_handler',
            self::CancelledBeforeExecution => 'review_cancellation',
            self::ExecutionFailed => 'inspect_primary',
            self::CleanupFailed => 'inspect_cleanup',
            self::ExecutionAndCleanupFailed => 'inspect_combined',
        };
    }
}
