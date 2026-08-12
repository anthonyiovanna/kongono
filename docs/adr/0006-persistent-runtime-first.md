# ADR 0006: Persistent-Runtime-First Architecture

## Status

Accepted

## Date

2026-08-12

## Context

Modern PHP applications may run in processes that handle multiple operations. A framework designed around request-by-request process teardown risks state leakage and runtime lock-in.

## Decision

Design Kongono's core lifecycle for persistent runtimes from the beginning. No particular runtime may become a hard kernel dependency.

## Rationale

Explicit scopes, ownership, cleanup, and isolation support long-running processes while remaining compatible with PHP-FPM, CLI workers, serverless execution, and future runtimes.

## Consequences

Future framework state must have intentional lifecycle semantics. Runtime adapters may support FrankenPHP, RoadRunner, OpenSwoole, PHP-FPM, CLI workers, serverless environments, and future runtimes without redefining the kernel.

## Rejected alternatives

Assuming all state disappears after each HTTP request and coupling the kernel to a single runtime are rejected.

## What remains open

Operation scopes, boot and teardown APIs, concurrency, cancellation, failure cleanup, state-reset contracts, adapter interfaces, and default runtime choices remain open.
