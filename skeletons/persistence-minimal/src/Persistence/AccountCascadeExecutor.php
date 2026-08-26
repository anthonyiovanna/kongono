<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Persistence;

use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\AggregateCascadeExecutor;
use Kongono\Persistence\AggregateCascadePlan;
use Kongono\Persistence\CascadeAction;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\FlushResult;
use Kongono\Persistence\LoadedAggregate;
use KongonoPersistenceMinimal\Domain\Account;
use LogicException;

/** Demonstrates explicit cascade authority without acquiring a transaction. */
final readonly class AccountCascadeExecutor implements AggregateCascadeExecutor
{
    public function __construct(private AccountChangeExecutor $changes, private AccountStateAdapter $state) {}

    public function execute(DatabaseExecutor $database, AggregateCascadePlan $plan, CascadeAction $action, LoadedAggregate $aggregate): FlushResult
    {
        $account = $aggregate->root();
        if ('account' !== $plan->aggregateName() || !$account instanceof Account) {
            throw new LogicException('Unsupported aggregate cascade plan.');
        }
        $change = CascadeAction::Persist === $action
            ? $this->state->insertion($account)->changes()[0]
            : $this->state->deletion($account)->changes()[0];
        $outcome = $this->changes->execute($database, $change);

        return match ($outcome) {
            \Kongono\Persistence\ChangeExecutionOutcome::Applied => new FlushResult(FlushOutcome::Applied, 1, 1, 0, 0),
            \Kongono\Persistence\ChangeExecutionOutcome::EntityAbsent => new FlushResult(FlushOutcome::EntityAbsent, 1, 0, 1, 0),
            \Kongono\Persistence\ChangeExecutionOutcome::OptimisticConflict => new FlushResult(FlushOutcome::OptimisticConflict, 1, 0, 0, 1),
        };
    }
}
