# ADR 0017: PHPUnit as Core Test Runner

## Status

Accepted

## Date

2026-08-12

## Context

Kongono core development needs one official test lifecycle before framework implementation begins, without deciding what downstream applications must use.

## Decision

Use `phpunit/phpunit ^13.2` as the official test runner for Kongono's framework repository. Configure explicit failure behavior for warnings, risky tests, deprecations, PHPUnit deprecations, notices, incomplete tests, an empty suite, and unexpected output.

This decision does not select or restrict test frameworks for applications built with Kongono.

## Rationale

PHPUnit provides the direct, established test runner needed for core repository validation without adding an alternative DSL or another dependency layer.

## Consequences

Tests live under the development-only `Kongono\Tests\` namespace. Phase 0B contains only an environment sanity test and no production class. Coverage metadata and percentage gates are not required.

## Rejected alternatives

Pest and other test frameworks are not added to the core repository in Phase 0B. Empty, skipped, or incomplete tests may not be used to conceal missing validation.

## What remains open

Application testing integrations, fixture policy, integration-test infrastructure, parallel execution, coverage targets, and CI execution remain separate decisions.
