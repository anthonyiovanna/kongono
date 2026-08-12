# ADR 0010: Vite Integration Strategy

## Status

Accepted

## Date

2026-08-12

## Context

Kongono needs a modern frontend build path without owning the complexity and ecosystem burden of a JavaScript bundler.

## Decision

Do not build a Kongono JavaScript bundler. Use Vite as the intended default frontend build integration, while Kongono owns an Asset Platform or integration abstraction rather than the bundler.

## Rationale

This boundary combines a productive default with replaceability and avoids duplicating mature frontend build tooling.

## Consequences

Future adapters may support React, Vue, Svelte, HTMX, Alpine, server-rendered HTML, and native/no-bundler assets. The kernel must not depend on Vite.

## Rejected alternatives

Building a custom Kongono bundler and making a bundler part of the kernel are rejected.

## What remains open

The Asset Platform API, Vite adapter, manifest format, development-server integration, asset discovery, adapter contracts, and frontend package strategy remain open. No frontend tooling is introduced in Phase 0A.
