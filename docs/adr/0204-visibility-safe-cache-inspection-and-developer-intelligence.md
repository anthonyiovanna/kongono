# ADR 0204: Visibility-safe cache inspection and developer intelligence

- Status: Accepted
- Date: 2026-09-17
- Phase: 20G

## Context

The accepted cache platform has neutral contracts, deterministic profile
artifacts, bounded adapters, exact-current operations, and an explicit
application workflow. Developer tooling still needs a way to explain and
verify supplied cache evidence without turning inspection into a backend
browser, a runtime observer, an authority source, or an alternate cache
workflow.

Cache declarations and runtime operations can contain highly sensitive
identity and content material. Profile names, namespaces, codec identities,
keys, key identities, values, content identities, versions, freshness times,
provider details, paths, and arbitrary errors are therefore outside the
inspection surface.

## Decision

Add the optional Experimental `kongono/cache-inspection` upper leaf. Its
request accepts exactly one explicitly supplied
`CurrentCacheApplicationBuild` and optional caller-constructed
`DetachedCacheRuntimeEvidence`.

The inspector independently reconstructs the `CacheProfileSet` from public
immutable declarations and structurally validates the exact public artifact
bytes. It compares the reconstructed profile ordering, count, source identity,
build identity, artifact identity, and object equality with the supplied
exact-current build. The resulting claim is narrow: the supplied typed object
is structurally valid evidence that the accepted upstream workflow established
as exact-current when supplied. It makes no live filesystem, application,
runtime, adapter, backend, entry, operation, or production claim.

Detached runtime evidence contains only the exact profile-set, build, and
artifact SHA-256 bindings; the eleven closed cache outcome counts; their
bounded total; fixed omission and detachment flags; and its own canonical
identity. Each count and the total are at most 1,000,000. It is not a runtime
observer and cannot prove that operations occurred.

The canonical `kongono.cache-inspection` Version 1 artifact uses fixed-order
UTF-8 JSON with LF framing. Safe evidence is restricted to the schema and
fixed semantic tokens, runtime-evidence status, profile count, bounded outcome
counts and total, fixed structural/omission/no-effect booleans, limitations,
and role-purpose vocabulary. Protected evidence is restricted to the
inspection, profile-set/source, build, artifact, and optional detached-runtime
SHA-256 identities.

The developer roles are evidence-purpose labels only:

- Chie maps to `explain`.
- Hantei maps to `verify`.
- Oshie maps to `teach`.

No agent package is depended upon or executed. Optional matching-role
Application Graph intelligence contributes only its existing bounded Safe
aggregates. The cache-to-Graph relationship is always `not-established`.

Pure projectors construct neutral in-memory `Observation` and
`SecurityAuditEvidence` values from a validated inspection and explicit
`ObservabilityContext`. They are marked `constructed-not-recorded` and perform
no recording or export. An independent verifier reconstructs the permitted
inspection, Graph, role, and projection evidence and returns deterministic
closed PASS/FAIL checks without repair.

## Disclosure boundary

Inspection never returns profile names, namespaces, codec identities,
adapter/provider identity, keys, key identities, entry versions, freshness or
time evidence, value/content bytes, content identities, artifact bytes,
source/output paths, raw JSON, individual operation data, environment data,
timestamps, arbitrary messages, exception text, or traces.

Diagnostics are fixed and nondisclosing:

- `KGN-CACHE-INSPECTION-2051`: invalid inspection request/evidence;
- `KGN-CACHE-INSPECTION-2052`: profile artifact or current-build binding failure;
- `KGN-CACHE-INSPECTION-2053`: invalid detached runtime/role/Graph/intelligence evidence;
- `KGN-CACHE-INSPECTION-2054`: projection failure;
- `KGN-CACHE-INSPECTION-2055`: independent verification failure.

Services are stateless and retain no request, artifact, runtime evidence,
intelligence, or projection between operations.

## Consequences

Phase 20G provides deterministic offline developer evidence without granting
cache or agent authority. It rejects backend/runtime access, clocks, cache
mutation, lookup, replay, retry, repair, apply, role execution, recording,
export, production observation, and certification.

Phase 20G does not establish provider compatibility, live backend currentness,
cache correctness, performance, security, availability, production readiness,
Stable/1.0 status, Phase 20 completion, Phase 20H, or Phase 21 behavior.
