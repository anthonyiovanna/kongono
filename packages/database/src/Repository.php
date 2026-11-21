<?php

declare(strict_types=1);

namespace Kongono\Database;

/**
 * Clean, zero-magic base Repository for typed PHP 8.5 readonly entity/DTO CRUD.
 *
 * @template T of object
 */
abstract readonly class Repository
{
    /**
     * @param class-string<T> $dtoClass
     */
    public function __construct(
        protected ConnectionInterface $db,
        protected string $table,
        protected string $dtoClass,
        protected string $primaryKey = 'id',
    ) {}

    /** @return ?T */
    public function find(int|string $id): ?object
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id)
            ->firstAs($this->dtoClass);
    }

    /** @return T */
    public function findOrFail(int|string $id): object
    {
        $found = $this->find($id);
        if (null === $found) {
            throw new DatabaseException(sprintf('Record "%s" not found in table "%s".', (string) $id, $this->table));
        }

        return $found;
    }

    /** @return list<T> */
    public function all(?string $orderBy = null, string $direction = 'ASC'): array
    {
        $query = $this->db->table($this->table);
        if (null !== $orderBy) {
            $query->orderBy($orderBy, $direction);
        }

        return $query->getAs($this->dtoClass);
    }

    public function count(): int
    {
        return $this->db->table($this->table)->count();
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): int
    {
        return $this->db->table($this->table)->insert($values);
    }

    /** @param array<string, mixed> $values */
    public function updateById(int|string $id, array $values): int
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id)
            ->update($values);
    }

    public function deleteById(int|string $id): int
    {
        return $this->db->table($this->table)
            ->where($this->primaryKey, '=', $id)
            ->delete();
    }
}
