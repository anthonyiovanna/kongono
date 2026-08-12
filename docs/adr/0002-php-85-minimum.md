# ADR 0002: PHP 8.5 Minimum

## Status

Accepted

## Date

2026-08-12

## Context

Kongono needs a declared primary application language and minimum runtime baseline before framework APIs are designed.

## Decision

PHP is the primary application language. Kongono requires PHP 8.5 or newer, represented in Composer as `php >=8.5`.

## Rationale

Beginning from the PHP 8.5 baseline allows later architecture to use the language level deliberately rather than inherit compatibility constraints from older versions.

## Consequences

Development and consumer environments must provide PHP 8.5+ once executable framework code exists. Phase 0A can validate manifest structure on an older local PHP runtime because no framework code or dependencies are installed or executed.

## Rejected alternatives

Supporting an older PHP baseline or selecting another primary application language is rejected by the locked identity.

## What remains open

Supported PHP minor-version policy, upgrade cadence, platform extensions, configuration requirements, and runtime certification matrix remain open.
