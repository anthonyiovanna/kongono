<?php

declare(strict_types=1);

namespace Kongono\Hantei\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\Hantei\Exception\InvalidHanteiValueException;

/** Owns the eight closed Safe diagnostic contracts for read-only Hantei. */
#[Api(ApiStatus::Internal)]
enum HanteiDiagnostic: string
{
    case InvalidRequest = 'KGN-HANTEI-1001';
    case InvalidProfile = 'KGN-HANTEI-1002';
    case SubjectUnavailable = 'KGN-HANTEI-1003';
    case SubjectChanged = 'KGN-HANTEI-1004';
    case ToolUnavailable = 'KGN-HANTEI-1005';
    case CheckBlocked = 'KGN-HANTEI-1006';
    case InvalidEvidence = 'KGN-HANTEI-1007';
    case UnexpectedCommandFailure = 'KGN-HANTEI-1008';

    public function context(string $violation): DiagnosticContext
    {
        $valid = in_array($violation, match ($this) {
            self::InvalidRequest => ['invalid-command-usage', 'invalid-root'],
            self::InvalidProfile => ['profile-mismatch'],
            self::SubjectUnavailable => ['subject-unavailable', 'knowledge-unavailable'],
            self::SubjectChanged => ['subject-changed'],
            self::ToolUnavailable => ['tool-unavailable'],
            self::CheckBlocked => ['process-start-failed', 'process-timeout', 'output-limit', 'audit-unavailable'],
            self::InvalidEvidence => ['invalid-evidence'],
            self::UnexpectedCommandFailure => ['unexpected-failure'],
        }, true);
        if (!$valid) {
            throw InvalidHanteiValueException::diagnosticViolation();
        }

        return new DiagnosticContext(new DiagnosticContextSchema(
            $this->operation(),
            $this->violatedInvariant(),
            [new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe)],
        ), ['violation' => $violation]);
    }

    public function operation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Admit one exact read-only Hantei request',
            self::InvalidProfile => 'Load the closed framework quality profile',
            self::SubjectUnavailable => 'Calculate the exact Git-visible framework subject',
            self::SubjectChanged => 'Keep one framework subject unchanged throughout verification',
            self::ToolUnavailable => 'Admit the fixed local verification tools',
            self::CheckBlocked => 'Execute one bounded framework quality check',
            self::InvalidEvidence => 'Construct one bounded Hantei evidence set',
            self::UnexpectedCommandFailure => 'Run the read-only Hantei command',
        };
    }

    public function violatedInvariant(): string
    {
        return match ($this) {
            self::InvalidRequest => 'The request must use the exact verify grammar and one explicit framework root',
            self::InvalidProfile => 'The package profile and root Composer scripts must match the fixed versioned contract',
            self::SubjectUnavailable => 'Every admitted subject and knowledge byte must be safely readable and current',
            self::SubjectChanged => 'All checks must judge one byte-identical Git-visible framework subject',
            self::ToolUnavailable => 'Only identified local PHP, Composer, and Git tools may execute',
            self::CheckBlocked => 'Every selected check must start and finish within fixed process and stream bounds',
            self::InvalidEvidence => 'Evidence must be complete, ordered, relevant, bounded, and internally consistent',
            self::UnexpectedCommandFailure => 'Unexpected failure must remain bounded and must not disclose native or rejected data',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use a valid read-only Hantei request',
            self::InvalidProfile => 'Restore the fixed Hantei quality profile',
            self::SubjectUnavailable => 'Restore the exact framework subject',
            self::SubjectChanged => 'Rerun against an unchanged framework subject',
            self::ToolUnavailable => 'Provide the required local verification tool',
            self::CheckBlocked => 'Restore bounded execution of the selected check',
            self::InvalidEvidence => 'Restore complete deterministic verification evidence',
            self::UnexpectedCommandFailure => 'Inspect the bounded Hantei diagnostic',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidRequest => 'Use kongono-hantei verify with one explicit framework root',
            self::InvalidProfile => 'Restore the package profile and exact required root Composer script graph',
            self::SubjectUnavailable => 'Restore ordinary readable current source files and the current knowledge foundation',
            self::SubjectChanged => 'Stop concurrent source changes and start a fresh verification run',
            self::ToolUnavailable => 'Invoke Hantei through Composer with readable identified local PHP, Composer, and Git tools',
            self::CheckBlocked => 'Resolve the bounded process, timeout, output, or audit transport condition and retry',
            self::InvalidEvidence => 'Discard the invalid evidence and run all eight fixed checks again',
            self::UnexpectedCommandFailure => 'Inspect the diagnostic identifier and deterministic evidence before retrying',
        };
    }
}
