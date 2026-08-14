# ADR 0032: Kernel Lifecycle Diagnostic Context Integration

## Status

Accepted

## Date

2026-08-14

## Context

Phase 0S moved API compatibility metadata into `kongono/api-metadata`, making a
one-way Kernel-to-Diagnostics dependency acyclic. Application Graph already
owns bounded context for its 11 deliberate failures. Kernel's six lifecycle
failures retained package-owned identifiers, titles, remediation, messages,
zero codes, and optional causes but did not expose their Accepted context
schemas or exact failure-site values.

## Decision

Add exactly `kongono/kernel -> kongono/diagnostics: *`. Kernel retains its
direct API Metadata dependency. Kernel and Application Graph depend on
Diagnostics independently; neither depends on the other, and all package
exception markers remain pairwise unrelated.

The Internal `LifecycleDiagnostic` catalog owns these exact closed schemas, with
all fields required and Safe in written order:

| Diagnostic | Operation | Violated invariant | Ordered fields |
| --- | --- | --- | --- |
| `KGN-KERNEL-1001` | Boot Kernel | Boot requires `Unbooted` | `actual_state` (Actual state, text); `expected_state` (Expected state, text) |
| `KGN-KERNEL-1002` | Shut down Kernel | Shutdown requires `Booted` | `actual_state` (Actual state, text); `expected_state` (Expected state, text) |
| `KGN-KERNEL-1003` | Shut down Kernel | Shutdown requires zero active operations | `active_operation_count` (Active operation count, integer); `expected_active_operation_count` (Expected active operation count, integer) |
| `KGN-KERNEL-1004` | Begin operation | Beginning work requires `Booted` | `actual_state` (Actual state, text); `expected_state` (Expected state, text) |
| `KGN-KERNEL-1005` | End operation | Ending a scope requires `Booted` | `actual_state` (Actual state, text); `expected_state` (Expected state, text) |
| `KGN-KERNEL-1006` | End operation | Scope must be active and owned by this Kernel | `scope_condition` (Scope condition, text) |

Failure sites supply only these exact values: current `KernelState` case name
and the expected `Unbooted` or `Booted` case name; one exact active-operation
count and expected count `0`; or the conservative closed token
`inactive_or_foreign`. The shutdown count is read once before mutation and used
for validation, the unchanged message, and context. Kernel intentionally does
not distinguish inactive, ended, and foreign scopes and gains no owner metadata.

The Public Kernel exception marker adds
`diagnosticContext(): DiagnosticContext`. The Internal Kernel exception base
constructs one context eagerly before native exception initialization, retains
only that context, and returns the same object by identity. Input and returned
arrays are detached, schemas and contexts are immutable, and separate
exceptions share neither context nor schema objects.

Existing lifecycle transitions, validation priority and timing, operation
identity, valid-state preservation, identifiers, titles, remediation, messages,
zero codes, optional cause identity, API status, exception ownership, and
persistent-runtime doctrine remain unchanged.

## Rationale

Closed catalog schemas preserve Kernel ownership of lifecycle meaning while the
Diagnostics package supplies presentation-neutral bounded values. Eager,
immutable, detached exception ownership makes failure context deterministic and
prevents later state or another operation from changing it.

## Consequences

Consumers catching the Public Kernel marker can inspect one exact bounded
context. Phase 0U subsequently completes all 42 original deliberate failures
through independent Kernel, Graph, and DI integrations. Diagnostics' own 11
construction failures remain context-free. All Kernel fields are Safe
eligibility, not automatic disclosure authorization.

Context construction failure propagates unchanged as a Diagnostics-package
internal integration defect. It is not caught, wrapped, replaced, suppressed,
or combined with the intended Kernel failure.

## Compatibility impact

The Public Kernel marker gains one method and the Internal base constructor
gains the context-values argument. This is an intentional pre-alpha Public
signature addition. No Internal catalog type enters the Public marker.

## Security considerations

Captured values are limited to closed state case names, bounded native integer
counts, and `inactive_or_foreign`. Context never captures object identity,
hashes, class names, addresses, operation data, request, session, authorization,
secrets, credentials, tokens, headers, bodies, environment, paths, source,
stacks, messages, arbitrary strings, or user data. Safe does not make a value
public; a later explicit disclosure policy remains required.

## Performance implications

Each failure performs eager work and allocation bounded by at most two fields
and the existing Diagnostics text limits. There is no cache, I/O, ambient
lookup, static registry, renderer, transport, logger, or telemetry path.

## Rejected alternatives

- Aliases, a universal exception marker, arbitrary maps, optional fields, and
  Protected Kernel fields are rejected because the Accepted schemas are exact.
- Raw scope or object identity, owner tracking, and a semantic split among
  inactive, ended, and foreign scopes are rejected because existing Kernel
  state cannot and must not distinguish them.
- Lazy or nullable context, fallback context, and swallowed construction failure
  are rejected because sanctioned maps are total and integration defects must
  remain visible.
- Rendering, transport integration, logging, telemetry, and Dependency
  Injection integration are rejected as later capabilities.

## What remains open

Exactly these areas remain open: simultaneous construction-failure
representation; disclosure policy; trusted
developer mode; rendering and transports; serialization, localization, source,
stack, and documentation providers; logging, telemetry, and incidents; retry,
cancellation, cleanup, and recovery; and later framework capabilities.

## Related decisions

- [ADR 0026](0026-actionable-diagnostics-and-error-boundaries.md)
- [ADR 0027](0027-package-local-runtime-diagnostic-metadata.md)
- [ADR 0028](0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0029](0029-presentation-neutral-diagnostic-value-foundation.md)
- [ADR 0030](0030-application-graph-diagnostic-context-integration.md)
- [ADR 0031](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md)
- [ADR 0033](0033-dependency-injection-diagnostic-context-integration.md)
- [Error-handling guide](../engineering/error-handling.md)
