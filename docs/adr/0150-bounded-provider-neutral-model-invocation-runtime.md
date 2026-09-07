# ADR 0150: Bounded Provider-Neutral Model-Invocation Runtime

- Status: Accepted
- Date: 2026-09-01
- Stability: Experimental implementation contract

## Context

Phase 14B defines normalized invocation evidence and Phase 14C binds one exact
request, adapter label, and finite plan budget without execution. An optional
runtime is needed to execute that evidence without selecting a provider,
discovering credentials, weakening Phase 13 authority, or hiding lifecycle
uncertainty.

## Decision

Create `kongono/model-invocation-runtime`. The application constructs and
supplies one adapter directly. Before any attempt, the runtime validates the
exact supplied Phase 14C source/artifact pair and requires exact adapter
identity equality. Rejection records zero attempts; accepted execution makes
exactly one attempt and opens at most one synchronous pull session.

The pull protocol is the backpressure boundary. Before and after each pull the
runtime checks caller-owned cancellation and nonnegative monotonic time. It
accepts only normalized, contiguous, request-bound events and results; meters
duration, event count, output, retained content, cumulative usage, and exact
decimal cost; and requires a final `null` after terminal evidence. It never
executes an action suggestion.

Every opened session receives exactly one close attempt. Returned success,
normalized failure, and cancellation evidence is retired and cleanup-complete.
Operation failure is preserved as the exact previous throwable. Cleanup-only
and combined failures receive `Uncertain` receipts and preserve the exact close
throwables separately. Diagnostic context contains only closed finite labels
and bounded counts.

The runtime owns operation-local state only. It supports sequential reuse in a
persistent process, rejects reentrancy, and clears state after every return or
throw.

## Consequences

The production graph is 72 packages and 269 local edges, acyclic. PHP inventory
is 1,294 production files, 660 package-test files, and 76 root-test files. No
existing production package depends on the new runtime.

The runtime contains no concrete provider adapter or SDK, credential access,
registry, discovery, auto-selection, retry, fallback, Agent bridge, authority
decision, capability execution, application workflow, command, telemetry,
recording, background work, retained session, or provider-state claim. Plans,
results, action suggestions, usage, cost, and receipts remain evidence.

## Rejected alternatives

- Ambient registries or provider defaults would hide application policy.
- Push callbacks or background production would weaken deterministic
  backpressure and cleanup.
- SDK retry or fallback would violate the one-attempt receipt contract.
- Floating-point cost comparison would lose exact decimal evidence.
- Converting normalized action suggestions into effects would bypass the
  separately governed Phase 13 boundary.
