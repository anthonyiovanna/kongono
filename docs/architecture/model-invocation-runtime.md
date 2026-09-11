# Model Invocation Runtime

## Purpose and context

Phase 14D implements the optional Experimental
`kongono/model-invocation-runtime` package above the inert Phase 14B contract
and Phase 14C plan-artifact packages. It executes one exact-current invocation
through one adapter that the application constructs and supplies directly.
It does not select providers or grant authority.

Phase 14E separately adds the optional
[model-result to Agent-proposal bridge](model-result-agent-proposal-bridge.md).
The runtime still returns normalized evidence only. The bridge may reduce one
eligible action suggestion through explicit application-owned mapping into
inert proposal evidence, but it is not an `AgentProposalPort`, does not invoke
a capability, and never treats raw action arguments as capability input.

Phase 14F separately adds the optional
[exact-current application workflow](model-invocation-application-workflow.md).
It creates one Phase 14D request for one bound profile and direct adapter, then
returns this runtime's exact result. It neither changes session/cleanup
ownership nor calls the Phase 14E bridge.

Phase 14G separately adds the optional
[visibility-safe inspection leaf](model-invocation-inspection-and-evidence.md).
It may summarize one completed detached runtime result as bounded aggregate
evidence, but it never replays that result, invokes an adapter, records a
signal, or establishes live provider or application truth.

## Calling contract

The caller supplies an `InvocationPlanSource`, its exact canonical artifact
bytes, one receipt identity, an identity-matching `InvocationAdapter`, an
`InvocationCancellation` probe, and an `InvocationMonotonicClock`. The runtime
validates currentness and adapter binding before an attempt. It then performs
at most one synchronous session and never retries or falls back.

The adapter session is pull-based: each `next()` returns one already-normalized
event or result, or `null`. This gives the runtime backpressure between pulls so
it can validate, meter, and check cancellation and time. A nonstreaming request
returns a result then `null`. A streaming request starts at sequence one,
remains contiguous and mode-correct, and ends with `Completed`, a result, and
`null`, or with `Failed` and `null`.

Illustrative application fake, not a bundled provider adapter:

```php
$adapter = new class ($session) implements InvocationAdapter {
    public function __construct(private InvocationAdapterSession $session) {}
    public function identity(): AdapterIdentity { return new AdapterIdentity('adapter.example'); }
    public function open(InvocationPlanSource $source): InvocationAdapterSession { return $this->session; }
};

$result = (new ModelInvocationRuntime())->run(
    new InvocationRuntimeRequest($source, $artifactBytes, $receiptId, $adapter, $cancellation, $clock),
);
```

## Budgets, cancellation, and evidence

Cancellation is checked before opening and before and after every pull. The
clock is caller-owned, nonnegative, and monotonic; integer elapsed time must not
exceed the explicit plan duration. Output, stream events, retained payload
bytes, cumulative usage snapshots, and exact decimal cost are checked against
the request and plan ceilings. Missing limits reject reported evidence; the
runtime never invents absent usage or cost.

Normalized content, results, action suggestions, usage, cost, and receipts are
evidence only. Action suggestions are never invoked or converted into Phase 13
proposals or capability grants.

## Lifecycle and failure

An opened session is closed exactly once on success, normalized failure,
cancellation, protocol failure, or budget failure. A returned receipt is
cleanup-complete. Close failure produces an `Uncertain` receipt. When both an
operation and close fail, both exact throwable objects remain separately
inspectable while diagnostic messages and context omit caller/provider values.

The runtime supports sequential reuse, rejects reentrancy, and clears all
operation state after completion. It starts no background work and retains no
session between calls.

## Security and non-capabilities

The package has no provider SDK or first-party adapter, credential lookup,
endpoint selection, network transport, filesystem discovery, service locator,
registry, retry/fallback, Agent bridge, authority decision, capability effect,
application workflow inside this package, command, persistence, telemetry, recording, role
execution, or sandbox. Applications remain responsible for provider transport,
credentials, adapter construction, and all external policy.

See [ADR 0150](../adr/0150-bounded-provider-neutral-model-invocation-runtime.md),
the [application workflow](model-invocation-application-workflow.md), the
[inspection guide](model-invocation-inspection-and-evidence.md), and the
[Phase 14 roadmap](phase-14-roadmap.md).

Phase 14H externally exercises one explicit offline fake adapter, bounded
attempts, cancellation, terminal state, primary failure, and deterministic
cleanup. The fake is root test evidence, not a provider integration, and no
retry, fallback, account, credentials, or network is added. See [Phase 14
external acceptance](phase-14-external-acceptance.md).

## Relationship to Phase 15 egress and Phase 16 binding

Phase 14 is closed at checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`. Phase 15A-H accept the
separate general [outbound HTTP and governed network-egress
boundary](outbound-http-and-network-egress.md), and Phase 15 is closed at
commit `b4cb57ffa01eea130a693daee0be605ae7f0dda5`. Invocation plans, results,
action suggestions, receipts, and adapter labels grant no network authority.

Phase 16A-H are Accepted and Phase 16 is closed at commit
`3690aab42e3759d3f42e711703d4f23e6588fa85`. Models cannot choose or
authorize service bindings, credential references or leases, endpoints,
resolution evidence, selected addresses, transport, or provider semantics.

Phase 17A accepts the optional outer boundary in [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md).
Phase 17B accepts [ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
and adds only deterministic offline mapping; decoded evidence alone is not a
`ModelResult`. Phase 17C accepts [ADR
0173](../adr/0173-one-shot-openai-responses-invocation-adapter.md) and implements
this runtime's pull-based `InvocationAdapter` port through one explicit Phase
16 application and one Phase 15 attempt. It emits one normalized result and
then `null`, never retries uncertain or cancelled effects, and retains no
session for another invocation. This local execution path is not live-provider
compatibility. Phase 17D accepts [ADR
0174](../adr/0174-pull-based-openai-responses-sse-streaming.md) and maps one
closed bounded SSE sequence to contiguous `Started`, `ContentDelta`, optional
`Usage`, and terminal events before the normalized result. The neutral runtime
continues to own cancellation checks, sequence validation, evidence budgets,
and final session cleanup. There is no background reader, retry, reconnect,
replay, or immediate first-token guarantee. Phase 17E accepts [ADR
0175](../adr/0175-strict-structured-output-and-inert-openai-function-call-mapping.md)
and reuses the same one-operation runtime for non-streaming structured results
and inert action suggestions. The runtime validates one normalized terminal
result; it does not dispatch the suggestion, create function output, or open a
second operation. Phase 17F-H remain Planned. See the [adapter
guide](openai-responses-provider-adapter.md).
