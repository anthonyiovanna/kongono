# Kongono Network-Egress Inspection

This optional Experimental package transforms one explicit exact supplied
network-egress application profile and generated-wiring value into bounded,
visibility-safe evidence. It can also summarize one exact corresponding
operation and detached completed runtime result. Evidence remains caller-relative
and does not establish live state, external authority, endpoint state, or effect.

## Use when

Use this package when trusted application code already owns a Phase 15F profile
and generated wiring and needs deterministic, offline inspection evidence. Supply
the operation and runtime result together only when bounded terminal evidence is
needed. All six terminal outcomes remain exact, including uncertain-effect
outcomes.

## Do not use when

Do not use inspection to authorize an operation, discover an endpoint, resolve
DNS, probe TLS or HTTP, load generated PHP, start the application, call the
runtime, read a request or response body, retry, replay, repair, record, export,
or certify an effect. The package contains no command, provider, credential
source, resolver, transport, recorder, or role implementation.

## Contract

`NetworkEgressInspector` reuses the Phase 15F current validator and then
independently compares the profile, policy, artifact, capability, implementation,
namespace, class, canonical-byte, and generated-wiring bindings. Optional runtime
evidence must bind the operation policy reference, allowed origin, exact budget,
operation identity, terminal receipt shape, transfer, cancellation, protocol,
capability evidence, and response-or-failure presence.

`NetworkEgressInspection` emits canonical version-1 JSON containing only
Protected lowercase SHA-256 bindings, closed tokens, Safe bounded counts, and
fixed limitations. Raw identities, origins, addresses, requests, headers, bodies,
credentials, response/failure objects, paths, generated PHP, and caller text are
omitted.

The Graph projector accepts only an explicit same-role Phase 11G Safe aggregate
view and states that the network-egress-to-Graph relationship is not established.
The Observability and Security Audit projectors construct values only. The
developer-role enum selects Chie, Hantei, or Oshie evidence purposes without
executing or depending on those roles. The independent verifier reconstructs the
permitted view shape without calling the deriver or repairing a candidate.

## Lifecycle and side effects

The package is stateless and retains no value across calls. Calls are synchronous,
bounded by supplied in-memory evidence, safe for concurrent independent use, and
perform no filesystem, environment, clock, randomness, process, DNS, socket,
network, recording, or export operation. Trusted generated and in-process PHP is
not a sandbox; the inspector does not load generated PHP.

## Stability

Every consumer-facing type is Experimental. Canonicalization and diagnostic
implementation details are Internal. See the [architecture guide](../../docs/architecture/network-egress-inspection-and-evidence.md)
and [ADR 0161](../../docs/adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md).
