<?php

declare(strict_types=1);

namespace Kongono\ExtensionContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ExtensionContract\Internal\ContractGuard;

/** Preserves only exact bounded non-secret source evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class ExtensionProvenance
{
    private string $sourceSha256;

    public function __construct(
        private ExtensionSourceIdentity $sourceIdentity,
        string $sourceSha256,
    ) {
        $this->sourceSha256 = ContractGuard::provenanceDigest($sourceSha256);
    }

    public function sourceIdentity(): ExtensionSourceIdentity
    {
        return $this->sourceIdentity;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
}
