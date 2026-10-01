<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Closed Safe diagnostic for overlapping dispatch on one instance. */
#[Api(ApiStatus::Internal)]
enum EventRuntimeDiagnostic: string
{
    case ReentrantDispatch = 'KGN-EVENT-RUNTIME-2221';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema('Enter synchronous event dispatch', 'One dispatcher instance has at most one active dispatch', [
            new DiagnosticFieldSchema('violation', 'Violation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
        ]);
    }

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), ['violation' => 'reentrant_dispatch']);
    }

    public function title(): string
    {
        return 'Event dispatch is already active';
    }

    public function remediation(): string
    {
        return 'Wait for the active dispatch to finish or use a separate dispatcher instance';
    }
}
