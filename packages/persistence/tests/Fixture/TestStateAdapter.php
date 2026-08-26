<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Fixture;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ChangeSet;
use Kongono\Persistence\ConcurrencyToken;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\PersistenceChange;
use Kongono\Persistence\PersistenceStateAdapter;
use Kongono\Persistence\Tests\Fixture\Domain\TestEntity;
use RuntimeException;

final class TestStateAdapter implements PersistenceStateAdapter
{
    public int $entityTypeCalls = 0;
    public int $identityCalls = 0;
    public int $snapshotCalls = 0;
    public int $changesCalls = 0;
    public int $insertionCalls = 0;
    public int $deletionCalls = 0;
    public string $mode = 'normal';
    public ?int $throwIdentityOnCall = null;
    public ?int $throwSnapshotOnCall = null;
    public string $declaredEntityType = TestEntity::class;

    public function entityType(): string
    {
        ++$this->entityTypeCalls;

        return $this->declaredEntityType;
    }

    public function identity(object $entity): EntityIdentity
    {
        ++$this->identityCalls;
        if ($this->identityCalls === $this->throwIdentityOnCall) {
            throw new RuntimeException('synthetic-provider-identity-secret');
        }
        $entity = $this->requireEntity($entity);

        return new EntityIdentity(TestEntity::class, 'entity-' . $entity->id);
    }

    public function snapshot(object $entity): array
    {
        ++$this->snapshotCalls;
        if ($this->snapshotCalls === $this->throwSnapshotOnCall) {
            throw new RuntimeException('synthetic-provider-snapshot-secret');
        }
        $entity = $this->requireEntity($entity);

        return ['id' => $entity->id, 'name' => $entity->name, 'version' => $entity->version];
    }

    public function changes(object $entity, array $snapshot): ChangeSet
    {
        ++$this->changesCalls;
        $entity = $this->requireEntity($entity);
        if ('empty' === $this->mode) {
            return new ChangeSet([]);
        }
        if ('multiple' === $this->mode) {
            return new ChangeSet([
                $this->update($entity, $snapshot),
                $this->update(new TestEntity($entity->id + 100, 'other', 1), ['id' => $entity->id + 100, 'name' => 'before', 'version' => 1]),
            ]);
        }
        if ('wrong_entity' === $this->mode) {
            return new ChangeSet([$this->update(new TestEntity($entity->id + 100, 'other', 1), ['id' => $entity->id + 100, 'name' => 'before', 'version' => 1])]);
        }
        if ('wrong_kind' === $this->mode) {
            return new ChangeSet([new PersistenceChange($entity, ChangeKind::Delete, $this->identity($entity), [], null)]);
        }
        if ('wrong_identity' === $this->mode) {
            return new ChangeSet([new PersistenceChange($entity, ChangeKind::Update, new EntityIdentity(TestEntity::class, 'other'), ['name'], null)]);
        }

        $changedFields = [];
        foreach (['name', 'version'] as $field) {
            if (!array_key_exists($field, $snapshot)) {
                throw InvalidPersistenceValueException::validation();
            }
            if ($entity->{$field} !== $snapshot[$field]) {
                $changedFields[] = $field;
            }
        }
        if ([] === $changedFields) {
            return new ChangeSet([]);
        }

        return new ChangeSet([$this->update($entity, $snapshot, $changedFields)]);
    }

    public function insertion(object $entity): ChangeSet
    {
        ++$this->insertionCalls;
        $entity = $this->requireEntity($entity);
        if ('empty' === $this->mode) {
            return new ChangeSet([]);
        }

        return new ChangeSet([new PersistenceChange($entity, ChangeKind::Insert, null, ['id', 'name', 'version'], null)]);
    }

    public function deletion(object $entity): ChangeSet
    {
        ++$this->deletionCalls;
        $entity = $this->requireEntity($entity);
        if ('empty' === $this->mode) {
            return new ChangeSet([]);
        }

        return new ChangeSet([new PersistenceChange(
            $entity,
            ChangeKind::Delete,
            $this->identity($entity),
            [],
            new ConcurrencyToken('version', (string) $entity->version),
        )]);
    }

    /**
     * @param array<array-key, mixed> $snapshot
     * @param list<string> $fields
     */
    private function update(TestEntity $entity, array $snapshot, array $fields = ['name']): PersistenceChange
    {
        $version = $snapshot['version'] ?? 1;
        if (!is_int($version)) {
            throw InvalidPersistenceValueException::validation();
        }

        return new PersistenceChange(
            $entity,
            ChangeKind::Update,
            $this->identity($entity),
            $fields,
            new ConcurrencyToken('version', (string) $version),
        );
    }

    private function requireEntity(object $entity): TestEntity
    {
        if (!$entity instanceof TestEntity) {
            throw InvalidPersistenceValueException::validation();
        }

        return $entity;
    }
}
