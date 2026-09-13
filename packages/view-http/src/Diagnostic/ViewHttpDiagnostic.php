<?php

declare(strict_types=1);

namespace Kongono\ViewHttp\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed bounded diagnostic vocabulary for view HTTP adaptation. */
#[Api(ApiStatus::Internal)]
enum ViewHttpDiagnostic: string
{
    case InvalidPolicy = 'KGN-VIEW-HTTP-1840';
    case InvalidRenderResult = 'KGN-VIEW-HTTP-1841';
    case StreamCreationFailed = 'KGN-VIEW-HTTP-1842';
    case ResponseCreationFailed = 'KGN-VIEW-HTTP-1843';
    case ResponseAssemblyFailed = 'KGN-VIEW-HTTP-1844';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            new DiagnosticFieldSchema('limit', 'Limit', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('observed_count', 'Observed count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('status_code', 'Status code', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('header_count', 'Header count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
            new DiagnosticFieldSchema('output_byte_count', 'Output byte count', DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidPolicy => 'View response policy is invalid',
            self::InvalidRenderResult => 'Completed view result is invalid',
            self::StreamCreationFailed => 'View response stream could not be created',
            self::ResponseCreationFailed => 'View response could not be created',
            self::ResponseAssemblyFailed => 'View response could not be assembled',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidPolicy => 'Supply one bounded status, reason, content type, and caller-header policy',
            self::InvalidRenderResult => 'Supply one internally consistent completed Phase 18 render result',
            self::StreamCreationFailed => 'Supply a PSR-17 stream factory that preserves exact supplied bytes',
            self::ResponseCreationFailed => 'Supply a PSR-17 response factory that preserves explicit status and reason',
            self::ResponseAssemblyFailed => 'Supply PSR-7 implementations that preserve exact immutable response state',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidPolicy => 'Validate explicit view response policy',
            self::InvalidRenderResult => 'Validate completed render evidence',
            self::StreamCreationFailed => 'Create and verify exact response body stream',
            self::ResponseCreationFailed => 'Create explicit PSR-7 response',
            self::ResponseAssemblyFailed => 'Attach and verify exact PSR-7 response state',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidPolicy => 'All response choices are explicit, bounded, and free of framing or hop-by-hop headers',
            self::InvalidRenderResult => 'Only exact completed output with matching byte-count and digest evidence is adapted',
            self::StreamCreationFailed => 'The response body stream is seekable, readable, exact, and rewound',
            self::ResponseCreationFailed => 'The factory response retains the exact caller-selected status and reason',
            self::ResponseAssemblyFailed => 'The returned response contains only exact derived and caller-selected state',
        };
    }
}
