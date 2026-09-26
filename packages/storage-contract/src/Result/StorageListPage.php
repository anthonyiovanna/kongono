<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Result;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Pagination\StorageContinuationToken;

/** Holds one detached strictly ordered bounded list page. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageListPage
{
    /** @var list<StorageListEntry> */
    private array $entries;

    /** @param array<array-key, mixed> $entries */
    public function __construct(
        array $entries,
        private bool $complete,
        private ?StorageContinuationToken $continuationToken = null,
    ) {
        if (!array_is_list($entries)) {
            throw InvalidStorageContractException::invalidResult('list_entries_not_list', entryCount: count($entries));
        }
        if (count($entries) > StorageContractBounds::MAX_LIST_PAGE_SIZE) {
            throw InvalidStorageContractException::contractBoundExceeded('list_page_size', StorageContractBounds::MAX_LIST_PAGE_SIZE, count($entries));
        }
        $validated = [];
        $previousKey = null;
        foreach ($entries as $position => $entry) {
            if (!$entry instanceof StorageListEntry) {
                throw InvalidStorageContractException::invalidResult('invalid_list_entry', entryCount: count($entries), entryPosition: $position);
            }
            $identity = $entry->identity();
            $key = $identity->namespace()->value() . "\0" . $identity->logicalIdentity()->value();
            if (null !== $previousKey && strcmp($previousKey, $key) >= 0) {
                throw InvalidStorageContractException::invalidResult('duplicate_or_unordered_list_entry', entryCount: count($entries), entryPosition: $position);
            }
            $previousKey = $key;
            $validated[] = $entry;
        }
        if ($complete === (null !== $continuationToken)) {
            throw InvalidStorageContractException::invalidResult('page_continuation_relation', entryCount: count($entries));
        }
        $this->entries = $validated;
    }

    /** @return list<StorageListEntry> */
    public function entries(): array
    {
        return [...$this->entries];
    }

    public function complete(): bool
    {
        return $this->complete;
    }

    public function continuationToken(): ?StorageContinuationToken
    {
        return $this->continuationToken;
    }
}
