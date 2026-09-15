<?php

declare(strict_types=1);

namespace Kongono\AssetContract\Delivery;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetContract\Internal\ContractGuard;

/** Carries structurally validated integrity evidence, not browser SRI text. */
#[Api(ApiStatus::Experimental)]
final readonly class AssetIntegrity
{
    private string $digest;

    public function __construct(
        private IntegrityAlgorithm $algorithm,
        string $digest,
    ) {
        $this->digest = ContractGuard::integrityDigest($algorithm, $digest);
    }

    public function algorithm(): IntegrityAlgorithm
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
