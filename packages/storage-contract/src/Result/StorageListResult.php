<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Outcome\StorageListOutcome;

/** Preserves the exact relation between a list outcome and page evidence. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageListResult
{
    public function __construct(
        private StorageListOutcome $outcome,
        private ?StorageListPage $page = null,
    ) {
        if ((StorageListOutcome::Completed === $outcome) !== (null !== $page)) {
            throw InvalidStorageContractException::invalidResult('list_result_page_relation', $outcome->value);
        }
    }

    public function outcome(): StorageListOutcome
    {
        return $this->outcome;
    }

    public function page(): ?StorageListPage
    {
        return $this->page;
    }
}
