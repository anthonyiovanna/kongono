<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Digest;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Internal\ContractGuard;

/** Carries bounded content evidence without durability or authority claims. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageContentDigest
{
    private string $digest;

    public function __construct(
        private StorageDigestAlgorithm $algorithm,
        string $digest,
    ) {
        $this->digest = ContractGuard::contentDigest($algorithm, $digest);
    }

    public function algorithm(): StorageDigestAlgorithm
    {
        return $this->algorithm;
    }

    public function digest(): string
    {
        return $this->digest;
    }

    public function equals(self $other): bool
    {
        return $this->algorithm === $other->algorithm && $this->digest === $other->digest;
    }
}
