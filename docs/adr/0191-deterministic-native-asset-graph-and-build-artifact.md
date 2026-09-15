# ADR 0191: Deterministic native asset graph and build artifact

- Status: Accepted
- Date: 2026-09-14
- Phase: 19C

## Context

Phase 19B supplies inert neutral declarations but intentionally performs no
graph traversal, source access, build, artifact validation, or publication.
Applications need an optional deterministic boundary that can prove one
complete explicit source set without making a bundler or runtime resolver part
of Kongono core.

## Decision

Add the optional Experimental `kongono/asset-build` leaf. An application gives
it one complete bounded `AssetDeclarationSet` and exactly one in-memory source
for every declaration. Source paths are protected relative identities and do
not grant filesystem access. The package performs no source discovery or read.

All Static, Dynamic, and Resource dependency relations form one directed graph
where a source depends on its target. Every nonempty graph has an entry point,
every declaration is reachable, cycles fail closed, dependency depth is at
most 256, dependencies precede dependents, and ordinal identity resolves every
otherwise equal ordering choice. The empty declaration and source set remains
valid.

The only build profile is `native-copy/1`. It copies each exact byte sequence
without parsing, transformation, concatenation, compression, source maps, or
execution. SHA-256 content identity selects
`objects/sha256/<first-two>/<digest>`; equal content shares object bytes while
logical output records remain distinct. Delivery evidence uses the declared
kind and content type, exact byte count, SHA-384 integrity, and no preload hint.

The canonical version-1 artifact is compact duplicate-safe UTF-8 JSON with one
terminal LF. It records complete declarations, dependencies, entry points,
logical output metadata, source-set identity, graph identity, and fixed builder
identity. It omits source and output bytes, private source paths, public URLs,
deployment policy, timestamps, environment, and mutable pointers. Structural
validation reconstructs the neutral contract and graph and checks the complete
caller-supplied output map. Exact currentness rebuilds from one complete
explicit current source set and requires byte-for-byte equality.

Publication is the sole filesystem write boundary. It accepts one already
existing canonical ordinary writable directory, constructs one unpredictable
operation-owned sibling temporary directory, writes only validated contained
paths exclusively, flushes complete bytes, and atomically renames to the
content-addressed final directory. An existing target is accepted only when
its complete ordinary recursive inventory and bytes are exact. Publication is
no-clobber and cleans only its owned temporary state while preserving primary
and cleanup failure truth.

## Consequences

Source-set, graph, content, build, logical, integrity, and output-path
identities remain distinct. The build is synchronous, stateless, deterministic,
operation-scoped, and isolated across sequential, overlapping, and reentrant
calls. Browser-executable bytes remain trusted application input rather than a
Kongono sandbox.

The package adds no adapter protocol, Node-family dependency, Vite integration,
subprocess, network call, URL selection, HTTP behavior, view integration,
runtime resolution, watcher, cache, command, deployment behavior, or authority.
Phase 19D-H remain Planned and require separate authenticated authority.

## Alternatives rejected

- Filesystem scans, import parsing, and extension inference would add ambient
  authority and incomplete-input ambiguity.
- Mutable manifests, newest-file selection, and mtime currentness would permit
  stale fallback.
- Request-time building, a mandatory Node process, or a Vite-shaped neutral
  contract would violate optionality and package direction.
- In-place or merging publication would weaken containment and no-clobber
  evidence.

## Related material

- [ADR 0188](0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0190](0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
- [Deterministic asset build](../architecture/deterministic-asset-build.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
