# Persistence application workflow

Use `PersistenceApplicationBuildWorkflow` when an application has all four
explicit sources: mapping, persistence, aggregate repositories, and
Composition. The output directory must already exist inside the application
root.

`build()` publishes only immutable content-addressed outputs. Existing equal
bytes are idempotent; different bytes at an expected path fail without
overwrite. `check()` performs no writes and requires every exact output.

Data access is prepared in two steps. `prepareBuild()` or `prepareCheck()`
derives, validates, and snapshots mapping and generated PHP without executing
it. After persistence has been validated and loaded, `loadPrepared()` reverifies
the snapshots and uses the existing exact-current loader. Ordinary data-only
workflow methods still perform these two steps consecutively.

Run build and check in fresh processes. Generated loaders deliberately reject
classes already present in the process, including byte-identical classes. Never
catch that collision and continue.

Applications should keep runtime APIs typed. Create one persistence context for
one entity, flush through an explicitly supplied change executor, and retire the
context in `finally`. Pass database, aggregate-load, aggregate-cascade, and
change capabilities explicitly to generated repositories. Do not pass a
container, dynamic class name, repository map, SQL string, credentials, or
provider handle through the application root.

For transaction-protected work, the host must explicitly acquire and control
transaction authority. The Phase 7G workflow provides no automatic transaction,
retry, migration, provider selection, or deployment behavior.

