<?php

declare(strict_types=1);

namespace Kongono\Authentication\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the bounded non-disclosing authentication failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum AuthenticationDiagnostic: string
{
    case Validation = 'KGN-AUTHENTICATION-1001';
    case Registration = 'KGN-AUTHENTICATION-1002';
    case Evidence = 'KGN-AUTHENTICATION-1003';
    case Verification = 'KGN-AUTHENTICATION-1004';
    case Freshness = 'KGN-AUTHENTICATION-1005';
    case Strength = 'KGN-AUTHENTICATION-1006';
    case Cleanup = 'KGN-AUTHENTICATION-1007';
    case CombinedFailure = 'KGN-AUTHENTICATION-1008';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Execute explicit authentication orchestration',
                'Authentication failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'authentication',
                'failure_category' => $this->category(),
                'remediation' => $this->remediationToken(),
            ],
        );
    }

    public function category(): string
    {
        return match ($this) {
            self::Validation => 'validation',
            self::Registration => 'registration',
            self::Evidence => 'evidence',
            self::Verification => 'verification',
            self::Freshness => 'freshness',
            self::Strength => 'strength',
            self::Cleanup => 'cleanup',
            self::CombinedFailure => 'combined_failure',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Validation => 'Invalid authentication value',
            self::Registration => 'Invalid verifier registration',
            self::Evidence => 'Invalid authentication evidence',
            self::Verification => 'Invalid authentication verification',
            self::Freshness => 'Invalid authentication freshness',
            self::Strength => 'Invalid authentication strength',
            self::Cleanup => 'Authentication cleanup failed',
            self::CombinedFailure => 'Authentication operation and cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Validation => 'Provide an exact authentication value within the documented bounds.',
            self::Registration => 'Register one through thirty-two verifiers with unique exact mechanisms.',
            self::Evidence => 'Provide zero through eight ordered detached authentication evidence values.',
            self::Verification => 'Return one documented verification shape with consistent validated facts.',
            self::Freshness => 'Provide explicit ordered instants and an allowed maximum age.',
            self::Strength => 'Provide an authentication strength level from one through two hundred fifty-five.',
            self::Cleanup => 'Inspect the retained cleanup failure and retire adapter-owned attempt state safely.',
            self::CombinedFailure => 'Inspect the retained operation and cleanup failures without discarding either.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Validation => 'correct_value',
            self::Registration => 'correct_registration',
            self::Evidence => 'correct_evidence',
            self::Verification => 'correct_verification',
            self::Freshness => 'correct_freshness',
            self::Strength => 'correct_strength',
            self::Cleanup => 'inspect_cleanup',
            self::CombinedFailure => 'inspect_combined_failure',
        };
    }
}
