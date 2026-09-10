<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Carries one closed failure kind and Safe reason token only. */
#[Api(ApiStatus::Experimental)]
final readonly class ExternalServiceFailure
{
    private string $reason;

    public function __construct(private ExternalServiceFailureKind $kind, string $reason)
    {
        $this->reason = ContractGuard::reason($reason);
    }

    public function kind(): ExternalServiceFailureKind
    {
        return $this->kind;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function equals(self $other): bool
    {
        return $this->kind === $other->kind && $this->reason === $other->reason;
    }
}
