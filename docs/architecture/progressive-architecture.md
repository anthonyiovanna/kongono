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

The completed Phase 6 Data Platform lets applications use explicit parameter-
bound SQL without requiring an ORM. Applications may progressively adopt
immutable SQL plans, deterministic migrations, optional generated Data Mapper
support, and operation-scoped integration as their needs justify each layer.

This progression does not authorize static database facades, ambient
connections, runtime service location, hidden lazy loading, request-time mapping
reflection or proxies, or Active Record as the framework core. Provider
capabilities, transactions, cleanup, and portability claims remain explicit.
Phase 6F implements the optional Experimental mapping build layer without
changing these invariants. Generated output is derived and not checked in; raw
SQL and typed plans remain first-class. Composition/lifecycle integration is
implemented by Phase 6G's outer database-application package without changing
the lower mapping or composition contracts. Phase 6 is complete at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05`.

Phase 7A accepts Deterministic Persistence as one optional later progression
above those first-class paths. It keeps domain classes plain and application-
owned, mapping compiled before runtime, identity and change state operation-
scoped, flush and transaction authority explicit, and relationship load plans
bounded and visible. An application may continue using direct SQL or generated
Data Mapper repositories without installing persistence. Phase 7B now
implements the Experimental neutral `kongono/persistence` contract package.
Phase 7C adds the optional upper `kongono/persistence-build` package for one
strict application source, current mapping/domain validation, detached model,
and canonical provenance artifact. Phase 7D adds deterministic content-addressed
ordinary PHP state adapters with non-executing structural/current validation and
guarded publication. Applications may still stop at direct SQL or Data Mapper
repositories. Phase 7E adds one optional concrete single-entity operation
context over caller-owned database authority. Explicit flush executes at most
one change and uncertain failure releases all retained domain state. It adds no
generated-state loader, transaction owner, or SQL generation. Phase 7F adds the
separate repository source, explicit bounded one-level relationship/cascade
contracts, and generated stateless aggregate repositories. Command behavior
remains deferred; Phase 7G adds the optional exact-current generated loading and
Composition workflow.

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
- [Deterministic Persistence](deterministic-persistence.md)
- [Persistence build engineering guide](../engineering/persistence-build.md)
- [Persistence generation engineering guide](../engineering/persistence-generation.md)
- [Phase 7 roadmap](phase-7-roadmap.md)
