# Kongono Architectural Doctrine

## Status

The principles in this document are locked architectural direction. Phase 0C implements the explicitly identified Kernel lifecycle and API-status mechanisms. Phase 0D adds the Application Graph structural snapshot, and Phase 0H adds only its exact outgoing and incoming direct-relation selectors. Phase 0F adds only explicit typed constructor resolution through immutable definitions and transient-per-edge construction; other planned mechanisms do not yet exist unless this document says otherwise.

## Product philosophy

Kongono Framework is a modern application framework designed for humans, software clients, and AI agents. Its guiding product statement is **Build what comes next.** It is not a Laravel clone. It seeks comparable developer productivity through an explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

Applications should be understandable to developers, infrastructure, tooling, AI agents, and other applications without relying on undocumented convention or probabilistic inference.

## Authoritative application knowledge

The planned Application Graph will be Kongono's authoritative, machine-readable application representation. It is intended to describe modules, services, dependencies, routes, actions, entities, schemas, events, jobs, workflows, policies, permissions, principals, agents, delegations, capabilities, extension contributions, frontend contracts, external APIs, and documentation relationships.

The Graph will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, not the permanent conceptual boundary. The implemented in-memory subset contains only immutable nodes with opaque graph-local keys and open kinds, plus directed open-kind relations between existing nodes. It validates duplicates and endpoints, returns canonical binary-string order, performs exact node lookup, and selects immediate outgoing or incoming relations by exact endpoint-key value. Missing and isolated keys return empty lists; self-relations select in both directions; detached results preserve global canonical relation order and the same immutable relation objects. Each selector is an `O(R)` scan with no retained index or cache. This narrow read boundary is not traversal, reachability, paths, neighbor selection, kind filtering, predicates, or a generic query system. It is not yet the full authoritative representation. Stable identities, schemas, metadata, provenance, visibility, serialization, discovery, compilation, persistence, mutation, and framework integration remain experimental or open.

AI may suggest interpretations and changes, but correctness-critical application structure, authorization, security policy, migration safety, dependency legality, module boundaries, and API contracts must be determined by Kongono-owned, deterministic mechanisms.

## Microkernel and capabilities

Kongono will use a small microkernel with independently installable components that contribute coherent capabilities. Potential capabilities include HTTP, Data, ORM, Migrations, Authentication, Authorization, Events, Queues, Workflows, Cache, Storage, Mail, Notifications, Realtime, Scheduling, AI, Agents, Delegation, MCP, A2A, Frontend integration, Testing, and Developer tooling.

This list is direction, not a package map. The production packages are the Phase 0C Kernel, Phase 0D Application Graph structural core, and Phase 0F Dependency Injection component. The resolver is not a general capability platform and implies none of the other listed packages.

## Progressive architecture

MVC remains a friendly default at the HTTP and UI edge. Business logic must not be structurally trapped in controllers. Applications should be able to evolve toward actions or use cases, modular monoliths, domain-driven architecture, ports and adapters, justified CQRS, events, and durable workflows without switching frameworks.

Flexibility at application architecture layers does not weaken framework invariants. Phase 0F implements only explicit typed constructor resolution with transient construction. Broader dependency injection, the authoritative Application Graph, structured CLI contracts, architecture validation, security metadata, telemetry, and other machine-readable contracts remain planned.

## Persistent-runtime-first lifecycle

Core lifecycle design must not assume the process or all application state ends after each HTTP request. Future adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes. No runtime may become a hard kernel dependency.

The implemented Kernel lifecycle boots once, permits overlapping opaque operation scopes owned by Kernel-instance object identity, refuses shutdown while work is active, and exposes no ambient current operation. It does not yet define scoped data, cleanup orchestration, context propagation, cancellation, or runtime adapters; those lifecycle contracts remain open.

## Platform boundaries

PHP 8.5+ is the primary application language. TypeScript and JavaScript are for the browser and frontend environment. Rust may later be used for high-performance framework tooling, while Go may be selected for specific infrastructure or runtime integration. No Rust, Go, Node.js, or TypeScript tooling is introduced in Phase 0A.

Kongono will not build a JavaScript bundler. Vite is the intended default build integration. Kongono will own an Asset Platform abstraction, with possible future adapters for React, Vue, Svelte, HTMX, Alpine, server-rendered HTML, and native/no-bundler assets.

## Observability and APIs

OpenTelemetry is intended to be native to the framework's instrumentation model. Framework operations should eventually emit structured telemetry hooks, with replaceable exporters. No telemetry package is selected or installed in Phase 0A.

OpenAPI is intended to be generated from authoritative application metadata so contracts do not require independent manual maintenance. No OpenAPI mechanism exists yet.

## Agents and interoperability

Kongono recognizes Human Experience, Software/API Experience, and Agent Experience as first-class consumption surfaces. An application capability should eventually be definable once and safely exposed through appropriate authorized surfaces.

Agents are potential first-class principals alongside humans, services, organizations, and the system. Planned concepts include delegation, capability, trust, intent, consent, constraints, delegation chains, revocation, audit, and receipts. MCP and A2A are adapters around Kongono concepts; they must not define Kongono's internal model. Provider independence is required, and no AI provider SDK belongs in this phase.

## Secure extension platform

The planned Extension Platform will support core, trusted/in-process, sandboxed, and external extensions. An Extension Host is expected to govern permissions, capabilities, lifecycle, storage, network access, secrets, events, UI contributions, agent capabilities, updates, removal, and health.

Third-party extensions must not automatically receive unrestricted access to databases, filesystems, secrets, shells, processes, or networks. WASM/WASI sandboxing is anticipated as a possible future direction but is neither a v1 requirement nor authorized in Phase 0A.

## Standards-first interoperability

Kongono should use stable open standards when they improve interoperability without surrendering architecture. Likely standards include Composer, PSRs, OpenAPI, OpenTelemetry, OAuth/OIDC, MCP, A2A, and web standards. External protocols must sit behind Kongono-owned abstractions where appropriate.

## Structured CLI

The official future executable is `kongo`. Meaningful commands should eventually support machine-readable and automation-safe behavior such as `--json`, `--dry-run`, and `--no-interaction` where applicable. No CLI architecture or executable exists in Phase 0A.

## Smallest correct implementation

Do not build speculative abstractions or future phases early. Public APIs create compatibility obligations. Prefer explicit, inspectable, deterministic behavior over hidden magic and implement only approved scope.
