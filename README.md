# Kongono

> **Build what comes next.**

Kongono is a planned modern application framework for applications that must be understandable to humans, software clients, tooling, infrastructure, and AI agents. PHP 8.5+ is its primary application language.

## Who Kongono is designed for

- Humans building and operating applications
- Software consuming explicit, machine-readable contracts
- AI agents acting through governed, inspectable capabilities

## Current status

Kongono is **pre-alpha**. Phase 0F contains governance, design documentation, the PHP development-quality toolchain, and three production packages: the Kernel lifecycle core, the Application Graph structural snapshot, and explicit typed dependency resolution. The resolver accepts canonical interface contracts, explicit implementations, and ordered constructor dependencies; validates the complete definition graph; and constructs transient objects on every edge. It provides no PSR-11 contract, autowiring, shared or scoped lifetime, Kernel lifecycle integration, or Application Graph integration. The Graph remains a structural prerequisite rather than the complete authoritative Application Graph. Runtime adapters, broader capabilities, and a working `kongo` CLI remain unimplemented.

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
- [Glossary](docs/glossary.md)
- [Architecture Decision Records](docs/adr/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

The official future CLI name is `kongo`; no executable is implemented in this phase.
