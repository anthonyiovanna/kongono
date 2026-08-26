# Persistence application integration

Phase 7G connects compile-time persistence evidence to the existing generated
application boundary. The integration is optional and Experimental; neutral
persistence contracts remain usable without it.

The build boundary has four ordered stages:

1. Prepare mapping and generated data-access bytes without executing them.
2. Compile, publish or check, and non-executingly validate persistence artifact,
   generated state, and generated aggregate repositories.
3. Load persistence state and repositories, then reverify and load data access.
4. Build or check Composition once and reverify every captured snapshot.

This order preserves mapping's collision invariant. Persistence compilation
reproduces current mapping evidence before generated mapping symbols exist in
the process. Composition then sees the exact declaring files for all generated
and application-owned services.

The returned current build is detached evidence: paths, hashes, artifact bytes,
loaded metadata, and the current Composition build. It does not expose a
connector, executor, session, transaction, repository selector, provider, or
container.

At runtime the host supplies one explicit connector. Each operation constructs
its own session, generated repositories, application adapters, and typed root.
No operation shares these objects or a persistence context with another.
Generated Composition performs declared reverse cleanup; persistence contexts
are explicitly retired by application code.

The host alone decides whether and how to use a transaction. Neither the
integration package nor generated repositories begin, commit, roll back, retry,
or reconnect automatically.

