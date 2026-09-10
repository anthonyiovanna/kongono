# Phase 16 Roadmap: External-Service Binding and Secret Leases

## Status and entry state

Phase 16A accepts [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md)
and the [developer guide](external-service-binding-and-secret-leases.md) as
documentation architecture only. Phase 15A-H remain Accepted and Phase 15 is
closed at commit `b4cb57ffa01eea130a693daee0be605ae7f0dda5`.

Phase 16B accepts [ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and the optional Experimental `kongono/external-service-contract` package.
Phase 16C accepts [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and the optional Experimental `kongono/external-service-binding-build` package.
Phase 16D accepts [ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md)
and the optional Experimental `kongono/external-service-resolver-system`
package. Phase 16E accepts [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md) and the optional
Experimental `kongono/external-service-credential-runtime` package. Phase 16F
accepts [ADR
0168](../adr/0168-exact-current-external-service-binding-application.md) and
the optional Experimental `kongono/external-service-application` package.
Phase 16G accepts [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and the optional Experimental `kongono/external-service-inspection` package.
Phase 16H accepts [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md), adds
the root-owned deterministic local external-service acceptance harness and one
exact checkpoint, and closes Phase 16. Phase 17 requires separate authenticated
authority.

Current topology is 87 packages / 355 local production edges / acyclic. PHP
inventories are 1,647 production, 851 package-test, and 88 root-test files.

## Fixed rows

| Row | Required boundary | Required exclusions |
| --- | --- | --- |
| **16A - Architecture and fixed roadmap** | Accepted documentation-only external-service binding, endpoint-resolution, and operation-scoped credential-lease doctrine. | No package, PHP API, schema, secret, resolver, credential source, provider adapter, command, network activity, or runtime behavior. |
| **16B - Neutral binding and credential contracts** | Accepted optional Experimental immutable service-binding, endpoint, credential-reference, lease-port, resolution-port, lifecycle, result, and bounded diagnostic contracts in `kongono/external-service-contract`; 27 production declarations and 15 tests reuse Phase 15 origin, capability, resolver, and resolution evidence. | No artifact compiler, secret material in values, concrete resolver/source, provider SDK, network, environment lookup, runtime orchestration, command, or application wiring. |
| **16C - Deterministic secret-free binding artifact** | Accepted optional Experimental exact typed source, canonical versioned artifact, structural or explicit-source-current validation, provenance, deterministic ordinary PHP, and independent path-safe content-addressed atomic no-clobber publication in `kongono/external-service-binding-build`. Generated PHP is validated as inert bytes and never loaded. | No secret retrieval or material, DNS, network, provider mapping, resolver or credential runtime, application workflow, ambient discovery, command execution, generated-code loading, or mutable registry. |
| **16D - Explicit bounded system resolver adapter** | Accepted optional Experimental first-party `ext-sockets` resolver leaf using one explicit profile, one bounded lookup, complete fail-closed candidate validation, deterministic ordering, Phase 15 classification, and exact selected-member evidence. `SOL_TCP` is required and `ai_family` is omitted for the fixed unspecified-family PHP-binding policy. | No network authority, credential access, provider semantics, ambient private-network exception, hidden cache, connection, retry, fallback, TTL/DNSSEC/provenance/deadline claim, or DNS-security certification. |
| **16E - Operation-scoped credential lease runtime** | Accepted optional Experimental explicit caller-supplied source coordination, purpose/reference/state binding, cooperative finite lifetime checks, typed outcomes, operation isolation, borrowed-material delegation, and deterministic retirement. | No bundled secret store, default environment or vault source, provider SDK, network authority, token refresh, background renewal, reliable-zeroization or preemptive-deadline claim, registry, or ambient current secret. |
| **16F - Exact-current external-service binding application** | Accepted optional Experimental exact-current binding/profile validation and one-use composition of explicit credential, resolver, transport, authority, cancellation, clocks, request factory, and accepted Phase 15 application for one terminal operation. | No universal service client, provider default, credential source, resolver or transport implementation, service locator, dynamic discovery, automatic retry/fallback, background work, retained session, command, inspection, or deployment manager. |
| **16G - Visibility-safe inspection and verification** | Accepted optional Experimental offline read-only exact-current binding inspection with bounded role-aware evidence and optional detached completed-result pairing. | No secret retrieval, DNS, networking, generated-code execution, authority evaluation, provider invocation, recording, replay, repair, live probe, or certification. |
| **16H - External acceptance and checkpoint** | Accepted root-owned deterministic local external-process acceptance of the separately implemented Phase 16 boundaries and one exact local checkpoint. | No public endpoint, real credential, production DNS dependency, provider compatibility claim, push, tag, release, Stable/1.0 claim, production-readiness claim, or Phase 17 behavior. |

The order is fixed. Evidence accepted for one row does not authorize the next.

## Dependency direction

```text
application-owned service intent and current authority
  -> exact-current secret-free service-binding evidence
  -> explicit endpoint selection and credential reference
  -> operation-scoped resolver and credential-source ports
  -> bounded resolution evidence plus one credential lease
  -> existing Phase 15 exact-current egress application
  -> optional service/provider adapter with service-specific semantics
  -> bounded result and receipts
  -> deterministic reverse cleanup and lease retirement
```

The flow separates binding evidence, endpoint selection, resolution evidence,
selected address, transport, credential lease, and provider-specific semantics.
Each arrow is permitted dependency direction after validation, not authority.

## Evidence and authority

Service/profile names, endpoint constraints, credential references, retrieved
lease metadata, DNS answers, selected addresses, certificates, receipts,
deterministic artifacts, generated PHP, Graph facts, model or Agent evidence,
inspection, and verifier output remain non-authoritative. Credential access and
network access are separate current permissions.

Only explicit current application-owned authorization and the accepted Phase
15 policy and authority gates may permit an external attempt. Secrets remain
application-owned and cannot enter deterministic evidence, generated PHP,
developer output, logs, diagnostics, inspection, or receipts.

## Package and API-status direction

If separately authorized, new Phase 16 PHP surfaces begin Experimental.
Validators, codecs, loaders, resolver mechanisms, lease internals, adapters,
and lifecycle helpers remain Internal by default. A Public marker or exception
contract requires a deliberate compatibility decision under the
[API-status doctrine](../engineering/api-status.md).

Neutral lower packages do not depend on a credential-provider SDK, DNS
library, service/provider SDK, HTTP client, framework container, Agent or model
runtime, command package, Chie, Hantei, or Oshie. Provider-specific behavior
remains in optional outer adapters.

## Developer-experience constraints

The planned safe path uses one immutable secret-free source, one later
deterministic build/check command, one exact credential reference, explicit
resolver and credential-source adapters, one bounded operation, typed outcomes,
and deterministic cleanup. It uses typed values and ordinary generated PHP
rather than reflection, service location, hidden autowiring, dynamic proxies,
annotations, global state, or stringly typed bags.

Applications that do not access external services do not adopt the layer or
carry provider SDKs, resolvers, credential sources, or network dependencies.

## Deterministic testing expectations

Later rows use deterministic fake credential sources and resolvers for
contract, change, rotation, currentness, selected-address binding, lifecycle,
isolation, cleanup, and nondisclosure tests. External acceptance is local only,
uses no real credential or public endpoint, and makes no production-DNS or
provider-compatibility claim.

## Exit conditions

- **16A:** ADR 0163, the guide, and this fixed roadmap are accepted and all
  existing documentation is reconciled without runtime or package changes.
- **16B:** neutral Experimental contracts are complete, deterministic,
  secret-free, and independently validated without an implementation adapter.
- **16C:** one exact secret-free source can produce and validate canonical
  content-addressed evidence and ordinary generated PHP without retrieving a
  secret or performing network work.
- **16D:** one explicit bounded system-resolver leaf returns complete classified
  evidence suitable for exact selected-address binding without granting
  authority or connecting.
- **16E:** one explicitly supplied credential source can produce one
  purpose-bound operation lease with finite lifecycle and deterministic
  retirement, with no bundled source or zeroization overclaim.
- **16F:** one exact-current application profile composes the separately
  accepted boundaries for one terminal operation without discovery, retry,
  fallback, background work, or retained sessions.
- **16G:** offline inspection validates exact supplied evidence and optional
  completed-result pairing without retrieving a secret, resolving, networking,
  replaying, recording, repairing, or certifying.
- **16H:** the root-owned local external acceptance harness proves the
  separately implemented Phase 16 boundaries and a separately authorized exact
  local checkpoint closes Phase 16.

Each exit requires its own authenticated directive and completion evidence.
Phase 16H alone checkpoints and closes Phase 16 under its separate authority.

## Current and planned status

Phase 16A through Phase 16H are Accepted, and Phase 16 is closed at one exact
local checkpoint. No later row is implied by this status, and no real
credential, bundled credential source, provider adapter or SDK, ambient
production DNS, public endpoint, compatibility, Stable/1.0, release,
deployment, or production-readiness claim is selected.

Phase 17 does not begin automatically and remains unauthorized.

## Related material

- [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md)
- [ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
- [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
- [ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md)
- [ADR 0167](../adr/0167-operation-scoped-credential-lease-runtime.md)
- [ADR 0168](../adr/0168-exact-current-external-service-binding-application.md)
- [ADR 0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
- [ADR 0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md)
- [Phase 16 external acceptance](phase-16-external-acceptance.md)
- [External-service binding and secret leases](external-service-binding-and-secret-leases.md)
- [External-service inspection and evidence](external-service-inspection-and-evidence.md)
- [Phase 15 roadmap](phase-15-roadmap.md)
- [Outbound HTTP and governed network egress](outbound-http-and-network-egress.md)
- [Security policy](../../SECURITY.md)
