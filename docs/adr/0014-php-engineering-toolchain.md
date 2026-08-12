# ADR 0014: PHP Engineering Toolchain

## Status

Accepted

## Date

2026-08-12

## Context

Framework implementation needs deterministic quality checks before source packages or public APIs are introduced. Independent tool invocations without a governed order would make contributor results inconsistent.

## Decision

Use Composer 2.10+ to manage the root development environment and expose one non-mutating `composer quality` gate. The gate runs the `composer validate:composer` manifest validation, the `composer audit:locked` lock-file audit, actual platform verification, PHP CS Fixer style checking, PHPStan analysis, and PHPUnit tests in that order. `composer validate:composer` executes `composer validate --no-check-publish`, and `composer audit:locked` executes `composer audit --locked`; Composer's built-in `composer validate` and `composer audit` commands remain unchanged.

Use `friendsofphp/php-cs-fixer ^3.95`, `phpstan/phpstan ^2.2`, `phpstan/phpstan-strict-rules ^2.0`, and `phpunit/phpunit ^13.2` as the only Phase 0B direct development dependencies.

## Rationale

A single ordered gate makes failures reproducible and prevents later implementation from choosing quality policy opportunistically. Establishing the system before framework source also proves the tools can operate without creating accidental APIs.

## Consequences

PHP changes must pass relevant focused checks and normally the complete gate before completion. The formatter's mutating command remains separate from the quality gate. Tool caches and installed dependencies are generated local state.

## Rejected alternatives

Pest, Psalm, PHP_CodeSniffer, Rector, mutation testing, architecture-test packages, Git hooks, Node or Rust tooling, and CI/CD are deferred rather than included in the initial gate.

## What remains open

CI execution, coverage policy, mutation testing, architecture checks, benchmark tooling, release gates, and later quality services require separate authorization.
