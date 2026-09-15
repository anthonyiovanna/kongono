# ADR 0193: Exact-current asset resolution and explicit browser/HTTP policy

## Status

Accepted.

## Context

Phase 19C native builds and Phase 19D adapters produce different validated
build results, while application code needs one deterministic way to resolve a
logical asset identity. Resolution must not discover artifacts, read published
bytes, infer deployment policy, or turn metadata into authority.

## Decision

Add the optional Experimental `kongono/asset-resolution` package above
`kongono/asset-contract`, `kongono/asset-build`, and
`kongono/asset-adapter-contract`. It revalidates a complete native or adapter
result and normalizes it into one immutable canonical resolution artifact.
Equivalent safe metadata has the same resolution shape regardless of producer.

The normalized artifact records only its Version 1 schema, closed producer
kind, build/source-set/graph identities, sorted logical entries, and complete
reachable content-addressed object metadata. Object keys have the fixed
`objects/sha256/xx/<digest>` grammar and must agree with content identity.
Source and output bytes, private paths, workspaces, tool manifests, environment
values, credentials, request values, and process evidence are excluded.

An application supplies both the expected resolution-artifact identity and an
immutable policy containing an explicit public base URL, cache policy,
integrity policy, and preload policy. Identity mismatch fails before lookup;
missing logical identity fails without aliases, scans, newest selection, or
stale fallback. The resolver is stateless and performs only in-memory work.

Public bases are canonical root-relative paths or explicit HTTP/HTTPS URLs.
HTTP is available for explicitly selected local development and is not a
transport-security claim. Resolved values contain typed URL, content, cache,
ETag, optional SHA-384 browser-integrity, and inert preload metadata. They do
not contain bytes, streams, mutable header arrays, responses, CSP, nonces,
crossorigin decisions, cookies, credentials, or authority.

Applications and deployment systems continue to own origin, CDN, storage,
cache choice, CSP, nonces, headers, authentication, authorization, credentials,
and byte delivery. API-only, CLI, worker, agent, headless, mobile-backend, and
no-asset applications install none of this optional capability.

## Consequences

Native and adapter outputs share one exact-current consumer contract without
making either producer the permanent model. Resolution is deterministic across
repeated, sequential, and overlapping calls because it retains no mutable or
ambient state. Failures use bounded nondisclosing diagnostics
`KGN-ASSET-RESOLUTION-1930` through `1938`; remediation requires corrected
explicit evidence or policy, never discovery or repair.

This decision adds no build, parser, watcher, HMR, development server, network
access, filesystem discovery, publication, cache store, response emission,
view helper, tag renderer, frontend framework, mobile adapter, command,
inspection, deployment manager, acceptance checkpoint, certification, or
Stable/1.0 promise. Phase 19F-H require separate decisions.

## Related material

- [ADR 0189](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0191](0191-deterministic-native-asset-graph-and-build-artifact.md)
- [ADR 0192](0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
- [Exact-current asset resolution](../architecture/exact-current-asset-resolution.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
