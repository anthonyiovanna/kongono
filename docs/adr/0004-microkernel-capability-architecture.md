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

## Rejected alternatives

A feature-heavy monolithic kernel and an uncoordinated collection of unrelated packages are rejected.

## What remains open

Kernel responsibilities, package boundaries, capability metadata, dependency rules, composition, boot lifecycle, container contracts, and public APIs remain experimental.
