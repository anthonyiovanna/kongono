<?php

declare(strict_types=1);

namespace Kongono\ExtensionComposition\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionComposition\Exception\InvalidExtensionGrantPlanException;

/** Holds the exact lowercase SHA-256 identity of one canonical extension grant plan. */
#[Api(ApiStatus::Experimental)]
final readonly class GrantPlanIdentity
{
    public function __construct(private string $sha256)
    {
        if (1 !== preg_match('/\A[a-f0-9]{64}\z/D', $sha256)) {
            throw InvalidExtensionGrantPlanException::invalid('grant_plan_identity_grammar');
        }
    }

    public function sha256(): string
    {
        return $this->sha256;
    }
    public function value(): string
    {
        return $this->sha256;
    }
    public function equals(self $other): bool
    {
        return hash_equals($this->sha256, $other->sha256);
    }
}
