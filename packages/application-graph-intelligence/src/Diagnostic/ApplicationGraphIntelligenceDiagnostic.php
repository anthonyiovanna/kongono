<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphIntelligence\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed Safe metadata for bounded derivation and verification failures. */
#[Api(ApiStatus::Internal)]
enum ApplicationGraphIntelligenceDiagnostic: string
{
    case InvalidDerivedValue = 'KGN-APPLICATION-GRAPH-INTELLIGENCE-1001';
    case InvalidVerificationValue = 'KGN-APPLICATION-GRAPH-INTELLIGENCE-1002';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema(
            $this->operation(),
            $this->invariant(),
            [
                new DiagnosticFieldSchema(
                    'component',
                    'Component',
                    DiagnosticValueKind::Text,
                    DiagnosticDisclosure::Safe,
                ),
            ],
        );
    }

    public function context(string $component): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), ['component' => $component]);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidDerivedValue => 'Application Graph intelligence view is invalid',
            self::InvalidVerificationValue => 'Application Graph intelligence verification is invalid',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidDerivedValue => 'Supply the exact bounded aggregate view vocabulary and fixed semantics',
            self::InvalidVerificationValue => 'Supply the exact ordered checks, verdict, and derived failure tokens',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidDerivedValue => 'Create Application Graph intelligence view',
            self::InvalidVerificationValue => 'Create Application Graph intelligence verification',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidDerivedValue => 'Derived view must retain only complete bounded Safe aggregate evidence',
            self::InvalidVerificationValue => 'Verification must retain exact ordered bounded comparison evidence',
        };
    }
}
