<?php

declare(strict_types=1);

namespace Kongono\Persistence\Internal;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\EntityState;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\PersistenceStateAdapter;

/** Holds and explicitly releases the sole operation-owned entity state tuple. */
#[Api(ApiStatus::Internal)]
final class TrackedEntity
{
    /** @param array<array-key, mixed>|null $snapshot */
    public function __construct(
        private ?PersistenceStateAdapter $adapter,
        private ?object $entity,
        private ?EntityIdentity $identity,
        private ?EntityState $state,
        private ?array $snapshot,
    ) {}

    public function adapter(): PersistenceStateAdapter
    {
        return $this->adapter ?? throw InvalidPersistenceStateException::lifecycle();
    }

    public function entity(): object
    {
        return $this->entity ?? throw InvalidPersistenceStateException::lifecycle();
    }

    public function identity(): EntityIdentity
    {
        return $this->identity ?? throw InvalidPersistenceStateException::lifecycle();
    }

    public function state(): EntityState
    {
        return $this->state ?? throw InvalidPersistenceStateException::lifecycle();
    }

    /** @return array<array-key, mixed> */
    public function snapshot(): array
    {
        return $this->snapshot ?? throw InvalidPersistenceStateException::lifecycle();
    }

    public function isEntity(object $entity): bool
    {
        return null !== $this->entity && $this->entity === $entity;
    }

    public function markRemoved(): void
    {
        $this->state = EntityState::Removed;
    }

    /** @param array<array-key, mixed> $snapshot */
    public function synchronize(EntityIdentity $identity, array $snapshot): void
    {
        $this->identity = $identity;
        $this->snapshot = $snapshot;
        $this->state = EntityState::Managed;
    }

    public function release(): void
    {
        $this->snapshot = null;
        $this->identity = null;
        $this->state = null;
        $this->entity = null;
        $this->adapter = null;
    }
}
