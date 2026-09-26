<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Outcome\StorageReadOutcome;

/** Preserves the exact relation between a read outcome and object evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageReadResult
{
    public function __construct(
        private StorageReadOutcome $outcome,
        private ?StorageObjectEvidence $evidence = null,
    ) {
        if ((StorageReadOutcome::Completed === $outcome) !== (null !== $evidence)) {
            throw InvalidStorageContractException::invalidResult('read_result_evidence_relation', $outcome->value);
        }
    }

    public function outcome(): StorageReadOutcome
    {
        return $this->outcome;
    }

    public function evidence(): ?StorageObjectEvidence
    {
        return $this->evidence;
    }
}
