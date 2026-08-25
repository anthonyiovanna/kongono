# ADR 0082: Immutable Database SQL Plans and PostgreSQL Dialect Compilation

- **Status:** Accepted
- **Date:** 2026-08-24
- **Phase:** 6D

## Context

[ADR 0081](0081-postgresql-pdo-adapter-and-real-provider-proof.md) implements
the low-level database session and parameter-bound raw-SQL boundary. The next
Data Platform layer needs reviewable structured plans without replacing raw SQL,
introducing an ORM, or moving execution responsibility into a plan.

## Decision

### Package boundaries

Phase 6D adds Experimental `kongono/database-sql` under
`Kongono\DatabaseSql\` and Experimental `kongono/database-sql-pgsql` under
`Kongono\DatabaseSqlPgsql\`. The neutral plan package depends only on PHP, API
Metadata, Database, and Diagnostics. The PostgreSQL compiler depends one way on
Database and the neutral plan package. Neither package owns PDO, a connection,
execution, environment lookup, provider configuration, or I/O.

### Immutable version-1 grammar

The neutral package owns final readonly, detached, typed Select, Insert,
Update, and Delete plans. Version 1 accepts one source or target; explicit
columns and projections; ordered assignments; comparison, null, IN, AND, OR,
and NOT predicates; ordering with explicit direction and optional null order;
Select limit and offset; and mutation RETURNING projections. Fixed collection
and parameter bounds fail at construction or compilation.

An identifier is one exact valid-UTF-8 segment of 1 through 128 bytes without
NUL or ASCII control bytes. Dots remain segment data. Qualified names contain
one through three segments, and columns have at most one qualifier. The model
does not trim, normalize, fold case, split dots, infer delimiters, accept `*`,
or admit arbitrary expressions or fragments.

Update and Delete require one `SqlMutationScope`. A predicate scope and
explicit `allRows()` intent are distinct source values. An absent predicate can
therefore never accidentally select all rows.

Every executable value remains an existing `DatabaseValue`. Each occurrence,
including limit and offset, compiles to its own ordered `DatabaseParameter`.
Values are not interpolated, deduplicated, stringified, normalized, or inferred
from arbitrary arrays.

### Explicit capability and compiler boundary

The closed version-1 capabilities are Select, Insert, Update, Delete,
Returning, LimitOffset, NullOrdering, Like, and InList. Capability sets are
duplicate-free, canonically ordered, immutable, and exactly inspectable. Plans
derive requirements from their actual shape. A compiler declares its support
and fails before statement construction on a missing requirement or foreign
plan.

`PostgreSql17Compiler` is stateless and reentrant. It quotes every identifier
segment, doubles embedded quotes, rejects segments above PostgreSQL's 63-byte
profile bound, emits the accepted canonical grammar without comments,
semicolon, or terminal newline, and allocates parameter names from 1 through
65,535 in depth-first left-to-right occurrence order. Each compile call owns a
fresh counter and parameter list.

### Raw SQL coexistence

The existing immutable parameter-bound `DatabaseStatement` remains the
first-class raw-SQL escape hatch. Raw SQL is not wrapped in a plan, parsed,
rewritten, deprecated, or routed through the dialect compiler. Applications
choose the structured path when its bounded grammar fits and the raw path when
provider-specific SQL or unsupported grammar is required.

### Diagnostics and nondisclosure

The neutral package reserves `KGN-SQL-1001` through `KGN-SQL-1003` for invalid
plan, unsupported dialect capability, and compilation failures. Messages,
stringification, diagnostic context, external output, and reports expose only
fixed package-owned categories and bounded structural tokens. They exclude SQL,
identifiers, aliases, parameter names and values, object names, provider/native
detail, paths, and traces.

### Evidence

Focused unit, maximum PHPStan, cache-disabled style, architecture, Hantei, and
real PostgreSQL 17.2 acceptance prove the immutable plan shapes, capability
derivation, canonical compiler output, occurrence-ordered binding, reentrancy,
adversarial rejection, raw-SQL coexistence, transaction execution, isolation,
and cleanup. PostgreSQL 17 is the sole compiler and provider proof profile; this
is not a universal SQL-portability claim.

## Consequences

- Plans can be inspected without parsing SQL and cannot execute themselves.
- Destructive all-row intent is reviewable in the source plan.
- PostgreSQL-specific compilation remains replaceable without entering the
  neutral execution package.
- The bounded grammar deliberately requires raw SQL for unsupported constructs.
- Two additional packages increase the current production topology to 23.

## Rejected alternatives

- A generic associative-array builder or one stringly typed builder.
- Runtime service location, ambient connections, reflection, discovery,
  autowiring, or compiler I/O.
- Raw fragments, literal expressions, interpolation helpers, parsers, or
  identifier string shortcuts.
- Inferring all-row mutation from an absent predicate.
- Treating PostgreSQL identifier truncation as valid compilation.
- Array, iterable, variadic, tagged, or automatic multibinding semantics.
- Schema, migration, mapper, repository, ORM, Active Record, lazy-loading,
  proxy, retry, pooling, logging, telemetry, CLI, or deployment behavior.

## Non-goals

This decision does not implement joins, subqueries, CTEs, unions, grouping,
aggregates, windows, DISTINCT, functions, casts, locking, conflicts, schema
discovery, migrations, mapping, repositories, composition integration, another
provider, production startup, Stable API, or Phase 6E.
