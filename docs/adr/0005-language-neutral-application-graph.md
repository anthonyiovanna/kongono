# ADR 0005: Language-Neutral Application Graph

## Status

Accepted

## Date

2026-08-12

## Context

Humans, infrastructure, tooling, AI agents, and other applications need authoritative application knowledge without reverse-engineering arbitrary PHP source.

## Decision

The Application Graph will become Kongono's authoritative machine-readable application representation and will derive from a language-neutral Intermediate Representation. PHP is the first source-language frontend, not the permanent definition of the Graph.

## Rationale

A language-neutral model makes application knowledge deterministic, supports multiple consumers, avoids permanent PHP-syntax coupling, and gives future tooling a common architectural source.

## Consequences

Correctness-critical structure and relationships must eventually be derived and validated deterministically. Human documentation will explain concepts while the Graph supplies machine-derived relationships. Phase 0D implements only the language-neutral structural subset: immutable nodes with opaque graph-local keys and open kinds, immutable directed open-kind relations, structural validation, canonical binary-string order, and exact node lookup. It is a prerequisite rather than the complete authoritative representation.

## Rejected alternatives

Binding the conceptual Graph directly to PHP syntax and asking tools or LLMs to infer architecture by crawling arbitrary source are rejected.

## What remains open

Stable cross-build identifiers, domain vocabularies and schemas, serialization and versioning, compiler pipeline and source frontends, metadata and provenance, persistence, caching, visibility and security filtering, merging, queries, and broader public APIs are explicitly experimental and unselected.
