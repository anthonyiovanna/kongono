# ADR 0195: Visibility-safe asset inspection and developer intelligence

- Status: Accepted
- Date: 2026-09-15
- Phase: 19G

## Context

Phase 19F supplies an explicit application-owned workflow whose successful
native result is represented by `CurrentAssetApplicationBuild`. That value and
its normalized `AssetResolutionArtifact` contain enough immutable evidence for
offline structural explanation, but they do not grant permission to inspect a
deployment, read published objects, resolve URLs, build assets, or observe a
running application.

Inspection belongs above the application and resolution packages. Placing it
inside either lower package would mix evidence presentation with construction
or resolution semantics and would force applications that need neither
inspection nor developer intelligence to install it.

## Decision

Accept the optional Experimental `kongono/asset-inspection` upper leaf. Its
only request input is one explicitly supplied `CurrentAssetApplicationBuild`.
The inspector reconstructs every resolution object, entry, relation, and the
artifact itself from public immutable evidence. It compares canonical bytes,
artifact identity, producer and binding identities, ordering, metadata, and
all current-result counts before producing evidence.

The successful claim is deliberately narrow: the supplied object is
structurally valid and arrived through the accepted current-result type. It is
not proof of live files, deployment, serving, reachability, browser behavior,
security, accessibility, performance, provenance, or production readiness.

The canonical `kongono.asset-inspection` schema is version 1. Its identity is
the lowercase SHA-256 of fixed-order UTF-8 JSON with one LF terminator,
excluding only the identity member. Safe evidence is restricted to fixed
schema, version, scope, producer, posture, disclosure, authority, limitation,
purpose and absence tokens; eight bounded counts; and six fixed booleans.
Protected evidence is restricted to the inspection, resolution, build,
source-set, asset-graph, and aggregate content-set SHA-256 identities. The
content-set digest is derived from ordinally sorted content identities.

Logical asset identities, paths, basenames, object keys, URLs, raw bytes,
individual content identities, integrity strings, MIME inventories, cache
values, tool or process evidence, environment values, arbitrary messages,
traces, host data, and timestamps are omitted.

Exactly three role-purpose labels exist: Chie/explain, Hantei/verify, and
Oshie/teach. They do not load, invoke, configure, authorize, impersonate, or
observe an agent. Optional Application Graph evidence must have the same role
and is reduced to supported Safe aggregate counts and fixed tokens. Its
relationship to the asset evidence is permanently `not-established`.

Pure projectors construct neutral `Observation` and `SecurityAuditEvidence`
values from a validated inspection and explicit `ObservabilityContext`. Every
projection is marked `constructed-not-recorded`; no recorder, exporter,
provider, tracer, audit sink, or policy effect is invoked.

An independent verifier receives the original request, claimed inspection,
role-derived view, optional Graph input, and both projections. It reconstructs
the request evidence, compares every permitted value, and returns a fixed
ordered PASS/FAIL verdict. Tampering fails closed with bounded tokens; the
verifier performs no repair.

All services are operation-local, synchronous, deterministic, and stateless.
Diagnostics `KGN-ASSET-INSPECTION-1950` through `1954` expose only bounded Safe
components and retain useful previous throwables without disclosing their
messages or traces.

## Consequences

Applications may opt into a visibility-safe structural evidence transformer
without adding it to API-only, CLI, worker, mobile, or other headless systems.
The package has no filesystem, discovery, loading, build, resolution, network,
serving, role-execution, repair, apply, recording, export, production
observation, or certification behavior. Phase 19G is Accepted. Phase 19H
external acceptance and checkpoint work remains Planned.

## References

- [Asset inspection and intelligence](../architecture/asset-inspection-and-intelligence.md)
- [Asset application workflow](../architecture/asset-application-workflow.md)
- [Exact-current asset resolution](../architecture/exact-current-asset-resolution.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
