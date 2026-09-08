# Phase 15 Roadmap: Governed Outbound HTTP and Network Egress

## Status and entry state

Phase 15A is Accepted documentation architecture only under
[ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md).
Phase 14 is fully closed at checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`, with 75 production packages,
289 local production dependency edges, and an acyclic package graph.

Phase 15B is Accepted under
[ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md).
It adds only the optional Experimental inert contract package. Phase 15C is
Accepted under [ADR 0157](../adr/0157-deterministic-network-egress-policy-artifact.md)
and adds only the optional Experimental deterministic inert offline policy
artifact package. Phase 15D is Accepted under [ADR
0158](../adr/0158-bounded-provider-neutral-network-egress-runtime.md) and adds
only the optional Experimental bounded one-operation runtime over explicit
caller-owned collaborators. Phase 15E is Accepted under [ADR
0159](../adr/0159-explicit-curl-http11-egress-transport-profile.md) and adds only
the optional Experimental cURL HTTP/1.1 adapter. Phase 15F is Accepted under
[ADR 0160](../adr/0160-exact-current-single-profile-network-egress-application-workflow.md)
and adds only the optional Experimental exact-current single-profile application
workflow. Phase 15G is Accepted under
[ADR 0161](../adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
and adds only the optional Experimental offline visibility-safe inspection and
bounded-evidence leaf. The current graph is 81 packages / 317 local production
edges / acyclic and PHP inventories are 1,514 / 768 / 84. Phase 15H is Accepted
under [ADR 0162](../adr/0162-phase-15-external-network-egress-acceptance-and-checkpoint.md),
adds only bounded local external acceptance and one exact local checkpoint,
and closes Phase 15. Phase 16 requires separately authenticated authority.

## Fixed rows

| Row | Accepted scope | Explicit exclusions |
| --- | --- | --- |
| **15A — Architecture and roadmap** | Accept ADR 0155, the outbound HTTP and network-egress guide, and the fixed roadmap; reconcile current documentation after the Phase 14 checkpoint. Documentation only. | No PHP, package, API, schema, dependency, transport, DNS, TLS, credential, network, command, or Phase 15B implementation. |
| **15B — Neutral egress contract foundation** | Accepted optional Experimental versioned neutral values for operation, attempt, and receipt identities; canonical origin; authority and policy references; budgets; cancellation/lifecycle evidence; transport capabilities; transfer, outcomes, receipts, results, and failures while reusing PSR-7 message semantics without reading body streams. | No DNS, socket, HTTP client, TLS implementation, credential source, policy compiler, runtime, adapter, redirect/retry engine, provider, registry, command, or network effect. |
| **15C — Deterministic egress policy artifact** | Accepted exact application-owned typed policy source, canonical artifact, validation, content-addressed publication, and caller-relative currentness. | No network, DNS, connection, credential, proxy discovery, runtime authorization, mutable registry, provider selection, or execution. |
| **15D — Bounded egress runtime** | Accepted optional Experimental one-operation coordinator over explicit caller-owned resolver, transport, authority-currentness, cancellation, clock, and exact-current policy evidence, with selected-address binding, limits, pull-based backpressure, uncertainty, and cleanup. | No concrete transport library, ambient proxy/credential lookup, cookies, automatic redirect/retry/fallback, provider SDK, background worker, or application workflow. |
| **15E — First-party transport and interoperability profile** | Accepted optional Experimental `kongono/network-egress-curl` HTTP/1.1 adapter with exact selected-address binding, explicit in-memory CA trust, bounded PSR-7 mapping, one-shot lifecycle, and numeric nondisclosing failure mapping. | No default/global client, environment discovery, disabled TLS verification, unconstrained destinations, provider semantics, automatic credentials, hidden retry, public compatibility, or production-readiness claim. |
| **15F — Exact-current application workflow** | Accepted optional Experimental canonical profile, deterministic generated wiring, exact-current validation, content-addressed no-clobber publication, explicit contained loading, transport-capability preflight, and one terminal application operation delegated to a fresh Phase 15D runtime. | No registry, auto-selection, hot reload, service locator, general SDK client, provider default, background work, deployment manager, resolver, credential source, or hidden collaborator. |
| **15G — Visibility-safe inspection and bounded evidence** | Accepted optional Experimental offline read-only inspection of one explicit exact supplied profile/wiring value and an optional exactly paired detached operation/result, with bounded Graph, Observability, Security Audit, and role-addressed aggregate evidence. | No network probe, DNS lookup, generated-code loading, live endpoint claim, credential access, replay, repair, authorization, recording, role execution, certification, or autonomous remediation. |
| **15H — External acceptance and checkpoint** | Accepted root-owned deterministic local external harness proving the accepted Phase 15 boundaries and one exact local checkpoint. | No public Internet dependency, production endpoint, real credential, provider compatibility claim, push, tag, release, Stable/1.0 claim, or Phase 16 behavior. |

## Dependency flow

```text
15A architecture
  -> 15B neutral contracts
  -> 15C deterministic policy evidence
  -> 15D bounded runtime
  -> 15E explicit transport/interoperability profile
  -> 15F exact-current application workflow
  -> 15G visibility-safe inspection
  -> 15H external acceptance and checkpoint
```

Arrows express dependency order, never execution authority.

## Roadmap rules

- Each row requires a separately authenticated directive.
- Correction rows may be inserted without silently reordering scope.
- Phase 15 ends at 15H.
- Phase 16 does not begin automatically.
- No row carries a target date, release promise, benchmark win, vendor promise,
  or production-readiness claim.
- A concrete model-provider adapter remains outside Phase 15.
- Accepted evidence in one row does not authorize the next row or a network
  effect.

## Persistent invariants

Across every row:

- application-owned current authority, policy, credentials, endpoint
  selection, transport selection, and external-service semantics remain
  explicit;
- URI, canonical origin, policy-permitted origin, resolution evidence,
  selected address, attempt, response origin, and receipt remain distinct;
- resolution is bound to the selected connection address, redirects are fully
  revalidated, and SSRF and DNS-rebinding cases fail closed;
- TLS verification cannot be disabled by request data or hidden state;
- proxies, cookies, credentials, redirects, retries, and pools are never
  ambient defaults;
- budgets, streaming, backpressure, cancellation, monotonic time, uncertain
  effects, concurrency, cleanup, and retirement remain explicit;
- PSR-7 message reuse does not collapse the egress control plane into PSR-18;
- diagnostics and operational evidence remain bounded and nondisclosing;
- Graph, Security, Observability, Extension, Agent, model-invocation,
  developer-tool, generated-artifact, and verifier evidence never becomes
  network authority; and
- Kongono remains usable without networking, AI, a provider, credentials, an
  account, or Internet access.

## Current status

Rows 15A through 15H are Accepted. Row 15A is documentation architecture and
reconciliation. Row 15B is only the inert neutral contract package. Row 15C is
only deterministic inert offline policy source/artifact evidence and optional
content-addressed publication. Row 15D is the bounded runtime over explicit
caller-owned collaborators. Row 15E is only the explicit bounded cURL HTTP/1.1
transport adapter. Row 15F is only the exact-current single-profile application
workflow over supplied collaborators. Row 15G is only offline deterministic
inspection and bounded inert evidence over exact supplied values. Row 15H adds
the root-owned bounded local external harness and exact local checkpoint. Its
loopback evidence is not public compatibility, production certification,
deployment truth, or release status. Phase 15 is closed.
No first-party resolver, credential provider, proxy discovery, cookie jar,
provider adapter, or automatic network authority exists.

## Correction semantics

A correction may clarify or repair only its explicitly authenticated boundary.
It cannot silently promote a planned row, reorder dependencies, select a
transport or provider, broaden authority, weaken a gate, or begin a later
phase. Any such decision requires new architect authority.

## Related material

- [ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
- [ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md)
- [ADR 0157](../adr/0157-deterministic-network-egress-policy-artifact.md)
- [ADR 0158](../adr/0158-bounded-provider-neutral-network-egress-runtime.md)
- [ADR 0159](../adr/0159-explicit-curl-http11-egress-transport-profile.md)
- [ADR 0160](../adr/0160-exact-current-single-profile-network-egress-application-workflow.md)
- [ADR 0161](../adr/0161-visibility-safe-network-egress-inspection-and-bounded-evidence.md)
- [ADR 0162](../adr/0162-phase-15-external-network-egress-acceptance-and-checkpoint.md)
- [Network-egress contract foundation](network-egress-contract-foundation.md)
- [Network-egress policy artifact](network-egress-policy-artifact.md)
- [Network-egress runtime](network-egress-runtime.md)
- [Network-egress cURL transport](network-egress-curl-transport.md)
- [Network-egress application workflow](network-egress-application-workflow.md)
- [Network-egress inspection and bounded evidence](network-egress-inspection-and-evidence.md)
- [Phase 15 external acceptance](phase-15-external-acceptance.md)
- [Outbound HTTP and network egress](outbound-http-and-network-egress.md)
- [Phase 14 roadmap](phase-14-roadmap.md)
- [Phase 14 external acceptance](phase-14-external-acceptance.md)
- [HTTP message model](http-message-model.md)
- [Provider-neutral model invocation](provider-neutral-model-invocation.md)

## Phase 15F-H application, inspection, and checkpoint status

Phase 15F accepts ADR 0160 and the optional Experimental
`kongono/network-egress-application` boundary. Its canonical profile and
generated wiring remain inert evidence; its loaded application validates exact
currentness and required transport capabilities before delegating one operation
to a fresh Phase 15D runtime. Phase 15G accepts ADR 0161 and the optional
Experimental `kongono/network-egress-inspection` leaf. It revalidates one
exact supplied profile/wiring value and can summarize one exactly paired
detached operation/result without loading generated PHP, network access,
authority evaluation, recording, replay, repair, or developer-role execution.
Phase 15H accepts ADR 0162 and adds only the root-owned bounded local external
acceptance harness and exact local checkpoint. The topology is 81 packages /
317 local edges / acyclic, with PHP inventories 1,514 / 768 / 84. Phase 15 is
closed; Phase 16 requires separate authority.
