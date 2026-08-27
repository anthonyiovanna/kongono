<?php

declare(strict_types=1);

namespace Kongono\SecurityEnforcement\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns fixed non-disclosing protected-capability enforcement failures. */
#[Api(ApiStatus::Internal)]
enum SecurityEnforcementDiagnostic: string
{
    case ContextMismatch = 'KGN-SECURITY-ENFORCEMENT-1001';
    case ActionMismatch = 'KGN-SECURITY-ENFORCEMENT-1002';
    case ResourceMismatch = 'KGN-SECURITY-ENFORCEMENT-1003';
    case DecisionRejected = 'KGN-SECURITY-ENFORCEMENT-1004';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            'Enforce one protected capability',
            'Only one exact Permit over identical bound inputs may invoke the capability',
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('invariant', 'Invariant', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'package' => 'security_enforcement',
            'failure_category' => $this->category(),
            'invariant' => $this->invariantToken(),
            'remediation' => $this->remediationToken(),
        ]);
    }

    public function title(): string
    {
        return match ($this) {
            self::ContextMismatch => 'Security Context mismatch',
            self::ActionMismatch => 'Protected Action mismatch',
            self::ResourceMismatch => 'Protected Resource mismatch',
            self::DecisionRejected => 'Protected capability authorization rejected',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::ContextMismatch => 'Use the identical operation Security Context in the authorization request.',
            self::ActionMismatch => 'Bind the request and capability to the same exact Action.',
            self::ResourceMismatch => 'Bind the request and capability to the same exact Resource Reference.',
            self::DecisionRejected => 'Obtain an exact Permit for the bound request before invoking the capability.',
        };
    }

    private function category(): string
    {
        return match ($this) {
            self::ContextMismatch => 'context_mismatch',
            self::ActionMismatch => 'action_mismatch',
            self::ResourceMismatch => 'resource_mismatch',
            self::DecisionRejected => 'decision_rejected',
        };
    }

    private function invariantToken(): string
    {
        return match ($this) {
            self::ContextMismatch => 'identical_operation_context',
            self::ActionMismatch => 'equal_action',
            self::ResourceMismatch => 'equal_resource',
            self::DecisionRejected => 'exact_permit_only',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::ContextMismatch => 'bind_identical_context',
            self::ActionMismatch => 'bind_equal_action',
            self::ResourceMismatch => 'bind_equal_resource',
            self::DecisionRejected => 'stop_without_permit',
        };
    }
}
