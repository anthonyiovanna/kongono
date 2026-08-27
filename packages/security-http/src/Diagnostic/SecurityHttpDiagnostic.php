<?php

declare(strict_types=1);

namespace Kongono\SecurityHttp\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the fixed non-disclosing selected browser-profile failure vocabulary. */
#[Api(ApiStatus::Internal)]
enum SecurityHttpDiagnostic: string
{
    case InvalidWireValue = 'KGN-SECURITY-HTTP-1001';
    case InvalidTrustedOrigin = 'KGN-SECURITY-HTTP-1002';
    case InvalidCookie = 'KGN-SECURITY-HTTP-1003';
    case TransportRejected = 'KGN-SECURITY-HTTP-1004';
    case CsrfRejected = 'KGN-SECURITY-HTTP-1005';
    case AuthenticationRejected = 'KGN-SECURITY-HTTP-1006';

    public function context(): DiagnosticContext
    {
        return new DiagnosticContext(new DiagnosticContextSchema(
            'Enter the selected browser-session security profile',
            'Transport evidence must remain bounded, unambiguous, and non-authorizing until exact verification',
            [
                new DiagnosticFieldSchema('package', 'Package', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('failure_category', 'Failure category', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('invariant', 'Invariant', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
                new DiagnosticFieldSchema('remediation', 'Remediation', DiagnosticValueKind::Text, DiagnosticDisclosure::Safe),
            ],
        ), [
            'package' => 'security_http',
            'failure_category' => $this->category(),
            'invariant' => $this->invariantToken(),
            'remediation' => $this->remediationToken(),
        ]);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidWireValue => 'Invalid browser-session wire value',
            self::InvalidTrustedOrigin => 'Invalid trusted HTTPS origin',
            self::InvalidCookie => 'Invalid browser-session cookie input',
            self::TransportRejected => 'Browser-session transport rejected',
            self::CsrfRejected => 'Browser-session CSRF input rejected',
            self::AuthenticationRejected => 'Browser-session authentication rejected',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidWireValue => 'Supply the exact selected 43-character opaque wire syntax and evidence shape.',
            self::InvalidTrustedOrigin => 'Configure one exact HTTPS origin containing only scheme, host, and optional valid port.',
            self::InvalidCookie => 'Supply at most one bounded unambiguous Cookie header using the selected reserved name.',
            self::TransportRejected => 'Use the exact trusted HTTPS origin and a bounded valid request method.',
            self::CsrfRejected => 'Supply one exact trusted Origin and one selected-profile CSRF proof for state-changing requests.',
            self::AuthenticationRejected => 'Reject the presented session and begin no operation unless verification is exactly Authenticated.',
        };
    }

    private function category(): string
    {
        return match ($this) {
            self::InvalidWireValue => 'wire_value',
            self::InvalidTrustedOrigin => 'trusted_origin',
            self::InvalidCookie => 'cookie',
            self::TransportRejected => 'transport',
            self::CsrfRejected => 'csrf',
            self::AuthenticationRejected => 'authentication',
        };
    }

    private function invariantToken(): string
    {
        return match ($this) {
            self::InvalidWireValue => 'exact_opaque_wire_syntax',
            self::InvalidTrustedOrigin => 'one_exact_https_origin',
            self::InvalidCookie => 'bounded_unambiguous_cookie',
            self::TransportRejected => 'trusted_https_request_origin',
            self::CsrfRejected => 'state_change_origin_and_proof',
            self::AuthenticationRejected => 'exact_authenticated_only',
        };
    }

    private function remediationToken(): string
    {
        return match ($this) {
            self::InvalidWireValue => 'correct_selected_wire_value',
            self::InvalidTrustedOrigin => 'configure_exact_https_origin',
            self::InvalidCookie => 'reject_malformed_cookie',
            self::TransportRejected => 'reject_untrusted_transport',
            self::CsrfRejected => 'require_exact_csrf_boundary',
            self::AuthenticationRejected => 'stop_without_authenticated_result',
        };
    }
}
