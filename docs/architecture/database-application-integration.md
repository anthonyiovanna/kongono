# Database Application Integration

## Purpose

The Experimental database-application layer joins exact-current generated Data
Mapper output to Kongono's accepted generated composition lifecycle without
giving lower database packages application or runtime ownership.

## Build and load order

The explicit `DataApplicationBuildWorkflow` owns this order:

```text
admit application root and contained existing output directory
  -> build current mapping artifact
  -> generate current data-access PHP
  -> publish or check exact content-addressed mapping/data files
  -> validate and load the exact generated data-access file
  -> run the accepted Composition build or check workflow
  -> reverify mapping/data snapshots
  -> return detached identities for all four outputs
```

Loading before Composition is required because the composition builder reflects
the generated repository constructor. Composition provenance therefore records
the exact generated PHP file rather than an autoload guess or mutable alias.
`check()` writes nothing. Missing, stale, crossed, tampered, renamed, linked,
colliding, or changed-during-read state fails closed.

## Runtime ownership

`GeneratedDataApplication` validates a profile with no application root and one
typed `operationRoot1` before startup. `beginOperation()` returns a
`GeneratedDataOperation` that owns the generated boundary and its
`DataOperationRoot`. The marker exposes no generic database or service lookup.

`OperationDatabaseSession` is an operation service constructed from the
host-supplied `DatabaseConnector`. Construction opens once; generated reverse
cleanup closes once. The wrapper retires before delegate cleanup begins, so
failing cleanup cannot permit later execution or a retry. Transactions remain
explicit caller-owned objects and are never begun, committed, rolled back, or
closed by the wrapper.

Overlapping operations share only application-lifetime stateless objects such
as a dialect compiler. Their roots, repositories, session wrappers, provider
sessions, results, and transactions remain distinct. Application shutdown
refuses active generated operation leases.

## Boundary

The integration package is provider-neutral. The data-minimal skeleton selects
the existing PostgreSQL connector/compiler explicitly, but credentials and the
connector instance remain host inputs. There is no environment discovery,
container, service location, request-time build/load/generation, runtime record
Reflection, migration startup, ORM, retry, pool, or deployment entrypoint.
