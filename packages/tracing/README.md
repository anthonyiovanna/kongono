# Kongono Tracing

`kongono/tracing` implements the Experimental Phase 10C operation-scoped
tracing lifecycle. It provides opaque validated trace/span identifiers, trusted
typed propagation, caller-owned clock/identifier/sampling collaborators, one
explicit root operation, bounded child spans, and deterministic trace
observations through an explicitly supplied `ObservationRecorder`.

## Dependency direction

The package depends only on `kongono/api-metadata`, `kongono/diagnostics`,
`kongono/observability`, and PHP `>=8.5.0 <8.6.0`. No existing production
package depends on Tracing. It has no Kernel, Composition, HTTP, Console,
database, persistence, security, application, provider, exporter, or
OpenTelemetry dependency.

## Use when

Use `TraceRuntime` when a caller can explicitly supply an `ObservabilityContext`,
versioned `SignalIdentity`, bounded `AttributeSet`, monotonic nanosecond clock,
typed identifier source, sampling policy, and recorder. `begin()` creates one
fresh isolated root operation. `beginSpan()` creates a child of the root or an
exact active child owned by that operation.

Use `TracePropagation` only after an outer caller has validated any untrusted
carrier. It retains exact typed identifiers and a sampling decision; it parses,
formats, authenticates, and authorizes nothing.

## Identifiers, attributes, and observations

`TraceId` accepts exactly 32 lowercase ASCII hexadecimal characters and
`SpanId` exactly 16; all-zero identifiers are invalid. The package makes no
randomness, uniqueness, secrecy, timing-resistance, or W3C-conformance claim.

Root and child callers may supply at most 25 attributes and may not use the
seven reserved `trace.*` keys. A recorded observation preserves each exact
caller attribute object and adds trace ID, span ID, optional parent span ID,
sampled `true`, start/end ticks, and nonnegative duration. New roots omit only
the parent key. A Drop decision constructs and records no observation.

## Lifecycle, cleanup, and concurrency

An operation and its spans begin Active. At most 64 child spans may be Active
simultaneously. Parent ownership uses exact object identity, and span IDs cannot
repeat anywhere in one operation, including its inbound parent and root.
Completing a root requires zero Active children. Completion accepts only Ok or
Error, reads one validated end tick, releases retained operation evidence before
the external recorder call, and is final even if recording fails.

Retirement is idempotent. Active operation retirement retires all active
children, releases operation-local references, records nothing, calls no
collaborator, and cannot fail. Completed or retired handles cannot propagate.
Sequential, overlapping, and reentrant operations share no runtime-owned
mutable state. The package exposes no ambient current span, global tracer,
registry, thread/fiber local, request singleton, or shutdown hook.

## Failure and security

Invalid values, lifecycles, collaborator failures, and recording failures use
the closed `KGN-TRACING-1001` through `KGN-TRACING-1004` diagnostics. Contexts
contain only fixed Safe package, category, and remediation evidence. Rejected
IDs, attributes, ticks, exception messages, stack values, carriers, paths, and
provider output are not retained or disclosed. Collaborator and recorder
wrappers preserve the exact previous throwable for programmatic inspection.
There is no retry, fallback, hidden logging, or no-op recorder.

Tracing evidence grants no identity, authentication, authorization, disclosure,
business-truth, repair, or mutation authority. Accessors exposing exact caller
bytes do not classify those bytes as safe for display or export.

## API and extension contract

The package exception interface is Public. Diagnostics, the exception base, and
all `Internal` implementations are Internal. Every other production declaration
is Experimental. Callers implement the three collaborator interfaces and may
provide an `ObservationRecorder`; the package supplies no implementation or
default policy.

## Explicitly absent

There is no W3C carrier codec, baggage, OpenTelemetry API/SDK, exporter,
processor, queue, provider, backend, wall clock, random source, default sampler,
metric runtime, operational-event or security-audit pipeline, application
integration, generated wiring, configuration, command, deployment behavior, or
Phase 10D work.

See [ADR 0117](../../docs/adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md),
the [Observability and Operational Evidence guide](../../docs/architecture/observability-and-operational-evidence.md),
and the [Phase 10 roadmap](../../docs/architecture/phase-10-roadmap.md).
