<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressContract\Failure;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressContract\Internal\ContractGuard;

/** Stores a closed failure kind and allowlisted reason token only. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressFailure
{
    private string $reason;

    public function __construct(
        private EgressFailureKind $kind,
        string $reason,
    ) {
        $this->reason = ContractGuard::reason($reason);
    }

    public function kind(): EgressFailureKind
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
