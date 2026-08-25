<?php

declare(strict_types=1);

namespace KongonoDataMinimal;

use Kongono\Database\CommandResult;
use Kongono\DatabaseApplication\DataOperationRoot;
use KongonoDataMinimal\Generated\AccountRepository;

/** Exposes typed generated CRUD without leaking persistence capabilities. */
final readonly class AccountOperations implements DataOperationRoot
{
    public function __construct(private AccountRepository $repository) {}

    public function find(int $id): ?Account
    {
        return $this->repository->find($id);
    }

    public function insert(Account $account): CommandResult
    {
        return $this->repository->insert($account);
    }

    public function update(Account $account): CommandResult
    {
        return $this->repository->update($account);
    }

    public function delete(int $id): CommandResult
    {
        return $this->repository->delete($id);
    }
}
