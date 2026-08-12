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

Phase 0F implements explicit typed dependency resolution in a separate component. The resolver has no Kongono-supplied, special, or preconfigured Kernel binding or lifecycle behavior; its production source depends on Kernel only for API-status metadata. A consumer may explicitly select a Kernel interface as an ordinary service contract without creating framework-owned integration.

## Rejected alternatives

A feature-heavy monolithic kernel and an uncoordinated collection of unrelated packages are rejected.

## What remains open

Kernel responsibilities beyond the implemented lifecycle, future package boundaries, capability metadata, composition, service lifetimes, PSR-11 interoperability, Graph integration, and broader public APIs remain experimental or open.
