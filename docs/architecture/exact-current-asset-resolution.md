# Exact-current asset resolution

Phase 19E accepts [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
and the optional Experimental `kongono/asset-resolution` package. The package
is a presentation-neutral, stateless, pure in-memory leaf. Install it only when
an application resolves validated asset-build evidence; API-only, CLI, worker,
agent, headless, mobile-backend, and no-asset applications install none of it.

## Normalized resolution artifact

`AssetResolutionArtifactFactory` accepts either a complete native
`AssetBuildResult` plus its explicit current source set, or a complete
`AdapterBuildResult`. Both paths rerun the existing strict validators before
normalization. Producer assertions are not trusted. Stale, mixed, missing,
extra, tampered, cyclic, unreachable, ambiguous, or relation-invalid evidence
fails closed.

The resulting canonical artifact carries Version 1, schema
`kongono.asset-resolution-artifact`, the closed producer kind `native` or
`adapter`, exact build/source-set/graph identities, and ordinal logical entries.
Each entry has one primary object and its complete unique reachable dependency
closure. Objects contain only closed kind/content type, content identity,
bounded length, validated SHA-384 integrity, inert preload hint, and a relative
content-addressed key of the form `objects/sha256/xx/<digest>`.

The artifact contains no source or output bytes, private source/output root,
absolute path, workspace path, tool manifest, environment value, credential,
request value, response, or process evidence. Empty validated native input
produces the first-class empty resolution artifact.

## Exact-current lookup

The caller supplies the exact expected resolution-artifact identity on every
lookup. It must match before logical lookup begins. A mismatch is stale and a
missing identity is absent; neither case selects the newest artifact, scans a
directory, consults a registry, aliases a logical name, repairs evidence, or
falls back.

Resolution returns one primary value and an ordinal detached dependency list.
Ordering is evidence only, not script-execution, CSS-cascade, tag, preload, or
network-scheduling authority. Constructors detach lists, and the resolver
retains no state, so sequential and overlapping calls remain isolated.

## Explicit application-owned policy

`AssetResolutionPolicy` contains exactly four caller choices:

- a canonical root-relative or explicit HTTP/HTTPS public base URL;
- `no-store`, `private, no-cache`, or `public, max-age=31536000, immutable`;
- omit integrity or require exact artifact SHA-384 evidence; and
- omit preload metadata or honor the artifact's inert closed hint.

The base has bounded ASCII grammar and rejects userinfo, query, fragment,
backslash, controls, non-ASCII bytes, empty/dot/dot-dot segments, encoded path
separators, encoded dot segments, scheme-relative input, and ambiguous percent
encoding. Kongono performs no DNS, origin authorization, deployment discovery,
or transport-security certification.

Resolved HTTP metadata is immutable evidence: exact content type and length, a
strong quoted ETag derived from content identity, and the selected cache value.
Browser integrity is exactly `sha384-<base64>` when required. Preload remains
inert. The package emits no markup, Link header, header array, stream, or PSR-7
response and makes no `crossorigin`, CSP, or nonce decision.

## Ownership, failures, and exclusions

Applications and deployment layers own origin, CDN, storage, byte serving,
cache selection, CSP, nonces, security headers, cookies, authentication,
authorization, credentials, and external effects. Browser-executable output is
trusted application code or content, not a Kongono sandbox.

Diagnostics `KGN-ASSET-RESOLUTION-1930` through `1938` expose only bounded Safe
context and retain previous throwables without presenting their messages,
traces, arguments, paths, URLs, logical inputs, bytes, credentials, or
environment. Remediation is to supply the documented Version 1 evidence,
rebuild and validate complete inputs, use the exact expected identity, declare
the missing logical asset, or correct the explicit policy.

The package performs no build, bundling, import parsing, transformation,
filesystem access or discovery, publication, runtime process, network access,
watcher, HMR, development server, cache storage, mutable/global manifest,
request-time work, response emission, routing, middleware, view integration,
frontend/mobile integration, inspection, command, deployment, or certification.

## Related material

- [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
- [Asset Platform](asset-platform.md)
- [Deterministic asset build](deterministic-asset-build.md)
- [Replaceable asset-build adapters](replaceable-asset-build-adapters.md)
- [Phase 19 external asset acceptance](phase-19-external-asset-acceptance.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
