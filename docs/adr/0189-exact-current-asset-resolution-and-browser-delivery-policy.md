# ADR 0189: Exact-Current Asset Resolution and Browser Delivery Policy

- Status: Accepted
- Date: 2026-09-13
- Phase: 19A

## Context

A validated asset build artifact is evidence, not a browser URL, HTTP response,
filesystem server, CDN configuration, or authorization grant. Resolution must
therefore remain separate from build production and from application-owned
browser and delivery policy.

An ambient or mutable manifest would permit stale results, implicit base-URL
selection, file-path disclosure, cross-request state, and development behavior
that diverges from production. Phase 19A accepts the architecture of a future
exact-current boundary without implementing it.

## Decision

### Exact-current immutable resolution

A future resolver will accept one explicitly supplied, validated, immutable,
exact-current asset artifact and one logical asset identity. It will return
only bounded validated delivery metadata from that artifact. It will not scan,
build, select a newest file, consult a mutable global manifest, or repair stale
evidence.

Exact currentness is relative to the explicit complete source and policy
evidence required by the future accepted contract. Missing, stale, mixed-build,
mismatched, ambiguous, duplicate, unsupported, outside-root, linked, tampered,
or oversized evidence fails closed before delivery metadata is produced.

### Separate delivery concepts

The future model keeps these values distinct:

- content digest over validated output bytes;
- optional integrity metadata intended for an explicitly allowed browser use;
- public URL selected under application-owned policy;
- contained storage or output path used by an outer delivery adapter;
- validated content type;
- bounded content size;
- dependency relations;
- optional preload hints; and
- cache identity and application-owned cache policy.

No value silently derives authority from another. In particular, a filesystem
path is not a public URL, a digest is not a cache policy, a preload hint is not
an instruction to emit a tag, and optional integrity metadata is not proof of
origin, authorization, or safe execution.

### Application and deployment policy ownership

The application and deployment layers own public base URL, origin and CDN
selection, storage and server configuration, request and response policy,
cache-control policy, CSP, nonce creation, security headers, authentication,
authorization, cookies, sessions, credentials, network access, and external
effects. Resolution cannot fabricate, infer, or override them.

Public URLs must be constructed only from validated artifact metadata and an
explicit application policy accepted by a future boundary. Untrusted logical
identities, source paths, output paths, query strings, fragments, or manifest
values may not be concatenated into a URL implicitly. Diagnostics and returned
metadata must not disclose private filesystem paths.

Subresource Integrity, if a later phase selects supported algorithms and a
validation contract, is explicit metadata. It is never inferred from an
untrusted tool assertion, never grants authorization, and never replaces CSP,
origin policy, content-type enforcement, transport security, or application
review of executable output. Phase 19A selects no algorithm set.

### No effects in resolution

Resolution performs no filesystem discovery, build, subprocess execution,
network lookup, remote registry access, DNS, provider call, storage access,
HTTP response emission, or byte delivery. Static server, application server,
object-storage, and CDN delivery remain outer adapters or deployment concerns.

Immutable artifact evidence may be shared. Resolution outputs and any mutable
working state are operation-scoped and retired completely. Sequential and
overlapping requests must not leak selected identities, URLs, nonces, headers,
preload decisions, or policy state across operations.

### Presentation integration

Future Phase 18 view integration may receive explicit typed asset values or use
a separately accepted upper adapter. Templates do not gain an ambient helper,
service locator, global manifest, filesystem access, resolver discovery, URL
policy, or build authority. Routing continues to select handlers rather than
assets or templates.

Development and production must use the same artifact, currentness, identity,
containment, and policy contract. Development may improve diagnostics or invoke
an explicit build command, but it may not fall back to a stale artifact, scan a
directory, contact a dev server implicitly, or build during a request.

### Failure and disclosure

Failures identify bounded logical evidence, the violated invariant, and an
actionable build, declaration, currentness, containment, or policy remediation.
They omit source and output bytes, private paths, credentials, secrets, raw tool
manifests, arbitrary URLs, request values, headers, cookies, CSP nonces, and
provider payloads.

### API status direction

Phase 19A creates no callable resolver, URL policy, HTTP adapter, view adapter,
browser integration, or delivery API. Any later neutral and resolution
contracts begin Experimental under separate authority. Phase 19B-H remain
Planned. Internal validators and
adapter machinery remain Internal. Public, Stable, or 1.0 status is not
authorized.

## Security consequences

Hostile source declarations, tool output, manifests, paths, links, metadata,
and browser-executable bytes cross separate validation boundaries. Containment
must account for symlinks, junctions, reparse points, alternate streams, path
normalization, path collisions, and state changes between validation and use.
An exact-current artifact narrows ambiguity but is not authentication,
authorization, deployment truth, or a sandbox.

## Consequences

- Resolution remains deterministic and inspectable without acquiring build or
  delivery authority.
- Applications retain complete control of origins, CDN, cache, CSP, headers,
  credentials, and external effects.
- Explicit policy and immutable artifacts require more composition than a
  global manifest but avoid hidden state and stale fallback.
- Native, Vite-produced, and future adapter-produced artifacts can share the
  same resolution semantics once separately implemented.

## Alternatives and tradeoffs

Implicit URL concatenation was rejected because it combines untrusted values
with external policy. A global mutable manifest was rejected because it hides
currentness and leaks state. Serving files inside the resolver was rejected
because resolution evidence and byte delivery have different authority and
lifecycle. Treating SRI as sufficient security was rejected because it does not
replace origin, CSP, transport, authorization, or executable-code trust.

## Excluded scope

No browser, CDN, web server, SRI algorithm set, URL policy, runtime resolver,
HTTP adapter, tag renderer, view integration, Vite adapter, storage adapter,
deployment workflow, performance certification, accessibility certification,
security certification, production claim, Stable promise, or 1.0 promise is
implemented or accepted by this ADR.

## Related authorities

- [ADR 0188](0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Compiled-view HTTP boundary](../architecture/compiled-view-http.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [Security policy](../../SECURITY.md)
