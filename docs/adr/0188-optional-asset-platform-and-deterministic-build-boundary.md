# ADR 0188: Optional Asset Platform and Deterministic Build Boundary

- Status: Accepted
- Date: 2026-09-13
- Phase: 19A

## Context

Kongono has an accepted optional server-rendered presentation stack, but it has
no Asset Platform contract. Applications may already serve copied or prebuilt
files under their own policy, and headless applications need no browser asset
capability at all. A future integration must not turn those choices into hidden
framework behavior or make one build tool the permanent model.

Conventional asset integrations often infer sources by scanning directories,
run a tool during a request, trust an ambient manifest, select the newest
output, concatenate URLs, or retain mutable process-global state. Those
patterns conflict with Kongono's explicit construction, build-before-runtime,
exact-current validation, containment, boundedness, and persistent-runtime
isolation doctrines.

Phase 19A accepts documentation architecture only. It creates no schema, PHP or
JavaScript API, package, adapter, manifest, build, output, process, command,
resolver, HTTP behavior, view integration, or runtime behavior.

## Decision

### Optional upper application capability

Kongono will own an optional Asset Platform abstraction, not a bundler.
API-only, CLI, worker, agent, headless, and mobile backends install no asset
package unless their application composition explicitly selects the capability.
Server-rendered applications without a JavaScript toolchain remain first-class.

The neutral vocabulary is independent of Vite, Webpack, Rollup, esbuild, Node,
npm, pnpm, Bun, Deno, CDNs, web servers, and frontend frameworks. Vite is the
intended first optional adapter, not a core dependency, runtime authority, or
permanent conceptual model. Native or no-bundler assets and adapter-produced
assets are peers behind the same future neutral validation boundary.

### Explicit language-neutral declarations

A future application-owned source set will declare every source explicitly.
No directory scan, glob expansion, convention search, package discovery,
remote lookup, or implicit manifest may add an asset. The contract must remain
language-neutral so PHP, TypeScript or JavaScript, Rust, and external build
adapters can implement it without redefining its meaning.

The future neutral model will distinguish:

- logical asset identity selected by application code;
- source path identifying explicit build input;
- output or storage path identifying contained produced bytes;
- public URL selected under application and deployment policy;
- content identity derived from validated content bytes;
- build identity derived from the complete validated build input; and
- optional integrity metadata carried as a separately validated claim.

None of these concepts is interchangeable. A path or URL does not grant a
logical identity, a digest does not grant delivery authority, and integrity
metadata is not authorization.

The planned declaration domain is closed and bounded. It will include a closed
asset-kind and content-type vocabulary, explicit entry-point and dependency
relations, deterministic canonical ordering, and rejection of duplicate
identities, duplicate relations, path collisions, output collisions,
conflicting metadata, unsupported values, and exceeded bounds. Phase 19A does
not select that schema or its numeric limits.

### Deterministic build evidence

A future build consumes one explicit complete source set and explicit adapter
inputs. Its validated output must yield deterministic source, graph, build,
artifact, and currentness evidence. Currentness is relative only to the exact
complete input supplied by the caller; timestamps, directory state, newest-file
selection, mutable pointers, and tool assertions are insufficient.

Build tools are untrusted producers until their complete bounded output has
been validated against the neutral contract. A successful subprocess exit or
well-formed tool manifest is not proof of containment, completeness,
determinism, currentness, or safe delivery metadata.

Future publication must be content-addressed, contained beneath one explicit
application-owned root, atomic, idempotent only for identical valid bytes, and
no-clobber. Existing different bytes, linked or outside-root paths, ambiguous
state, malformed output, and tampering fail closed. No mutable current pointer
is implied.

### Adapter replacement and evolution

Adapters translate explicitly authenticated inputs and outputs. They do not
define the neutral model and receive no ambient filesystem, network,
credential, package-installation, or process authority. Modern tools may evolve
behind versioned adapters; Kongono does not promise compatibility with obsolete
tool generations merely to preserve one adapter implementation.

A native flow may validate and publish copied or prebuilt application assets
without Node. A future Vite adapter may invoke an application-provided Vite
toolchain under a separately accepted bounded subprocess contract. Neither flow
weakens the neutral artifact or exact-current rules.

### Lifecycle, bounds, and diagnostics

Every future source count, relation count, path length, metadata length, file
size, total byte count, subprocess output, diagnostic field, and operation time
must have an explicit finite bound before an effect. Shared evidence is
immutable. Mutable build or resolution state is operation-scoped, supports
overlapping persistent-runtime operations without leakage, and is completely
retired on success or failure.

Diagnostics will identify the failed operation and invariant with bounded Safe
or Protected evidence and actionable remediation. They must omit source bytes,
browser-executable output, private paths, tool environment, credentials,
secrets, unbounded subprocess output, and arbitrary manifest content.

### Security and trust

Source declarations, source bytes, third-party packages, build tools, plugins,
tool manifests, and generated outputs are hostile until validated at their
owned boundary. Symlinks, junctions, reparse points, alternate streams,
outside-root traversal, remote inputs, and unexpected linked output fail
closed. Browser-executable output is trusted application code or content, not a
sandbox supplied by Kongono.

Application and deployment layers own public origins and base URLs, CDN and
storage selection, credentials, network access, cache policy, CSP, nonces,
security headers, authentication, authorization, cookies, sessions, and all
external effects. The Asset Platform neither infers nor grants those policies.

### API status direction

Phase 19A exposes no callable API. Phase 19B-H remain Planned. If separately
accepted, neutral contracts begin Experimental; adapter and workflow surfaces
remain Experimental; implementation internals remain Internal; and Public or
Stable status requires separate evidence and authority. PHP 8.5 remains the
current baseline. PHP 8.6 or 9 readiness means explicit contracts and
replaceable adapters, not unverified syntax or behavior claims.

## Consequences

- Applications can remain headless or use a native asset flow without Node.
- A future bundler can be replaced without changing the logical asset model.
- Reproducible validation and deployment become more explicit, at the cost of
  declared inputs, bounded adapters, immutable outputs, and build discipline.
- Development and production use the same contract; development gains no stale
  fallback, hidden scan, or permissive manifest path.
- Performance work may target bounded parsing, validation, lookup, and process
  startup, but Phase 19A makes no performance claim.

## Alternatives and tradeoffs

Making Vite the core contract was rejected because it would couple Kongono to
one language ecosystem and tool generation. Request-time builds and dev-only
fallbacks were rejected because they make correctness depend on ambient mutable
state. Directory convention scanning was rejected because it hides the source
set. A global runtime manifest was rejected because it permits stale selection
and cross-request leakage. Treating assets as unstructured public files remains
an application option, but it does not receive future Asset Platform evidence.

## Deferred and excluded scope

Phase 19A implements no schema, package, parser, graph, build, publisher,
adapter, subprocess, Vite integration, resolver, URL generator, HTTP response,
tag renderer, view helper, watcher, development server, hot reload, daemon,
installer, deployment manager, CDN or storage adapter, frontend framework,
mobile adapter, inspection integration, checkpoint, or production claim.

No performance, browser, accessibility, security, deployment, production,
Stable, or 1.0 claim is made.

## Related authorities

- [ADR 0189](0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [Asset Platform](../architecture/asset-platform.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Architectural doctrine](../doctrine.md)
