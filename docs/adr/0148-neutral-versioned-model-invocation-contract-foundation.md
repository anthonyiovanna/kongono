# ADR 0148: Neutral Versioned Model-Invocation Contract Foundation

- **Status:** Accepted
- **Date:** 2026-08-31

## Context

[ADR 0147](0147-provider-neutral-model-invocation-and-adapter-boundary.md)
separates neutral model-invocation meaning from provider transport,
credentials, application policy, Agent authority, and effects. Phase 14B needs
one executable foundation for that meaning without prematurely choosing an
adapter, provider SDK, source schema, invocation plan, runtime, or Agent bridge.

Provider/model labels, capabilities, prompts, responses, stream fragments,
action suggestions, usage/cost evidence, and receipts cross disclosure and
authority boundaries. Unbounded strings, mutable arrays, ambient configuration,
or provider-native objects would make those boundaries difficult to inspect and
unsafe for persistent runtimes.

## Decision

Implement the optional Experimental `kongono/model-invocation-contract`
package under `Kongono\ModelInvocationContract\`. It depends only on
`kongono/api-metadata`, `kongono/diagnostics`, and PHP `>=8.5.0 <8.6.0`.
Existing production packages do not depend on it.

The package owns version 1 and immutable provider/model/profile identities;
canonical declared capabilities; bounded messages, requests, result shapes,
inert action suggestions, stream events, usage and cost claims, lifecycle
vocabulary, visibility, and receipts. Every aggregate detaches arrays and
compares exact stored values. Identities and action names use the shared
1-128-byte lowercase ASCII grammar. Text, lists, integers, JSON-object text,
decimal strings, and currency labels use the exact bounds and grammars described
in the [developer guide](../architecture/model-invocation-contract-foundation.md).

Profile capability declaration is not discovery or availability. Request
construction checks only that the chosen result mode and optional streaming are
declared. Results check only their mode/finish-reason shape and declared
usage/cost evidence capability. Stream events remain independent partial
evidence; the package enforces neither continuity nor completion. Lifecycle
states are vocabulary, not a state machine. Usage, cost, and receipt values are
claims, not metering, billing, settlement, replay, non-repudiation, delivery,
durability, or external truth.

An action suggestion is provider-originated inert JSON-object evidence. It is
not an Agent proposal, capability request, grant, callable, tool execution,
authorization, or proof of effect. Any future result-to-proposal bridge remains
a separate Phase 14E concern and cannot bypass Phase 13 admission,
authorization, current-authority checks, budgets, or capability enforcement.

## API and diagnostic boundary

`ExceptionInterface` is Public. All immutable value classes, backed enums, and
`InvalidModelInvocationContractException` are Experimental. `ContractGuard`,
`ModelInvocationContractDiagnostic`, and the abstract
`ModelInvocationContractException` are Internal. No declaration is Stable or
Deprecated.

Nine package-owned diagnostic identifiers cover invalid identity, profile,
input, request, result, stream event, usage/cost evidence, receipt, and exceeded
limits. Context schemas allow only Safe closed values such as violation labels,
counts, positions, limits, result modes, and event kinds. Unknown labels become
`unsupported_violation`. Rejected content, identities, arguments, provider or
model strings, currency or amount text, credentials, secrets, paths, and
throwable messages are not copied into messages, remediation, contexts, or
deterministic exception stringification. A previous throwable may be retained
by identity only.

## Security and persistent-runtime implications

The package performs no I/O and retains no global or per-operation mutable
state. Values are immutable and arrays are detached, so sequential construction
cannot leak caller mutations or earlier rejected content. Intended visibility
does not grant access or redact content. Applications remain responsible for
credential handling, disclosure policy, provider/adapter selection, current
configuration, authorization, budgets, logging, storage, and effects.

The package performs no provider/model call, network access, credential lookup,
transport, adapter execution, retry, fallback, plan build, Agent bridge,
authority decision, capability effect, application workflow, registry,
discovery, command, serialization, publication, persistence, Graph integration,
observability recording, Security Audit recording, or developer-role execution.

## Consequences

Neutral consumers can exchange exact bounded typed values without importing a
provider SDK or authority package. The production graph becomes 70 acyclic
packages and 262 local production edges. Production PHP becomes 1,254 files,
package-test PHP becomes 634 files, and root-test PHP remains 76 files.

The package does not establish profile currentness, provider compatibility,
content safety, billing accuracy, delivery, production readiness, or Stable/1.0
compatibility. Phase 14C deterministic invocation-plan evidence and Phase 14D
adapter execution remain planned and require separate authenticated authority.

## Rejected alternatives

- Provider SDK types in the neutral package were rejected because they couple
  meaning to transport and vendor lifecycle.
- Ambient registries, default providers, credentials, and environment discovery
  were rejected because they hide selection and authority.
- Serializers, mutable builders, and provider-native result bags were rejected
  because they widen public surface and disclosure risk.
- Treating action suggestions as Agent proposals or tool calls was rejected
  because model evidence cannot expand Phase 13 authority.
- Treating usage, cost, or receipts as external truth was rejected because this
  package cannot independently establish those claims.

## Related material

- [Provider-Neutral Model Invocation](../architecture/provider-neutral-model-invocation.md)
- [Model Invocation Contract Foundation](../architecture/model-invocation-contract-foundation.md)
- [Phase 14 roadmap](../architecture/phase-14-roadmap.md)
- [Governed Agent Principals](0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [Agent Principal Runtime](0143-provider-neutral-bounded-agent-principal-runtime.md)
