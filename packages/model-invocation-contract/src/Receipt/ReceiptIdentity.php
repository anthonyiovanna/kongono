<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Receipt;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Identifies one bounded receipt claim without making it replayable. */
#[Api(ApiStatus::Experimental)]
final readonly class ReceiptIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'receipt_identity_grammar');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
