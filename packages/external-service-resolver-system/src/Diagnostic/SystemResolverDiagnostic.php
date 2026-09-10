<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceResolverSystem\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;
use LogicException;

/** Owns the closed nondisclosing system-resolver diagnostic vocabulary. */
#[Api(ApiStatus::Internal)]
enum SystemResolverDiagnostic: string
{
    case InvalidResolverProfile = 'KGN-RESOLVER-1618';
    case RequiredResolverCapabilityUnavailable = 'KGN-RESOLVER-1619';
    case SystemLookupFailed = 'KGN-RESOLVER-1620';
    case SystemResultRejected = 'KGN-RESOLVER-1621';
    case CandidateLimitExceeded = 'KGN-RESOLVER-1622';
    case NoAddressReturned = 'KGN-RESOLVER-1623';

    private const array TEXT_VALUES = [
        'stage' => ['environment', 'lookup', 'normalization', 'ordering', 'profile'],
        'violation' => [
            'candidate_limit', 'capability_unavailable', 'entry_family', 'entry_shape', 'entry_socket_type',
            'entry_protocol', 'entry_port', 'entry_address', 'lookup_failed', 'no_address',
            'unsupported_violation',
        ],
        'family_preference' => ['ipv4_first', 'ipv6_first'],
    ];

    public function contextSchema(): DiagnosticContextSchema
    {
        return new DiagnosticContextSchema($this->operation(), $this->invariant(), [
            $this->text('stage', 'Stage'),
            $this->text('violation', 'Violation'),
            $this->text('family_preference', 'Family preference'),
            $this->integer('count', 'Count'),
            $this->integer('limit', 'Limit'),
            $this->integer('position', 'Ordinal position'),
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
                    : 'unsupported_violation';
                continue;
            }
            if (!in_array($key, ['count', 'limit', 'position'], true) || !is_int($value)) {
                throw new LogicException('System resolver diagnostic context is unsupported.');
            }
            $safe[$key] = max(0, min(64, $value));
        }

        return new DiagnosticContext($this->contextSchema(), $safe);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidResolverProfile => 'System resolver profile is invalid',
            self::RequiredResolverCapabilityUnavailable => 'Required resolver capability is unavailable',
            self::SystemLookupFailed => 'System resolver lookup failed',
            self::SystemResultRejected => 'System resolver result was rejected',
            self::CandidateLimitExceeded => 'System resolver candidate limit was exceeded',
            self::NoAddressReturned => 'System resolver returned no address',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidResolverProfile => 'Supply a family preference and candidate limit from 1 through 64',
            self::RequiredResolverCapabilityUnavailable => 'Use a supported ext-sockets build without changing the resolver contract',
            self::SystemLookupFailed => 'Inspect local resolver availability without retrying or exposing native detail',
            self::SystemResultRejected => 'Reject the complete malformed result without using partial candidates',
            self::CandidateLimitExceeded => 'Use a candidate bound appropriate for the explicit application profile',
            self::NoAddressReturned => 'Treat the empty detached result as a terminal resolution failure',
        };
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidResolverProfile => 'Validate system resolver profile',
            self::RequiredResolverCapabilityUnavailable => 'Validate native resolver capabilities',
            self::SystemLookupFailed => 'Perform one operating-system lookup',
            self::SystemResultRejected => 'Normalize one complete lookup result',
            self::CandidateLimitExceeded => 'Enforce the native candidate bound',
            self::NoAddressReturned => 'Require at least one accepted address',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidResolverProfile => 'Resolver ordering and candidate bounds are explicit and immutable',
            self::RequiredResolverCapabilityUnavailable => 'Every locked native sockets capability is present before use',
            self::SystemLookupFailed => 'Foreign native failure text never enters returned evidence',
            self::SystemResultRejected => 'Every candidate is validated and malformed results fail as a whole',
            self::CandidateLimitExceeded => 'Native results are bounded before any entry is explained',
            self::NoAddressReturned => 'Successful evidence always contains one selected member',
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
