<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;

/** Carries the SHA-256 identity of exact canonical adapter-artifact bytes. */
#[Api(ApiStatus::Experimental)]
final readonly class AdapterBuildIdentity
{
    public function __construct(private string $value)
    {
        AdapterContractGuard::sha256($value, 'build_identity_grammar');
    }
    public static function fromArtifactBytes(string $bytes): self
    {
        return new self(hash('sha256', $bytes));
    }
    public function value(): string
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
