# Immutable Database SQL Plans

## Purpose and status

Phase 6D implements two Experimental packages above the low-level
[database contract](database-contract.md): neutral immutable plans in
`kongono/database-sql` and one explicit PostgreSQL 17 compiler in
`kongono/database-sql-pgsql`. Plans describe structure only. They do not open a
session, start a transaction, execute SQL, discover schema, or retain provider
state.

Kongono remains pre-1.0 and not production-ready. PostgreSQL 17 is the sole
compiler and provider proof profile. Phase 6E uses the identifier and statement
contracts from this lower layer without changing the version-1 SQL-plan grammar.

## Layer boundary

```text
application-owned plan construction
    -> database-sql immutable neutral model
        -> database-sql-pgsql explicit PostgreSQL 17 compilation
            -> DatabaseStatement
                -> explicit DatabaseSession or DatabaseTransaction execution
```

The compiler depends on the neutral plan package. The low-level Database and
PDO PostgreSQL packages do not depend on either plan package. Kernel,
Composition, Dependency Injection, Application Graph, HTTP, and developer-
intelligence packages remain outside this boundary.

## Version-1 model

The model accepts Select, Insert, Update, and Delete over one relation. It has
explicit identifier segments, columns, projections, assignments, order terms,
comparison/null/IN/compound predicates, optional Select limit and offset, and
optional mutation RETURNING projections. Collections retain caller order,
detach their array storage, and fail outside fixed bounds.

Identifiers preserve exact valid-UTF-8 bytes. A dot inside a segment is data;
qualification is supplied as separate segments. Callers must enumerate
projections because version 1 has no `*` expression.

Update and Delete accept only an explicit predicate scope or explicit all-row
scope. Review tools can distinguish those source values even though deliberate
all-row compilation emits no WHERE clause.

## Capabilities and compilation

Each plan reports the exact subset of Select, Insert, Update, Delete, Returning,
LimitOffset, NullOrdering, Like, and InList that its shape needs. Capability
sets are canonical and duplicate-free. A compiler checks requirements before
constructing a statement.

The PostgreSQL 17 compiler quotes every segment and rejects a segment above 63
bytes instead of relying on provider truncation. It emits deterministic keyword,
spacing, parentheses, ordering, and clause order. Each value occurrence becomes
one fresh named `DatabaseParameter`; equal values remain separate occurrences.
Limit and offset are parameters too. A compiler instance retains no counter,
plan, value, or operation state between calls or fibers.

## Choosing structured plans or raw SQL

Use a structured plan when the bounded grammar represents the operation and
inspectability is useful. Use the existing parameter-bound `DatabaseStatement`
directly for joins, expressions, casts, provider features, or other grammar not
accepted by version 1. Raw SQL remains supported and is not deprecated. In both
paths, values belong in explicit parameters rather than interpolation.

## Failure and disclosure

Invalid structure, unsupported capability or plan type, identifier-profile
rejection, and parameter overflow fail visibly through the package-local SQL
exception marker. Diagnostic presentation includes only fixed package-owned
structural fields. SQL, identifiers, aliases, placeholder names, parameter
values, provider messages, native types, paths, and topology are excluded.

## Concurrency and lifecycle

Plans and their leaf values are immutable. Compilation uses call-local mutable
assembly only, so a compiler may be shared across sequential, overlapping, and
fiber operations. The returned `DatabaseStatement` is immutable. Session,
stream, and transaction lifecycle remains owned by the existing low-level
database contract.

## Related documents

- [ADR 0082](../adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md)
- [ADR 0083](../adr/0083-deterministic-schema-migrations-and-postgresql-execution.md)
- [Data Platform](data-platform.md)
- [Phase 6 roadmap](phase-6-roadmap.md)
- [SQL-plan engineering guide](../engineering/database-sql-plans.md)
- [Deterministic database migrations](database-migrations.md)
- [Low-level database engineering contracts](../engineering/database-contracts.md)
