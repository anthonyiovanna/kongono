<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Digest\StorageContentDigest;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Outcome\StorageMutationOutcome;
use Kongono\StorageContract\Version\StorageObjectVersion;

/** Preserves mutation truth without overclaiming uncertain or failed effects. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageMutationResult
{
    public function __construct(
        private StorageMutationOutcome $outcome,
        private ?StorageObjectVersion $resultingVersion = null,
        private ?StorageContentDigest $resultingDigest = null,
    ) {
        if (StorageMutationOutcome::Completed === $outcome) {
            if (null === $resultingVersion) {
                throw InvalidStorageContractException::invalidResult('mutation_result_evidence_relation', $outcome->value);
            }
        } elseif (null !== $resultingVersion || null !== $resultingDigest) {
            throw InvalidStorageContractException::invalidResult('mutation_result_evidence_relation', $outcome->value);
        }
    }

    public function outcome(): StorageMutationOutcome
    {
        return $this->outcome;
    }

    public function resultingVersion(): ?StorageObjectVersion
    {
        return $this->resultingVersion;
    }

    public function resultingDigest(): ?StorageContentDigest
    {
        return $this->resultingDigest;
    }
}
