# Kongono Observability Build

`kongono/observability-build` compiles one application-owned
`kongono.observability.json` source into deterministic, content-addressed
configuration evidence for Kongono's supported OpenTelemetry bridge profile.
The package is Experimental.

The source selects only the fixed profile, three bounded resource values, and
the bounded queue, batch, and timeout policy. It cannot select an endpoint,
credential, transport, provider, sampler, signal schema, or authorization
policy. Those collaborators and decisions remain explicit at the application
boundary.

`ObservabilityBuildWorkflow::inspect()` computes bounded identities without
writing. `build()` explicitly publishes one canonical artifact into an existing
strictly contained output directory, then proves it current. `check()` performs
the same exact-current proof without mutation. Publication is content-addressed,
initial-only, idempotent for identical bytes, and no-clobber for conflicts.

A `CurrentObservabilityConfiguration` is an immutable process input. It does
not reload the source or artifact after admission, perform network access, or
register global state. A source or implementation change requires another
explicit build, check, and application start.

Generated artifacts are derived evidence and must not be committed as package
source. Applications own their configuration and output directory.

Phase 10H externally exercises these public build, inspect, check, idempotence,
no-clobber, stale/tamper, containment, and cleanup contracts through a fresh
temporary copied application. That root-owned acceptance evidence does not
change this package, grant runtime authority to its artifact, or introduce an
endpoint, credential, exporter, provider, backend, discovery, or network path.
