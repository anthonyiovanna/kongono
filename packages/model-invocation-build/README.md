# Kongono Model Invocation Build

Phase 14D separately adds the bounded
[`kongono/model-invocation-runtime`](../model-invocation-runtime/README.md),
which validates one exact supplied source/artifact pair before one directly
supplied adapter attempt. Phase 14E is the separate inert bridge.

Phase 14F separately adds the exact-current
[`kongono/model-invocation-application`](../model-invocation-application/README.md)
workflow. Its profile factory delegates to this package's current validator;
the build package still performs no application startup or adapter activity.

Phase 14G separately adds the read-only
[`kongono/model-invocation-inspection`](../model-invocation-inspection/README.md)
leaf. It delegates exact-current profile/wiring validation to the application
boundary and never publishes or loads generated PHP.

`kongono/model-invocation-build` is an optional Experimental package that turns
one explicit typed Phase 14B invocation request, one application-selected
adapter identity, and one finite execution budget into deterministic version-1
invocation-plan evidence.

The artifact binds the complete request by SHA-256 but intentionally omits the
message list and raw message content. It includes only the plan identities,
profile labels and declared capabilities, request mode and limits, inert budget,
and source/request digests needed to coordinate a later separately governed
runtime. Those values are Protected application configuration and evidence;
they are not authorization, provider truth, credentials, an adapter instance,
or execution authority.

## Boundaries

- Building and structural/current validation are deterministic, offline, and
  use only the complete typed value or bytes supplied in the call.
- Structural validation proves canonical plan-artifact shape; it cannot
  reconstruct or prove the omitted request messages.
- Current validation rebuilds byte-for-byte from one explicit current source.
- Publication accepts one explicit existing canonical ordinary writable
  directory, uses the content hash in the filename, and is atomic,
  idempotent for identical bytes, and no-clobber for conflicts.
- Diagnostics disclose only closed Safe labels and numeric bounds. Raw
  messages, identities, digests, currency/amount text, paths, and throwable
  messages are excluded from presentation.

The package performs no provider call, adapter acquisition or execution,
credential lookup, network access, retry, fallback, streaming coordination,
Agent bridge, workflow, registry, discovery, command, recording, or runtime
lifecycle. Phases 14D, 14F, and 14G exist only in their separate packages;
Phase 14H requires separate architect authority.

See [the architecture guide](../../docs/architecture/model-invocation-plan-artifact.md)
and [ADR 0149](../../docs/adr/0149-deterministic-model-invocation-plan-artifact.md).
