<?php

declare(strict_types=1);

namespace Kongono\Database\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing version-1 database failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum DatabaseDiagnostic: string
{
    case Validation = 'KGN-DATABASE-1001';
    case LifecycleState = 'KGN-DATABASE-1002';
    case UnsupportedCapability = 'KGN-DATABASE-1003';
    case Connection = 'KGN-DATABASE-1004';
    case ParameterBinding = 'KGN-DATABASE-1005';
    case Execution = 'KGN-DATABASE-1006';
    case Result = 'KGN-DATABASE-1007';
    case TransactionBegin = 'KGN-DATABASE-1008';
    case TransactionCommit = 'KGN-DATABASE-1009';
    case TransactionRollback = 'KGN-DATABASE-1010';
    case Cleanup = 'KGN-DATABASE-1011';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Execute bounded database operation',
                'Database failures must remain explicit, lifecycle-safe, and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'database',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Validation => 'validation',
            self::LifecycleState => 'lifecycle_state',
            self::UnsupportedCapability => 'unsupported_capability',
            self::Connection => 'connection',
            self::ParameterBinding => 'parameter_binding',
            self::Execution => 'execution',
            self::Result => 'result',
            self::TransactionBegin => 'transaction_begin',
            self::TransactionCommit => 'transaction_commit',
            self::TransactionRollback => 'transaction_rollback',
            self::Cleanup => 'cleanup',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Validation => 'Invalid database value',
            self::LifecycleState => 'Invalid database lifecycle state',
            self::UnsupportedCapability => 'Unsupported database capability',
            self::Connection => 'Unable to open database session',
            self::ParameterBinding => 'Unable to bind database parameter',
            self::Execution => 'Unable to execute database statement',
            self::Result => 'Unable to use database result',
            self::TransactionBegin => 'Unable to begin database transaction',
            self::TransactionCommit => 'Unable to commit database transaction',
            self::TransactionRollback => 'Unable to roll back database transaction',
            self::Cleanup => 'Unable to clean up database resources',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Validation => 'Provide a value within the documented neutral database bounds.',
            self::LifecycleState => 'Use the session, stream, or transaction only in its documented active state.',
            self::UnsupportedCapability => 'Request only capabilities reported by the opened session.',
            self::Connection => 'Verify explicit adapter configuration and provider availability before opening a new session.',
            self::ParameterBinding => 'Bind each named parameter with its exact neutral value kind.',
            self::Execution => 'Review the explicit statement and provider state without retrying automatically.',
            self::Result => 'Match the declared result expectation and decode only supported bounded provider values.',
            self::TransactionBegin => 'Request one supported transaction after releasing active results.',
            self::TransactionCommit => 'Retire the failed transaction and do not retry the terminal operation.',
            self::TransactionRollback => 'Retire the failed transaction and verify cleanup before reuse.',
            self::Cleanup => 'Retire uncertain provider state and preserve every primary and cleanup failure.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Validation => 'correct_input',
            self::LifecycleState => 'respect_lifecycle',
            self::UnsupportedCapability => 'check_capabilities',
            self::Connection => 'verify_provider',
            self::ParameterBinding => 'correct_binding',
            self::Execution => 'inspect_statement',
            self::Result => 'check_result_shape',
            self::TransactionBegin => 'release_before_begin',
            self::TransactionCommit => 'retire_transaction',
            self::TransactionRollback => 'retire_transaction',
            self::Cleanup => 'retire_session',
        };
    }
}
