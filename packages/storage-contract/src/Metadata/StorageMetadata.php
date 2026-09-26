<?php

declare(strict_types=1);

namespace Kongono\StorageContract\Metadata;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\StorageContract\Bound\StorageContractBounds;
use Kongono\StorageContract\Exception\InvalidStorageContractException;
use Kongono\StorageContract\Internal\ContractGuard;

/** Holds detached unique metadata entries in ordinal name order. */
#[Api(ApiStatus::Experimental)]
final readonly class StorageMetadata
{
    /** @var list<StorageMetadataEntry> */
    private array $entries;

    /** @param array<array-key, mixed> $entries */
    public function __construct(array $entries)
    {
        if (!array_is_list($entries)) {
            throw InvalidStorageContractException::invalidResult('metadata_entries_not_list', entryCount: count($entries));
        }
        if (count($entries) > StorageContractBounds::MAX_METADATA_ENTRIES) {
            throw InvalidStorageContractException::contractBoundExceeded('metadata_entries', StorageContractBounds::MAX_METADATA_ENTRIES, count($entries));
        }
        $byName = [];
        foreach ($entries as $position => $entry) {
            if (!$entry instanceof StorageMetadataEntry) {
                throw InvalidStorageContractException::invalidResult('invalid_metadata_entry', entryCount: count($entries), entryPosition: $position);
            }
            if (isset($byName[$entry->name()])) {
                throw InvalidStorageContractException::invalidResult('duplicate_metadata_entry', entryCount: count($entries), entryPosition: $position);
            }
            $byName[$entry->name()] = $entry;
        }
        ksort($byName, SORT_STRING);
        $this->entries = array_values($byName);
    }

    /** @return list<StorageMetadataEntry> */
    public function entries(): array
    {
        return [...$this->entries];
    }

    public function entry(string $name): ?StorageMetadataEntry
    {
        $canonical = ContractGuard::metadataName($name);
        foreach ($this->entries as $entry) {
            if ($canonical === $entry->name()) {
                return $entry;
            }
        }

        return null;
    }

    public function equals(self $other): bool
    {
        if (count($this->entries) !== count($other->entries)) {
            return false;
        }
        foreach ($this->entries as $position => $entry) {
            if (!$entry->equals($other->entries[$position])) {
                return false;
            }
        }

        return true;
    }
}
