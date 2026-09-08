<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use Kongono\NetworkEgressRuntime\Exception\InvalidEgressRuntimeException;

/** Owns the closed nondisclosing runtime diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum NetworkEgressRuntimeDiagnostic: string
{
    private const array TEXT_VALUES = [
        'stage' => ['authority', 'cancellation', 'clock', 'lifecycle', 'open', 'pull', 'resolution', 'time', 'transport'],
        'violation' => [
            'address_grammar', 'address_scope', 'authority_probe', 'budget_exceeded', 'budget_reference',
            'buffer_budget', 'cancellation_probe', 'capability_instability', 'cleanup_failures_empty',
            'clock_negative', 'clock_probe', 'clock_regression', 'connect_timeout', 'emission_bounds',
            'emission_limit', 'emission_shape', 'evidence_budget', 'first_byte_timeout', 'header_budget',
            'header_posture', 'literal_address', 'operation_incomplete', 'origin_denied', 'policy_reference',
            'protocol_capability', 'protocol_change', 'proxy_unsupported', 'request_budget',
            'resolution_candidates', 'resolution_duplicate', 'resolution_entry', 'resolution_list',
            'resolution_result_shape', 'resolver_failed', 'response_budget', 'response_shape', 'result_bounds',
            'result_relation', 'reentrancy', 'selected_membership', 'terminal_exhaustion', 'total_timeout',
            'transfer_regression', 'transport_capabilities', 'transport_capability', 'transport_open',
            'transport_pull', 'unsupported_violation',
        ],
        'outcome' => [
            'cancelled_after_possible_effect', 'cancelled_before_effect', 'failed_before_effect',
            'rejected_before_attempt', 'succeeded', 'uncertain_effect',
        ],
        'address_category' => [
            'documentation', 'link_local', 'loopback', 'multicast', 'private', 'public', 'reserved', 'unspecified',
        ],
    ];

    case InvalidInput = 'KGN-EGRESS-RUNTIME-1520';
    case PolicyRejected = 'KGN-EGRESS-RUNTIME-1521';
    case AuthorityRejected = 'KGN-EGRESS-RUNTIME-1522';
    case ResolutionFailed = 'KGN-EGRESS-RUNTIME-1523';
    case AddressDenied = 'KGN-EGRESS-RUNTIME-1524';
    case TransportFailed = 'KGN-EGRESS-RUNTIME-1525';
    case ProtocolViolated = 'KGN-EGRESS-RUNTIME-1526';
    case BudgetExceeded = 'KGN-EGRESS-RUNTIME-1527';
    case CancellationObserved = 'KGN-EGRESS-RUNTIME-1528';
    case CleanupFailed = 'KGN-EGRESS-RUNTIME-1529';

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            $this->text('stage', 'Stage'),
            $this->text('violation', 'Violation'),
            $this->text('outcome', 'Outcome'),
            $this->text('address_category', 'Address category'),
            $this->integer('count', 'Count'),
            $this->integer('position', 'Position'),
            $this->integer('limit', 'Limit'),
            $this->integer('observation', 'Observation'),
        ]);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        foreach ($values as $key => $value) {
            if (!isset(self::TEXT_VALUES[$key]) && !in_array($key, ['count', 'position', 'limit', 'observation'], true)) {
                throw InvalidEgressRuntimeException::invalid('unsupported_violation');
            }
            if (isset(self::TEXT_VALUES[$key]) && (!is_string($value) || !in_array($value, self::TEXT_VALUES[$key], true))) {
                throw InvalidEgressRuntimeException::invalid('unsupported_violation');
            }
            if (in_array($key, ['count', 'position', 'limit', 'observation'], true) && (!is_int($value) || $value < 0 || $value > 16_777_216)) {
                throw InvalidEgressRuntimeException::invalid('unsupported_violation');
            }
        }

        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInput => 'Network egress runtime input is invalid',
            self::PolicyRejected => 'Network egress policy rejected the operation',
            self::AuthorityRejected => 'Network egress authority is not current',
            self::ResolutionFailed => 'Network egress resolution failed',
            self::AddressDenied => 'Resolved network address was denied',
            self::TransportFailed => 'Network egress transport failed',
            self::ProtocolViolated => 'Network egress transport protocol was violated',
            self::BudgetExceeded => 'Network egress budget or timeout was exceeded',
            self::CancellationObserved => 'Network egress cancellation was observed',
            self::CleanupFailed => 'Network egress session cleanup failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Supply exact typed runtime values satisfying the documented invariant',
            self::PolicyRejected => 'Rebuild exact-current policy evidence and correct the closed policy mismatch',
            self::AuthorityRejected => 'Obtain current application-owned authority before attempting egress',
            self::ResolutionFailed => 'Correct the caller-owned resolver while preserving bounded failure evidence',
            self::AddressDenied => 'Return only addresses admitted by the explicit policy scope',
            self::TransportFailed => 'Inspect the caller-owned transport while preserving the original failure object',
            self::ProtocolViolated => 'Correct the pull sequence, transfer evidence, or negotiated protocol',
            self::BudgetExceeded => 'Keep transfer and time evidence within the explicit finite budget',
            self::CancellationObserved => 'Retire the operation without replay and inspect retained transfer evidence',
            self::CleanupFailed => 'Treat possible effects as uncertain and inspect each preserved close failure',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Validate network egress runtime input',
            self::PolicyRejected => 'Bind exact-current egress policy evidence',
            self::AuthorityRejected => 'Probe current egress authority',
            self::ResolutionFailed => 'Resolve one canonical origin',
            self::AddressDenied => 'Admit resolved addresses',
            self::TransportFailed => 'Operate the explicit egress transport',
            self::ProtocolViolated => 'Validate pull-based transfer evidence',
            self::BudgetExceeded => 'Enforce egress budgets and timeouts',
            self::CancellationObserved => 'Observe explicit cancellation',
            self::CleanupFailed => 'Retire the egress transport session',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidInput => 'Runtime values remain exact typed bounded and caller owned',
            self::PolicyRejected => 'Only exact-current coherent policy evidence proceeds',
            self::AuthorityRejected => 'Authority is current before resolution and immediately before open',
            self::ResolutionFailed => 'One bounded resolver result determines the selected address',
            self::AddressDenied => 'Every resolved candidate satisfies the explicit address posture',
            self::TransportFailed => 'One directly supplied transport performs at most one attempt',
            self::ProtocolViolated => 'Transfer evidence is cumulative monotonic bounded and terminal',
            self::BudgetExceeded => 'No observed count byte value or duration exceeds its ceiling',
            self::CancellationObserved => 'Cancellation preserves prior evidence and never authorizes replay',
            self::CleanupFailed => 'Every opened session receives exactly one close attempt',
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
