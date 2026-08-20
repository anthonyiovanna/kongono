# ADR 0004: Microkernel and Capability Architecture

## Status

Accepted

## Date

2026-08-12

## Context

Kongono needs broad framework capability without concentrating every feature in a coupled core or producing an incoherent collection of packages.

## Decision

Use a small microkernel with independently installable components that provide capabilities while presenting one coherent framework experience.

## Rationale

This structure preserves modularity, allows capabilities to evolve independently, and protects the kernel from feature and runtime coupling.

## Consequences

Future HTTP, Data, ORM, Authentication, Events, Workflows, AI, Agent, Frontend, and other capabilities will not automatically belong in the kernel. Components must participate in shared framework invariants where applicable.

Phase 0F implements explicit typed dependency resolution in a separate component. The resolver has no Kongono-supplied, special, or preconfigured Kernel binding or lifecycle behavior. Phase 0S replaces its former metadata-only Kernel dependency with the neutral API Metadata package; resolver behavior is unchanged. A consumer may explicitly select a Kernel interface as an ordinary service contract without creating framework-owned integration.

Phase 0T adds only the one-way `kernel -> diagnostics` dependency required for
Kernel-owned bounded lifecycle context. This does not make Diagnostics a Kernel
service, merge package exception boundaries, or change capability ownership.

Phase 0U independently adds `dependency-injection -> diagnostics` for exact
DI-owned bounded failure context. It adds no Kernel binding, service locator,
container lifetime, autowiring, PSR-11 surface, or shared exception boundary.

Phase 0X adds `diagnostic-presentation` only as a leaf consuming the five prior
packages. Its pure projector creates no Kernel hook, service binding, container
definition, Graph node, runtime adapter, renderer, or transport.

Phase 2A current-reality amendment: Phase 1H is preserved at local checkpoint
`d7846f137a1d53f5306f48d19196b916fde44ac2` with exactly eight implemented
packages. Diagnostic Handler is the eighth leaf and owns only transport-neutral
eligibility-first sequencing. [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
accepts the bounded Phase 2A-H first-runnable-web milestone. Phase 2A is
documentation-only: it creates no ninth package and implements no HTTP,
routing, middleware, bootstrap, adapter, response delivery, or production
failure behavior.

Phase 2D current-reality amendment: Kongono reached ten implemented packages.
The new `kongono/routing` package consumes PSR-7 interfaces directly and remains
separate from both Kernel and `kongono/http-message`. It owns immutable bounded
route declarations and deterministic encoded method/path matching only. It adds
no Kernel hook, service binding, Graph node, handler invocation, authorization,
ambient current-route state, adapter, or runnable application.

Phase 2E current-reality amendment: Kongono now has eleven implemented packages.
The new `kongono/http-server` package owns an explicit bounded immutable PSR-15
middleware pipeline and complete canonical route-name terminal mapping. It adds
no Kernel hook, service binding, Graph node, bootstrap, operation-scope
ownership, adapter, emission, supplied error middleware, or runnable
application. Phase 2F is next; Phase 2H remains the first runnable black-box
milestone.

## Rejected alternatives

A feature-heavy monolithic kernel and an uncoordinated collection of unrelated packages are rejected.

## What remains open

Kernel responsibilities beyond the implemented lifecycle and its exact diagnostic
context, package boundaries beyond the eleven implemented packages, capability
metadata, composition, service lifetimes, PSR-11 interoperability, Graph
integration, and broader public APIs remain experimental or open.
