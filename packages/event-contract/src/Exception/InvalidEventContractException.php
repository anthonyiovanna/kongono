<?php

declare(strict_types=1);

namespace Kongono\EventContract\Exception;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\EventContract\Diagnostic\EventContractDiagnostic;

/** Reports one rejected event-type or registration invariant. */
#[Api(ApiStatus::Experimental)]
final class InvalidEventContractException extends EventContractException
{
    public static function invalidEventType(string $violation): self
    {
        return new self(EventContractDiagnostic::InvalidEventType, $violation);
    }

    public static function invalidRegistration(string $violation): self
    {
        return new self(EventContractDiagnostic::InvalidRegistration, $violation);
    }

    public static function duplicateBinding(): self
    {
        return new self(EventContractDiagnostic::DuplicateBinding, 'duplicate_binding');
    }

    public static function contractBoundExceeded(string $violation): self
    {
        return new self(EventContractDiagnostic::ContractBoundExceeded, $violation);
    }
}
