<?php

declare(strict_types=1);

namespace Kongono\Persistence;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\Exception\PersistenceOperationException;
use Kongono\Persistence\Exception\PersistenceUncertainStateException;
use Kongono\Persistence\Internal\TrackedEntity;
use Throwable;

/**
 * Owns one entity for one explicit operation and executes at most one change.
 *
 * Database and change-executor authority is accepted only for the duration of
 * flush(); transaction and resource lifecycle remain entirely caller-owned.
 */
#[Api(ApiStatus::Experimental)]
final class OperationPersistenceContext implements PersistenceUnitOfWork
{
    private ContextState $contextState = ContextState::Active;

    private function __construct(private readonly TrackedEntity $tracked) {}

    private function __clone() {}

    public static function forManaged(PersistenceStateAdapter $adapter, object $entity): self
    {
        [$identity, $snapshot] = self::managedEvidence($adapter, $entity);

        return new self(new TrackedEntity($adapter, $entity, $identity, EntityState::Managed, $snapshot));
    }

    public static function forNew(PersistenceStateAdapter $adapter, object $entity): self
    {
        $identity = self::identityEvidence($adapter, $entity);

        return new self(new TrackedEntity($adapter, $entity, $identity, EntityState::New, null));
    }

    public function state(): ContextState
    {
        return $this->contextState;
    }

    public function identityOf(object $entity): ?EntityIdentity
    {
        $this->requireActive();

        return $this->tracked->isEntity($entity) ? $this->tracked->identity() : null;
    }

    public function entityForIdentity(EntityIdentity $identity): ?object
    {
        $this->requireActive();

        return $this->tracked->identity()->equals($identity) ? $this->tracked->entity() : null;
    }

    public function entityState(object $entity): ?EntityState
    {
        $this->requireActive();

        return $this->tracked->isEntity($entity) ? $this->tracked->state() : null;
    }

    public function changes(): ChangeSet
    {
        $this->requireActive();

        return $this->validatedChanges();
    }

    public function markRemoved(): void
    {
        $this->requireActive();
        if (EntityState::Managed !== $this->tracked->state()) {
            throw InvalidPersistenceStateException::lifecycle();
        }

        $this->tracked->markRemoved();
    }

    public function flush(DatabaseExecutor $database, PersistenceChangeExecutor $executor): FlushResult
    {
        $this->requireActive();
        $changes = $this->validatedChanges();
        if ($changes->isEmpty()) {
            return new FlushResult(FlushOutcome::NoChanges, 0, 0, 0, 0);
        }

        $change = $changes->changes()[0];
        $this->contextState = ContextState::Flushing;

        try {
            $outcome = $executor->execute($database, $change);
            if (ChangeExecutionOutcome::Applied === $outcome) {
                $this->synchronizeApplied($change);

                return new FlushResult(FlushOutcome::Applied, 1, 1, 0, 0);
            }
        } catch (Throwable $failure) {
            $this->failUncertain($failure);
        }

        $this->contextState = ContextState::Active;

        if (ChangeExecutionOutcome::EntityAbsent === $outcome) {
            return new FlushResult(FlushOutcome::EntityAbsent, 1, 0, 1, 0);
        }

        return new FlushResult(FlushOutcome::OptimisticConflict, 1, 0, 0, 1);
    }

    public function retire(): void
    {
        if (ContextState::Flushing === $this->contextState) {
            throw InvalidPersistenceStateException::lifecycle();
        }
        if (ContextState::Retired === $this->contextState) {
            return;
        }

        $this->tracked->release();
        $this->contextState = ContextState::Retired;
    }

    /** @return array{EntityIdentity, array<array-key, mixed>} */
    private static function managedEvidence(PersistenceStateAdapter $adapter, object $entity): array
    {
        $identity = self::identityEvidence($adapter, $entity);

        try {
            $snapshot = $adapter->snapshot($entity);
        } catch (Throwable $failure) {
            throw PersistenceOperationException::failed($failure);
        }

        return [$identity, $snapshot];
    }

    private static function identityEvidence(PersistenceStateAdapter $adapter, object $entity): EntityIdentity
    {
        try {
            if ($adapter->entityType() !== $entity::class) {
                throw InvalidPersistenceValueException::validation();
            }
            $identity = $adapter->identity($entity);
        } catch (InvalidPersistenceValueException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw PersistenceOperationException::failed($failure);
        }

        if ($identity->entityType() !== $entity::class) {
            throw InvalidPersistenceValueException::validation();
        }

        return $identity;
    }

    private function requireActive(): void
    {
        if (ContextState::Active !== $this->contextState) {
            throw InvalidPersistenceStateException::lifecycle();
        }
    }

    private function validatedChanges(): ChangeSet
    {
        $adapter = $this->tracked->adapter();
        $entity = $this->tracked->entity();
        $state = $this->tracked->state();

        try {
            $changes = match ($state) {
                EntityState::New => $adapter->insertion($entity),
                EntityState::Managed => $adapter->changes($entity, $this->tracked->snapshot()),
                EntityState::Removed => $adapter->deletion($entity),
            };
        } catch (InvalidPersistenceValueException|InvalidPersistenceStateException $failure) {
            throw $failure;
        } catch (Throwable $failure) {
            throw PersistenceOperationException::failed($failure);
        }

        $count = $changes->count();
        if ($count > 1 || (EntityState::Managed !== $state && 1 !== $count)) {
            throw InvalidPersistenceValueException::validation();
        }
        if (0 === $count) {
            return $changes;
        }

        $change = $changes->changes()[0];
        $expectedKind = match ($state) {
            EntityState::New => ChangeKind::Insert,
            EntityState::Managed => ChangeKind::Update,
            EntityState::Removed => ChangeKind::Delete,
        };
        if ($change->entity() !== $entity || $change->kind() !== $expectedKind) {
            throw InvalidPersistenceValueException::validation();
        }

        $changeIdentity = $change->identity();
        if (
            (ChangeKind::Insert === $expectedKind && null !== $changeIdentity)
            || (ChangeKind::Insert !== $expectedKind && (null === $changeIdentity || !$this->tracked->identity()->equals($changeIdentity)))
        ) {
            throw InvalidPersistenceValueException::validation();
        }

        return $changes;
    }

    private function synchronizeApplied(PersistenceChange $change): void
    {
        if (ChangeKind::Delete === $change->kind()) {
            $this->tracked->release();
            $this->contextState = ContextState::Retired;

            return;
        }

        $adapter = $this->tracked->adapter();
        $entity = $this->tracked->entity();
        $identity = $adapter->identity($entity);
        if ($identity->entityType() !== $entity::class || !$this->tracked->identity()->equals($identity)) {
            throw InvalidPersistenceValueException::validation();
        }
        $snapshot = $adapter->snapshot($entity);
        $this->tracked->synchronize($identity, $snapshot);
        $this->contextState = ContextState::Active;
    }

    private function failUncertain(Throwable $failure): never
    {
        $this->contextState = ContextState::Failed;
        $this->tracked->release();

        throw PersistenceUncertainStateException::failed($failure);
    }
}
