<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;

/**
 * Supplies detached state evidence for one exact application entity type.
 *
 * Implementations perform no database I/O and must reject entities whose exact
 * runtime class differs from entityType().
 */
#[Api(ApiStatus::Experimental)]
interface PersistenceStateAdapter
{
    public function entityType(): string;

    public function identity(object $entity): EntityIdentity;

    /** @return array<array-key, mixed> */
    public function snapshot(object $entity): array;

    /** @param array<array-key, mixed> $snapshot */
    public function changes(object $entity, array $snapshot): ChangeSet;

    public function insertion(object $entity): ChangeSet;

    public function deletion(object $entity): ChangeSet;
}
