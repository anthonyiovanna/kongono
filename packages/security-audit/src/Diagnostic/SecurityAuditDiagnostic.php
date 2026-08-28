<?php

declare(strict_types=1);

namespace Kongono\SecurityAudit\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed non-disclosing security-audit failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum SecurityAuditDiagnostic: string
{
    case Value = 'KGN-SEC-AUDIT-1001';
    case Schema = 'KGN-SEC-AUDIT-1002';
    case Recording = 'KGN-SEC-AUDIT-1003';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Record bounded security audit evidence',
                'Security-audit failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'security-audit',
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
            self::Value => 'Invalid security audit value',
            self::Schema => 'Invalid security audit schema',
            self::Recording => 'Security audit observation recording failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Value => 'Provide exact schema-admitted bounded security audit fields.',
            self::Schema => 'Provide a bounded predeclared security audit schema.',
            self::Recording => 'Inspect the retained cause and retire the failed external recorder path.',
        };
    }
}
