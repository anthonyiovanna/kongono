# Kongono persistence-minimal skeleton

This copy-once example shows the Phase 7G generated persistence application
profile. It keeps one account aggregate and one bounded, ordered profile
relationship deliberately small so every source and capability remains
inspectable.

Run `composer app:build` after installing the skeleton dependencies. The build
derives and validates mapping and persistence evidence without executing it,
then loads generated persistence state and aggregate repositories before data
access and Composition. `composer app:check` requires those exact outputs and
writes nothing. Both commands print all seven content identities.

`Runtime::start()` accepts a host-supplied `DatabaseConnector`; the skeleton
never reads credentials. Each generated operation owns its session,
repositories, adapters, and typed `PersistenceOperations` root. Closing an
operation releases them in generated reverse construction order.

The root explicitly creates, flushes, and retires a one-entity persistence
context. Generated repositories receive caller-supplied database, load,
cascade, and change capabilities. No container, SQL, provider handle, mutable
metadata, or session escapes the typed root.

The example does not automatically begin, commit, or roll back transactions.
A host that requires atomic work must choose and own transaction authority
explicitly; the integration package does not acquire it. There is no retry,
pooling, migration, listener, deployment entrypoint, or environment-based
provider selection.
