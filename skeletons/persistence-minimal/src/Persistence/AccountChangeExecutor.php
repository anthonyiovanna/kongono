<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Persistence;

use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\ChangeExecutionOutcome;
use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\PersistenceChange;
use Kongono\Persistence\PersistenceChangeExecutor;
use KongonoPersistenceMinimal\Data\AccountRecord;
use KongonoPersistenceMinimal\Domain\Account;
use KongonoPersistenceMinimal\GeneratedData\AccountRepository;
use LogicException;

/** Translates one account change through the explicitly injected typed repository. */
final readonly class AccountChangeExecutor implements PersistenceChangeExecutor
{
    public function __construct(private AccountRepository $repository) {}

    public function execute(DatabaseExecutor $database, PersistenceChange $change): ChangeExecutionOutcome
    {
        $account = $change->entity();
        if (!$account instanceof Account) {
            throw new LogicException('Unsupported persistence change.');
        }
        $record = new AccountRecord($account->id(), $account->name(), $account->version());
        $result = match ($change->kind()) {
            ChangeKind::Insert => $this->repository->insert($record),
            ChangeKind::Update => $this->repository->update($record),
            ChangeKind::Delete => $this->repository->delete($account->id()),
        };
        if (0 !== $result->affectedRows()) {
            return ChangeExecutionOutcome::Applied;
        }

        return ChangeKind::Update === $change->kind() && null !== $change->expectedConcurrencyToken()
            ? ChangeExecutionOutcome::OptimisticConflict
            : ChangeExecutionOutcome::EntityAbsent;
    }
}
