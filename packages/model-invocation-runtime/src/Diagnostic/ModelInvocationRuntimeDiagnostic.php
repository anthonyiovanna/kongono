<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing diagnostic vocabulary for model invocation. */
#[Api(ApiStatus::Internal)]
enum ModelInvocationRuntimeDiagnostic: string
{
    case InvalidInput = 'KGN-MODEL-RUNTIME-1417';
    case Rejected = 'KGN-MODEL-RUNTIME-1418';
    case AdapterFailed = 'KGN-MODEL-RUNTIME-1419';
    case ProtocolViolated = 'KGN-MODEL-RUNTIME-1420';
    case BudgetExceeded = 'KGN-MODEL-RUNTIME-1421';
    case ProbeFailed = 'KGN-MODEL-RUNTIME-1422';
    case CleanupFailed = 'KGN-MODEL-RUNTIME-1423';
    case OperationAndCleanupFailed = 'KGN-MODEL-RUNTIME-1424';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = match ($this) {
            self::InvalidInput => [$this->text('violation', 'Violation')],
            self::Rejected => [$this->text('stage', 'Stage'), $this->text('outcome', 'Outcome'), $this->integer('attempt_count', 'Attempt count')],
            self::AdapterFailed, self::ProtocolViolated, self::ProbeFailed => [$this->text('stage', 'Stage'), $this->text('violation', 'Violation')],
            self::BudgetExceeded => [$this->text('stage', 'Stage'), $this->text('violation', 'Violation'), $this->integer('limit', 'Limit'), $this->integer('observed_count', 'Observed count')],
            self::CleanupFailed => [$this->text('outcome', 'Outcome'), $this->integer('cleanup_count', 'Cleanup failure count')],
            self::OperationAndCleanupFailed => [$this->boolean('primary_present', 'Primary failure present'), $this->integer('cleanup_count', 'Cleanup failure count')],
        };

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
            self::InvalidInput => 'Model invocation runtime input is invalid',
            self::Rejected => 'Model invocation plan binding was rejected',
            self::AdapterFailed => 'Model invocation adapter operation failed',
            self::ProtocolViolated => 'Model invocation emission protocol was violated',
            self::BudgetExceeded => 'Model invocation evidence budget was exceeded',
            self::ProbeFailed => 'Model invocation clock or cancellation probe failed',
            self::CleanupFailed => 'Model invocation session cleanup failed',
            self::OperationAndCleanupFailed => 'Model invocation operation and cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Supply exact typed runtime values satisfying the documented invariant',
            self::Rejected => 'Rebuild the exact current plan artifact and verify the caller-selected adapter identity',
            self::AdapterFailed => 'Inspect the caller-owned adapter session while preserving the original failure object',
            self::ProtocolViolated => 'Correct the normalized pull sequence, identities, modes, or terminal lifecycle',
            self::BudgetExceeded => 'Adjust the explicit plan budget or keep normalized evidence within its exact ceiling',
            self::ProbeFailed => 'Correct the caller-owned monotonic clock or cancellation probe contract',
            self::CleanupFailed => 'Treat the attempt as uncertain and inspect every preserved close failure',
            self::OperationAndCleanupFailed => 'Preserve the primary failure and inspect every separate close failure',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Validate model invocation runtime input',
            self::Rejected => 'Bind exact-current model invocation evidence',
            self::AdapterFailed => 'Pull normalized adapter evidence',
            self::ProtocolViolated => 'Validate normalized invocation lifecycle',
            self::BudgetExceeded => 'Enforce explicit invocation evidence budgets',
            self::ProbeFailed => 'Probe invocation cancellation and monotonic time',
            self::CleanupFailed => 'Retire the invocation adapter session',
            self::OperationAndCleanupFailed => 'Compose invocation lifecycle failures',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidInput => 'Runtime values remain exact, typed, bounded, and caller-owned',
            self::Rejected => 'Current plan evidence and adapter identity match before an attempt begins',
            self::AdapterFailed => 'Adapter failures remain visible without disclosing adapter or provider values',
            self::ProtocolViolated => 'Pull emissions follow the exact normalized request-mode lifecycle',
            self::BudgetExceeded => 'Duration and accepted evidence never exceed explicit finite ceilings',
            self::ProbeFailed => 'Clock values are nonnegative and monotonic and probes remain synchronous',
            self::CleanupFailed => 'Every opened session receives exactly one close attempt',
            self::OperationAndCleanupFailed => 'Primary and cleanup failures remain separately inspectable',
        };
    }

    private function text(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Text, DiagnosticDisclosure::Safe);
    }

    private function integer(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe);
    }

    private function boolean(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe);
    }
}
