# Kongono Persistence Application

Experimental outer integration for exact-current persistence evidence and
generated Composition. The workflow prepares generated data access without
executing it, derives and loads generated persistence state and aggregate
repositories, then loads the prepared data access. Only after both exact
generated sets are loaded does it invoke the existing Composition workflow.

The package selects no provider, connector, transaction, retry, migration,
repository, or persistence-context policy. Runtime work continues through the
existing generated data-application lifecycle with explicit operation cleanup.

See the [persistence application workflow](../../docs/engineering/persistence-application-workflow.md).
