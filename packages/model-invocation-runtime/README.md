# Kongono Model Invocation Runtime

`kongono/model-invocation-runtime` is the optional Experimental Phase 14D
execution boundary for one exact-current invocation plan and one directly
supplied application adapter. It validates plan bytes before an attempt,
requires the adapter identity to match, and performs one synchronous pull-based
session with explicit cancellation, monotonic time, evidence budgets, receipts,
and deterministic close.

The package accepts only normalized Phase 14B events and results. Streaming
starts at sequence one, remains contiguous and mode-correct, terminates through
`Completed` plus one result or `Failed`, and proves exhaustion with a final
`null` pull. Usage snapshots, cost, output, event count, duration, and retained
content bytes are checked against the explicit Phase 14C source.

Applications own adapter construction, provider transport, credentials, and
external retry policy. The runtime contains no provider SDK or adapter,
registry, discovery, credential lookup, network transport, fallback, command,
Agent bridge, application workflow, recording, telemetry, background work, or
action execution. Normalized results, suggestions, usage, cost, plans, and
receipts remain evidence rather than authority or external truth.

Phase 14E separately adds the optional
[`kongono/model-invocation-agent-bridge`](../model-invocation-agent-bridge/README.md).
It may reduce one eligible action-suggestion result through an explicit trusted
application mapping into inert proposal evidence. It is not a proposal port,
grant, capability invocation, or proof of effect, and raw model arguments never
become capability inputs.

Phase 14F separately adds the optional
[`kongono/model-invocation-application`](../model-invocation-application/README.md).
It constructs one request from one exact profile and direct adapter, delegates
one terminal operation here, and returns this runtime's exact result. It does
not change session/cleanup ownership or call the Phase 14E bridge.

Phase 14G separately adds the optional
[`kongono/model-invocation-inspection`](../model-invocation-inspection/README.md).
It may summarize one completed detached result, but never replays the result,
invokes an adapter, records evidence, or establishes live provider truth.

Every opened session receives exactly one close attempt. Cleanup failure makes
the receipt `Uncertain`; a simultaneous operation failure and cleanup failure
remain separately inspectable without disclosing caller or provider values.
The runtime is safe for sequential reuse in a persistent process, rejects
reentrancy, and retains no completed operation state.

See [ADR 0150](../../docs/adr/0150-bounded-provider-neutral-model-invocation-runtime.md),
the [developer guide](../../docs/architecture/model-invocation-runtime.md), and
the [inspection guide](../../docs/architecture/model-invocation-inspection-and-evidence.md).
