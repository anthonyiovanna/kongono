# Kongono Architectural Doctrine

## Optional Application Events doctrine

Phase 22A accepts [ADR 0215](adr/0215-optional-application-events-and-explicit-synchronous-dispatch.md),
the [Application Events guide](architecture/application-events.md),
[event authoring](engineering/event-authoring.md), and the
[six-row roadmap](architecture/phase-22-roadmap.md) as documentation
architecture only in Phase 22A. Phase 22B implements the inert optional
Experimental `kongono/event-contract` under
[ADR 0216](adr/0216-neutral-application-event-contract-and-bounded-registration.md).
Application-owned occurrences may be offered to directly supplied handlers
through the separately optional Experimental `kongono/event-runtime` under
[ADR 0217](adr/0217-optional-synchronous-application-event-dispatcher-and-instance-lifecycle.md).
Direct calls remain valid. Complete
registration validation precedes effects; exact matching and explicit order
govern dispatch; no-match is a no-op; the first handler Throwable stops later
handlers and reaches the caller unchanged while earlier effects remain
possible. Same-instance reentrancy is rejected and per-dispatch state is
released. Payload/business validation and effect authorization belong to
applications. Events are not telemetry, audit, Graph facts, commands, queues,
durable messages, or authority. The runtime retains the validated set and an
instance-local busy flag, passes the same event object to exact matches,
rejects same-instance overlap before matching and releases the guard in
`finally`. Phases 22A-C are architect-accepted within their recorded limits.
The [copy-once event-minimal example](../skeletons/event-minimal/README.md)
shows caller-owned direct composition and authorization before each effect.
Phase 22D is architect-accepted within its recorded local limits. Phase 22E
assembled-path and local copied-example validation are complete, including a
bounded demo bootstrap warning correction, and architect-accepted within their
recorded limits. Phase 22F's offline own-autoload consumer and current-source
focused validation have passed; broad validation, advisory audit, architect
milestone acceptance and the exact checkpoint remain pending. There is no
automatic publisher or completed Phase 22 checkpoint.

## Optional Object Storage Platform doctrine

Phase 21A accepts documentation architecture for an optional provider-neutral
Storage Platform. Phase 21B accepts only the optional Experimental inert
`kongono/storage-contract` package. Its bounded identities, declared support,
content evidence, conditions, requests, outcomes, results, and stream
lifecycle interfaces grant no authority and perform no I/O. Phase 21C accepts
only the optional Experimental `kongono/storage-build` package for explicit
profiles, canonical artifact evidence, strict exact-current validation, and
contained no-clobber publication. Phases 21A-G are Current and Accepted;
Phase 21D accepts the optional Experimental neutral adapter protocol and
separately optional Experimental contained local-filesystem implementation.
Phase 21E accepts the separately optional Experimental
`kongono/storage-runtime` exact-current operation leaf over one exact profile
and one directly supplied compatible neutral adapter; it does not require the
local adapter. Phase 21F accepts the optional Experimental
`kongono/storage-application` workflow, three profile commands, and the
copy-once `storage-minimal` example with an application-supplied authorizer.
Phase 21G accepts optional Experimental `kongono/storage-inspection`
offline evidence. It checks supplied
in-memory profile/artifact coherence and emits only bounded Safe aggregates
and Protected digests. See [ADR 0213](adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](architecture/storage-inspection-and-intelligence.md).
Phase 21H adds [root-owned local acceptance](architecture/phase-21-external-storage-acceptance.md)
under [ADR 0214](adr/0214-phase-21-external-storage-acceptance-and-checkpoint.md).
The separate time-bound audit, architect acceptance, and exact checkpoint
completed for Phase 21 with their recorded local-validation limits. No cloud
provider, credential discovery, generated business authorization, or public
delivery is implemented.

Storage may retain authoritative and irreplaceable application-owned binary
content. It therefore never inherits cache expiry, eviction, loss-tolerance,
stale fallback, or best-effort mutation. Logical identity, namespace, provider
location, content evidence, metadata, concurrency evidence, operation
lifecycle, authority, authorization, credentials, and egress permission remain
separate.

The doctrine requires explicit capabilities, provider-neutral contracts,
honest adapter limitations, create-only and conditional mutation, visible
uncertain outcomes, bounded streaming, cancellation, close/abort, deterministic
cleanup, persistent-runtime isolation, contained local mapping, deterministic
profile artifacts, exact-current runtime consumption, and bounded
nondisclosing diagnostics. It prohibits ambient roots, path/string authority,
facades, service location, runtime scanning, provider auto-selection, silent
overwrite, hidden retry, directory discovery, and automatic public URLs.

See [ADR 0206](adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
[ADR 0208](adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
[ADR 0209](adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md),
[ADR 0210](adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md),
[ADR 0211](adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md),
[ADR 0212](adr/0212-supported-storage-application-workflow-and-explicit-authorization.md),
the [Storage Platform guide](architecture/storage-platform.md), the
[deterministic storage-profile build guide](architecture/deterministic-storage-profile-build.md), the
[contained local adapter guide](architecture/contained-local-storage-adapter.md), the
[exact-current storage runtime guide](architecture/exact-current-storage-runtime.md), the
[storage application workflow guide](architecture/storage-application-workflow.md), the
[storage-authoring guide](engineering/storage-authoring.md), and the fixed
[Phase 21 roadmap](architecture/phase-21-roadmap.md).

## Optional Derived-Data Cache Platform doctrine

Phase 20A accepts documentation architecture for an optional Derived-Data
Cache Platform. Phase 20B accepts only the optional Experimental
`kongono/cache-contract` neutral leaf of bounded immutable values, closed
evidence vocabulary, and nondisclosing diagnostics. Phase 20C accepts the
optional Experimental `kongono/cache-build` deterministic profile, canonical
artifact, exact-current validation, and contained publication leaf. Phase 20D
accepts the optional Experimental neutral adapter
protocol and separately optional Experimental bounded memory implementation.
Phase 20E accepts exact-current operations with explicit caller time and one supplied adapter. Phase 20F accepts explicit application plan/build/check, inert codec mapping, and direct runtime composition. Phase 20G accepts offline supplied-evidence inspection. Phase 20H adds only root-owned local external acceptance and the exact checkpoint. Phases 20A-H are Accepted and Phase 20 is closed. No framework serialization, external provider integration, or production-availability claim exists.

Cached data is bounded, derived, disposable, and non-authoritative.
Applications remain correct when it is absent, expired, evicted, rejected,
unavailable, or flushed. Cache evidence never replaces persistence,
exact-current validation, security decisions, credentials, sessions, grants,
durable records, audit evidence, coordination, or proof of an effect.

Application values, namespace, logical key, key identity, codec/schema,
bounded opaque bytes, content identity, entry version, finite freshness,
caller-supplied time, provider identity, operation, and typed outcome remain
explicit and separate. The contract has no ambient clock, hidden default,
facade, global registry, service location, magic serialization, implicit stale
path, read-through callback, provider discovery, or implicit retry.

Adapters are directly supplied and replaceable behind the neutral contract.
The first in-memory adapter is Accepted and carries no durability,
distribution, shared-host, multi-process, performance, or production claim.
Atomic entry visibility and version-bound conditional mutation do not grant
lock, lease, queue, scheduler, rate-limit, counter, or exactly-once semantics.
Mutable state is operation-local, results are detached, and retirement is
deterministic on every path.

Lower packages do not depend outward on caching. Provider and PSR
interoperability may be future optional adapters but cannot redefine the
internal correctness model. See [ADR 0197](adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md),
[ADR 0198](adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md),
[ADR 0201](adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md),
[ADR 0205](adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md),
the [Cache Platform guide](architecture/cache-platform.md), and the
[Cache Application workflow](architecture/cache-application-workflow.md), the
[Phase 20 external acceptance guide](architecture/phase-20-external-cache-acceptance.md), and the
[Phase 20 roadmap](architecture/phase-20-roadmap.md).

## Optional deterministic Asset Platform doctrine

Phase 19A accepts documentation architecture. Phase 19B accepts the optional
Experimental `kongono/asset-contract` neutral leaf. Kongono owns an optional,
language-neutral Asset Platform abstraction, not a JavaScript bundler. Headless
and no-asset applications install none of it. Native or no-bundler assets are a
first-class path, while Vite is the first optional replaceable
adapter and never a core dependency or conceptual authority.

Future asset declarations and artifacts are explicit, complete, bounded,
deterministic, content-addressed, contained, no-clobber, and exact-current.
Logical identity, source path, output path, public URL, content identity, build
identity, and integrity metadata are separate evidence. Build tools remain
untrusted producers until complete output is validated against the neutral
contract. Development uses the same contract as production and receives no
hidden build, directory scan, ambient manifest, newest-file selection,
request-time process, or stale fallback.

Browser-executable output is trusted application code or content, not a
Kongono sandbox. Application and deployment layers own origin, CDN, CSP,
nonces, headers, cache, credentials, byte delivery, and every external effect.
Modern tools may evolve behind versioned adapters without forcing core
compatibility with obsolete tool generations. PHP 8.5 remains the baseline;
future readiness means explicit contracts and replaceable adapters, not claims
about unverified PHP 8.6 or 9 behavior.

Phase 19B values are bounded, immutable, deterministic, and inert. Logical
identity, content identity, integrity, preload hints, and delivery metadata
remain distinct and grant no authority. An empty declaration set is the
first-class no-asset contract. Phase 19C adds only one optional explicit
source-set, complete deterministic graph, byte-identical native build,
canonical exact-current artifact, and contained no-clobber publication leaf.
Phase 19D accepts the optional neutral adapter contract and separately optional
bounded fixed-profile Vite adapter. Phase 19E accepts exact-current effect-free
resolution, Phase 19F the explicit application workflow, and Phase 19G the
offline inspection leaf. Phase 19H accepts root-owned external acceptance and
the exact checkpoint. Phase 19A-H are Accepted and Phase 19 is closed. No
network, automatic browser delivery, watcher/HMR, development server, frontend
integration, real Vite certification, Stable status, 1.0 status, or Phase 20
authority is added. See [ADR 0196](adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md)
and [external acceptance](architecture/phase-19-external-asset-acceptance.md).

## Compiled server-rendered presentation doctrine

Phase 18A accepts [ADR 0179](adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
the [presentation guide](architecture/server-rendered-presentation.md), and the
[fixed roadmap](architecture/phase-18-roadmap.md) as documentation architecture.
Phase 18B accepts [ADR 0181](adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and only the optional Experimental neutral view-contract leaf. Phase 18C
accepts [ADR 0182](adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and the optional Experimental deterministic View Build leaf. Phase 18D accepts
[ADR 0183](adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the optional exact-current [View Runtime](architecture/compiled-view-runtime.md).
Phase 18E accepts [ADR 0184](adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the optional explicit [View HTTP adapter](architecture/compiled-view-http.md).
Phase 18F accepts [ADR 0185](adr/0185-supported-compiled-view-application-workflow.md),
the optional exact-current [View Application workflow](architecture/compiled-view-application-workflow.md),
three explicit commands, and one copy-once example.
Phase 18G accepts [ADR 0186](adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the optional [View Inspection](architecture/compiled-view-inspection-and-intelligence.md)
leaf. It validates one supplied profile in memory and derives only bounded
Safe/Protected evidence without I/O, generated-code execution, rendering,
recording, Graph establishment, or role execution. Phase 18H accepts the
root-owned local proof and exact checkpoint under [ADR 0187](adr/0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md).
Phase 18A-H are Accepted and Phase 18 is closed.

Presentation is an optional application-selected upper capability. Routing
selects a handler, not a template. Neutral render contracts remain independent
of PSR-7, and HTTP adaptation is an explicit separate upper package over
completed results only.
Templates and rendered bytes are evidence, not authority.

Compile before runtime. Use one explicit bounded source set, deterministic
content-addressed ordinary PHP, immutable metadata, and exact-current contained
loading. Do not discover, scan, parse, interpret, compile, reflect, `eval`, or
locate services at request time. Development convenience uses the same
deterministic pipeline.

Data is closed, schema-declared, bounded, and presentation-specific. Plain
strings are data; context is known at compile time; unsupported contexts fail
closed. Rendering is synchronous, operation-scoped, bounded, stateless, and
isolated across sequential and overlapping calls. Partial output is failure,
temporary state is retired on every path, and primary plus cleanup truth is
preserved. Phase 18B adds inert closed contract evidence and diagnostics. Phase
18C adds the closed build-time source/compiler/artifact boundary but never loads
or executes generated PHP. Phase 18D authenticates one explicit contained
artifact and executes its generated class synchronously with contextual escaping,
exact limits, operation isolation, and partial-output rejection. No source is
parsed or compiled during rendering. Generated code and the deployment
filesystem are trusted inputs.

> **Current through Phase 14G:** the optional bounded model-invocation runtime
> validates one exact-current plan and runs one directly supplied adapter once.
> The optional inert bridge validates one eligible action suggestion and maps
> it through exact trusted application configuration into proposal evidence.
> Phase 14F binds one exact-current profile, deterministic inert wiring, and one
> direct adapter for one terminal operation without calling the bridge. The
> optional inspection leaf revalidates exact supplied evidence without loading
> generated PHP and emits only bounded inert aggregate views. The graph is 75
> packages / 289 local edges / acyclic. Phase 14 is closed at checkpoint
> `05bf8367b56e694a8c388ccb96cdc305cb6e40ae`.

Phase 14B accepts [ADR 0148](adr/0148-neutral-versioned-model-invocation-contract-foundation.md)
and implements only the optional Experimental
`kongono/model-invocation-contract` values described by the [contract
guide](architecture/model-invocation-contract-foundation.md). Phase 14C accepts
[ADR 0149](adr/0149-deterministic-model-invocation-plan-artifact.md) and adds
only the optional deterministic [plan-artifact boundary](architecture/model-invocation-plan-artifact.md). Optional model
invocation sits beside, not
inside, Phase 13 authority. Applications own policy, exact profile and adapter
supply, credentials, transport, budgets, and any selection of a result-to-
proposal mapping. Phase 14E supplies only a deterministic reduction mechanism;
the mapping remains trusted application configuration and the model arguments
remain untrusted inert content.
Provider capabilities, prompts, responses, stream events, tool suggestions,
normalized results, usage/cost evidence, and receipts remain inert evidence.
The values perform no provider/model call, SDK or credential work, network,
adapter execution, plan build, authority effect, serialization,
command, persistence, or runtime enforcement. Raw messages are absent from plan
artifact bytes; plans, budgets, adapter labels, and digests remain evidence.
The bridge is not an `AgentProposalPort`, grant, capability invocation, or
proof of effect. Raw action arguments never become capability input. Phase 14F
adds only the separate single-profile application workflow; trusted generated
PHP is not a sandbox. Phase 14G inspection is explicit, offline, read-only,
stateless, and presentation-neutral; it does not replay runtime evidence or
establish live provider, application, or authority truth. The graph is 75
packages / 289 local edges / acyclic. Phase 14 is closed at checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`.

Phase 13 is closed. Phases 13B-G implement separate opt-in neutral Agent Principal contract,
deterministic declaration artifact, deterministic admission, and synchronous
one-operation runtime packages plus one exact-current application workflow and
one visibility-safe read-only inspection leaf.
Agent identity, declaration, admission, profile, generated identity, and
authority-plan evidence are not authority; requests and proposals are
not grants; and model, provider, prompt, retrieval, memory, extension, Graph,
inspection, and developer-tool output cannot authorize effects. Future
capabilities are explicit, typed, bounded, attributable, and denied by default.
See [ADR 0139](adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
the [Agent Principal Platform](architecture/agent-principal-platform.md), and
the [Agent Declaration Artifact](architecture/agent-declaration-artifact.md),
the [Agent Admission and Authority Plan](architecture/agent-admission-and-authority-plan.md),
and the [Agent Principal Runtime](architecture/agent-principal-runtime.md).
The outer runtime boundary is the [Agent Application Workflow](architecture/agent-application-workflow.md);
the separate upper evidence leaf is [Agent Principal Inspection and Evidence](architecture/agent-principal-inspection-and-evidence.md).
Phase 13H adds only root-owned external acceptance and the exact local
checkpoint. The harness is deterministic offline test evidence, not identity,
authority, execution, monitoring, recording, repair, or certification. See
[ADR 0146](adr/0146-phase-13-external-agent-principal-acceptance-and-checkpoint.md).

Phase 12H closes Phase 12 with root-owned external evidence only. Declaration
evidence is not admission, requests are not grants, neutral Graph IR is not
execution authority, visibility is not authorization, and inspection grants no
currentness, trust, host-state, or authority claim. Trusted in-process PHP is
not sandboxed. See [ADR 0138](adr/0138-phase-12-external-extension-platform-acceptance-and-checkpoint.md).

> **Phase 12 current boundary:** Phases 12A-12H are implemented to their
> accepted boundaries. Phase 12H adds external acceptance evidence only; the
> graph remains 63 packages / 226 acyclic local edges.

> **Phase 13 current boundary:** Phase 13B adds the neutral contract package,
> Phase 13C adds deterministic declaration artifacts, Phase 13D adds
> deterministic admission and inert authority-plan evidence, Phase 13E adds
> one provider-neutral bounded runtime, Phase 13F adds one exact-current
> application workflow, Phase 13G adds deterministic Safe aggregate
> inspection, bringing the graph to 69 acyclic production packages / 260 local
> edges. Admission, generated, inspection, Graph, projection, and verifier
> evidence are not grants;
> receipts are claims; trusted in-process PHP is not sandboxed. No provider SDK,
> provider adapter, profile registry, state or memory store, autonomous loop, or
> background runtime exists. Inspection records nothing and observes no live
> application, provider, or external-authority state. Phase 13H adds only
> root-owned external evidence and exact checkpoint
> `63376c802ac4672c21458b67b2fcadaf8e03a019`. Phase 14A now adds documentation
> architecture only; Phase 14B now adds only the separate neutral contract
> package and Phase 14C remains planned.

## Status

The principles in this document are locked architectural direction. Phase 2A
through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Exactly fourteen acyclic production
packages implement the bounded framework path, and a root-owned, test-owned
fixture proves that path externally. The fixture is not a production
application, supported template, SAPI adapter, server, listener, emitter,
deployment entrypoint, or trust mechanism.

Phase 3B selects PHP `cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER` as the first
exact production host profile. Phase 3C and ADR 0053 implement bounded explicit
inbound conversion. Phase 3D and ADR 0054 implement fully staged bounded
response emission. Phase 3E and ADR 0055 implement explicit typed operator
selection, fixed native bootstrap validation, and independent current-delivery
permission evaluation without a general configuration or raw scalar parser.
Streaming is unsupported, and post-commit failure is uncertain, throws, and is
never rolled back or retried. The Phase 3F delivery and thin production entry,
Phase 3G front-controller lifecycle/deployment constraints, and Phase 3H
test-owned external acceptance are implemented. The acceptance harness is not a
production listener, server, client, process manager, or deployment artifact.
Kernel remains
transport-neutral; Web Application retains one local Kernel scope per accepted
application call; and Web Runtime retains outer failure and send/close
orchestration. Phase 3 is complete; no next phase starts automatically. See the
[PHP CGI profile](architecture/php-cgi-fastcgi-profile.md) and [Phase 3 roadmap](architecture/phase-3-roadmap.md).

Phase 2B accepts PSR-7, the selected server-side PSR-17 factories, future
PSR-15, and the first-party `kongono/http-message` package boundary. Phase 2C
implements that ninth package with the five selected message roles, five
stateless factories, and bounded package-local diagnostics. Phase 2D implements
Routing as a separate tenth package consuming only PSR-7 interfaces, API
Metadata, and Diagnostics. It performs exact encoded method/path matching with
no normalization, handler execution, authorization, or retained request state.
Phase 2F owns application bootstrap and application-call Kernel scope cleanup,
Phase 2G owns the ports-only outer adapter, and Phase 2H's fixture proves their
composition. Production host conversion, emission, deployment, and broader
resource cleanup remain unimplemented.

## Product philosophy

Kongono Framework is a modern application framework designed for humans, software clients, and AI agents. Its guiding product statement is **Build what comes next.** It is not a Laravel clone. It seeks comparable developer productivity through an explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

Applications should be understandable to developers, infrastructure, tooling, AI agents, and other applications without relying on undocumented convention or probabilistic inference.

## Authoritative application knowledge

The planned Application Graph will be Kongono's authoritative, machine-readable application representation. It is intended to describe modules, services, dependencies, routes, actions, entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation relationships.

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, not the permanent conceptual boundary. The implemented in-memory subset contains only immutable nodes with opaque graph-local keys and open kinds, plus directed open-kind relations between existing nodes. It validates duplicates and endpoints, returns canonical binary-string order, performs exact node lookup, selects immediate outgoing or incoming relations by exact endpoint-key value, and selects whole-snapshot nodes or relations by exact kind-label value. Node-kind and relation-kind selection remain separate domains even when labels are byte-equal. Absent kinds return empty lists; kind results preserve canonical order and the same immutable objects in fresh arrays. Node-kind selection scans `N` nodes in `O(N)` time, relation-kind selection scans `R` relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open labels define no vocabulary, schema, hierarchy, or compatibility rules. These narrow read boundaries are not combined filters, multiple-kind selection, predicates, traversal, reachability, paths, neighbor selection, or a generic query system. The snapshot is not yet the full authoritative representation. Stable identities, schemas, metadata, provenance, visibility, serialization, discovery, compilation, persistence, mutation, framework integration, query planning, and performance budgets remain experimental or open.

AI may suggest interpretations and changes, but correctness-critical application structure, authorization, security policy, migration safety, dependency legality, module boundaries, and API contracts must be determined by Kongono-owned, deterministic mechanisms.

## Microkernel and capabilities

Kongono will use a small microkernel with independently installable components that contribute coherent capabilities. Potential capabilities include HTTP, Data, ORM, Migrations, Authentication, Authorization, Events, Queues, Workflows, Cache, Storage, Mail, Notifications, Realtime, Scheduling, AI, Agents, Delegation, MCP, A2A, Frontend integration, Testing, and Developer tooling.

This list is direction, not a package map. The fourteen implemented production
packages are API Metadata, Application Graph, Dependency Injection, Diagnostic
Activation, Diagnostic Handler, Diagnostic Presentation, Diagnostics, HTTP
Message, HTTP Server, Kernel, PHP CGI, Routing, Web Application, and Web Runtime. The
resolver is not a general capability platform and implies none of the other
listed packages.

Neutral ownership of bootstrap API metadata and acyclic package dependencies is
locked and implemented. Phase 0S moved `Api` and `ApiStatus` atomically from the
former `Kongono\Kernel\Api\` namespace to `Kongono\ApiMetadata\` in the fifth
`kongono/api-metadata` package. Kernel, Diagnostics, Graph, and DI depend
directly on that neutral package; Graph, Kernel, and DI independently require
Diagnostics.
The old FQCNs are absent without a shim. Phase 0S does not authorize Kernel
diagnostic context.

## Progressive architecture

MVC remains a friendly default at the HTTP and UI edge. Business logic must not be structurally trapped in controllers. Applications should be able to evolve toward actions or use cases, modular monoliths, domain-driven architecture, ports and adapters, justified CQRS, events, and durable workflows without switching frameworks.

Flexibility at application architecture layers does not weaken framework invariants. Phase 0F implements only explicit typed constructor resolution with transient construction. Phase 8E implements only deterministic Console result/help presentation and explicit one-write delivery; real commands, native process integration, executable behavior, and application integration remain unimplemented. Broader dependency injection, the authoritative Application Graph, architecture validation, security metadata, telemetry, and other machine-readable contracts remain bounded by their current accepted state.

## Persistent-runtime-first lifecycle

Core lifecycle design must not assume the process or all application state ends after each HTTP request. Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime may become a hard kernel dependency.

The implemented Kernel lifecycle boots once, permits overlapping opaque operation scopes owned by Kernel-instance object identity, refuses shutdown while work is active, and exposes no ambient current operation. Web Application now orchestrates exact begin/end cleanup around one application call without changing Kernel. Scoped data, broader resource cleanup, context propagation, cancellation, and runtime adapters remain open.

## Platform boundaries

PHP 8.5 is the primary application language. TypeScript and JavaScript are for the browser and frontend environment. Rust may later be used for high-performance framework tooling, while Go may be selected for specific infrastructure or runtime integration. No Rust, Go, Node.js, or TypeScript tooling is introduced in Phase 0A.

Kongono will not build a JavaScript bundler. Vite is the intended default build integration. Kongono will own an Asset Platform abstraction, with possible future adapters for React, Vue, Svelte, HTMX, Alpine, server-rendered HTML, and native/no-bundler assets.

## Observability and APIs

OpenTelemetry is intended to be native to the framework's instrumentation model. Framework operations should eventually emit structured telemetry hooks, with replaceable exporters. No telemetry package is selected or installed in Phase 0A.

OpenAPI is intended to be generated from authoritative application metadata so contracts do not require independent manual maintenance. No OpenAPI mechanism exists yet.

## Actionable diagnostics and error boundaries

Every deliberate Kongono failure must answer:

```text
What failed?
Why did it fail?
Where did it fail?
What can the developer do next?
```

Deliberate framework failures belong to the package or capability that owns the
violated invariant. Expected application outcomes remain ordinary typed control
flow rather than automatically becoming exceptions. Infrastructure failures are
wrapped only when an owning semantic layer adds meaning, and the original cause
chain must be preserved. Engine and programmer failures must not be disguised as
validation or business outcomes.

Diagnostics must be precise without assuming that identifiers, values, paths,
provider messages, source, request data, or environment data are safe for an
untrusted audience. Protected developer detail and intentionally generic
production output remain separate. A client-controlled request value must never
enable debug disclosure.

Persistent-runtime operation boundaries must eventually guarantee cleanup while
preserving the primary failure, reporting cleanup failure, and retiring runtime
instances whose state becomes uncertain. The exact cleanup and simultaneous-
failure mechanisms remain open.

Phase 0M implements the 42 current assignments through three package-local
Internal catalogs. Each existing Public package marker exposes the stable ID,
title, and remediation as strings while markers remain unrelated and messages
remain unchanged protected detail. Titles, remediation, and messages are prose;
only the ID is stable machine identity. No universal diagnostic interface, safe
structured context, renderer, error page, logger, telemetry integration, retry
engine, cleanup runner, incident system, localization, or transport mapping
exists.

Diagnostic structural context is never an arbitrary bag. Schemas fix
operation, violated invariant, keys, labels, scalar kinds, disclosures, order,
and optionality; direct construction produces eager immutable detached snapshots
bounded to 16 fields and free of ambient state, I/O, or global per-failure
accumulation. Structural validity alone does not make a schema semantically
authorized. The Graph catalog owns exact schemas for its 11 identifiers and
Graph throw sites provide only their approved values. Phase 0T does the same for
all six Kernel lifecycle failures using required Safe fields. Phase 0U completes
the original families with 25 DI schemas and 26 exact throw-site maps.

Disclosure has exactly two cases. Safe means eligible under a later explicit
policy, not automatically public. Protected means trusted developer or
protected operational use only. Secret-bearing, unbounded, or uncertain data is
forbidden from capture rather than assigned a third classification. The four
package catch boundaries remain separate now that `kongono/diagnostics` owns
presentation-neutral values and its own construction failures. AI may
explain diagnostics but cannot classify fields, override disclosure, recover
forbidden material, or become authoritative for interpretation.

The value types, structural validation, all 42 original Kernel/Graph/DI catalog
schemas, their three package-local marker accessors, and exact throw-site capture
are implemented. Diagnostics construction failures remain context-free. No
forbidden-data detection, sanitizer, transport, logger, or telemetry hook is
implemented. Safe and Protected classifications authorize no presentation.

Diagnostic presentation belongs to the separate implemented leaf package,
never to presentation-neutral Diagnostics or an originating semantic package.
Its first implemented model is a pure bounded projection over exactly one Kernel,
Graph, or DI contextual marker. It preserves package ownership, catalog prose,
fixed operation and invariant, canonical field order, value kind, Safe or
Protected disclosure, and truncation state without messages, causes, traces,
source, requests, environment, transport data, I/O, caches, or retained source
objects. It does not create a universal exception marker.

Trusted diagnostic activation is governed by a locked two-gate fail-closed
rule. At the Phase 1 boundary, a future outer composition boundary had to provide affirmative
**trusted bootstrap permission** as an immutable decision for one adapter instance,
and the adapter must independently provide affirmative
**adapter-controlled delivery permission** for the current delivery. Both precede presentation access, projection, copying,
rendering, and delivery. Absence, invalidity, ambiguity, evaluation failure,
denial, or uncertainty prohibits the trusted path. No request or other client
input, raw environment label, truthy string, heuristic, AI output, ambient
global or service state, or prior operation may enable or widen either
permission. Phase 1C implements the Experimental unbacked
`TrustedDeveloperBootstrapPermission` value and Phase 1D adds the Experimental
unbacked `AdapterControlledDeliveryPermission` value in the separate Diagnostic
Activation package. They are neutral typed inputs, not evidence evaluators or
authorization by themselves. Web Runtime now performs the implemented two-gate
fail-closed response-selection and delivery sequence from explicitly supplied
decisions. Raw operator configuration, host evidence evaluation, and production
SAPI integration remain future and unimplemented. API status,
package-local tokens, output escaping, and direct projector or renderer
callability are not authorization. The trusted developer model is prohibited as
a production model; production needs a separate deny-by-default policy and
explicit allowlists. Projection and concrete rendering remain distinct
implemented responsibilities.

Phase 1E's pure conjunction returns true only when both typed permissions are
`Allowed`. The eligibility object is limited to one current delivery, proves no
external authority, and performs no enforcement or presentation access.

Phase 0Z implements the first concrete presentation architecture accepted in
Phase 0Y. Actionable developer failures are deliberately structured into
fixed, scannable regions that state what happened, why, bounded context, and how
to fix it. Output encoding belongs to the final grammar boundary: the implemented
HTML renderer escapes every dynamic text value there and never accepts raw
markup. Its exact template and CSS are fixture-backed and it retains no state.
Escaping and disclosure authorization are separate controls; escaped Protected
data remains Protected. Diagnostic presentation must remain useful without
remote assets, network access, or AI services. Both typed permission decisions
and their neutral Web Runtime enforcement now exist, while raw-input validation,
operator and host evidence evaluation, production SAPI transport, and production
presentation remain future outer-boundary concerns.

## Agents and interoperability

Kongono recognizes Human Experience, Software/API Experience, and Agent Experience as first-class consumption surfaces. An application capability should eventually be definable once and safely exposed through appropriate authorized surfaces.

Agents are potential first-class principals alongside humans, services, organizations, and the system. Planned concepts include delegation, capability, trust, intent, consent, constraints, delegation chains, revocation, audit, and receipts. MCP and A2A are adapters around Kongono concepts; they must not define Kongono's internal model. Provider independence is required, and no AI provider SDK belongs in this phase.

## Secure extension platform

The planned Extension Platform will support core, trusted/in-process, sandboxed, and external extensions. An Extension Host is expected to govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health.

Third-party extensions must not automatically receive unrestricted access to databases, filesystems, secrets, shells, processes, or networks. WASM/WASI sandboxing is anticipated as a possible future direction but is neither a v1 requirement nor authorized in Phase 0A.

## Standards-first interoperability

Kongono should use stable open standards when they improve interoperability without surrendering architecture. Likely standards include Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards. External protocols must sit behind Kongono-owned abstractions where appropriate.

Phase 2B applies that doctrine by accepting PSR-7 through
`psr/http-message: ^2.0`, the selected server-side PSR-17 factories through
`psr/http-factory: ^1.1`, and PSR-15 for Phase 2E handling. These are
interface-only edge contracts. Kongono retains an implemented first-party
`kongono/http-message` implementation, rejects framework substrate and runtime
factory discovery, keeps factories as explicit composition dependencies, and
keeps the Kernel transport-neutral and PSR-free. Phase 2D routing consumes the
PSR-7 interface without depending on that concrete package. Phase 2E owns the
implemented PSR-15 middleware and terminal-handler behavior; Phase 2F owns the
implemented Web Application bootstrap and Kernel operation-scope composition boundary.

## Structured CLI

The official executable is exactly `kongo`; no alias is accepted.
Phase 8A accepts Structured CLI architecture. Phase 8B implements the neutral
`kongono/console` contract foundation. Phase 8C adds immutable specifications,
a pure already-tokenized parser with explicit mutation authority and a separate
secure-input carrier, and the build-only `kongono/console-build` package.
Phase 8D adds immutable explicit handler bindings and the separate
`kongono/console-runtime` boundary. One bound dispatch owns one fresh operation,
one explicit pre-execution cancellation check, bounded reverse cleanup, safe
combined failure preservation, reentrancy rejection, and sequential isolation.
Phase 8E adds bounded neutral result records and the separate
`kongono/console-presentation` boundary. Human and canonical machine output
share one neutral result/help projection and exit map, omit Protected values,
and deliver one completed document through one explicit caller-owned port.
Phase 8F adds the upper Experimental `kongono/console-commands` package with
three exact read-only handlers over Application Graph, Composition, and Hantei
evidence. The handlers run through Console Runtime and exact generated
Composition roots without acquiring mutation or process authority.

Phase 8G adds the upper Experimental `kongono/console-application` package and
exactly `application:build`. Its `--plan` mode is a non-mutating dry run;
`--apply` is explicit authority for the existing atomic, content-addressed,
contained, initial-only, no-clobber Composition publishers. No prompt exists,
and `--no-interaction` is automation-safe. Human and machine forms share one
result and exit map. Phase 8F snapshot handlers remain programmatic until an
application supplies truthful snapshots; this edge fabricates none.

Command meaning, one explicit invocation producing a neutral result, and human
or versioned machine presentation plus outer process adaptation are separate.
Handlers do not print, style output, read global input, prompt invisibly, or
call `exit`. Presenters do not execute behavior or redefine success. The
neutral engine receives explicit tokens, ports, and detached values rather than
ambient process, stream, environment, directory, terminal, signal, clock,
randomness, credential, or registry state.

Definitions and provenance must be deterministic build evidence and fail closed
when missing, stale, partial, mixed, ambiguous, unsupported, or unverifiable.
Runtime scanning, Reflection discovery, mutable registries, plugin probing,
network discovery, service location, hidden autowiring, proxies, and `eval` are
rejected. Human and machine presentations derive from the same neutral result
and diagnostic identity. Machine mode is a bounded versioned contract and never
silently prompts. Declared interaction, `--no-interaction`, destructive intent,
confirmation, apply authority, and honest `--dry-run` behavior remain explicit.

Each invocation owns one bounded operation lifecycle with explicit reverse
cleanup, combined-failure preservation, and sequential same-process isolation.
Existing nondisclosure doctrine applies unchanged. Neutral CLI contracts remain
independent of capability adapters; the executable is an upper
Composition leaf. See [ADR 0012](adr/0012-structured-cli-contract.md),
[ADR 0096](adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
[ADR 0098](adr/0098-neutral-structured-cli-contracts-and-package-boundary.md),
[ADR 0099](adr/0099-deterministic-command-parsing-and-definition-evidence.md),
[ADR 0100](adr/0100-operation-scoped-command-execution-and-cleanup.md),
[ADR 0101](adr/0101-deterministic-command-presentation-and-process-adaptation.md),
[ADR 0102](adr/0102-first-read-only-framework-capability-commands.md),
[ADR 0103](adr/0103-supported-kongo-edge-and-honest-application-build.md), the
[Structured CLI guide](architecture/structured-cli.md), and the
[Phase 8 roadmap](architecture/phase-8-roadmap.md). The versioned command source,
model, and artifact are deterministic evidence only. Phase 8G adds no mutable
registry, discovery, alias, second command, daemon, watcher, installer,
deployment manager, or shell. Phase 8H adds root-owned real-process evidence
and the exact local checkpoint under
[ADR 0104](adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md). It does
not widen command, process, filesystem, Git, deployment, or runtime authority.

## Smallest correct implementation

Do not build speculative abstractions or future phases early. Public APIs create compatibility obligations. Prefer explicit, inspectable, deterministic behavior over hidden magic and implement only approved scope.

## Phase 2 runnable milestone

Phase 2A through 2H established thirteen production packages; Phase 3C adds PHP
CGI as the fourteenth. The runnable fixture remains root-owned Phase 2 test
infrastructure, not a package or API. It composes the real Public outer delivery port,
one Web Application-owned Kernel scope, deterministic routing and middleware,
fixed generic output, permission-gated trusted HTML, and exact cleanup.

Fixture-runnable does not mean production-ready. The runner selects only fixed
test modes and scenarios and is not a general CLI, listener, emitter,
authorization mechanism, deployment surface, or supported application template.
Production integration and every broader framework capability remain subject to
explicit architect authority.

Phase 3's accepted direction is one supported synchronous production HTTP
integration profile with bounded conversion, exact emission, immutable
operator-owned bootstrap, independent trust inputs, preserved Web Runtime/Web
Application/Kernel ownership, deterministic cleanup, deployment documentation,
and external proof. Phase 3B fixes the first PHP CGI profile's architecture,
Phase 3C implements its bounded explicit inbound converter, Phase 3D implements
bounded response emission, and Phase 3E implements immutable typed operator
bootstrap plus independent current-delivery permission evaluation. Phase 3F
implements a fresh Internal delivery and Experimental thin entry. The delivery
keeps native capture bounded, input lazy, emission single-attempt, cleanup
explicit, and all per-delivery state off ambient framework surfaces. Invalid
caller input reaches Web Runtime's fixed generic 500; Phase 3 creates no semantic
client-error mapping. Phase 3G adds one stateless front-controller entry attempt
followed by one explicit supplied application-shutdown attempt. Its four-way
matrix uses 1019 when both fail, adds no direct PHP-CGI-to-Web-Application edge,
and retains no ambient state. Application composition and partial-boot cleanup
remain caller-owned. `Production` trust remains denied; uncertain emission or
shutdown requires external process retirement. Phase 3H supplies external,
test-owned proof for the selected synchronous PHP-CGI FastCGI RESPONDER profile,
without adding acceptance dependencies to production packages. Process, proxy,
TLS, credentials, logs, monitoring, incidents, and deployment ownership remain
external. Production trusted diagnostics remain denied. Phase 3 is complete,
no next phase starts automatically, and the milestone remains smaller than the full
framework, Stable API, 1.0, or production-hardening certification.

## Rolling current-minor PHP doctrine

The actively developed line supports exactly one stable PHP minor at a time:
the newest minor that has completed Kongono's compatibility gate. PHP 8.5 is
the current certified line, and Phase 5B applies the bounded Composer
expression `>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP 8.5.9
on Windows is the environment proved by this execution; stable eligible 8.5
patches remain within range. Composer admission, publication, or prerelease success
does not establish PHP 8.6 or PHP 9 support.

Deprecations are migration signals, not warnings to suppress. A new minor
requires deterministic dependency, platform, autoload, style, analysis, test,
external acceptance, persistent-runtime, cleanup, host, extension, performance,
memory, and release evidence. See [ADR 0059](adr/0059-rolling-current-minor-php-baseline.md)
and the [PHP support policy](engineering/php-support-policy.md).

## Human-centered developer intelligence

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. Phase 4A accepts documentation
architecture for three future optional roles above one versioned, inspectable,
AI-independent knowledge foundation:

- Kongono Chie helps you build it but cannot certify its own candidate.
- Kongono Hantei proves it works from deterministic evidence and cannot repair
  what it judges.
- Kongono Oshie helps you understand it from version-matched accepted sources
  and cannot make generated teaching authoritative.

AI may suggest; Kongono must know. The framework remains usable offline and
without AI, a provider, an account, or a network. Developer companions are
distinct from application Agent Principals. Future answers must identify the
exact version or source state, cite bounded provenance, distinguish fact,
observation, inference, recommendation, candidate, and uncertainty, and fail
closed on missing or ambiguous authority or evidence.

The roles, knowledge artifacts, packages, commands, providers, patch proposals,
auto-fix, autonomous operations, and production access are not implemented.
See [ADR 0060](adr/0060-human-centered-developer-intelligence-family.md) and the
[Phase 4 roadmap](architecture/phase-4-roadmap.md).

## Exact-state knowledge doctrine

[ADR 0061](adr/0061-versioned-knowledge-source-and-provenance-contract.md)
accepts six distinct source-authority classes and six claim classes. Accepted
policy, observed implementation, current explanation, historical evidence,
workspace observation, and derived advice cannot be flattened into one corpus.
Every version-sensitive claim binds to an exact immutable source state and
retains bounded, non-secret provenance.

Derived material and model output never become independent authority.
Unresolved conflicts, staleness, version mismatch, missing validation, and
incomplete evidence remain visible. Deterministic knowledge work stays offline
and AI-independent; source text remains inert and cannot authorize execution.
Official framework knowledge and project-scoped workspace evidence remain
separate. Phase 4B implements no artifact or mechanism; Phase 4C must separately
select the concrete deterministic slice.

## Deterministic artifact implementation

ADR 0062 and Phase 4C implement one bounded consequence of the exact-state
knowledge doctrine. `kongono/knowledge` builds and validates a canonical
31-source framework-foundation artifact without AI, a network, a workspace
reader, or runtime integration. Exact bytes and authority labels remain
inspectable; whole-source references are source-set-specific; stale state fails
visibly. Optional publication is immutable, content-addressed, atomic, and
no-clobber. The artifact is derived evidence and cannot authorize execution,
replace governing sources, or become a complete framework or workspace model.

## Read-only role derivation

## Independent deterministic verification

Hantei owns verification, not repair. A pass requires every fixed relevant
check against one unchanged exact subject. Failures and unavailable evidence
remain visibly distinct, raw hostile output stays outside verdicts, and derived
reports never become accepted framework authority.
ADR 0063 and Phase 4D implement one least-authority consumer of that artifact.
Chie may return exact cited source sections for six closed topics and bounded
literal occurrences of one supplied Safe diagnostic identifier. Its output is
derived material; the supplied identifier is only a current workspace
observation and does not prove occurrence. Retrieved instructions remain inert.
Chie cannot infer authority, recommend or change code, select or certify gates,
impersonate Hantei, access arbitrary workspace state, or use a model, network,
Git, deployment, or production capability.

Phase 4F Oshie teaches only from exact current accepted source sections through one closed package curriculum. Its lessons and answer feedback are derived teaching, never doctrine. Oshie cannot execute examples, retain learner state, mutate a subject, verify correctness, or elevate its own output into authority.

Phase 4G Chie may construct one deterministic, reversible review candidate only
from an explicit current-explanatory-documentation source in the validated
current artifact. Caller-asserted Safe text is not sanitized. The candidate is
derived material, remains unapplied and unverified, and requires explicit human
application plus fresh independent Hantei evidence. Chie cannot write, apply,
certify, use Git, invoke Hantei, or use a model, provider, or network. Oshie
teaching does not supply proposal authority.

Phase 4H closes Phase 4 with root-owned external evidence only. Its exact 28
scenarios prove direct accepted-source availability, one current identity,
offline local Knowledge/Chie/Oshie behavior, real and explicitly synthetic
Hantei distinctions, visible failure, inert retrieved instructions,
non-disclosure, temporary cleanup, and no authority elevation. The harness is
not doctrine, a production service, an application Agent Principal, or a source
of model, provider, network, Git, deployment, or production authority. Phase 5
is complete at checkpoint `c431482bea664ce3c3201794332c62cab1ed034e`.

## Supported composition and deterministic build doctrine

Kongono provides one explicit supported application composition root. It
owns construction, startup and shutdown order, application-lifetime objects,
operation factories, and partial-boot cleanup without becoming Kernel. Kernel
remains lifecycle-neutral and gains no configuration, discovery, routing,
transport, resolver, generated-code, or application-definition responsibility.

Discovery, declaration validation, dependency validation, route assembly, and
generation occur in an explicit deterministic build/check step before runtime,
never on the first request. Production accepts only one complete current
artifact and fails closed on missing, stale, mixed, partial, ambiguous,
unsupported, or unverifiable state. Build inputs and outputs are bounded,
versioned, content-identified, inspectable, atomically published, safe to
rebuild, and free of secrets. Generated output is derived evidence, not
authority.

Generated readable ordinary PHP constructor wiring is the supported production
mechanism. Constructor injection and canonical explicit dependency
declarations are the default. Runtime service location, ambient containers,
resolver/container injection, property-hook dependency resolution, request-time
reflection or scanning, hidden autowiring, runtime `eval`, remote generated
code, and implicit lazy lifecycle behavior are rejected.

Application, operation, and transient lifetimes have explicit generated
construction, sharing, cleanup, reverse-order release, failure composition, and
concurrency boundaries. The current resolver and Application Graph remain
unchanged and unintegrated.
Phase 5B changes installability metadata only and adds no runtime semantics,
package, dependency, public API, polyfill, shim, release/LTS promise, or
composition/build capability. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support remain unclaimed. Phase 5B is accepted and the bounded PHP
constraint is current.

Phase 5C accepts one inert data-only source at
`<application-root>/kongono.composition.json`, with exact schema/version,
declaration grammar, canonical symbol, typed root, lifetime/reachability,
cleanup/failure, bound, validation-order, path-safety, disclosure, and
provenance semantics. The application source is authority and derived state is
evidence. Input values and secrets never enter source or provenance.

Phase 5D implements the deterministic build evidence boundary while preserving
the application source as authority. Raw source provenance and canonical model
identity remain separate. Current composition remains caller-owned; the DI
resolver, Graph, Kernel, routes, and runtime remain unchanged. Phase 5E adds
derived canonical typed wiring and isolated lifecycle support while preserving
source authority. It rejects collection widening and represents plural
collaboration with an explicit application-owned typed aggregate. No schema
file or framework CLI is introduced. Phase 5F adds exact-current loading and
the single-root web profile without moving existing lifecycle ownership. Phase
5G adds the copy-once minimal application workflow, and Phase 5H supplies the
root-owned external proof and checkpoint. See [ADR 0070](adr/0070-versioned-composition-source-and-provenance.md),
[ADR 0071](adr/0071-roots-bindings-lifetimes-and-cleanup.md), and the
[ADR 0072](adr/0072-deterministic-composition-artifact-and-publication.md), and the
[ADR 0073](adr/0073-generated-typed-wiring-and-lifecycle-boundary.md), and the
[composition source guide](architecture/composition-source.md).

## Deterministic Data Platform doctrine

Phase 6 is a layered Data Platform capability family, not one monolithic ORM
and not a Kernel responsibility. Low-level connectivity and execution,
explicit SQL plans and dialect compilation, schema and migrations, optional
generated Data Mapper support, and composition with operation-scoped lifecycle
remain conceptually separate.

Raw SQL is an explicit parameter-bound escape hatch. Values are not
interpolated into executable SQL, provider capability requirements remain
visible, and unsupported behavior fails closed. Portability and performance
claims require provider-specific external evidence and named measurements.

Database configuration and factories are application-owned; leases,
statements, cursors, results, and transactions are operation-owned under later
accepted contracts. Static facades, ambient connections, service location,
cross-operation mutable state, request-time entity reflection, runtime proxy
generation, hidden lazy loading, property-hook infrastructure lookup, and
Active Record as the core model are rejected defaults. Transactions remain
explicit and do not imply arbitrary retry or distributed guarantees.

Derived SQL plans, migration plans, and generated mapping are deterministic
evidence, never authority. Migration direction requires authoritative sources,
canonical order, checksums, locks, preconditions, applied history, partial-
failure evidence, destructive review, and honest reversibility. Data
diagnostics retain existing non-disclosure rules.

Phase 6B accepts the exact low-level contract and Phase 6C implements its narrow
boundary. The neutral Experimental `kongono/database` package owns explicit connector,
operation session, statement/value/result, capability, transaction, lifecycle,
and non-disclosing failure contracts. The separate Experimental
`kongono/database-pdo-pgsql` adapter depends on that neutral package and proves
PostgreSQL through PDO `pdo_pgsql` first; this is not universal portability.

Raw SQL remains immutable, explicitly parameter-bound, unparsed by the neutral
layer, and expected as either rows or completion. Results preserve exact ordinal
and duplicate labels, while one active stream and transaction, reverse cleanup,
combined failures, no automatic retry, and no native/ambient authority protect
persistent runtimes. The PDO implementation remains internal and the real proof
uses an isolated PostgreSQL 17.2 process outside normal framework runtime.

Phase 6D implements immutable bounded Select, Insert, Update, and Delete plans
in `kongono/database-sql` and explicit stateless PostgreSQL 17 compilation in
`kongono/database-sql-pgsql`. Every value occurrence becomes an ordered
parameter, destructive all-row intent is explicit, and declared dialect
capabilities fail closed. Raw parameter-bound `DatabaseStatement` remains a
first-class path. Phase 6E implements an explicit caller-owned typed migration
catalog, exact-prefix history, deterministic identities, two destructive gates,
and PostgreSQL 17 transaction-scoped execution. Phase 6F implements the optional
Experimental `kongono/database-mapping` build layer: explicit inert metadata,
build-time record validation, provenance artifacts, and generated typed
mappers/repositories. Generated output is derived and not checked in; raw SQL
and typed plans remain first-class. Phase 6G provides the exact-current outer
loading/composition workflow and operation-owned sessions without adding an ORM,
provider startup, or ambient authority. Phase 6H completes the milestone at
checkpoint `f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with whole-platform
PostgreSQL 17 acceptance, local observational performance evidence,
documentation reconciliation, and no new production package. See
[ADR 0077](adr/0077-layered-data-platform-and-explicit-sql-boundary.md),
[ADR 0078](adr/0078-deterministic-data-plans-and-operation-scoped-persistence.md),
[ADR 0079](adr/0079-neutral-database-session-and-provider-capability-contract.md),
[ADR 0080](adr/0080-explicit-database-result-transaction-and-failure-lifecycle.md),
[ADR 0081](adr/0081-postgresql-pdo-adapter-and-real-provider-proof.md),
[ADR 0082](adr/0082-immutable-database-sql-plans-and-postgresql-dialect-compilation.md),
[ADR 0083](adr/0083-deterministic-schema-migrations-and-postgresql-execution.md),
[ADR 0084](adr/0084-compile-time-data-mapping-and-generated-repositories.md),
the [Data Platform guide](architecture/data-platform.md), the
[database contract](architecture/database-contract.md), the
[SQL plan guide](architecture/database-sql-plans.md), the
[migration guide](architecture/database-migrations.md), the
[mapping guide](architecture/database-mapping.md), the
[engineering contract tables](engineering/database-contracts.md), and the
[Phase 6 roadmap](architecture/phase-6-roadmap.md).

## Deterministic Persistence doctrine

Phase 7 is an optional upper capability over the completed Phase 6 Data
Platform, not its replacement. Direct parameter-bound SQL, immutable plans,
migrations, generated Data Mappers, and direct repositories remain first-class
and usable without the future persistence layer. Lower Phase 6 packages cannot
depend upward on it.

Domain objects remain application-owned plain PHP classes. Constructors and
named factories are the primary invariant boundary. Future hydration must use a
reviewed invariant-preserving contract; Reflection writes to private state,
serialization tricks, uninitialized-object bypass, framework base classes,
Active Record inheritance, magic properties, and runtime subclasses are
rejected.

Persistence metadata is deterministic, versioned, bounded, reviewable, and
compiled before runtime. A future source, model, artifact, and ordinary
generated PHP are separate evidence stages; derived bytes are never authority.
Runtime mapping Reflection, discovery, scanning, proxy generation, `eval`,
mutable registries, newest-file loading, hidden I/O, and service location are
not accepted.

Every persistence context, identity map, loaded-object registry,
snapshot, and change set is bounded to one operation. Changing a domain object
performs no I/O. Flush is explicit, callers retain transaction authority, and
no destructor, response completion, shutdown, property access, retry,
reconnect, or isolation widening may hide database authority. Terminal or
uncertain failure retires state before fallible resource release and permits no
automatic replay.

Relationships, aggregate boundaries, load plans, ordering, and cascades require
explicit bounded metadata. Lazy proxies, property-hook queries, implicit joins,
hidden N+1 behavior, unbounded graph walks, recursive cascades, and a magic
general query language are rejected. Optimistic concurrency remains distinct
from absence and provider failure without disclosing identity/version values.
Diagnostics exclude SQL, parameters, domain data, relationships, identity and
version values, snapshots, change sets, generated bytes, paths, provider
messages, stack arguments, and topology.

Phase 7A accepts this architecture. Phase 7B implements the Experimental
neutral `kongono/persistence` contract package. Phase 7C adds the Experimental
`kongono/persistence-build` package, bringing the package count to twenty-nine.
The neutral package's explicit identity, opaque concurrency token, state,
value-free change set, context interface, flush result, diagnostics, and
failures do not implement a runtime. The build package compiles bounded
application persistence intent against exact current mapping and domain-source
evidence into a detached model and canonical artifact. Phase 7D emits
deterministic content-addressed ordinary PHP adapters from one exact-current
artifact. The adapters use public application construction and accessors for
hydration, extraction, typed identity, snapshots, and neutral change evidence;
they do not bypass domain invariants. Structural and exact-current validation is
non-executing and fail closed. Phase 7E adds only one concrete single-entity
operation context. It executes at most one reviewed change through the caller's
exact `DatabaseExecutor`, never owns transaction/resource lifecycle, and
releases all retained domain state on terminal uncertainty or retirement. It
does not load generated PHP, coordinate multiple entities, or generate SQL.
Phase 7F adds explicit bounded load/cascade evidence and stateless generated
aggregate repositories; database and transaction authority remain caller-owned.
Phase 7G adds exact-current generated loading and Composition integration above
those lower contracts; provider and transaction authority remain caller-owned. See
[ADR 0087](adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](adr/0091-deterministic-generated-persistence-state-evidence.md),
[ADR 0092](adr/0092-operation-scoped-persistence-context-and-explicit-unit-of-work.md),
[ADR 0093](adr/0093-explicit-load-plans-bounded-cascades-and-generated-aggregate-repositories.md),
[ADR 0094](adr/0094-exact-current-generated-persistence-application-integration.md),
the [Deterministic Persistence guide](architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](architecture/phase-7-roadmap.md).

## Identity and Access Security doctrine

Identity, authentication, authorization, credential/session transport,
protected-capability enforcement, disclosure, audit, and telemetry are separate
concerns. A Principal is an immutable reference, not an application user record,
provider object, credential, tenant, actor, subject, role, or permission.
Human, Service, Organization, System, and Agent principal kinds grant nothing
by themselves. Application domain identity and policy meaning remain
application-owned; Phase 9A creates no delegation runtime.

Authentication evaluates explicit detached evidence through one selected typed
verifier and returns bounded facts or a distinct non-success outcome. It does
not read ambient state or silently fall back to weaker evidence. Authentication
success grants no authorization. Credentials and cryptographic material remain
opaque, bounded to an explicit lifecycle, and absent from diagnostics, logs,
human/machine output, and ordinary retained state. Kongono invents no
cryptographic primitive.

Authorization is deny by default and fail closed. It evaluates one immutable
operation Security Context, exact application-owned Action and Resource, exact
current Policy Evidence, and explicit environmental facts. Only exact `Permit`
may proceed; `Deny`, `Indeterminate`, missing, stale, partial, mixed, ambiguous,
unsupported, conflicting, unverifiable, or failed evidence cannot become
permission. Object access and field disclosure remain separate.

Enforcement belongs at the typed protected capability before protected reads,
database or external effects, secret disclosure, filesystem publication, or
mutation apply. Routing, middleware, sessions, roles, DI bindings, Graph nodes,
repository or CLI discovery, and diagnostic modes are not authorization.
Middleware may adapt evidence but is never the sole boundary.

Security Context is immutable, explicit, constructed at an outer boundary from
validated evidence, passed through generated typed Composition or operation
collaborators, and fully released at cleanup. Static current-user state,
ambient `Auth`, thread locals, request-singleton authority, service location,
and Kernel-owned security lifecycle are rejected. Anonymous is explicit rather
than `null`, and sequential or overlapping persistent-runtime operations must
not share security state.

Policy truth must be deterministic, versioned, bounded, provenance-bound, and
validated before runtime. Runtime scanning, Reflection policy discovery,
mutable registries, proxying, `eval`, newest-file selection, remote policy
loading, network compilation, AI authority, and silent mutation are rejected.
Diagnostics preserve existing nondisclosure rules. Decision receipts, audit,
and telemetry may later provide bounded non-secret evidence but cannot replay a
grant or become policy authority.

Phase 9A accepts this doctrine through [ADR 0105](adr/0105-explicit-identity-authentication-and-authorization-boundary.md),
[ADR 0106](adr/0106-fail-closed-security-context-and-policy-separation.md),
the [Identity and Access Security guide](architecture/identity-and-access-security.md),
and the fixed [Phase 9 roadmap](architecture/phase-9-roadmap.md). Phase 9B
accepts [ADR 0107](adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md)
and implements only immutable neutral vocabulary in
`kongono/identity-access`: exact principal/resource references, typed
authentication outcomes, inert authorization decisions, canonical
action/resource names, explicit anonymous/authenticated Security Context, and
non-disclosing diagnostics. These values validate shape and grant no authority.

Phase 9B implements no authenticator, verifier, credential, cryptography,
policy evaluator, receipt, session, HTTP or Composition integration,
enforcement, provider, command, audit, telemetry, application workflow,
compliance, performance, or production-readiness claim.

Phase 9C accepts
[ADR 0108](adr/0108-deterministic-explicit-authentication-orchestration.md)
and implements the separate Experimental `kongono/authentication` package.
Authentication evidence remains caller/adapter-owned and exposes only an exact
canonical mechanism to the neutral contract. One sole evidence object selects
one explicitly registered verifier without discovery, priority, or fallback.
Each begun attempt verifies once and retires once; cleanup never depends on a
destructor or process termination, and primary plus cleanup failures remain
visible without entering Safe diagnostic presentation.

Authentication facts are explicit evidence, not permission. Mechanism,
strength, authentication instant, validity, explicit current instant, and
maximum age are evaluated deterministically with inclusive equality bounds.
The orchestrator retains only immutable verifier registration and no request,
evidence, operation, result, principal, or failure state across sequential or
reentrant calls. Phase 9C adds no credential/provider, session or HTTP
transport, authorization/policy evaluation, Security Context construction,
Composition wiring, protected-capability enforcement, audit, telemetry,
application workflow, compliance claim, or Phase 9D behavior.

Phase 9D accepts
[ADR 0109](adr/0109-deterministic-deny-by-default-authorization-evaluation.md)
and implements the separate Experimental `kongono/authorization` package.
Callers independently supply the exact expected name/version/provenance identity
and opaque evidence identity. Mismatch and unsupported policy return
Indeterminate without evaluation; the sole exact evaluator's Permit, Deny, or
Indeterminate is preserved, and failure never permits. Bounded receipts contain
only decision, reason, expected policy, Action, Resource Type, Security Context
state, and environment profile and never become transferable authority.

Authorization retains only immutable evaluator registration and no call state.
Phase 9D adds no policy language/build/loader, roles/permissions, ambient guard,
protected-capability enforcement, Security Context construction/lifecycle,
Composition wiring, HTTP/session transport, database enforcement, audit,
telemetry, AI authority, workflow, compliance or production-readiness claim, or
Phase 9E behavior.

Phase 9E carries one immutable Security Context through explicit generated
operation inputs and releases it completely at cleanup. Phase 9F selects one
same-origin HTTPS browser-session evidence profile and exact protected-
capability enforcement. Phase 9G adds rule-free exact-current policy identity
evidence and one supported request-to-operation-to-enforcement workflow.

Phase 9H adds root-owned external proof only. Synthetic credentials, session,
CSRF, principal, policy, and failure values grant nothing and are never
emitted. The harness proves that authentication success alone remains
insufficient and only exact Permit for exact inputs can execute once. This
checkpoint creates no provider, policy semantics, cryptography, security
certification, production readiness, autonomous remediation, deployment, or
Phase 10 runtime behavior. Kongono remains pre-1.0.

## Observability and Operational Evidence doctrine

Phase 10A accepts Observability and Operational Evidence architecture through
[ADR 0114](adr/0114-explicit-observability-and-operational-evidence-boundary.md),
[ADR 0115](adr/0115-structured-signals-context-and-exporter-neutrality.md),
the [architecture guide](architecture/observability-and-operational-evidence.md),
and the fixed [Phase 10 roadmap](architecture/phase-10-roadmap.md). ADR 0011
remains byte-identical Accepted strategic direction and is refined, not
superseded or downgraded. Phase 10B accepts
[ADR 0116](adr/0116-neutral-observability-contracts-and-package-boundary.md)
and implements the Experimental `kongono/observability` neutral vocabulary.
Phase 10C accepts
[ADR 0117](adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md)
and implements the Experimental `kongono/tracing` operation runtime. Phase 10D
accepts [ADR 0118](adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md)
and implements the Experimental `kongono/metrics` aggregation runtime. Phase
10E accepts [ADR 0119](adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
and implements the separate Operational Events and Security Audit siblings. The
graph is forty-nine acyclic production packages and 166 local edges.

Observability is bounded evidence about what an operation or component did.
Diagnostics explain actionable failure; traces describe causal structure and
timing; metrics describe bounded numeric measurements; operational events
describe structured operator occurrences; security audit evidence records
bounded security facts under stricter policy; authorization receipts remain
non-authoritative evidence; and product analytics remain application-owned.
None establishes identity, permission, policy truth, business state, repair
authority, or permission to hide a primary failure.

Framework-owned signals are typed structured versioned records with explicit
names, kinds, status, severity, schemas, domains, and size/cardinality bounds.
Free-form text is bounded presentation, not sole semantics. Unknown, malformed,
oversized, unsupported, stale, mixed, or unverifiable evidence fails visibly.
Attributes are schema-allowlisted. Existing Safe/Protected/Internal disclosure
rules apply, and credentials, raw session/CSRF values, policy bodies, database
and request values, private paths, stack arguments, personal data, provider
payloads, and unbounded metric labels are denied by default. Redaction is not
permission to collect.

Correlation and trace state is immutable, explicit, operation-scoped, and
fully retired on success, failure, denial, cancellation, and cleanup. There is
no global logger, tracer, meter/provider, ambient current span, thread/fiber
local, request singleton, service location, hidden container lookup, or
automatic environment-driven behavior. Outer adapters validate propagation;
clocks, identifiers, randomness, and sampling enter through explicit typed
collaborators. Sampling grants no authority or disclosure. Runtime values do
not contaminate deterministic build evidence. Kernel remains neutral and
Composition remains lifecycle owner.

Exporters are bounded outer adapters with explicit memory, batching,
concurrency, backpressure, timeout, partial-failure, flush, and close behavior.
Their failure remains visible without indefinite blocking, memory growth,
primary-result corruption, or success conversion. Diagnostics explain
telemetry failure; telemetry does not replace diagnostics. Security audit
evidence remains distinct, nondisclosing, non-replayable, and without a Phase
10A storage, durability, retention, tamper-evidence, compliance, or fail-open/
fail-closed promise.

OpenTelemetry is the selected future outer interoperability direction, not a
current SDK, dependency, global provider, exporter, transport, collector,
backend, detector, propagator, sampler, retry policy, or deployment selection.
Neutral contracts remain independent and lower packages never depend on an
exporter. New runtime work begins Experimental; host adapters and mappings stay
Internal unless explicitly promoted. Public schemas carry compatibility and
privacy obligations.

Chie may explain accepted doctrine, Hantei may verify deterministic evidence,
and Oshie may teach it; none emits production signals, changes sampling,
repairs exporters, or certifies itself. Agent Principals gain no visibility by
kind. Kongono remains usable without AI, backend, account, collector, or
network. No performance, losslessness, compliance, Stable/1.0, production-
readiness, PHP 8.6, or PHP 9 claim is made. The neutral package contains only
explicit optional correlation, bounded scalar attributes, versioned signal
identity, closed kind/status, one immutable envelope, and an unimplemented
recorder capability. Tracing and Metrics each depend inward only on
Observability, API Metadata, Diagnostics, and PHP; neither has a production
reverse dependency. Tracing's typed IDs
and propagation, explicit caller-owned collaborators, bounded root/child
lifecycle and attributes, deterministic Record/Drop mapping, idempotent cleanup,
ownership validation, isolation, and visible nondisclosing failures grant no
authority. Metrics owns finite typed numbers, canonical bounded schemas and
plans, four exact instrument kinds, immediate aggregate-only state,
deterministic observations, overflow-before-mutation, exact ownership, and an
explicit Active/Collected/Retired lifecycle that releases state before
recording. It adds no dynamic labels, raw measurement log, registry, provider,
event/audit pipeline inside Metrics, exporter, backend, or application wiring.

Phase 10E accepts [ADR 0119](adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md)
and implements separate Experimental Operational Events and Security Audit
sibling packages over only Observability, API Metadata, Diagnostics, and PHP.
Finite typed schemas, canonical attributes, supplied-field disclosure, explicit
severity/status or outcome/retention semantics, `not_guaranteed` audit
durability, and exactly one recorder invocation per valid call are implemented.
The packages do not depend on or convert to one another. Recorder failure is
visible, nondisclosing, final, preserves the exact previous throwable, and is
never retried. No logger facade, buffer, exporter, store, retention enforcement,
tamper evidence, compliance claim, authorization behavior, product analytics,
application integration, or Stable API exists. Phase 10F requires separately
authenticated architect authority.

Phase 10F accepts [ADR 0120](adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md)
and implements one explicit outer OTLP HTTP/protobuf profile without changing
neutral evidence authority. The bounded bridge has caller-owned transport and
time/resource configuration, visible no-drop backpressure, synchronous
partial-progress flush, deterministic cleanup, and nondisclosing failure.
Events and audits remain distinct, correlation grants nothing, and exported
audit durability remains `not_guaranteed`. No SDK/provider/global state,
auto-configuration, environment discovery, HTTP client, retry, backend,
application integration, compliance, or durability promise exists. Phase 10G
requires separately authenticated architect authority.

Phase 10G accepts [ADR 0121](adr/0121-deterministic-observability-configuration-and-supported-application-workflow.md)
and implements closed deterministic configuration evidence, one explicit
application observability workflow, a zero-network copy-once skeleton, and two
read-only observability commands. Configuration selects only bounded resource
and batch values for the fixed Phase 10F profile; all live collaborators remain
caller-owned. Exact-current artifacts grant no authority and do not hot-reload.
Operations keep signal domains distinct, preserve failure identity, and release
state through explicit bounded cleanup. The graph is 52 acyclic packages and
182 local edges. No dashboard, alerting, collector deployment, profiler,
or autonomous behavior is added.

Phase 10H accepts
[ADR 0122](adr/0122-phase-10-external-observability-acceptance-and-checkpoint.md)
and adds only root-owned external acceptance and the exact Phase 10 local
checkpoint. Fresh child processes and a uniquely owned temporary application
prove the accepted configuration, application, signal, OTLP mapping,
backpressure, lifecycle, disclosure, compatibility, and cleanup boundaries.
The capture transport is test-owned and performs no network export. This proof
adds no production package, runtime API, provider, backend, collector,
deployment, durable audit, autonomous-operation, or Phase 11 authority.
Kongono remains pre-1.0.

## Authoritative Application Graph doctrine

Phase 10 is complete at checkpoint
`6f7865dbacc7dd9c4b90d2b60bd229b89efbd535`. Phase 11A accepted
[ADR 0123](adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
and the fixed [Phase 11 roadmap](architecture/phase-11-roadmap.md) as
documentation architecture only. Phase 11B accepts
[ADR 0124](adr/0124-versioned-application-graph-contract-foundation.md) and
implements the separate versioned contract package.

The language-neutral IR is a versioned producer-facing input boundary. The
authoritative Graph is a deterministic, canonical, immutable derivation from
admitted records and explicit accepted artifacts for one exact application
state. Only a separately accepted build/validation pipeline may establish
Graph facts. Source access, producer claims, cache presence, model confidence,
recency, repetition, and consumer inference grant no authority.

Graph authority is descriptive, not operational. It cannot authenticate,
authorize, grant a capability, disclose a secret, access the filesystem,
process, network, or database, deploy, or mutate production. Provenance is
evidence, not permission. Visibility and disclosure apply before consumption,
and omitted facts are not false.

The future boundary requires stable versioned identities, separate typed node
and relation vocabularies, typed schemas, bounded provenance, exact currentness,
deterministic collision-free validation, canonical results, and visible
fail-without-publication behavior. Phase 11B implements the exact first
identity, Composition vocabulary, schema catalog, provenance, visibility,
disclosure, and diagnostic values. Phase 11C implements explicit IR claim
records and complete deterministic all-or-nothing construction of canonical
immutable Graph facts. Phase 11D implements the separate Composition projection.
Phase 11E implements canonical content-derived artifacts, caller-relative
currentness, guarded loading, and no-clobber publication without source-code-
byte attestation, command, frontend, extension mechanism, or runtime integration.

The Experimental Phase 0J structural snapshot remains unchanged. Its opaque
local keys and open kinds are not retroactively Phase 11 identities or
vocabularies. Phase 11F adds bounded aggregate inspection, and Phase 11G adds
only a role-addressed Safe aggregate derivation plus independent verification.
Chie, Hantei, Oshie, Agent Principals, extensions, and other consumers gain no
raw or individual-fact access and no Graph authority by implication. Phase 11H
closes the exact checkpoint. Phase 12A accepts the governed Extension Platform
architecture only; Stable API, 1.0, and production-readiness claims remain
outside its authority.

## Governed Extension Platform doctrine

Phase 12A accepts [ADR 0131](adr/0131-governed-extension-platform-and-attributable-contribution-boundary.md)
and the fixed [Phase 12 roadmap](architecture/phase-12-roadmap.md). An extension
is an explicitly selected, versioned, attributable contribution unit. Installed
packages, manifests, signatures, artifacts, Graph facts, and self-claims grant
no authority.

Declaration, deterministic admission, explicit application-owned capability
grant, and separately governed execution are distinct. Capabilities are typed,
bounded, attributable, and denied by default. Trusted/in-process PHP retains
the technical power of its process; capability ports are not a sandbox.

Future contributions are versioned language-neutral IR inputs carrying exact
source state, provenance, visibility, and schema. They become Graph facts only
through the accepted admission pipeline. The Graph neither loads nor executes
extensions, grants capabilities, authorizes Principals, approves publishers,
or monitors health. Principal, Security, Observability, Graph, Chie, Hantei,
and Oshie authority remains independent.

The production graph is 59 packages and 206 acyclic local edges. Phase 12B's
neutral package depends only on API Metadata and Diagnostics. Phase 12C's build
package additionally depends on Extension Contract and preserves exact neutral
fields as deterministic evidence with caller-relative currentness and atomic
no-clobber publication. Local construction and artifact validation are not
admission or trust; requests are not grants; contributions are not Graph facts;
visibility is not authorization; lifecycle is not hosting; and category is not
a security decision. No public loader or Application Graph integration exists.
Phase 12D implements exact application-owned selection and attributable Graph
IR admission while preserving declaration, admission, grant, and execution as
separate facts. Admission grants nothing and returns no authoritative Graph.
That checkpoint topology was 60 packages / 212 local edges. Phase 12E adds
explicit capability grants and inert generated extension wiring without
loading or execution. Phase 12F adds one exact-file trusted/in-process host
lifecycle with ordered activation, reverse cleanup, operation leases,
retirement, and deterministic failure preservation. It neither turns capability
ports into a sandbox nor grants Security, Graph, CLI, external-execution, or
developer-intelligence authority. The current topology is 62 packages / 220
local edges. Phase 12G adds detached structural inspection and Safe aggregate
evidence only. It never loads generated PHP and does not establish currentness,
host state, execution, trust, authorization, or production readiness. The
current topology is 63 packages / 226 local edges; Phase 12H closes the milestone with root-owned external evidence only.

## Provider-neutral model-invocation acceptance doctrine

Phase 14A-H are implemented and Phase 14 is closed. Root external evidence may
exercise one explicit offline fake adapter, but model identities, plans,
responses, usage, cost, receipts, proposals, inspection, and verifier output
remain evidence rather than authority or truth. See [Phase 14 external
acceptance](architecture/phase-14-external-acceptance.md).

## Governed network-egress doctrine

Phase 15A accepts [ADR 0155](adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md),
the [egress guide](architecture/outbound-http-and-network-egress.md), and the
[fixed roadmap](architecture/phase-15-roadmap.md) as documentation architecture.
Phase 15B accepts [ADR 0156](adr/0156-neutral-versioned-network-egress-contract-foundation.md)
and the optional Experimental inert contract package. Phase 15C accepts [ADR
0157](adr/0157-deterministic-network-egress-policy-artifact.md) and the
optional Experimental deterministic [policy-artifact
boundary](architecture/network-egress-policy-artifact.md). One explicit typed
application policy becomes inert offline evidence; exact origins and artifact
bytes remain Protected. Network destinations,
authority and policy references, capabilities, resolution, connections,
responses, receipts,
model output, Graph facts, and developer-tool results remain evidence rather
than authority. Applications own current authority, policy, credentials,
endpoint and transport selection, and external-service meaning. Planned egress
must fail closed, bind resolution to connection selection, bound streaming and
time, preserve uncertain effects, and clean up deterministically. PSR-7 message
semantics do not make PSR-18 a control plane, body streams are neither read nor
claimed deeply immutable, and neither Phase 15B values nor Phase 15C artifacts
perform network work. Artifact validity and caller-relative currentness do not
grant authority. Phase 15D accepts [ADR 0158](adr/0158-bounded-provider-neutral-network-egress-runtime.md)
and the optional Experimental [bounded runtime](architecture/network-egress-runtime.md).
It coordinates one attempt only through explicit caller-owned ports, exact
selected-address binding, finite budgets, cancellation, bounded evidence, and
deterministic close; it supplies no concrete network, credential, provider,
command, or application behavior. Phase 15E separately adds the explicit cURL
HTTP/1.1 adapter, and Phase 15F separately adds the exact-current single-profile
application workflow. Phase 15G separately adds offline visibility-safe
inspection and bounded inert evidence without network, authority, recording,
repair, replay, or role execution. Phase 15H adds bounded local external
acceptance and closes Phase 15 at one exact local checkpoint.

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements the optional Experimental
`kongono/network-egress-inspection` offline upper evidence leaf. It
revalidates exact supplied profile/wiring evidence and can summarize one exactly
paired detached operation/result without generated-code loading, networking,
authority evaluation, recording, replay, repair, certification, or role
execution.

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15 is closed. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
production-readiness claim exists.

## Phase 16A-C external-service binding doctrine

Phase 16A accepts [ADR 0163](adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [external-service binding guide](architecture/external-service-binding-and-secret-leases.md),
and the [fixed roadmap](architecture/phase-16-roadmap.md) as documentation
architecture. Phase 16B accepts
[ADR 0164](adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and the optional Experimental `kongono/external-service-contract` package.

Service binding evidence, credential references, operation-scoped credential
leases, endpoint selection, resolution evidence, selected addresses,
transport, provider-specific semantics, and current network authority remain
distinct. Evidence grants nothing; credential access and network access remain
independent permissions. The 27 production declarations and 15 tests reuse
Phase 15 origin, capability, resolver, and resolution evidence. Bindings and
references are secret-free; the one borrowed-consumer material parameter
cannot prevent copies or promise PHP zeroization. The package supplies ports,
not implementations, and exposes nondisclosing failures. Phase 16C accepts
[ADR 0165](adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and the optional Experimental `kongono/external-service-binding-build` package.
Canonical artifact and ordinary-PHP bytes are deterministic inert evidence
from one explicit typed secret-free source. Structural validation reconstructs
exact bytes; currentness is relative only to one supplied current source.
Generated PHP is never loaded, and path-safe content-addressed publication is
atomic, identical-byte idempotent, and no-clobber. Phase 16D accepts
[ADR 0166](adr/0166-explicit-bounded-system-resolver-adapter.md) and the
optional Experimental system-resolver leaf. One explicit profile bounds one
`ext-sockets` lookup; `SOL_TCP`, omitted `ai_family`, complete fail-closed
validation, Phase 15 classification, and deterministic selected membership are
evidence only. No connection, network grant, credential access, cache, retry,
fallback, or DNS truth claim is added. Phase 16E accepts [ADR
0167](adr/0167-operation-scoped-credential-lease-runtime.md) and an optional
Experimental runtime coordinating one explicitly supplied source, request,
consumer, and monotonic clock. It validates exact lease relations, delegates
borrowed material without receiving it, and retires every acquired lease once.
It supplies no credential source, network permission, refresh, renewal, retry,
fallback, or reliable-zeroization guarantee. Phase 16F accepts [ADR
0168](adr/0168-exact-current-external-service-binding-application.md) and one
optional Experimental one-use coordinator over an exact-current binding and
Phase 15 profile. Credential possession and network permission remain
independent; neither profiles nor results grant authority. Caller code owns the
request factory and its credential-copy risk. Phase 16G accepts [ADR
0169](adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and an optional visibility-safe evidence leaf. Inspection revalidates only
explicit supplied evidence, never retrieves material or performs network work,
and does not convert digests, Graph facts, cleanup claims, or role-addressed
views into authority or external truth. Phase 16H accepts [ADR
0170](adr/0170-phase-16-external-service-acceptance-and-checkpoint.md) and adds
only a root-owned deterministic local acceptance command plus the exact Phase
16 checkpoint. Local loopback, numeric resolver, inert lease, and inspection
evidence grant no authority and prove no public DNS, TLS, credential, provider,
remote-effect, deployment, or production-readiness truth. Phase 16 is closed;
Stable/1.0 remains unauthorized, and Phase 17A is separately accepted below as
documentation only. Phase 17B-G add the optional mapping/adapter application
and inspection leaves. The
current graph is 89 packages / 375 local production edges / acyclic; PHP
inventories are 1,726 / 894 / 92.

## Phase 17 provider mapping remains an outer evidence boundary

Phase 17A accepts [ADR 0171](adr/0171-first-party-openai-responses-adapter-boundary.md)
and the [fixed roadmap](architecture/phase-17-roadmap.md) as documentation only.
One optional OpenAI Responses profile maps between Phase 14 neutral
invocation and the accepted Phase 16 and Phase 15 service boundaries. The
provider is not a default or a generic compatibility fiction.

Provider mapping is not neutral invocation; provider output is not authority;
structured output is not truth; an action suggestion is not a capability or
effect; a credential lease is not network permission; and successful egress is
not proof of billing, delivery, safety, or application effect. Phase 17B
accepts [ADR 0172](adr/0172-deterministic-openai-responses-offline-protocol-mapping.md)
and adds only exact bounded offline request/response mapping with closed
nondisclosing failures. It adds no credential, endpoint, HTTP transport,
network request, runtime adapter, tool effect, or live connection. Phase 17C-G
are separately accepted, culminating in the optional offline visibility-safe
inspection leaf. Phase 17H accepts the root-owned local proof without promoting
provider evidence to authority or compatibility truth. Phase 17A-H are
Accepted and Phase 17 is closed. Phase 18A-H are Accepted and Phase 18 is
closed. See the
[adapter guide](architecture/openai-responses-provider-adapter.md) and
[inspection guide](architecture/openai-responses-inspection-and-evidence.md),
plus [ADR 0178](adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md).
