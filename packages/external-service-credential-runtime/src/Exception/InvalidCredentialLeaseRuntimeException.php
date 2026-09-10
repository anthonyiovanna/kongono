<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceCredentialRuntime\Diagnostic\CredentialLeaseRuntimeDiagnostic;

/** Reports a package invariant failure through one closed nondisclosing token. */
#[Api(ApiStatus::Experimental)]
final class InvalidCredentialLeaseRuntimeException extends ExternalServiceCredentialRuntimeException
{
    private const array VIOLATIONS = [
        'source_identity_relation', 'failure_reason_relation', 'result_count_range', 'terminal_result_relation',
        'reentrancy', 'clock_negative', 'clock_regression', 'lease_reference_mismatch',
        'lease_purpose_mismatch', 'lease_state_invalid', 'lifetime_exceeded', 'cleanup_failures_relation',
        'exception_result_relation', 'diagnostic_relation',
    ];

    private string $violation;

    public static function invalid(string $violation): self
    {
        $safe = in_array($violation, self::VIOLATIONS, true) ? $violation : 'unsupported_violation';
        $exception = new self(CredentialLeaseRuntimeDiagnostic::InvalidInput, ['violation' => $safe]);
        $exception->violation = $safe;

        return $exception;
    }

    public function violation(): string
    {
        return $this->violation;
    }
}
