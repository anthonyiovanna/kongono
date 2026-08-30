<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphContract\Provenance;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\ApplicationGraphContract\Exception\InvalidGraphContractException;
use Kongono\ApplicationGraphContract\Identity\ProducerIdentity;
use Kongono\ApplicationGraphContract\Identity\SourceIdentity;
use Kongono\ApplicationGraphContract\Internal\IdentityGuard;

/** Preserves only bounded non-secret producer and exact-source evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class GraphProvenance
{
    public function __construct(
        private ProducerIdentity $producerIdentity,
        private SourceIdentity $sourceIdentity,
        private ProducerContract $producerContract,
        private DerivationVersion $derivationVersion,
        private string $sourceSha256,
    ) {
        if (!IdentityGuard::isDigest($sourceSha256)) {
            throw InvalidGraphContractException::provenance('source_digest_grammar');
        }
    }

    public function producerIdentity(): ProducerIdentity
    {
        return $this->producerIdentity;
    }

    public function sourceIdentity(): SourceIdentity
    {
        return $this->sourceIdentity;
    }

    public function producerContract(): ProducerContract
    {
        return $this->producerContract;
    }

    public function derivationVersion(): DerivationVersion
    {
        return $this->derivationVersion;
    }

    public function sourceSha256(): string
    {
        return $this->sourceSha256;
    }
}
