<?php

declare(strict_types=1);

namespace Kongono\Console\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Console contract and parsing vocabulary. */
#[Api(ApiStatus::Internal)]
enum ConsoleDiagnostic: string
{
    case Validation = 'KGN-CONSOLE-1001';
    case Definition = 'KGN-CONSOLE-1002';
    case Invocation = 'KGN-CONSOLE-1003';
    case Failure = 'KGN-CONSOLE-1004';
    case Lifecycle = 'KGN-CONSOLE-1005';
    case Parsing = 'KGN-CONSOLE-1006';
    case UnknownCommand = 'KGN-CONSOLE-1007';
    case UnknownInput = 'KGN-CONSOLE-1008';
    case MissingInput = 'KGN-CONSOLE-1009';
    case SensitiveInput = 'KGN-CONSOLE-1010';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Use neutral Console contracts',
                'Console failures must remain explicit, bounded, and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'console',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Validation => 'validation',
            self::Definition => 'definition',
            self::Invocation => 'invocation',
            self::Failure => 'failure',
            self::Lifecycle => 'lifecycle',
            self::Parsing => 'parsing',
            self::UnknownCommand => 'unknown_command',
            self::UnknownInput => 'unknown_input',
            self::MissingInput => 'missing_input',
            self::SensitiveInput => 'sensitive_input',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Validation => 'Invalid Console value',
            self::Definition => 'Invalid command definition',
            self::Invocation => 'Invalid command invocation',
            self::Failure => 'Invalid command failure or result',
            self::Lifecycle => 'Invalid command lifecycle',
            self::Parsing => 'Invalid command token syntax or policy',
            self::UnknownCommand => 'Unknown command',
            self::UnknownInput => 'Unknown command input',
            self::MissingInput => 'Missing required command input',
            self::SensitiveInput => 'Invalid sensitive input carrier',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Validation => 'Provide a value within the documented neutral Console bounds.',
            self::Definition => 'Provide a command definition that satisfies the documented policy contract.',
            self::Invocation => 'Provide detached inputs and intent that satisfy the command definition.',
            self::Failure => 'Use the documented failure category and result outcome combination.',
            self::Lifecycle => 'Use the command operation only within its documented lifecycle.',
            self::Parsing => 'Provide already-tokenized input using the documented exact grammar and policy tokens.',
            self::UnknownCommand => 'Provide one exact identifier from the explicit command specification set.',
            self::UnknownInput => 'Provide only named inputs declared by the selected command specification.',
            self::MissingInput => 'Supply every required named input through its permitted carrier.',
            self::SensitiveInput => 'Supply sensitive values only through the separate secure input carrier.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Validation => 'correct_input',
            self::Definition => 'correct_definition',
            self::Invocation => 'correct_invocation',
            self::Failure => 'correct_failure',
            self::Lifecycle => 'respect_lifecycle',
            self::Parsing => 'correct_tokens',
            self::UnknownCommand => 'select_command',
            self::UnknownInput => 'select_input',
            self::MissingInput => 'supply_input',
            self::SensitiveInput => 'use_secure_carrier',
        };
    }
}
