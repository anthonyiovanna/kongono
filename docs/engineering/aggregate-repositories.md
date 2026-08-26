# Aggregate repository engineering

Phase 7F adds deterministic generated aggregate repositories above the Phase 7E
single-entity context. Use them when an operation needs one explicitly named,
bounded relationship shape and the caller can supply database and execution
capabilities. Do not use them for arbitrary queries, lazy loading, recursive
graphs, or hidden transaction management.

## Author the separate source

Keep `kongono.persistence.json` unchanged. Add
`kongono.persistence-repositories.json` under the application root:

```json
{
  "schema": "kongono.persistence-repositories",
  "version": 1,
  "persistence_source": "config/kongono.persistence.json",
  "generated_namespace": "App\\Generated\\PersistenceRepositories",
  "repositories": [
    {
      "aggregate": "account",
      "class": "AccountAggregateRepository",
      "load_methods": [
        {
          "method": "loadWithProfiles",
          "relationships": [
            {"relationship": "account_profiles", "max_related": 32}
          ]
        }
      ],
      "cascades": [
        {"relationship": "account_profiles", "persist": true, "remove": true}
      ]
    }
  ]
}
```

To-many loads require a positive `max_related`; to-one loads require exactly
one. Every relationship sourced by the aggregate root requires one explicit
cascade record. Reference-owned relationships must set both flags to `false`.

## Generate and validate

Build the current persistence artifact first, then generate and validate the
repository PHP without executing it:

```php
$persistence = (new PersistenceArtifactBuilder())->build(
    $applicationRoot,
    'config/kongono.persistence.json',
);

$repositories = (new GeneratedAggregateRepositoryGenerator())->generate(
    $applicationRoot,
    'config/kongono.persistence-repositories.json',
    $persistence->canonicalBytes(),
);

(new GeneratedAggregateRepositoryValidator())->validateCurrent(
    $applicationRoot,
    'config/kongono.persistence-repositories.json',
    $persistence->canonicalBytes(),
    $repositories->canonicalBytes(),
);
```

Optional publication uses
`kongono-persistence-repositories-v1-<generated-file-sha256>.php`. Phase 7F
does not provide a production loader or command; the caller controls how one
validated file is made available.

## Invoke a concrete load method

Generated methods name the I/O shape in code. The database and load executor
remain explicit:

```php
$identity = new EntityIdentity(Account::class, $opaqueKey);
$loaded = $accountRepository->loadWithProfiles(
    $identity,
    $database,
    $aggregateLoadExecutor,
);

if (null === $loaded) {
    // Root absence is complete evidence for this call; no hidden follow-up runs.
    return;
}

$account = $loaded->root();
$profiles = $loaded->relationship('account_profiles');
```

The relationship value is explicit: an object, nullable to-one `null`, or an
ordered list of objects. The generated repository verifies the declared key
set, types, cardinality, nullability, and bounds before returning it.

## Persist or remove under caller transaction authority

The caller owns transaction lifecycle and decides how absence or conflict is
handled. Release loaded evidence in `finally`:

```php
$transaction = $session->begin($transactionOptions);
$loaded = null;

try {
    $loaded = $accountRepository->loadWithProfiles(
        $identity,
        $transaction,
        $aggregateLoadExecutor,
    );

    if (null === $loaded) {
        $transaction->rollback();
        return;
    }

    $result = $accountRepository->persist(
        $loaded,
        $transaction,
        $aggregateCascadeExecutor,
    );

    if (FlushOutcome::Applied === $result->outcome()) {
        $transaction->commit();
    } else {
        $transaction->rollback();
        // EntityAbsent and OptimisticConflict remain distinct zero-applied outcomes.
    }
} catch (Throwable $failure) {
    $transaction->rollback();
    throw $failure; // Do not replay or retry an uncertain executor outcome here.
} finally {
    $loaded = null;
    $transaction->close();
}
```

`remove()` has the same authority boundary and uses the explicit remove policy.
The generated repository calls the supplied cascade executor once. It does not
flush automatically, recursively discover work, begin or close a transaction,
retry, reconnect, or choose a provider.

## Failure and lifecycle

- `null` means root absence, with no hidden load.
- `EntityAbsent` and `OptimisticConflict` are distinct zero-applied results.
- Thrown failures remain caller-visible; uncertain outcomes are not replayed.
- Generated repositories have no properties and retain no database, executor,
  identity, aggregate, relationship, result, or failure between calls.
- `LoadedAggregate` retains its explicit object evidence until the caller
  releases it; this is why persistent workers should clear operation references
  in `finally`.

Phase 7G exact-current runtime loading and Composition integration are complete.
Phase 7H externally proves the resulting boundary against PostgreSQL 18.6,
including ordered two-statement loading, reviewed one-level cascades, optimistic
conflict without retry, operation isolation, cleanup, and nondisclosure. See the
[Phase 7 external acceptance guide](../architecture/phase-7-external-acceptance.md).
