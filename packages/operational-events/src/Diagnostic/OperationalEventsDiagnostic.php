<?php

declare(strict_types=1);

namespace Kongono\OperationalEvents\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing operational-event failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum OperationalEventsDiagnostic: string
{
    case Value = 'KGN-OP-EVENTS-1001';
    case Schema = 'KGN-OP-EVENTS-1002';
    case Recording = 'KGN-OP-EVENTS-1003';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Record bounded operational event evidence',
                'Operational-event failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'operational-events',
                'failure_category' => $this->category(),
                'remediation' => match ($this) {
                    self::Value => 'correct_value',
                    self::Schema => 'correct_schema',
                    self::Recording => 'inspect_recorder',
                },
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Value => 'value',
            self::Schema => 'schema',
            self::Recording => 'recording',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Value => 'Invalid operational event value',
            self::Schema => 'Invalid operational event schema',
            self::Recording => 'Operational event observation recording failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Value => 'Provide exact schema-admitted bounded operational event fields.',
            self::Schema => 'Provide a bounded predeclared operational event schema.',
            self::Recording => 'Inspect the retained cause and retire the failed external recorder path.',
        };
    }
}
