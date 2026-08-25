<?php

declare(strict_types=1);

namespace Kongono\DatabaseMigration;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\DatabaseMigration\Exception\InvalidMigrationDefinitionException;

/** Stores a contiguous ordered detached applied-migration history. */
#[Api(ApiStatus::Experimental)]
final readonly class MigrationHistory
{
    /** @var list<AppliedMigration> */
    private array $records;

    /** @param array<array-key, mixed> $records */
    public function __construct(array $records)
    {
        if (!array_is_list($records) || count($records) > 4_096) {
            throw InvalidMigrationDefinitionException::invalidDefinition();
        }
        $detached = [];
        $seenIds = [];
        foreach ($records as $index => $record) {
            if (!$record instanceof AppliedMigration || $record->ordinal() !== $index + 1 || isset($seenIds[$record->id()])) {
                throw InvalidMigrationDefinitionException::invalidDefinition();
            }
            $seenIds[$record->id()] = true;
            $detached[] = $record;
        }
        $this->records = $detached;
    }

    /** @return list<AppliedMigration> */
    public function records(): array
    {
        return [...$this->records];
    }

    public function count(): int
    {
        return count($this->records);
    }
}
