<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal\Persistence;

use Kongono\Database\DatabaseExecutor;
use Kongono\Persistence\AggregateLoadExecutor;
use Kongono\Persistence\AggregateLoadPlan;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\LoadedAggregate;
use KongonoPersistenceMinimal\Domain\Account;
use KongonoPersistenceMinimal\GeneratedData\AccountRepository;
use LogicException;

/** Executes the example's one reviewed bounded aggregate load plan. */
final readonly class AccountLoadExecutor implements AggregateLoadExecutor
{
    public function __construct(private AccountRepository $accounts) {}

    public function load(DatabaseExecutor $database, AggregateLoadPlan $plan, EntityIdentity $identity): ?LoadedAggregate
    {
        if ('account' !== $plan->aggregateName() || 'loadWithProfiles' !== $plan->methodName() || Account::class !== $identity->entityType()) {
            throw new LogicException('Unsupported aggregate load plan.');
        }
        if (1 !== preg_match('/^i:(0|[1-9][0-9]*)$/D', $identity->key())) {
            throw new LogicException('Unsupported account identity.');
        }
        $record = $this->accounts->find((int) substr($identity->key(), 2));
        if (null === $record) {
            return null;
        }

        return new LoadedAggregate(
            new Account($record->id(), $record->name(), $record->version()),
            ['account_profiles' => []],
        );
    }
}
