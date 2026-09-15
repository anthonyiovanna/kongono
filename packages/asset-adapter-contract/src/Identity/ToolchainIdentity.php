<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterContract\Identity;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\AssetAdapterContract\Internal\AdapterContractGuard;

/** Carries a digest of canonical semantic toolchain evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ToolchainIdentity
{
    public function __construct(private string $value)
    {
        AdapterContractGuard::sha256($value, 'toolchain_identity_grammar');
    }
    public static function fromEvidence(string $canonicalEvidence): self
    {
        return new self(hash('sha256', $canonicalEvidence));
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
