# ADR 0013: Documentation Doctrine

## Status

Accepted

## Date

2026-08-12

## Context

Developers, tools, and agents cannot safely use a framework when documentation restates names but omits purpose, contracts, lifecycle, failure, or security context.

## Decision

Kongono documentation must explain the relevant purpose, context, use and non-use cases, inputs, outputs, side effects, security, lifecycle, concurrency, failure, performance, calling contract, extension contract, agent contract, stability, and related concepts. Coverage must be proportional to complexity and exposure.

Prefer good types, names, and structure before comments. Do not use comments that merely narrate code. Human-authored documentation should explain conceptual relationships rather than maintain exhaustive caller/callee lists.

## Rationale

Context-rich documentation reduces reverse-engineering, preserves architectural intent, exposes risks and invariants, and supports reliable human and agent consumption.

## Consequences

Public and security-sensitive concepts require substantial contextual documentation. Planned tooling should eventually test references, examples, public API coverage, security documentation, deprecations, and symbol links.

## Rejected alternatives

Vague documentation for brevity, comments that restate code, and manually maintained exhaustive relationship lists are rejected.

## What remains open

Stable Documentation ID syntax and mechanism, source linking, validation tooling, required public API coverage, templates, versioned documentation delivery, and enforcement policy remain experimental. No custom PHP attribute or annotation is authorized in Phase 0A.
