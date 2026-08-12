# ADR 0011: OpenTelemetry Observability Strategy

## Status

Accepted

## Date

2026-08-12

## Context

Framework operations need structured observability that works across runtimes and exporters without locking Kongono to a proprietary backend.

## Decision

Make OpenTelemetry the intended native architectural instrumentation model. The kernel and capabilities should eventually expose structured telemetry hooks, while exporters remain replaceable.

## Rationale

A standards-based instrumentation model supports interoperability and consistent framework-level visibility without making an exporter the architecture.

## Consequences

Later lifecycle, capability, workflow, extension, and agent designs must consider meaningful telemetry and context propagation. Sensitive information must not be exposed indiscriminately.

## Rejected alternatives

A proprietary-only telemetry model, exporter coupling in the kernel, and unstructured logging as the sole observability contract are rejected.

## What remains open

Dependencies, APIs, semantic conventions, spans, metrics, logs, context propagation, redaction, sampling, exporters, and performance budgets remain open. No OpenTelemetry package is added in Phase 0A.
