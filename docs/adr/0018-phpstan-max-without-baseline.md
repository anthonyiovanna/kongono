# ADR 0018: PHPStan Maximum Level Without a Baseline

## Status

Accepted

## Date

2026-08-12

## Context

Introducing static analysis after a large implementation often creates a backlog that becomes normalized through broad suppressions. Kongono can establish its standard before framework source exists.

## Decision

Use PHPStan 2.2 with its strict-rules package at `level: max`, analyzing as PHP 8.5.0. Begin with no baseline, ignored errors, inline suppressions, blanket exclusions, or bleeding-edge configuration.

## Rationale

Maximum-level analysis from the first executable PHP file makes precise contracts the normal development condition rather than a later migration project.

## Consequences

Genuine findings must be corrected. An apparent false positive that cannot be resolved without degrading code requires architect review before any suppression. Local analysis cache data is ignored.

## Rejected alternatives

Lower levels, Psalm, a generated baseline, broad `ignoreErrors`, inline PHPStan ignores, error-identifier suppression, and removing strict rules to obtain a green build are rejected for Phase 0B.

## What remains open

Production analysis paths, extension configuration, custom rules, architecture checks, and any narrowly justified future suppression require explicit authorization.
