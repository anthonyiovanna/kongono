<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceContract\Credential;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExternalServiceContract\Internal\ContractGuard;

/** Stores a secret-free opaque reference for one explicit credential source. */
#[Api(ApiStatus::Experimental)]
final readonly class CredentialReference
{
    private string $reference;

    public function __construct(private CredentialSourceIdentity $source, string $reference)
    {
        $this->reference = ContractGuard::credentialReference($reference);
    }

    public function source(): CredentialSourceIdentity
    {
        return $this->source;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function equals(self $other): bool
    {
        return $this->source->equals($other->source) && $this->reference === $other->reference;
    }
}
