<?php

declare(strict_types=1);

namespace Kongono\Observability\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing Phase 10B observability failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum ObservabilityDiagnostic: string
{
    case Context = 'KGN-OBSERVABILITY-1001';
    case Attribute = 'KGN-OBSERVABILITY-1002';
    case SignalIdentity = 'KGN-OBSERVABILITY-1003';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Construct neutral observability values',
                'Observability contract failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'observability',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Context => 'context',
            self::Attribute => 'attribute',
            self::SignalIdentity => 'signal_identity',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Context => 'Invalid observability context value',
            self::Attribute => 'Invalid observability attribute value',
            self::SignalIdentity => 'Invalid signal identity',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Context => 'Provide an exact correlation value within the documented neutral bounds.',
            self::Attribute => 'Provide exact bounded structured scalar attributes within the documented neutral bounds.',
            self::SignalIdentity => 'Provide an exact canonical signal name and supported schema version.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Context => 'correct_context',
            self::Attribute => 'correct_attribute',
            self::SignalIdentity => 'correct_signal_identity',
        };
    }
}
