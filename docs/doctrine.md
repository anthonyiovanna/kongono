# Kongono Architectural Doctrine

## Status

The principles in this document are locked architectural direction. Phases 0A through 1H implement the authenticated Kernel, Application Graph, Dependency Injection, diagnostic metadata/context/presentation/activation/handler, API Metadata, and package-topology increments recorded by their Accepted ADRs. Phase 1 is complete at local checkpoint `d7846f137a1d53f5306f48d19196b916fde44ac2`. Phase 2A and 2B accept the bounded runnable-web roadmap and interoperability boundary. Phase 2C implements the ninth package, `kongono/http-message`; Phase 2D implements the tenth, `kongono/routing`; Phase 2E implements the eleventh, `kongono/http-server`; and Phase 2F implements the twelfth, `kongono/web-application`, which owns one local Kernel scope per application call. Phase 2G and ADR 0049 implement the thirteenth package, `kongono/web-runtime`, as the ports-only outer delivery leaf with fixed generic and eligible trusted responses plus exact send/close orchestration. No concrete SAPI/server/listener/emitter, supplied error middleware, trusted network authorization mechanism, or runnable fixture exists. Phase 2H remains the first runnable black-box milestone.

Phase 2B accepts PSR-7, the selected server-side PSR-17 factories, future
PSR-15, and the first-party `kongono/http-message` package boundary. Phase 2C
implements that ninth package with the five selected message roles, five
stateless factories, and bounded package-local diagnostics. Phase 2D implements
Routing as a separate tenth package consuming only PSR-7 interfaces, API
Metadata, and Diagnostics. It performs exact encoded method/path matching with
no normalization, handler execution, authorization, or retained request state.
Phase 2F now owns application bootstrap and application-call Kernel scope
cleanup. Adapters, emission, broader resource cleanup, and runnable web behavior
remain unimplemented.

## Product philosophy

Kongono Framework is a modern application framework designed for humans, software clients, and AI agents. Its guiding product statement is **Build what comes next.** It is not a Laravel clone. It seeks comparable developer productivity through an explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

Applications should be understandable to developers, infrastructure, tooling, AI agents, and other applications without relying on undocumented convention or probabilistic inference.

## Authoritative application knowledge

The planned Application Graph will be Kongono's authoritative, machine-readable application representation. It is intended to describe modules, services, dependencies, routes, actions, entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation relationships.

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, not the permanent conceptual boundary. The implemented in-memory subset contains only immutable nodes with opaque graph-local keys and open kinds, plus directed open-kind relations between existing nodes. It validates duplicates and endpoints, returns canonical binary-string order, performs exact node lookup, selects immediate outgoing or incoming relations by exact endpoint-key value, and selects whole-snapshot nodes or relations by exact kind-label value. Node-kind and relation-kind selection remain separate domains even when labels are byte-equal. Absent kinds return empty lists; kind results preserve canonical order and the same immutable objects in fresh arrays. Node-kind selection scans `N` nodes in `O(N)` time, relation-kind selection scans `R` relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open labels define no vocabulary, schema, hierarchy, or compatibility rules. These narrow read boundaries are not combined filters, multiple-kind selection, predicates, traversal, reachability, paths, neighbor selection, or a generic query system. The snapshot is not yet the full authoritative representation. Stable identities, schemas, metadata, provenance, visibility, serialization, discovery, compilation, persistence, mutation, framework integration, query planning, and performance budgets remain experimental or open.

AI may suggest interpretations and changes, but correctness-critical application structure, authorization, security policy, migration safety, dependency legality, module boundaries, and API contracts must be determined by Kongono-owned, deterministic mechanisms.

## Microkernel and capabilities

Kongono will use a small microkernel with independently installable components that contribute coherent capabilities. Potential capabilities include HTTP, Data, ORM, Migrations, Authentication, Authorization, Events, Queues, Workflows, Cache, Storage, Mail, Notifications, Realtime, Scheduling, AI, Agents, Delegation, MCP, A2A, Frontend integration, Testing, and Developer tooling.

This list is direction, not a package map. The implemented production packages
are API Metadata, Diagnostic Activation, Kernel, Application Graph, Dependency
Injection, Diagnostics, Diagnostic Presentation, Diagnostic Handler, and HTTP
Message. The
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

Flexibility at application architecture layers does not weaken framework invariants. Phase 0F implements only explicit typed constructor resolution with transient construction. Broader dependency injection, the authoritative Application Graph, structured CLI contracts, architecture validation, security metadata, telemetry, and other machine-readable contracts remain planned.

## Persistent-runtime-first lifecycle

Core lifecycle design must not assume the process or all application state ends after each HTTP request. Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime may become a hard kernel dependency.

The implemented Kernel lifecycle boots once, permits overlapping opaque operation scopes owned by Kernel-instance object identity, refuses shutdown while work is active, and exposes no ambient current operation. Web Application now orchestrates exact begin/end cleanup around one application call without changing Kernel. Scoped data, broader resource cleanup, context propagation, cancellation, and runtime adapters remain open.

## Platform boundaries

PHP 8.5+ is the primary application language. TypeScript and JavaScript are for the browser and frontend environment. Rust may later be used for high-performance framework tooling, while Go may be selected for specific infrastructure or runtime integration. No Rust, Go, Node.js, or TypeScript tooling is introduced in Phase 0A.

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
rule. A future outer composition boundary must provide affirmative
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
authorization by themselves. Raw configuration, delivery evidence and its
evaluation, combined enforcement, and adapters remain future and unimplemented. API status,
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
now exist, while raw-input validation, delivery-evidence evaluation, combined
activation enforcement, transport, and production presentation remain future
outer-boundary concerns.

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

The official future executable is `kongo`. Meaningful commands should eventually support machine-readable and automation-safe behavior such as `--json`, `--dry-run`, and `--no-interaction` where applicable. No CLI architecture or executable exists in Phase 0A.

## Smallest correct implementation

Do not build speculative abstractions or future phases early. Public APIs create compatibility obligations. Prefer explicit, inspectable, deterministic behavior over hidden magic and implement only approved scope.

## Phase 2 runnable milestone

Phase 2A through 2H are implemented. Thirteen production packages remain the
complete package graph; the runnable fixture is root-owned test infrastructure,
not a fourteenth package or API. It composes the real Public outer delivery port,
one Web Application-owned Kernel scope, deterministic routing and middleware,
fixed generic output, permission-gated trusted HTML, and exact cleanup.

Fixture-runnable does not mean production-ready. The runner selects only fixed
test modes and scenarios and is not a general CLI, listener, emitter,
authorization mechanism, deployment surface, or supported application template.
Production integration and every broader framework capability remain subject to
explicit architect authority.
