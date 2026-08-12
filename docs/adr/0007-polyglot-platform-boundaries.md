# ADR 0007: Polyglot Platform Boundaries

## Status

Accepted

## Date

2026-08-12

## Context

Kongono will span server applications, browser integration, and potentially performance-sensitive tooling. Each language needs an intentional role.

## Decision

Use PHP 8.5+ as the primary application language. Use TypeScript or JavaScript for browser/frontend concerns. Rust may be used for high-performance framework tooling. Go may be used selectively for infrastructure or runtime integration where appropriate.

## Rationale

Explicit boundaries avoid introducing languages as novelty while leaving room for fit-for-purpose frontend, tooling, and infrastructure work.

## Consequences

PHP syntax cannot define the language-neutral IR permanently. Later use of TypeScript, JavaScript, Rust, or Go requires phase-specific authorization and justified scope.

## Rejected alternatives

Building all platform concerns in one language regardless of fit and introducing polyglot tooling during Phase 0A are rejected.

## What remains open

Tool implementations, inter-process protocols, distribution, build systems, language versions, and the specific conditions justifying Rust or Go remain open.
