# ADR 0003: Monorepo for Initial Development

## Status

Accepted

## Date

2026-08-12

## Context

Early framework development will require architectural changes and validation that span the kernel, Graph work, tooling, and multiple capabilities.

## Decision

Use a monorepo for Kongono's initial development. Do not create empty package or component directories before their approved implementation phases.

## Rationale

A monorepo enables atomic architectural refactors, cross-component testing, shared tooling, coordinated early releases, and Application Graph work spanning multiple components.

## Consequences

Early components will be developed together when authorized. Repository structure must remain intentional rather than pre-allocating speculative packages. Phase 0C implements `packages/kernel`; Phase 0D adds `packages/application-graph`; Phase 0F adds `packages/dependency-injection` as the third physical Composer package. The root project consumes all three through the canonical `packages/*` path repository and locks each untagged local package through an exact root `@dev` constraint. Repository-wide production inventory and API-status enforcement belongs to the root architecture test suite rather than any package. Components may later be published independently.

## Rejected alternatives

Starting with separate repositories is rejected because it would make early cross-cutting changes and coordination harder. Creating a large empty package skeleton is also rejected.

## What remains open

Future component boundaries, publishing strategy, automated repository splitting, versioning, release coordination, and package distribution remain open.
