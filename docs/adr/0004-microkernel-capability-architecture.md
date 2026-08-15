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

## Rejected alternatives

A feature-heavy monolithic kernel and an uncoordinated collection of unrelated packages are rejected.

## What remains open

Kernel responsibilities beyond the implemented lifecycle and its exact diagnostic
context, package boundaries beyond the six implemented packages, capability
metadata, composition, service lifetimes, PSR-11 interoperability, Graph
integration, and broader public APIs remain experimental or open.
