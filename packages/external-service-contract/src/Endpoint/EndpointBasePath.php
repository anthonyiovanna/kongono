<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Endpoint;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Stores an optional canonical absolute endpoint path without URI resolution. */
#[Api(ApiStatus::Experimental)]
final readonly class EndpointBasePath
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = ContractGuard::endpointBasePath($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return '' === $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
