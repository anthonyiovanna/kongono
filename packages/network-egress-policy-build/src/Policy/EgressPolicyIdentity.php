<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressPolicyBuild\Policy;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\NetworkEgressPolicyBuild\Exception\InvalidEgressPolicySourceException;

/** Identifies one application-owned inert network-egress policy source. */
#[Api(ApiStatus::Experimental)]
final readonly class EgressPolicyIdentity
{
    private string $value;

    public function __construct(string $value)
    {
        $length = strlen($value);
        if ($length < 1 || $length > 128 || 1 !== preg_match('/\A[a-z0-9](?:[a-z0-9._-]{0,126}[a-z0-9])?\z/D', $value)) {
            throw InvalidEgressPolicySourceException::invalidIdentity();
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
