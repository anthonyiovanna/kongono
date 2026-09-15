# ADR 0190: Neutral asset contract and bounded declaration vocabulary

- Status: Accepted
- Date: 2026-09-13
- Phase: 19B

## Context

Phase 19A accepted the architecture for an optional deterministic Asset
Platform while deliberately creating no callable API. The first implementation
boundary must let applications describe asset meaning and closed structural
relations without introducing a filesystem, bundler, build, resolver, browser,
HTTP, view, or deployment authority.

Logical identity, content identity, integrity evidence, preload hints, and
delivery metadata must remain distinct. Values and evidence must be bounded,
immutable, deterministic, safe in persistent runtimes, and nondisclosing when
validation fails. API-only, CLI, worker, agent, headless, mobile, and other
no-asset applications must remain first-class.

## Decision

Accept the optional Experimental `kongono/asset-contract` neutral leaf. Its
production dependencies are exactly PHP 8.5, API Metadata, and Diagnostics. No
existing production package depends on it.

The contract version is exactly Version 1. A logical asset identity is 1-128
ASCII bytes in the canonical lower-case segmented grammar. A content identity
is exactly one lower-case SHA-256 digest and may be derived only from at most
67,108,864 caller-supplied bytes. Neither identity is interpreted as a path,
URL, filename, class, package, service, or capability.

Asset kinds and content types are closed enums with an exact compatibility
relation. Declarations contain only contract version, logical identity, kind,
and content type. Dependencies contain only matching version, distinct source
and target identities, and one static, dynamic, or resource relation kind.
Entry points contain only matching version and logical identity.

One declaration set accepts at most 1,024 declarations, 4,096 dependencies,
and 256 entry points. Inputs must be PHP lists containing package-owned values.
The set rejects mixed versions, duplicates, missing relation endpoints, and
undeclared entry points; detaches caller arrays; and stores declarations,
dependencies, and entry points in ordinal order. It validates relation shape,
uniqueness, and membership only. It deliberately performs no reachability,
cycle analysis, or build ordering. An empty set is the first-class no-asset
contract.

Integrity evidence uses exactly SHA-256, SHA-384, or SHA-512 with lower-case
hexadecimal digests of the corresponding fixed length. Delivery metadata
contains only version, logical identity, kind, content type, content identity,
bounded byte count, optional integrity evidence, and an inert preload hint.
Module preload is valid only for scripts, and SHA-256 integrity must equal the
content identity. This metadata contains no path, URL, origin, CDN, storage
locator, HTTP header, cache directive, CSP, nonce, credential, executable byte,
or arbitrary map.

The package owns six diagnostics, `KGN-ASSET-CONTRACT-1901` through `1906`.
Diagnostic context is limited to the closed Safe fields `violation`,
`entry_count`, `entry_position`, `limit`, `asset_kind`, `dependency_kind`,
`algorithm`, and `preload_hint`. Rejected identities, digests, paths, URLs,
bytes, arbitrary values, and throwable text are never rendered by package
messages, stringification, or diagnostic context.

All consumer values, enums, and the concrete validation exception are
Experimental. The exception-family interface is Public. The diagnostic enum,
abstract exception base, and guard are Internal. No declaration is Stable.

## Determinism and lifecycle

Values are immutable and operation-independent. Ordering is derived only from
validated bounded values. The package has no mutable static state, singleton,
registry, cache, ambient current set, service locator, destructor work,
shutdown hook, Fiber-local state, or retained cross-operation state. Sequential
and overlapping construction remain isolated.

## Security and authority

Every value is inert evidence. Possession grants no filesystem read,
publication, execution, integrity, URL, cache, delivery, network, credential,
authentication, authorization, or application-effect authority. Browser
executable output remains trusted application code or content, not a Kongono
sandbox. Applications and deployments own origin, CDN, CSP, nonces, headers,
cache, credentials, byte delivery, and all external effects.

## Consequences

Phase 19B provides a small language-neutral vocabulary that later build and
resolution packages may consume through explicit dependencies. Native and
no-bundler flows remain peers of future adapters. Vite remains only the planned
first optional replaceable Phase 19D adapter and is not installed or made part
of the core model.

Phase 19C may separately define explicit source sets, graph validation, build
artifacts, currentness, and publication. This decision grants no authority for
that work or for any later Phase 19 row.

## Alternatives considered

- Use bundler-native manifests as the core contract. Rejected because it makes
  one tool an ambient authority and weakens native/no-bundler parity.
- Combine logical identity, paths, URLs, content identity, and integrity in one
  value. Rejected because those concepts have different trust and lifecycle
  boundaries.
- Traverse dependencies or reject cycles in this package. Rejected because
  graph semantics and ordering belong to the separately authorized Phase 19C
  build boundary.
- Infer content types from extensions or accept arbitrary MIME strings.
  Rejected because aliases, parameters, and tool conventions would make the
  Version 1 contract ambiguous.
- Add global registries or a mutable current manifest. Rejected because ambient
  state breaks exactness and persistent-runtime isolation.

## Explicit exclusions

This decision adds no source path, output path, public URL, artifact schema,
serialization, parser, filesystem behavior, discovery, graph traversal, cycle
detection, compiler, bundler, minifier, transpiler, source map, publication,
resolver, cache, Node dependency, Vite adapter, subprocess, watcher, HMR,
server, command, workflow, inspection, HTTP behavior, view integration,
frontend integration, deployment behavior, checkpoint, Stable API, or 1.0
claim.

## Related decisions

- [ADR 0188](0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
