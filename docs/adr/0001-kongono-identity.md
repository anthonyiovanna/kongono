# ADR 0001: Kongono Identity

## Status

Accepted

## Date

2026-08-12

## Context

The framework needs a consistent identity across documentation, packages, namespaces, domains, and future command-line interfaces.

## Decision

The project is **Kongono**, the framework name is **Kongono Framework**, the primary domain is `kongono.com`, the PHP namespace is `Kongono\`, the Composer vendor is `kongono`, and the official future CLI is `kongo`. The product statement is **Build what comes next.**

## Rationale

A locked identity prevents incompatible names and accidental public contracts from spreading during foundation work.

## Consequences

All project materials must use these values consistently. The root Composer package is `kongono/framework`. Future PHP code will use the `Kongono\` namespace when authorized.

## Rejected alternatives

Renaming, abbreviating, reinterpreting, or replacing these values is rejected. The optional `kgo` CLI alias is not authorized.

## What remains open

Brand implementation, domain operations, package naming beyond the root manifest, CLI architecture, and any optional aliases remain open. No license decision is implied.
