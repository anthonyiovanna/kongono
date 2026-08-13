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

The broader production behavior remains the accepted Phase 0J implementation:
the PHP quality toolchain and three packages for Kernel lifecycle, the Application Graph structural
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
Application Graph. Safe structured context, development or production error
pages, HTTP or CLI mappings, logging, telemetry, retry, cleanup orchestration,
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
- Actionable, package-owned diagnostics with secure separation of protected
  developer detail from untrusted output

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture overview](docs/architecture/overview.md)
- [Kernel package](packages/kernel/README.md)
- [Application Graph structural package](packages/application-graph/README.md)
- [Dependency Injection package](packages/dependency-injection/README.md)
- [Documentation standard](docs/documentation-standard.md)
- [Engineering toolchain](docs/engineering/README.md)
- [Dependency Injection engineering guide](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Glossary](docs/glossary.md)
- [Architecture Decision Records](docs/adr/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

The official future CLI name is `kongo`; no executable is implemented in this phase.
