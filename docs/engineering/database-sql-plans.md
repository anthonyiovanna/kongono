# Database SQL Plan Engineering Guide

## Construction contract

Use the typed values in `Kongono\DatabaseSql`. Supply `SqlIdentifier` objects
for exact identifier segments and `SqlQualifiedName` for one through three
segments. Do not pass dotted strings expecting qualification: a dot inside one
identifier remains data.

Select requires at least one explicit projection. Insert and Update require at
least one unique assignment. Returning aliases, when present, must be unique.
AND, OR, and IN lists are non-empty. Comparison predicates reject a null
`DatabaseValue`; use the explicit null predicate instead.

Update and Delete require `SqlMutationScope::forPredicate(...)` or the explicit
`SqlMutationScope::allRows()`. Treat all-row intent as a destructive review
point. There is no default scope and no builder method that silently removes a
predicate.

## Compilation contract

Call `PostgreSql17Compiler::compile()` only after constructing a valid plan.
Compilation is pure and returns an immutable `DatabaseStatement`; execution is
a separate explicit session or transaction operation. The compiler:

- checks the plan's required capabilities;
- rejects foreign plan implementations;
- checks every PostgreSQL identifier segment against the 63-byte profile;
- pre-counts all value occurrences and rejects more than 65,535;
- allocates one ordered parameter for every occurrence, including limit and
  offset; and
- produces no partial statement on failure.

Compiler instances are stateless and safe to reuse. Do not add caches, static
counters, ambient state, filesystem reads, provider access, or execution to a
dialect compiler.

## Raw SQL contract

Direct `DatabaseStatement` construction remains supported. Prefer it when the
operation needs grammar outside version 1. Keep the SQL explicit, bind every
value with `DatabaseParameter`, and declare the correct `Rows` or `Completion`
expectation. Do not introduce a RawPlan, arbitrary expression, fragment wrapper,
interpolation helper, or parser to bridge the two paths.

## Tests

Package tests cover identifier boundaries, detached collections, plan shapes,
mutation intent, predicate and capability cases, diagnostics, exact compiler
output, binding order, compiler reuse, fibers, identifier-profile rejection,
foreign plans, and parameter limits. Architecture tests lock dependency
direction, API status, package inventory, native isolation, and the absence of
later-phase declarations.

Run focused checks with the root configuration, then canonical quality:

```powershell
vendor\bin\phpunit --configuration phpunit.xml.dist packages/database-sql/tests packages/database-sql-pgsql/tests
php -d memory_limit=512M vendor/bin/phpstan analyse packages/database-sql/src packages/database-sql/tests packages/database-sql-pgsql/src packages/database-sql-pgsql/tests --configuration=phpstan.neon.dist --no-progress
vendor\bin\php-cs-fixer fix packages/database-sql packages/database-sql-pgsql --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no
composer quality
```

The root-owned PostgreSQL proof remains:

```powershell
composer acceptance:phase6 -- "C:\laragon\bin\postgresql\postgresql-17.2-3-windows-x64-binaries\bin"
```

The harness owns one fresh temporary PostgreSQL tree, loopback listener,
ephemeral role, execution cases, and complete cleanup. It is test evidence, not
a product command, deployment surface, or provider startup service.

## Nondisclosure

Rejected SQL, identifiers, aliases, placeholder names, values, object names,
provider/native details, traces, and paths must not enter exception messages,
stringification, diagnostic fields, external output, or reports. Add only
package-owned bounded Safe structural tokens when extending diagnostics.

## Non-goals

Do not add joins, subqueries, arbitrary expressions, schema work, migration execution,
mapping, repositories, ORM, Active Record, lazy loading, proxies, automatic
retry, pooling, composition integration, configuration, logging, telemetry, a
CLI, another provider, or higher-layer behavior in this package boundary. Use
the separate [migration engineering guide](database-migrations.md) for Phase 6E.
