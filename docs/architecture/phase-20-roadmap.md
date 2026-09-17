# Phase 20 Roadmap: Explicit Derived-Data Cache Platform

## Status and entry state

Phase 19A-H are Accepted and Phase 19 is closed by the exact local checkpoint.
Phase 20A accepts
[ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md),
[ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md),
the [Cache Platform guide](cache-platform.md), and the
[cache-authoring guide](../engineering/cache-authoring.md) as documentation
architecture. Phase 20B accepts
[ADR 0199](../adr/0199-neutral-cache-contract-and-bounded-evidence-vocabulary.md)
and the optional Experimental `kongono/cache-contract` neutral leaf.

Phase 20C accepts
[ADR 0200](../adr/0200-deterministic-cache-profile-artifact-and-publication.md),
the optional Experimental `kongono/cache-build` leaf, and the
[deterministic cache-profile build guide](deterministic-cache-profile-build.md).

Phase 20D accepts
[ADR 0201](../adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md),
the optional Experimental `kongono/cache-adapter-contract`, the separately
optional Experimental `kongono/cache-adapter-memory`, and the
[bounded memory adapter guide](bounded-memory-cache-adapter.md).

Phase 20E accepts [ADR 0202](../adr/0202-exact-current-cache-runtime-and-operation-lifecycle.md),
the optional Experimental `kongono/cache-runtime`, and the
[exact-current runtime guide](exact-current-cache-runtime.md). Phase 20F accepts
[ADR 0203](../adr/0203-supported-cache-application-workflow-and-explicit-codec-policy-mapping.md),
the optional Experimental `kongono/cache-application`, the
[Cache Application workflow guide](cache-application-workflow.md), three
profile-evidence commands, and the copy-once `cache-minimal` slice.

Phase 20G accepts
[ADR 0204](../adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md),
the optional Experimental `kongono/cache-inspection` leaf, and the
[cache inspection guide](cache-inspection-and-intelligence.md).

Phase 20H accepts [ADR 0205](../adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md)
and the root-owned [external acceptance](phase-20-external-cache-acceptance.md).
Phases 20A-H are Accepted and Phase 20 is closed. Phase 20B defines only
bounded immutable values, closed evidence vocabulary, and nondisclosing
diagnostics. Phase 20C adds only explicit profiles, canonical inert artifacts,
strict exact-current validation, and contained publication; it adds no
serialization, adapter, backend,
runtime operation, command, application workflow, or provider compatibility.
Phase 20D adds only a directly supplied adapter protocol and bounded memory
implementation. Phase 20E adds only exact-current operations over a supplied
adapter with caller time. Phase 20F adds only explicit profile workflow,
codec-identity mapping, direct runtime composition, commands, and a copy-once
example. Phase 20G adds only supplied-evidence reconstruction, optional
detached aggregate runtime evidence, bounded projections, and independent
verification. Phase 20H adds only local root-test evidence and the exact
checkpoint; it grants no authority to begin Phase 21.

## Fixed Phase 20 roadmap

| Row | Required boundary | Explicit exclusions |
| --- | --- | --- |
| **20A - Architecture and fixed roadmap** | Accept ADRs 0197-0198, the Cache Platform guide, cache-authoring guide, doctrine, security/lifecycle direction, package direction, and this fixed roadmap as documentation only. | No package, PHP API, schema, artifact, adapter, backend, cache operation, command, skeleton, test, dependency, or checkpoint. |
| **20B - Neutral cache contracts** | Accepted optional Experimental `kongono/cache-contract` immutable bounded namespace/key/value/content/freshness/version/outcome vocabulary and package diagnostics. | No clock read, serialization, filesystem, network, backend, mutation, adapter, runtime, callback, command, or application integration. |
| **20C - Deterministic cache profile and artifact** | Accepted optional Experimental `kongono/cache-build` explicit profile declarations, canonical artifact, structural and exact-current validation, and contained content-addressed no-clobber publication. | No generated executable code, cache entry data, backend connection, runtime operation, secret, environment discovery, or implicit configuration. |
| **20D - Neutral adapter contract and bounded memory adapter** | Accepted optional Experimental neutral adapter protocol plus separately optional Experimental bounded in-memory implementation with atomic visibility, explicit versions, finite capacity, deterministic eviction policy, and isolation. | No Redis/Memcached/cloud/filesystem/database provider, ambient singleton, durability, distribution, lock, queue, session, shared-host, or production availability claim. |
| **20E - Exact-current cache runtime** | Accepted optional bounded runtime over one supplied current profile, supplied `now`, and directly supplied matching adapter, with typed lookup/mutation outcomes, conditional operations, complete validation, and deterministic retirement. | No hidden clock, global registry, service locator, magic serialization, implicit retry, stale fallback, background refresh, stampede lock, or retained operation state. |
| **20F - Supported application workflow** | Accepted optional application composition, explicit codec/policy mapping, human/machine command parity for profile plan/build/check, and one copy-once minimal slice. | No automatic caching of database/HTTP/view/model results, generated business policy, daemon, scheduler, provider installation, destructive flush command, or mandatory cache. |
| **20G - Visibility-safe inspection and developer intelligence** | Accepted offline/read-only/stateless `kongono/cache-inspection` of one supplied exact-current build and optional detached runtime evidence, emitting bounded Safe/Protected projections for Graph, Observability, Security Audit, Chie, Hantei, and Oshie. | No value/key disclosure, backend access, clock read, mutation, replay, repair, role execution, recording, export, production observation, or certification. |
| **20H - External acceptance and checkpoint** | Accepted root-owned deterministic external proof of contracts, build/currentness, memory-adapter semantics, runtime isolation, application parity, inspection, nondisclosure, cleanup, regressions, and one exact local Phase 20 checkpoint. | No distributed-provider certification, Redis/Memcached/cloud compatibility, performance superiority, production availability, push, tag, release, Stable/1.0, mobile SDK, or Phase 21 behavior. |

## Locked direction

Cached data is bounded, derived, disposable, and non-authoritative.
Applications remain correct after miss, expiry, eviction, backend loss, or
flush. Namespace, logical key, key identity, codec, opaque value bytes, content
identity, entry version, freshness/time evidence, provider identity, operation,
and outcome remain distinct.

Time and freshness policy are explicit caller inputs. There is no hidden clock,
default lifetime, stale fallback, read-through callback, facade, global
registry, magic serialization, or coordination claim. Entry visibility is
atomic, conditional mutations bind to version evidence, results are detached,
and mutable state is operation-local and deterministically retired.

The first adapter is only an Accepted bounded in-memory implementation. Future
provider and PSR interoperability remains optional and cannot redefine the
neutral correctness model.

## API and compatibility direction

Phase 20A adds zero callable declarations. Current callable declarations are
Experimental unless private implementation helpers are Internal. No Public, Stable,
1.0, provider compatibility, production availability, performance, or
unreleased-PHP claim is authorized.

## Related material

- [ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md)
- [ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md)
- [ADR 0201](../adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md)
- [Cache Platform](cache-platform.md)
- [Bounded memory adapter](bounded-memory-cache-adapter.md)
- [Cache authoring](../engineering/cache-authoring.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
