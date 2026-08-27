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

Architectural flexibility does not authorize arbitrary framework behavior. Phase 0F now supplies only explicit interface-to-implementation constructor resolution with transient construction; autowiring, service scopes, and application-architecture conventions remain unselected. Phase 8E now supplies only deterministic Console result/help presentation and explicit one-write delivery; real commands, native process adaptation, executable behavior, and application integration remain unselected. Broader Application Graph knowledge, architecture validation, security metadata, telemetry, and other machine-readable contracts remain bounded by their current accepted state.

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

## CLI progression

Phase 8A makes Structured CLI architecture an Accepted optional progression
above existing capabilities. Phase 8B adds the neutral `kongono/console`
contract package above API Metadata and Diagnostics. Phase 8C adds immutable
specifications and already-tokenized parsing plus the separate
`kongono/console-build` evidence layer. Phase 8D adds the optional
`kongono/console-runtime` execution layer with explicit handlers, cancellation,
reverse cleanup, and sequential isolation. Phase 8E adds the optional
`kongono/console-presentation` layer above Console with canonical human/machine
forms and explicit ports. Phase 8F adds the upper
`kongono/console-commands` capability layer with three exact read-only handlers
and generated Composition roots. Applications may continue to use lower
capabilities directly; no lower production package depends on the capability
layer. Phase 8G adds the upper `kongono/console-application` package and the
official one-shot `kongo` executable with exactly `application:build`.

Progression remains ordered: neutral definitions and invocation values,
bounded parsing and exact-current build evidence, operation-scoped execution,
human and versioned machine presentation, a first read-only command family, a
supported one-shot executable plus narrowly reviewed mutation, and external
acceptance. The sequence through the supported executable is implemented. Command
meaning, execution, and presentation remain separate.
Hidden host reads, runtime discovery, service location, hidden interaction,
false dry runs, direct handler output, and process-exit cleanup are rejected.

Phase 8G changes the topology from thirty-five to thirty-six packages and adds
only the supported process/application edge. `--plan` is non-mutating;
`--apply` explicitly authorizes contained, atomic, content-addressed,
no-clobber Composition publication. No prompt exists and `--no-interaction` is
automation-safe. Phase 8F snapshots remain programmatic rather than fabricated.
It changes no Application Graph or Composition schema, Kernel lifecycle, PHP
policy, or Stable API status. Phase 8H supplies root-owned external acceptance
and the exact Phase 8 checkpoint without changing that topology or production
authority. See the [Phase 8 external acceptance guide](phase-8-external-acceptance.md).

## Identity and access progression

Phase 9 follows the completed Phase 8 checkpoint as a separate optional
security progression. Phase 9A accepts doctrine and the fixed staged sequence
only. It adds no runtime or package. The ordered path is: neutral principal and
security-result contracts; explicit authentication orchestration; deterministic
deny-by-default authorization evaluation; operation-scoped Security Context and
Composition integration; one selected HTTP authentication/session and
protected-capability enforcement profile; one supported application workflow
with exact-current policy evidence; and external acceptance at the exact
checkpoint.

Each letter requires a separate architect directive. Applications cannot skip
the separation by treating a user record, principal kind, role, session, route,
middleware, binding, or command as permission. Authentication success is not
authorization, only exact `Permit` proceeds, enforcement belongs at the
protected capability, and immutable security state ends with one operation.
Future outer adapters may depend inward on neutral contracts; lower packages do
not depend upward. See the [Identity and Access Security guide](identity-and-access-security.md)
and fixed [Phase 9 roadmap](phase-9-roadmap.md).

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
- [Structured CLI](structured-cli.md)
- [Phase 8 roadmap](phase-8-roadmap.md)
- [Phase 8 external acceptance](phase-8-external-acceptance.md)
- [ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md)
- [ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md)
- [Identity and Access Security](identity-and-access-security.md)
- [Phase 9 roadmap](phase-9-roadmap.md)
- [ADR 0105](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md)
- [ADR 0106](../adr/0106-fail-closed-security-context-and-policy-separation.md)
