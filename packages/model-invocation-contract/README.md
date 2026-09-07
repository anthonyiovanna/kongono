# Kongono Model Invocation Contract

Phase 14D separately adds the bounded
[`kongono/model-invocation-runtime`](../model-invocation-runtime/README.md) over
these inert values and one exact-current plan. It does not widen this package's
authority or make normalized evidence externally true.

Phase 14E separately adds the optional
[`kongono/model-invocation-agent-bridge`](../model-invocation-agent-bridge/README.md).
It validates one eligible normalized action suggestion and maps it through
trusted application configuration into inert proposal evidence. It does not
implement a proposal port or turn model arguments into capability inputs.

Phase 14F separately adds the optional
[`kongono/model-invocation-application`](../model-invocation-application/README.md).
It validates one exact profile, loads deterministic inert wiring, and delegates
one terminal operation to the Phase 14D runtime without widening this package's
authority or calling the Phase 14E bridge.

Phase 14G separately adds the optional
[`kongono/model-invocation-inspection`](../model-invocation-inspection/README.md).
It consumes contract values only as explicit bounded evidence, revalidates an
exact supplied profile/wiring pair, and never invokes a provider or loads
generated PHP.

`kongono/model-invocation-contract` is the optional Experimental Phase 14B
package for neutral, versioned model-invocation values. It depends only on API
Metadata, Diagnostics, and PHP. Its current in-repository consumers are the
separate Build, Runtime, Agent Bridge, Application, and Inspection packages.

The package contains contract version 1; provider and model identities; a
canonical declared capability set; one application-owned profile reference;
bounded inputs, requests, normalized results, inert action suggestions, stream
events, usage and cost claims, lifecycle vocabulary, receipts, visibility, and
closed non-disclosing failures.

These values are evidence, not behavior. Construction performs no provider or
model call, network access, credential lookup, transport, adapter execution,
retry, fallback, serialization, discovery, publication, persistence,
authorization, capability effect, Agent bridge, Graph integration, telemetry,
or role execution. An action suggestion is not a proposal, grant, callable, or
proof of effect. Usage, cost, and receipt values are bounded claims rather than
tokenization, billing, settlement, delivery, durability, or external truth.

Identities use 1-128 bytes of lowercase ASCII matching
`[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*`. Input, result, action-argument, and stream
payload values have explicit raw-byte bounds. Diagnostics expose only closed
Safe labels and counts; rejected identities, content, arguments, provider or
model strings, currency or amount text, paths, secrets, and throwable messages
are never copied into package-produced evidence.

`ExceptionInterface` is Public. Immutable values, backed enums, and
`InvalidModelInvocationContractException` are Experimental. `ContractGuard`,
`ModelInvocationContractDiagnostic`, and the abstract base exception are
Internal. No API is Stable.

Phase 14C separately adds deterministic invocation-plan evidence through the
optional [`kongono/model-invocation-build`](../model-invocation-build/README.md)
package. Phases 14D, 14F, and 14G implement their separate runtime,
application, and inspection boundaries; none exists here. See [ADR
0148](../../docs/adr/0148-neutral-versioned-model-invocation-contract-foundation.md),
the [Model Invocation Contract Foundation
guide](../../docs/architecture/model-invocation-contract-foundation.md), and
the [fixed roadmap](../../docs/architecture/phase-14-roadmap.md).
