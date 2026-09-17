# Asset Platform

## Relationship to the Phase 20 cache capability

Phase 19 delivery metadata may carry an explicit application-owned browser or
HTTP cache policy. That metadata describes how validated content-addressed
asset output may be delivered to a browser or intermediary; it is not a
server-side derived-data cache entry, adapter, backend, operation, or grant of
Phase 20 authority.

Conversely, the Phase 20 Derived-Data Cache Platform is Accepted only as
documentation architecture in Phase 20A; Phases 20B-H remain Planned. It does
not select asset URLs,
origins, CDN behavior, cache-control headers, integrity, preload, storage, or
delivery. Neither capability implies, installs, configures, or depends on the
other. Views and asset resolution receive no ambient cache helper, facade,
registry, clock, or stale fallback. See the
[Cache Platform guide](cache-platform.md) and
[Phase 20 roadmap](phase-20-roadmap.md).

## Status

Phase 19A is Accepted documentation architecture. It defines the optional
Asset Platform boundary through [ADR 0188](../adr/0188-optional-asset-platform-and-deterministic-build-boundary.md)
and [ADR 0189](../adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md).
Phase 19B accepts [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
and the optional Experimental `kongono/asset-contract` neutral leaf. Phase 19C
accepts [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
and the optional Experimental `kongono/asset-build` leaf described by the
[deterministic build guide](deterministic-asset-build.md). Phase 19D accepts
[ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md),
the optional Experimental `kongono/asset-adapter-contract` neutral boundary,
and the separately optional Experimental `kongono/asset-adapter-vite`
fixed-profile implementation described by the
[replaceable adapter guide](replaceable-asset-build-adapters.md). Phase 19E
accepts [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
and the optional Experimental `kongono/asset-resolution` leaf described by the
[exact-current resolution guide](exact-current-asset-resolution.md). Phase
19F accepts [ADR 0194](../adr/0194-supported-asset-application-workflow-and-explicit-view-integration.md),
the optional Experimental `kongono/asset-application` package, three supported
commands, and the copy-once native slice described by the
[application workflow guide](asset-application-workflow.md). Phase 19G accepts
[ADR 0195](../adr/0195-visibility-safe-asset-inspection-and-developer-intelligence.md)
and the optional Experimental `kongono/asset-inspection` upper evidence leaf
described by the [asset-inspection guide](asset-inspection-and-intelligence.md).
Phase 19H accepts [ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md),
the root-owned [external acceptance](phase-19-external-asset-acceptance.md), and
the exact local checkpoint. Phase 19A-H are Accepted and Phase 19 is closed.
No Node/Vite dependency, package installation, network access, response
emission, automatic view integration, watcher/HMR, development server, or
frontend integration exists.

Applications that are API-only, CLI, worker, agent, headless, or mobile install
none of this stack. Server-rendered applications may continue to use
application-owned copied or prebuilt public files without a Kongono asset
capability.

## Purpose

The Asset Platform gives applications one framework-neutral vocabulary
for explicitly declared assets, deterministic build evidence, immutable
content-addressed outputs, exact-current resolution, and explicit delivery
policy. Kongono owns this contract and its validation boundaries; Kongono does
not become a JavaScript bundler.

Vite is intended as the first optional replaceable build adapter. It is not a
core dependency, runtime authority, required Node process, or permanent model.
A native or no-bundler flow is equally first-class.

## Implemented Phase 19B neutral contract

The Version 1 contract defines bounded immutable logical and SHA-256 content
identities, closed asset kinds and content types, unique declarations,
dependency relations, entry points, integrity evidence, and inert preload and
delivery metadata. Declaration sets are detached and ordinally canonical. An
empty declaration set is the first-class no-asset contract.

The contract validates relation shape, membership, uniqueness, and bounds. It
deliberately performs no graph traversal, reachability, cycle analysis, build
ordering, filesystem access, parsing, hashing of anything except directly
supplied bounded bytes, publication, URL construction, HTTP work, view
integration, or runtime resolution. Its evidence grants no authority.

## Boundary map

The following is Planned pseudocode describing responsibilities, not callable
API:

1. The application supplies one explicit bounded source declaration.
2. The neutral contract validates logical identities, asset kinds, content
   types, entry points, dependencies, paths, metadata, and bounds.
3. A native flow or separately selected adapter produces bounded candidate
   output.
4. Kongono validates the complete output against the neutral contract and
   creates deterministic graph, build, artifact, and currentness evidence.
5. A publisher writes only validated bytes beneath one explicit root by
   content identity, atomically and without clobbering different bytes.
6. A future resolver accepts one caller-supplied exact-current artifact and a
   logical identity, then returns bounded delivery metadata.
7. Application-owned browser, HTTP, CDN, storage, cache, CSP, header, and
   deployment policy decides how or whether those bytes become externally
   visible.
8. Application code or a separately accepted view adapter consumes explicit
   typed values; no ambient template helper or global manifest appears.

Completion of any step in a later roadmap row does not authorize the next.

## Logical identity model

A logical asset identity is the stable application meaning selected by code,
such as a particular application entry point. It is not a filename and does
not reveal where source or output bytes reside.

The model keeps logical identity, source path, output path, public URL, content
identity, build identity, and integrity metadata distinct:

- a source path locates one explicitly declared input;
- an output path locates one contained validated result;
- a public URL is chosen under application and deployment policy;
- a content identity is derived from exact validated bytes;
- a build identity covers the complete validated inputs and build contract;
- integrity metadata is an optional separately validated browser claim; and
- logical identity is the application-facing selection key.

Substitution among these concepts would create ambiguity or authority. A path
does not become a URL; a URL does not prove content; a digest does not grant
delivery; a tool-generated name does not define application identity.

## Implemented declarations and deterministic source graph

Phase 19B fixes Version 1 logical and content identities, seven asset kinds,
seventeen content types and their exact compatibility, three dependency kinds,
bounded entry points, integrity algorithms, preload hints, and delivery
metadata. Declaration sets accept at most 1,024 assets, 4,096 dependency
relations, and 256 entry points. They validate membership and uniqueness and
order values ordinally, but do not traverse relations or reject cycles.

The Phase 19C source set is complete, explicit, finite, and
application-owned. It adds in-memory sources without changing Phase 19B logical
identity. Hidden scans, conventional directories, globbing, remote discovery,
package discovery, and implicit transitive sources remain outside the contract.

Graph construction uses deterministic typed ordinal ordering. Missing targets,
duplicates, cycles across every dependency kind, conflicting
relations, source aliases, output collisions, mixed versions, unsupported
kinds, unreachable declarations, dependency depth above 256, and exceeded
bounds fail before publication. Phase 19B remains inert; Phase 19C owns this
deterministic validation without filesystem source reads.

## Accepted native build artifact and currentness

The Phase 19C artifact records only the bounded neutral evidence needed to prove
the accepted source set, graph, adapter profile, outputs, content identities,
delivery metadata, and build identity. Canonical encoding and reconstruction
must be deterministic and language-neutral.

Currentness compares one artifact with one explicit complete current source and
adapter-policy input. It never consults directory mtimes, chooses a newest file,
follows a mutable pointer, or accepts the build tool's assertion as proof.
Missing or different evidence is stale and fails closed.

Publication is content-addressed, root-contained, atomic, idempotent only for
identical valid bytes, and no-clobber. Linked, outside-root, missing,
state-changed, or pre-existing different output fails. Publication does not
make output public and grants no URL, HTTP, network, storage, or deployment
authority.

## Adapter model

An adapter translates between a particular tool and the neutral contract. Its
inputs, executable identity, working root, environment allowlist, process
bounds, output paths, standard streams, exit state, and cleanup obligations
are explicit in the accepted Phase 19D boundary. Tool output remains untrusted
until fully validated.

The contract can outlive any implementation language. PHP may host one adapter,
TypeScript or JavaScript another, Rust another, and an external process another.
Modern tool versions can evolve behind versioned adapters without obligating
core compatibility with obsolete tool generations.

Vite is the first optional adapter. Phase 19D does not install Node, Vite, npm,
pnpm, Yarn, Bun, Deno, a JavaScript lockfile, a plugin, or an executable, and it
does not authorize arbitrary plugin execution, package installation, network
download, watcher, development server, or hot reload.

## Native and no-bundler flow

The native flow is for explicitly declared copied or already-built application
assets. It remains subject to the same bounds, content validation, graph rules,
artifact currentness, containment, and publication rules as adapter output. It
does not require Node or fabricate a bundler manifest.

Applications may also select no Asset Platform at all. Files served entirely
under application or deployment ownership remain outside Kongono asset
evidence. That is a supported architectural choice, not a degraded mode.

## Accepted bounded Vite flow

The optional Phase 19D Vite flow is a peer adapter. It receives authenticated
complete inputs, one explicit application-provided executable, and one fixed
profile. It creates a contained operation workspace, invokes exactly one
bounded subprocess without a shell or ambient environment, strictly translates
the bounded Vite manifest and complete ordinary-file output tree into the
neutral model, validates paths, relations, output bytes, metadata,
containment, completeness, and artifact currentness, and attempts cleanup once.
Exit zero and manifest presence are not success by themselves. The adapter
cannot redefine logical identity or acquire network, credential, install,
delivery, URL, HTTP, view, or runtime authority. Empty requests short-circuit
without workspace or process activity.

## Delivery-policy separation

The neutral and build boundaries may carry distinct bounded evidence for
content type, size, content digest, dependency relations, optional integrity
metadata, preload hints, and cache identity. They do not select an origin,
public base URL, CDN, storage provider, cache-control response, CSP, nonce,
security header, authentication, authorization, cookie, session, or credential.

A future exact-current resolver maps logical identity only to validated bounded
delivery metadata. URL construction requires explicit application policy and
cannot concatenate untrusted input. Byte serving through a web server,
application response, object store, or CDN is an outer adapter or deployment
concern. Preload evidence does not emit markup, and integrity metadata does not
replace CSP or origin policy.

## Security boundaries

Treat declarations, source bytes, package contents, build executables, plugins,
tool manifests, generated output, content-type claims, URLs, source maps, and
integrity claims as hostile at their respective inputs. Required later checks
include:

- canonical path validation and containment beneath explicit roots;
- rejection of traversal, ambiguity, case collisions, links, junctions,
  reparse points, alternate streams, and outside-root state;
- no remote source, registry, DNS, provider, or network lookup in neutral
  validation, build evidence, or resolution;
- bounded bytes, counts, relations, paths, metadata, subprocess streams, time,
  and diagnostics;
- validation of tool output against the neutral contract before publication;
- no private path, source byte, output byte, secret, credential, environment,
  nonce, cookie, header, raw manifest, or unbounded value disclosure; and
- complete operation cleanup after primary or cleanup failure.

Browser-executable JavaScript, CSS, WebAssembly, markup-bearing resources, and
other active content are trusted application build input and output. Kongono
does not sandbox, audit, certify, or make them safe merely by hashing them.
Supply-chain selection, lockfiles, tool installation, plugin review, and build
environment hardening remain application and deployment responsibilities.

## Determinism and persistent-runtime lifecycle

Shared declarations, graphs, artifacts, and resolution evidence are immutable.
Each build, validation, publication, resolution, or adapter invocation owns its
mutable state and cleanup. Sequential and overlapping operations cannot share
selected source sets, output buffers, temporary paths, subprocesses, URLs,
headers, nonces, or policy state.

No global registry, service locator, shutdown cleanup, destructor cleanup,
request singleton, retained tool process, watcher, daemon, or background worker
is part of the model. Primary and cleanup failure truth remains distinguishable.
Partial output is not success.

## Developer experience direction

Later phases may add explicit build, check, inspect, and resolve commands with
the same machine-readable operation semantics available to application code.
Human and machine paths must validate the same explicit source, artifact, and
policy evidence. Development may offer clearer diagnostics but cannot weaken
production currentness or use hidden builds and stale fallback.

Diagnostics should name the operation, violated invariant, bounded logical
identity or digest evidence, and a concrete remediation such as rebuilding from
the complete declared source, correcting a collision, or supplying the exact
artifact. They must not disclose raw sources, outputs, private paths, arbitrary
tool text, or delivery secrets.

See [Asset authoring](../engineering/asset-authoring.md) for developer-facing
planned workflows.

## Composition and dependency direction

The accepted direction is inward from application composition and optional
outer adapters toward neutral asset contracts. Lower Kernel, HTTP, routing,
security, Graph, extension, observability, and Phase 18 view packages do not
depend on the Asset Platform. Phase 19F proves explicit Phase 18 integration in
application code only, where already resolved metadata becomes ordinary typed
presentation values.

## Performance direction

Future work should bound source hashing, graph validation, artifact decoding,
lookup, output size, subprocess startup, and memory use. Content-addressed
evidence may permit safe application-owned reuse, but no cache, benchmark,
latency, throughput, scalability, browser, or production performance claim is
accepted in Phase 19A-H.

## API status direction

Phase 19B adds 19 callable declarations: 15 Experimental contract values,
enums, and the concrete validation exception; one Public exception-family
interface; and three Internal diagnostic/exception/guard declarations. No
declaration is Stable. Phase 19D adds 49 production declarations across two
optional packages: 33 Experimental values and boundaries, two Public
exception-family interfaces, and 14 Internal diagnostics, exceptions,
workspace, and implementation declarations. None is Stable.
Phase 19E adds 24 declarations: 20 Experimental, one Public exception-family
interface, and three Internal diagnostic/exception/guard declarations. None is
Stable. Phase 19F adds 18 Asset Application declarations: nine Experimental
values and workflows, one Public exception-family interface, and eight
Internal guards, diagnostics, and concrete foundations. Phase 19G adds 22 Asset
Inspection declarations: 19 Experimental evidence/service values, one Public
exception-family interface, and two Internal canonicalization/diagnostic
declarations. None is Stable. The three Console
handlers are Experimental. None is Stable. Public promotion and 1.0 require separate compatibility
policy and evidence. PHP 8.5 remains the current baseline.

## Exclusions

Phase 19B provides no source/output path, schema, manifest, parser, graph
implementation, compiler, bundler, subprocess, publisher, build, runtime
resolver, URL generator, HTTP response, tag renderer, view helper, command,
watcher, development server, hot reload, daemon, installer, deployment manager,
CDN/storage/frontend/mobile adapter, inspection role, checkpoint, release,
performance claim, browser claim, certification, production readiness, Stable
status, or 1.0 status.

## Related material

- [ADR 0188](../adr/0188-optional-asset-platform-and-deterministic-build-boundary.md)
- [ADR 0189](../adr/0189-exact-current-asset-resolution-and-browser-delivery-policy.md)
- [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
- [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
- [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md)
- [ADR 0193](../adr/0193-exact-current-asset-resolution-and-explicit-browser-http-policy.md)
- [ADR 0194](../adr/0194-supported-asset-application-workflow-and-explicit-view-integration.md)
- [ADR 0195](../adr/0195-visibility-safe-asset-inspection-and-developer-intelligence.md)
- [ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md)
- [Phase 19 external asset acceptance](phase-19-external-asset-acceptance.md)
- [Asset inspection and developer intelligence](asset-inspection-and-intelligence.md)
- [Asset application workflow](asset-application-workflow.md)
- [Replaceable asset-build adapters](replaceable-asset-build-adapters.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
- [Asset authoring](../engineering/asset-authoring.md)
- [Server-rendered presentation](server-rendered-presentation.md)
- [Phase 18 roadmap](phase-18-roadmap.md)
- [Application composition and build](application-composition-and-build.md)
- [Microkernel](microkernel.md)
- [Architectural doctrine](../doctrine.md)
- [Security policy](../../SECURITY.md)
