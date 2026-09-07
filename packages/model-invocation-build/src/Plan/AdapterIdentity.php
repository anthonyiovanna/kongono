<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationBuild\Plan;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ModelInvocationBuild\Exception\InvalidInvocationPlanSourceException;

/** Carries one application-owned adapter selector label without acquisition authority. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        if (strlen($value) < 1 || strlen($value) > 128 || 1 !== preg_match('/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/D', $value)) {
            throw InvalidInvocationPlanSourceException::invalidAdapterIdentity('identity_grammar');
        }
        $this->value = $value;
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
