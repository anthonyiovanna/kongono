<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Persistence;

use InvalidArgumentException;
use Kongono\Persistence\ChangeSet;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\PersistenceStateAdapter;
use KongonoPersistenceMinimal\Domain\Account;
use KongonoPersistenceMinimal\GeneratedPersistence\Artifact_6d074c84987ad34e2c760bd5389cfdd3\EntityState_0001 as GeneratedAccountStateAdapter;

/** Gives application code a stable typed bridge to exact generated state evidence. */
final readonly class AccountStateAdapter implements PersistenceStateAdapter
{
    public function entityType(): string
    {
        return Account::class;
    }
    public function identity(object $entity): EntityIdentity
    {
        return GeneratedAccountStateAdapter::identity($this->account($entity));
    }
    public function snapshot(object $entity): array
    {
        return GeneratedAccountStateAdapter::snapshot($this->account($entity));
    }
    public function changes(object $entity, array $snapshot): ChangeSet
    {
        return GeneratedAccountStateAdapter::changes($this->account($entity), $this->scalarSnapshot($snapshot));
    }
    public function insertion(object $entity): ChangeSet
    {
        return GeneratedAccountStateAdapter::insertion($this->account($entity));
    }
    public function deletion(object $entity): ChangeSet
    {
        return GeneratedAccountStateAdapter::deletion($this->account($entity));
    }

    private function account(object $entity): Account
    {
        if (!$entity instanceof Account) {
            throw new InvalidArgumentException('Account state adapter requires an Account.');
        }

        return $entity;
    }

    /**
     * @param array<mixed> $snapshot
     * @return array<string, bool|float|int|string|null>
     */
    private function scalarSnapshot(array $snapshot): array
    {
        $result = [];
        foreach ($snapshot as $field => $value) {
            if (!is_string($field) || !(null === $value || is_bool($value) || is_float($value) || is_int($value) || is_string($value))) {
                throw new InvalidArgumentException('Account snapshot must contain named scalar values.');
            }
            $result[$field] = $value;
        }

        return $result;
    }
}
