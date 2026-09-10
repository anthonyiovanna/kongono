<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceCredentialRuntime\Diagnostic;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticContextSchema;
use Kongono\Diagnostics\Context\DiagnosticDisclosure;
use Kongono\Diagnostics\Context\DiagnosticFieldSchema;
use Kongono\Diagnostics\Context\DiagnosticValueKind;

/** Owns the closed Safe diagnostic vocabulary for credential lease coordination. */
#[Api(ApiStatus::Internal)]
enum CredentialLeaseRuntimeDiagnostic: string
{
    case InvalidInput = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1601';
    case ClockFailed = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1602';
    case SourceFailed = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1603';
    case LeaseProtocolViolated = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1604';
    case LifetimeExceeded = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1605';
    case ConsumerFailed = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1606';
    case RetirementFailed = 'KGN-EXTERNAL-SERVICE-CREDENTIAL-RUNTIME-1607';

    public function contextSchema(): DiagnosticContextSchema
    {
        $fields = match ($this) {
            self::InvalidInput => [$this->text('violation', 'Violation')],
            self::LifetimeExceeded => [...$this->operationFields(), $this->integer('limit', 'Limit'), $this->integer('observed_count', 'Observed count')],
            default => $this->operationFields(),
        };

        return new DiagnosticContextSchema($this->operation(), $this->invariant(), $fields);
    }

    /** @param array<string, string|int|bool> $values */
    public function context(array $values): DiagnosticContext
    {
        return new DiagnosticContext($this->contextSchema(), $values);
    }

    public function title(): string
    {
        return match ($this) {
            self::InvalidInput => 'Credential lease runtime input is invalid',
            self::ClockFailed => 'Credential lease runtime clock failed',
            self::SourceFailed => 'Credential lease source failed',
            self::LeaseProtocolViolated => 'Credential lease protocol was violated',
            self::LifetimeExceeded => 'Credential lease lifetime was exceeded',
            self::ConsumerFailed => 'Credential material consumer failed',
            self::RetirementFailed => 'Credential lease retirement failed',
        };
    }

    public function remediation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Supply exact typed runtime values satisfying the documented invariant',
            self::ClockFailed => 'Correct the caller-owned monotonic clock while preserving the original failure object',
            self::SourceFailed => 'Inspect the caller-selected credential source while preserving the original failure object',
            self::LeaseProtocolViolated => 'Return a lease whose reference purpose and lifecycle match the exact request',
            self::LifetimeExceeded => 'Keep the cooperative operation within the explicit maximum lease lifetime',
            self::ConsumerFailed => 'Inspect the caller-owned consumer and treat material use as uncertain',
            self::RetirementFailed => 'Treat cleanup as uncertain and inspect the preserved retirement failure',
        };
    }

    /** @return list<DiagnosticFieldSchema> */
    private function operationFields(): array
    {
        return [
            $this->text('stage', 'Stage'),
            $this->text('violation', 'Violation'),
            $this->text('outcome', 'Outcome'),
            $this->text('state', 'State'),
            $this->integer('acquisition_attempt_count', 'Acquisition attempt count'),
            $this->integer('material_access_attempt_count', 'Material access attempt count'),
            $this->integer('retirement_attempt_count', 'Retirement attempt count'),
            $this->boolean('cleanup_complete', 'Cleanup complete'),
        ];
    }

    private function operation(): string
    {
        return match ($this) {
            self::InvalidInput => 'Validate credential lease runtime input',
            self::ClockFailed => 'Observe credential lease monotonic time',
            self::SourceFailed => 'Acquire one credential lease',
            self::LeaseProtocolViolated => 'Validate credential lease relations and lifecycle',
            self::LifetimeExceeded => 'Enforce the explicit credential lease lifetime',
            self::ConsumerFailed => 'Delegate borrowed credential material to the explicit consumer',
            self::RetirementFailed => 'Retire one acquired credential lease',
        };
    }

    private function invariant(): string
    {
        return match ($this) {
            self::InvalidInput => 'Runtime values remain exact typed bounded and caller-owned',
            self::ClockFailed => 'Clock observations remain nonnegative monotonic and synchronous',
            self::SourceFailed => 'Exactly one caller-selected source receives the exact request',
            self::LeaseProtocolViolated => 'Lease reference purpose and states match the exact operation contract',
            self::LifetimeExceeded => 'Observed elapsed time never exceeds the inclusive explicit lifetime ceiling',
            self::ConsumerFailed => 'The runtime delegates material without receiving or retaining it',
            self::RetirementFailed => 'Every acquired lease receives exactly one retirement attempt',
        };
    }

    private function text(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Text, DiagnosticDisclosure::Safe);
    }

    private function integer(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Integer, DiagnosticDisclosure::Safe);
    }

    private function boolean(string $name, string $label): DiagnosticFieldSchema
    {
        return new DiagnosticFieldSchema($name, $label, DiagnosticValueKind::Boolean, DiagnosticDisclosure::Safe);
    }
}
