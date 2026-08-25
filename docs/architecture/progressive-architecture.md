# Progressive Architecture

## Purpose

Kongono should let an application begin simply and adopt stronger boundaries as complexity grows without requiring a framework migration.

## MVC at the edge

MVC is a friendly default for HTTP and UI concerns. Controllers may translate transport input, invoke application behavior, and produce transport output. Business logic must not be structurally trapped in controllers or depend permanently on the HTTP edge.

## Supported direction

The architecture will support applications organized with:

- MVC
- Actions or use cases
- Modular monoliths
- Domain-driven architecture
- Ports and adapters
- CQRS where justified
- Events
- Durable workflows

These styles are not mandatory layers, generated templates, or implemented APIs in Phase 0A. Their future integration must preserve explicit boundaries and shared Kongono invariants.

## Progression principle

Increasing rigor should be driven by application needs rather than framework ceremony. A small application should not pay the structural cost of a large system, while a growing application must have a path toward explicit modules, application actions, domain boundaries, and asynchronous or durable coordination.

## Invariants

Architectural flexibility does not authorize arbitrary framework behavior. Phase 0F now supplies only explicit interface-to-implementation constructor resolution with transient construction; autowiring, service scopes, and application-architecture conventions remain unselected. Application Graph knowledge, structured CLI output, broader architecture validation, security metadata, telemetry, and other machine-readable contracts remain planned.

## Data progression

Phase 6A accepts a layered data direction that lets applications use explicit
parameter-bound SQL without requiring an ORM. Later applications may adopt
immutable SQL plans, deterministic migrations, optional generated Data Mapper
support, and operation-scoped lifecycle as their needs justify each layer.

This progression does not authorize static database facades, ambient
connections, runtime service location, hidden lazy loading, request-time mapping
reflection or proxies, or Active Record as the framework core. Provider
capabilities, transactions, cleanup, and portability claims remain explicit.
Phase 6F now implements the optional Experimental mapping build layer without
changing these invariants. Generated output is derived and not checked in; raw
SQL and typed plans remain first-class. Composition/lifecycle integration is
implemented by Phase 6G's outer database-application package without changing
the lower mapping or composition contracts.

## Do not use this doctrine to

- Put all business behavior in controllers because MVC is supported.
- Introduce CQRS, events, or workflows without a justified application need.
- Create speculative abstraction layers for future scale.
- Bypass framework security, lifecycle, or metadata invariants.

## Stability

The progressive-architecture direction is **LOCKED**. Specific scaffolds, module rules, action interfaces, CQRS contracts, and workflow APIs remain open.

## Related concepts

- [Architecture overview](overview.md)
- [Application Graph](application-graph.md)
- [Microkernel](microkernel.md)
- [Data Platform](data-platform.md)
- [Phase 6 roadmap](phase-6-roadmap.md)
- [Compile-time database mapping](database-mapping.md)
