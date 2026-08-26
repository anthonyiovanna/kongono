<?php

declare(strict_types=1);

namespace Kongono\ConsolePresentation\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Console Presentation diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum ConsolePresentationDiagnostic: string
{
    case InvalidValue = 'KGN-CONSOLE-PRESENTATION-1001';
    case UnsupportedResultData = 'KGN-CONSOLE-PRESENTATION-1002';
    case RenderingFailed = 'KGN-CONSOLE-PRESENTATION-1003';
    case OutputFailed = 'KGN-CONSOLE-PRESENTATION-1004';
    case InvalidHelp = 'KGN-CONSOLE-PRESENTATION-1005';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Present one neutral command result or definition',
                'Presentation failures must remain deterministic, bounded, and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'console-presentation',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidValue => 'Invalid command presentation value',
            self::UnsupportedResultData => 'Unsupported command result data',
            self::RenderingFailed => 'Command presentation rendering failed',
            self::OutputFailed => 'Command presentation output failed',
            self::InvalidHelp => 'Invalid command help presentation',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidValue => 'Provide one bounded valid UTF-8 presentation document.',
            self::UnsupportedResultData => 'Use the bounded CommandResultRecord data implementation.',
            self::RenderingFailed => 'Use validated neutral values supported by the selected presenter.',
            self::OutputFailed => 'Inspect the retained output-port failure through an authorized boundary.',
            self::InvalidHelp => 'Provide validated immutable command definitions.',
        };
    }

    private function category(): string
    {
        return match ($this) {
            self::InvalidValue => 'invalid_value',
            self::UnsupportedResultData => 'unsupported_result_data',
            self::RenderingFailed => 'rendering',
            self::OutputFailed => 'output',
            self::InvalidHelp => 'invalid_help',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::InvalidValue => 'correct_value',
            self::UnsupportedResultData => 'use_result_record',
            self::RenderingFailed => 'correct_rendering_input',
            self::OutputFailed => 'inspect_output_failure',
            self::InvalidHelp => 'correct_help_input',
        };
    }
}
