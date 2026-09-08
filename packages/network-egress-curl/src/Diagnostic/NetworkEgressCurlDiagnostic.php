<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressCurl\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use LogicException;

/** Owns the closed nondisclosing cURL transport diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressCurlDiagnostic: string
{
    private const array TEXT_VALUES = [
        'stage' => ['cleanup', 'environment', 'lifecycle', 'profile', 'request', 'response', 'transfer'],
        'violation' => [
            'body_cursor', 'body_read', 'buffer_budget', 'ca_bundle_bounds', 'ca_bundle_shape',
            'capability_unavailable', 'cleanup_failed', 'content_length', 'header_budget', 'header_grammar',
            'host_header', 'method', 'operation_origin', 'plain_http_disabled', 'protocol', 'reentrancy',
            'request_budget', 'request_target', 'response_budget', 'response_header', 'response_shape',
            'scheme', 'session_closed', 'stream_state', 'transfer_failed', 'unsupported_violation',
        ],
        'failure_kind' => [
            'budget', 'cancellation', 'cleanup', 'connection', 'policy_denied', 'protocol', 'redirect',
            'resolution', 'response', 'timeout', 'tls', 'uncertain_effect', 'validation',
        ],
    ];

    case InvalidTransportProfile = 'KGN-EGRESS-CURL-1530';
    case CurlCapabilityUnavailable = 'KGN-EGRESS-CURL-1531';
    case RequestRejected = 'KGN-EGRESS-CURL-1532';
    case CurlTransferFailed = 'KGN-EGRESS-CURL-1533';
    case ResponseRejected = 'KGN-EGRESS-CURL-1534';
    case SessionCleanupFailed = 'KGN-EGRESS-CURL-1535';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            $this->text('stage', 'Stage'),
            $this->text('violation', 'Violation'),
            $this->text('failure_kind', 'Failure kind'),
            $this->integer('curl_code', 'cURL code'),
            $this->integer('count', 'Count'),
            $this->integer('limit', 'Limit'),
            $this->integer('observation', 'Observation'),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        $safe = [];
        foreach ($values as $key => $value) {
            if (isset(self::TEXT_VALUES[$key])) {
                $safe[$key] = is_string($value) && in_array($value, self::TEXT_VALUES[$key], true)
                    ? $value
                    : ('failure_kind' === $key ? 'validation' : 'unsupported_violation');
                continue;
            }
            if (!in_array($key, ['curl_code', 'count', 'limit', 'observation'], true) || !is_int($value)) {
                throw new LogicException('Network egress cURL diagnostic context is unsupported.');
            }
            $maximum = 'curl_code' === $key ? 999 : 1_073_741_824;
            $safe[$key] = max(0, min($maximum, $value));
        }

        return new DiagnosticContext($this->contextSchema(), $safe);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidTransportProfile => 'cURL egress transport profile is invalid',
            self::CurlCapabilityUnavailable => 'Required cURL capability is unavailable',
            self::RequestRejected => 'cURL egress request was rejected before execution',
            self::CurlTransferFailed => 'cURL egress transfer failed',
            self::ResponseRejected => 'cURL response evidence was rejected',
            self::SessionCleanupFailed => 'cURL egress session cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidTransportProfile => 'Supply the explicit bounded TLS trust and secure transport profile',
            self::CurlCapabilityUnavailable => 'Use a supported ext-curl build without changing the transport contract',
            self::RequestRejected => 'Correct the request while preserving its canonical origin and finite budgets',
            self::CurlTransferFailed => 'Inspect the bounded numeric failure evidence without replaying the request',
            self::ResponseRejected => 'Correct the transport response mapping without exposing rejected bytes',
            self::SessionCleanupFailed => 'Retire the operation and preserve uncertainty about possible effects',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidTransportProfile => 'Validate cURL transport profile',
            self::CurlCapabilityUnavailable => 'Validate native cURL capabilities',
            self::RequestRejected => 'Admit one bounded HTTP request',
            self::CurlTransferFailed => 'Execute one exact-address cURL transfer',
            self::ResponseRejected => 'Map one bounded HTTP response',
            self::SessionCleanupFailed => 'Close one cURL transport session',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidTransportProfile => 'Transport trust and plain-HTTP posture are explicit and immutable',
            self::CurlCapabilityUnavailable => 'Every locked native cURL capability is present before use',
            self::RequestRejected => 'Request mapping is canonical bounded and free of transport-owned headers',
            self::CurlTransferFailed => 'One numeric native result maps to bounded neutral evidence without replay',
            self::ResponseRejected => 'Only one complete bounded HTTP 1.1 response becomes caller-visible',
            self::SessionCleanupFailed => 'Every fresh native handle is retired exactly once',
        };
    }

    private function text(string $key, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($key, $label, DiagnosticValueKind::Text, DiagnosticDisclosure::Safe, false);
    }

    private function integer(string $key, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($key, $label, DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe, false);
    }
}
