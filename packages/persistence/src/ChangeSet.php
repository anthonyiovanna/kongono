<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;

/** Stores one ordered, detached, duplicate-free list of persistence changes. */
#[Api(ApiStatus::Experimental)]
final readonly class ChangeSet
{
    /** @var list<PersistenceChange> */
    private array $changes;

    /** @param list<PersistenceChange> $changes */
    public function __construct(array $changes)
    {
        $detachedChanges = [];
        foreach ($changes as $change) {
            $detachedChanges[] = $this->requireChange($change);
        }

        if (count($detachedChanges) > 1024) {
            throw InvalidPersistenceValueException::validation();
        }

        $entityIds = [];
        $identities = [];
        foreach ($detachedChanges as $change) {
            $entityId = spl_object_id($change->entity());
            if (array_key_exists($entityId, $entityIds)) {
                throw InvalidPersistenceValueException::validation();
            }
            $entityIds[$entityId] = true;

            $identity = $change->identity();
            if (null === $identity) {
                continue;
            }

            $identityKey = $identity->entityType() . "\0" . $identity->key();
            if (array_key_exists($identityKey, $identities)) {
                throw InvalidPersistenceValueException::validation();
            }
            $identities[$identityKey] = true;
        }

        $this->changes = $detachedChanges;
    }

    /** @return list<PersistenceChange> */
    public function changes(): array
    {
        return $this->changes;
    }

    public function count(): int
    {
        return count($this->changes);
    }

    public function isEmpty(): bool
    {
        return [] === $this->changes;
    }

    private function requireChange(mixed $change): PersistenceChange
    {
        if (!$change instanceof PersistenceChange) {
            throw InvalidPersistenceValueException::validation();
        }

        return $change;
    }
}
