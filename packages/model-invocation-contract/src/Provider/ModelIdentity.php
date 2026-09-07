<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationContract\Provider;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationContract\Internal\ContractGuard;

/** Carries a model label without proving availability or compatibility. */
#[Api(ApiStatus::Experimental)]
final readonly class ModelIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::identity($value, 'model_identity_grammar');
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
