<?php

declare(strict_types=1);

namespace Kongono\Authorization\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the bounded non-disclosing authorization failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum AuthorizationDiagnostic: string
{
    case Validation = 'KGN-AUTHORIZATION-1001';
    case Registration = 'KGN-AUTHORIZATION-1002';
    case PolicyEvidence = 'KGN-AUTHORIZATION-1003';
    case Currentness = 'KGN-AUTHORIZATION-1004';
    case Evaluation = 'KGN-AUTHORIZATION-1005';
    case Receipt = 'KGN-AUTHORIZATION-1006';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(
            new DiagnosticContextSchema(
                'Evaluate one explicit authorization request',
                'Authorization failures must remain bounded and non-disclosing',
                [
                    new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                    new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                ],
            ),
            [
                'package' => 'authorization',
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
            self::PolicyEvidence => 'policy_evidence',
            self::Currentness => 'currentness',
            self::Evaluation => 'evaluation',
            self::Receipt => 'receipt',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Validation => 'Invalid authorization value',
            self::Registration => 'Invalid authorization evaluator registration',
            self::PolicyEvidence => 'Invalid authorization policy evidence',
            self::Currentness => 'Authorization policy evidence is not current',
            self::Evaluation => 'Authorization evaluation failed',
            self::Receipt => 'Invalid authorization decision receipt',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::Validation => 'Provide an exact authorization value within the documented bounds.',
            self::Registration => 'Register one through thirty-two evaluators with unique exact policy references.',
            self::PolicyEvidence => 'Provide opaque policy evidence exposing only its exact policy reference.',
            self::Currentness => 'Provide independently expected and evidence policy references that match exactly.',
            self::Evaluation => 'Inspect the retained evaluator failure without treating it as permission.',
            self::Receipt => 'Construct authorization receipts only through the package-owned factories.',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::Validation => 'correct_value',
            self::Registration => 'correct_registration',
            self::PolicyEvidence => 'correct_policy_evidence',
            self::Currentness => 'correct_currentness',
            self::Evaluation => 'inspect_evaluation',
            self::Receipt => 'correct_receipt',
        };
    }
}
