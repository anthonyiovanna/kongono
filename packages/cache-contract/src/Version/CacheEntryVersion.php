<?php

declare(strict_types=1);

namespace Kongono\CacheContract\Version;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\CacheContract\Internal\ContractGuard;

/** Carries opaque lowercase mutation-version evidence without coordination claims. */
#[Api(ApiStatus::Experimental)]
final readonly class CacheEntryVersion
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = ContractGuard::entryVersion($token);
    }

    public function token(): string
    {
        return $this->token;
    }

    public function equals(self $other): bool
    {
        return $this->token === $other->token;
    }
}
