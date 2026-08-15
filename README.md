# Kongono

> **Build what comes next.**

Kongono is a planned modern application framework for applications that must be understandable to humans, software clients, tooling, infrastructure, and AI agents. PHP 8.5+ is its primary application language.

## Who Kongono is designed for

- Humans building and operating applications
- Software consuming explicit, machine-readable contracts
- AI agents acting through governed, inspectable capabilities

## Current status

Kongono is **pre-alpha**. Phase 0M implements package-local runtime diagnostic
metadata for the deliberate failures currently raised by the Kernel, Application
Graph, and Dependency Injection packages. Each existing package exception marker
now exposes the exact diagnostic ID, title, and remediation from one owning
Internal enum catalog. The markers remain separate; there is no universal
Kongono catch interface. Existing exception messages remain unchanged and are
protected diagnostic detail.

Phase 0P implements the presentation-neutral `kongono/diagnostics` package.
Public closed schemas and immutable values support exact text, integer, and
boolean fields classified as Safe or Protected, schema-order capture, required
and optional fields, and bounded deterministic byte representation. Safe means
eligible for a later explicit disclosure policy, not automatically public.
Forbidden secret-bearing or unbounded material must be excluded before
construction; the package does not detect or redact it. Diagnostics construction
failures use their own fourth package marker and 11 diagnostic IDs.

Phase 0Q integrates all 11 Application Graph failures with their exact Graph-
owned bounded schemas and throw-site values. The Public Graph marker now exposes
`diagnosticContext(): DiagnosticContext`; each Graph exception eagerly owns one
immutable detached context while retaining its exact ID, title, remediation,
message, zero code, optional cause, and package-local catch boundary. Safe and
Protected classifications do not render or disclose any field.

Phase 0T integrates all six Kernel lifecycle failures with exact Kernel-owned
closed schemas and Safe state, count, or conservative scope-condition values.
The Public Kernel marker now exposes the same presentation-neutral
`diagnosticContext()` value while remaining unrelated to every other marker.

Phase 0U integrates all 25 Dependency Injection diagnostics across their 26
existing throw sites. DI now independently requires Diagnostics, its Public
marker exposes `diagnosticContext()`, and its exceptions eagerly own exact
bounded contexts without changing definition validation, deterministic graph
checks, requested-service behavior, or transient-per-edge construction.

Phase 0S implements the neutral ownership accepted by ADR 0031 and increased
Kongono to five packages. `kongono/api-metadata` owns `Kongono\ApiMetadata\Api` and
`ApiStatus` and requires PHP only. Kernel, Diagnostics, Application Graph, and
Dependency Injection depend directly on it; Application Graph also retains its
one-way Diagnostics dependency. The resulting package graph is acyclic.

The old `Kongono\Kernel\Api\Api` and `ApiStatus` FQCNs were removed atomically
without an alias or compatibility shim. All 40 declarations present at Phase 0S
and their statuses remained unchanged. Phase 0S changes ownership, imports, Composer
topology, tests, tooling, and documentation only. Phases 0T and 0U add the
resulting acyclic Kernel-to-Diagnostics and DI-to-Diagnostics edges, but no
renderer, error page, or transport.

All 42 original Kernel, Graph, and DI failures now expose exact package-owned
bounded context: 6/11/25. Diagnostics construction failures expose no recursive
context. Kernel, Graph, and DI depend on Diagnostics independently; no dependency
creates a universal catch boundary or reverse dependency.
No sanitizer, served error page, trusted developer mode, production allowlist,
transport, serializer, source/documentation lookup, logger, telemetry, or incident
system is implemented.

Phase 0X implements `kongono/diagnostic-presentation` as the sixth package and a
leaf above API Metadata, Diagnostics, Kernel, Application Graph, and Dependency
Injection. Its four Experimental developer concepts provide a pure projector
and immutable detached values containing only exact package ownership, catalog
metadata, fixed context prose, and canonically ordered classified fields. Both
Safe and Protected classifications are preserved exactly. Projection is not
authorization. Phase 0X added no renderer, trusted-mode selector, production
model, transport, service binding, or other runtime integration.

Phase 0Z implements the Experimental pure
`TrustedDeveloperHtmlRenderer` selected in Phase 0Y. It consumes only the
implemented trusted projection and returns one complete accessible,
deterministic, escaped, bounded HTML5 document using a fixed local stylesheet.
Golden fixtures lock the full and empty byte grammar.

Phase 1A accepts a fail-closed activation architecture but implements no
enforcement. A future outer composition boundary must provide both affirmative
trusted bootstrap permission for an immutable adapter instance and an
independent adapter-controlled delivery permission for the current delivery
before presentation access, projection, rendering, or delivery. Missing,
invalid, ambiguous, failed, denied, or uncertain decisions do not enable trusted
output, and no client-controlled value can enable either permission. The
projector and renderer remain directly callable only as low-level pure
Experimental APIs; calling them is not authorization. No activation type,
configuration reader, adapter, served page, HTTP behavior, or production
diagnostic presentation is implemented.

The broader originating-package behavior remains the accepted Phase 0U implementation:
the PHP quality toolchain and six packages for neutral API metadata, Kernel lifecycle, the Application Graph structural
snapshot, and explicit typed dependency resolution. The Graph supports exact
`outgoingRelations(NodeKey): array` and `incomingRelations(NodeKey): array`
selection over immediately stored relations, plus exact whole-snapshot
`nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array`
selection. The kind selectors keep node-kind and relation-kind domains separate,
preserve canonical order and stored-object identity in fresh lists, return `[]`
for absent kinds, and scan without indexes or caches. Kinds remain open
producer-defined labels; these methods do not establish a vocabulary, combine
endpoint and kind filters, traverse the Graph, or form a generic query system.
The resolver remains explicit and transient per dependency edge. The Graph
remains a structural prerequisite rather than the complete authoritative
Application Graph. Development or production error pages, HTTP or CLI mappings,
logging, telemetry, retry, cleanup orchestration,
incident storage, runtime adapters, broader capabilities, and a working `kongo`
CLI remain unimplemented.

Nothing is Stable. Public API status means supported consumer use under Kongono's pre-1.0 release maturity; it does not imply an unchangeable or production-ready contract. Experimental and open architecture may evolve.

## Core principles

- Explicit, typed, deterministic, inspectable behavior over hidden magic
- A small microkernel with independently installable capabilities
- A language-neutral Application Graph as the planned authoritative application model
- Progressive architecture that can grow beyond an MVC edge
- Persistent-runtime safety from the beginning
- First-class Human, Software/API, and Agent experiences
- Standards-first interoperability behind Kongono-owned abstractions
- Documentation that explains purpose, contracts, security, lifecycle, and failure
- Actionable, package-owned diagnostics with implemented presentation-neutral
  bounded structural values and exact Kernel, Graph, and DI exception context,
  plus an implemented pure trusted-developer projection and HTML rendering
  boundary while activation enforcement, runtime trust integration, transport,
  and production policy remain future work

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture overview](docs/architecture/overview.md)
- [API Metadata package](packages/api-metadata/README.md)
- [Kernel package](packages/kernel/README.md)
- [Application Graph structural package](packages/application-graph/README.md)
- [Dependency Injection package](packages/dependency-injection/README.md)
- [Diagnostics package](packages/diagnostics/README.md)
- [Diagnostic Presentation package](packages/diagnostic-presentation/README.md)
- [Documentation standard](docs/documentation-standard.md)
- [Engineering toolchain](docs/engineering/README.md)
- [Dependency Injection engineering guide](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Glossary](docs/glossary.md)
- [Architecture Decision Records](docs/adr/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

The official future CLI name is `kongo`; no executable is implemented in this phase.
