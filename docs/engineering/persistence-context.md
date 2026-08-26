# Operation-Scoped Persistence Context

## Purpose and stability

Phase 7E adds the Experimental `OperationPersistenceContext` for exactly one
application entity. Use it when an operation already has an explicit state
adapter, change executor, and caller-owned `DatabaseExecutor`. It is not an
entity manager, repository, transaction helper, generated-adapter loader, or
relationship unit of work.

## Managed and new entities

Create a managed context only from state already known to represent persisted
data. Construction captures one detached initial snapshot:

```php
$context = OperationPersistenceContext::forManaged($adapter, $account);
```

Create a new context only when the application has already assigned a stable
identity. Construction performs no insertion and no database I/O:

```php
$context = OperationPersistenceContext::forNew($adapter, $account);
```

The adapter must name the entity's exact runtime class, reject other objects,
and return detached evidence. One context never adopts a second entity.

## Explicit removal and flush

Only an Active Managed entity may be marked removed:

```php
$context->markRemoved();
```

Removal changes in-memory state only. Execute the resulting insert, update, or
delete explicitly through the caller's exact database authority:

```php
$result = $context->flush($databaseExecutor, $changeExecutor);
```

`NoChanges` means the executor was not called. `Applied` means the one change
was applied and post-write synchronization succeeded. `EntityAbsent` and
`OptimisticConflict` both mean zero writes were applied; they remain distinct,
leave the context Active, preserve the prior snapshot/state, and never trigger
automatic retry, refresh, merge, or conflict resolution.

## Transaction ownership

Pass an already selected session or transaction because each implements
`DatabaseExecutor`. The context never calls begin, commit, rollback, close,
retry, reconnect, savepoint, or isolation methods:

```php
$transaction = $session->begin($options);
try {
    $result = $context->flush($transaction, $changeExecutor);
    $transaction->commit();
} catch (Throwable $failure) {
    $transaction->rollback();
    throw $failure;
} finally {
    $transaction->close();
}
```

Those transaction decisions remain application policy. Do not infer commit or
rollback from the context state alone.

## Retirement and persistent workers

Retire the context in the operation's `finally` block. Retirement is idempotent
and releases all retained domain references; it does not close caller resources:

```php
try {
    $result = $context->flush($databaseExecutor, $changeExecutor);
} finally {
    $context->retire();
}
```

After Failed or Retired, only `state()` and idempotent `retire()` are accepted.
No destructor, response completion, entity method, property access, event, or
hook flushes automatically.

## Terminal uncertainty

Catch `PersistenceUncertainStateException` separately. It means the executor
threw after flushing began or post-Applied synchronization failed, so provider
effects may have occurred:

```php
try {
    $result = $context->flush($databaseExecutor, $changeExecutor);
} catch (PersistenceUncertainStateException $failure) {
    // Retire external operation/runtime state according to protected policy.
    throw $failure;
} finally {
    $context->retire();
}
```

Do not retry, replay, report Applied, or attempt later synchronization through
that context. Its fixed diagnostic presentation excludes application values,
identity and concurrency contents, SQL, parameters, and provider messages. The
original cause remains available only through a protected operational boundary.

## Deferred capabilities

Phase 7E does not load Phase 7D generated PHP, select adapters, coordinate
multiple entities, generate SQL, choose providers, or integrate with Composition.
Phase 7F adds separate stateless generated aggregate repositories with explicit
bounded load/cascade executors; it does not widen this single-entity context or
grant it relationship, repository, transaction, or loading authority.
