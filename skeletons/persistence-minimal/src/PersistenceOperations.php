<?php

declare(strict_types=1);

namespace KongonoPersistenceMinimal;

use Kongono\DatabaseApplication\DataOperationRoot;
use Kongono\DatabaseApplication\OperationDatabaseSession;
use Kongono\Persistence\AggregateCascadeExecutor;
use Kongono\Persistence\AggregateLoadExecutor;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\FlushResult;
use Kongono\Persistence\LoadedAggregate;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\PersistenceChangeExecutor;
use KongonoPersistenceMinimal\Domain\Account;
use KongonoPersistenceMinimal\GeneratedAggregateRepositories\AccountAggregateRepository;
use KongonoPersistenceMinimal\Persistence\AccountStateAdapter;

/** Exposes small typed persistence operations while retaining every capability. */
final readonly class PersistenceOperations implements DataOperationRoot
{
    public function __construct(
        private OperationDatabaseSession $session,
        private AccountAggregateRepository $accounts,
        private AggregateLoadExecutor $loads,
        private AggregateCascadeExecutor $cascades,
        private PersistenceChangeExecutor $changes,
        private AccountStateAdapter $state,
    ) {}

    public function loadAccount(int $id): ?LoadedAggregate
    {
        return $this->accounts->loadWithProfiles(new EntityIdentity(Account::class, 'i:' . $id), $this->session, $this->loads);
    }

    public function flushNew(Account $account): FlushResult
    {
        $context = OperationPersistenceContext::forNew($this->state, $account);
        try {
            return $context->flush($this->session, $this->changes);
        } finally {
            $context->retire();
        }
    }

    public function persistAggregate(LoadedAggregate $aggregate): FlushResult
    {
        return $this->accounts->persist($aggregate, $this->session, $this->cascades);
    }
}
