# Asset Authoring

## Status

Phase 19A accepts the documentation architecture. Phase 19B accepts
[ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
and the optional Experimental `kongono/asset-contract` neutral leaf. Phase
19C accepts [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
and the optional Experimental `kongono/asset-build` native leaf. Phase 19D
accepts [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md),
the optional Experimental `kongono/asset-adapter-contract` neutral boundary,
and the separately optional Experimental `kongono/asset-adapter-vite`
fixed-profile implementation. Phase 19E accepts the optional Experimental
`kongono/asset-resolution` leaf for exact-current metadata lookup under
explicit application policy. Phase 19F accepts the optional Experimental
`kongono/asset-application` workflow, three supported commands, and the
copy-once `asset-minimal` slice. Phase 19G accepts the optional Experimental
`kongono/asset-inspection` upper evidence leaf. Phase 19H accepts the root-owned
[external proof](../architecture/phase-19-external-asset-acceptance.md) and exact
checkpoint. Phase 19A-H are Accepted and Phase 19 is closed. The lower adapter and resolver leaves define no network or view
integration, runtime, Node/Vite installation, watcher/HMR, development server,
or frontend integration.

## When to install no asset capability

Install no Asset Platform capability for API-only, CLI, worker, agent,
headless, or mobile backends that do not publish browser assets. A
server-rendered application that already owns copied or prebuilt public files
may also keep that application-specific arrangement. Optionality is a supported
design choice, not an incomplete installation.

## Accepted native and adapter workflows

The native or no-bundler workflow accepts explicit application-owned in-memory
sources, validates them against the neutral contract, builds deterministic
graph and artifact evidence, and publishes validated content-addressed bytes.
It does not require Node or fabricate a bundler manifest.

The optional Phase 19D adapter workflow may select the first-party fixed Vite
profile or another separately accepted adapter. Vite is the first optional
adapter, not a core dependency or the neutral model. The application supplies
the executable explicitly. One contained workspace and at most one bounded
no-shell subprocess are used; the strict manifest and complete ordinary-file
output tree remain untrusted until Kongono validates completeness, identities,
relations, metadata, paths, bytes, bounds, the neutral artifact, and cleanup.

Application authors may use the supported source document and
plan/build/check/resolve workflow described in the
[asset-application guide](../architecture/asset-application-workflow.md). The
copy-once slice owns its files and browser policy; no automatic upgrade or
merge occurs. View context values are constructed explicitly by application
code after resolution.

## Current neutral declarations and future sources

The current Version 1 declaration set is complete, finite, detached, and
ordinally canonical. It distinguishes logical identities, closed asset kinds
and content types, entry points, explicit dependency relations, content
identity, integrity, preload hints, and bounded delivery metadata. It accepts
at most 1,024 declarations, 4,096 dependency relations, and 256 entry points.
An empty declaration set is the first-class no-asset contract.

Phase 19C adds explicit application-owned sources. It distinguishes:

- logical identities used by application code;
- explicit source paths;
- closed asset kinds and content types;
- entry points;
- explicit dependency relations;
- bounded metadata; and
- explicit adapter selection, if any.

Do not infer sources from directory contents, naming conventions, package
discovery, globs, imports found by an ambient scan, remote registries, or a
tool's mutable manifest. Duplicate identities, conflicting relations, missing
targets, path or output collisions, unsupported content, mixed versions,
outside-root paths, links, and exceeded bounds must fail closed. Phase 19B
validates relation membership and uniqueness but deliberately performs no graph
traversal or cycle analysis.

## Workflow outline

The supported application workflow follows this explicit sequence:

1. Declare one complete bounded source set.
2. Select the native path or one explicit versioned adapter.
3. Build candidate output in one operation-owned workspace.
4. Validate the complete output against the neutral asset contract.
5. Publish identical validated bytes by content identity beneath one explicit
   application-owned root.
6. Check the artifact against the same complete current declaration.
7. Inspect bounded Safe or Protected evidence when separately supported.
8. Resolve one logical identity from one supplied exact-current artifact under
   explicit application delivery policy.

Human and machine workflows perform the same validation. The three accepted
commands cannot discover inputs, install a toolchain, contact a network, build
during a request, or accept stale evidence.

## Immutable output and mutable source names

Source filenames are application-owned mutable names. Published output is
identified by validated content bytes and placed at a contained
content-addressed location. Reusing an existing identical valid output may be
idempotent; overwriting different bytes is prohibited.

Logical identity, source filename, output path, public URL, content digest,
build identity, and integrity metadata are separate. Rebuilding after a source,
dependency, adapter, or policy input changes produces new evidence rather than
moving a mutable current pointer.

## Delivery policy remains outside artifacts

Neutral build artifacts do not choose a public base URL, origin, CDN, storage
provider, cache-control policy, CSP, nonce, security header, credential,
authentication, authorization, cookie, session, or network effect. Those remain
application and deployment responsibilities.

The Phase 19E resolver returns bounded validated delivery metadata from one
exact-current artifact. It may not concatenate logical input into a URL or
expose private source or output paths. Static server, application response,
object-storage, and CDN byte delivery remain outer adapters or deployment work.

## Asset-class review

Phase 19B fixes the Version 1 asset-kind and content-type vocabulary and their
exact compatibility relation. Arbitrary MIME strings, parameters, aliases,
vendor types, and extension-based inference are rejected. Evolution requires a
later contract version and separate authority.

- JavaScript and other browser-executable modules are trusted application code.
  Review their dependencies, side effects, CSP needs, and supply chain.
- CSS may reference further resources and can affect disclosure and rendering.
  Declare dependencies explicitly rather than relying on hidden rewriting.
- Images require validated size, content type, and bounded bytes; a filename
  extension alone is not proof of format.
- Fonts require explicit licensing, origin, preload, content-type, and cache
  policy decisions owned by the application and deployment.
- Source maps can disclose source, private paths, names, and implementation
  detail. Publication is an explicit application policy decision.
- WebAssembly, media, icons, documents, and later classes require separately
  reviewed closed semantics and bounds before acceptance.

Hashing, content addressing, or integrity metadata does not sandbox active
content, audit it, grant authorization, or make it safe to execute.

## Diagnostics and evidence review

Future diagnostics should identify the operation and invariant, a bounded
logical identity or protected digest, and concrete remediation. Common planned
failure classes include:

- undeclared, duplicate, missing, or conflicting identity;
- unsupported asset kind or content type;
- missing dependency, invalid relation, or prohibited graph shape;
- path traversal, collision, outside-root state, link, junction, reparse point,
  or alternate stream;
- oversized file, source set, graph, metadata, manifest, or tool output;
- adapter mismatch, unexpected output, incomplete cleanup, or subprocess bound;
- stale or mixed build evidence;
- content, size, type, integrity, or build-identity mismatch; and
- missing or invalid explicit delivery policy.

Inspect the exact declared source identity, protected source or artifact digest,
adapter profile, currentness comparison, bounded graph summary, containment
result, and cleanup result. Do not copy raw source bytes, executable output,
private paths, arbitrary tool manifests, environment values, credentials,
secrets, headers, cookies, or nonces into diagnostics.

## Deterministic CI and release direction

CI and release builds should supply the same explicit source and adapter inputs,
reproduce canonical evidence, validate exact currentness, and publish by content
identity without clobbering different bytes. Development uses that same
contract. Faster feedback may improve presentation but cannot introduce hidden
scans, request-time builds, ambient dev servers, mutable manifests, or stale
fallback.

Tool versions, plugins, lockfiles, executable provenance, and network-disabled
installation are application supply-chain concerns. Modern tooling may evolve
behind versioned adapters without requiring core support for obsolete tool
generations.

## Frontend-framework adapters

React, Vue, Svelte, HTMX, Alpine, or another browser tool may later receive an
optional upper adapter. No frontend framework becomes required, and none may
redefine neutral logical identities, currentness, containment, delivery policy,
or application authority. Phase 19D implements none of these frontend-framework
adapters.

## Lifecycle and performance review

Build and resolution work is operation-scoped. Temporary paths, subprocesses,
streams, buffers, selected identities, URLs, and policy state must be retired on
success and failure without cross-request leakage. No watcher, daemon,
background worker, global registry, service locator, shutdown cleanup, or
destructor cleanup is implied.

The accepted contract, build, and adapter boundaries set finite counts, byte
sizes, relation depths, path lengths, subprocess limits, time limits, and
diagnostic bounds. Phase 19A-G make no
performance, browser, accessibility, security, deployment, production, Stable,
or 1.0 claim.

## Review checklist

- Is the application better served by no Asset Platform capability?
- Is every source and relation explicit and bounded?
- Are logical identity, paths, URLs, content/build identities, and integrity
  metadata kept distinct?
- Is the native flow available without Node?
- Is every build tool isolated behind a replaceable adapter and treated as an
  untrusted producer?
- Are publication and currentness deterministic, contained, atomic, and
  no-clobber?
- Does development use the production contract without stale fallback?
- Does application or deployment code retain origin, CDN, CSP, headers, cache,
  credentials, and external-effect policy?
- Are active browser outputs reviewed as trusted application code rather than
  assumed sandboxed?
- Are lifecycle cleanup and nondisclosure explicit?

## Related material

- [Asset Platform](../architecture/asset-platform.md)
- [Exact-current asset resolution](../architecture/exact-current-asset-resolution.md)
- [Phase 19 roadmap](../architecture/phase-19-roadmap.md)
- [Phase 19 external asset acceptance](../architecture/phase-19-external-asset-acceptance.md)
- [ADR 0188](../adr/0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](../adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
- [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
- [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
- [ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md)
- [Replaceable asset-build adapters](../architecture/replaceable-asset-build-adapters.md)
- [Server-rendered presentation](../architecture/server-rendered-presentation.md)
- [View authoring](view-authoring.md)
- [API status](api-status.md)
- [Security policy](../../SECURITY.md)
