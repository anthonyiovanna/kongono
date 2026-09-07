<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Provider;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Carries a provider label without selecting or contacting a provider. */
#[Api(ApiStatus::Experimental)]
final readonly class ProviderIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'provider_identity_grammar');
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
