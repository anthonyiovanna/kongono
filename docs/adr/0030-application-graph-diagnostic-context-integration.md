# ADR 0030: Application Graph Diagnostic Context Integration

## Status

Accepted

## Date

2026-08-13

## Context

The Diagnostics package provides bounded presentation-neutral schemas and
values, while the 42 deliberate Kernel, Application Graph, and Dependency
Injection failures previously exposed only package-owned identifiers, titles,
remediation, native messages, zero codes, and optional causes. A first complete
integration slice is needed to prove that an originating package can own exact
semantic schemas and throw-site values without creating a universal exception
boundary or selecting presentation policy.

Application Graph is the first slice because its 11 failures have closed
structural inputs and deterministic existing validation order. Their authorized
context consists only of bounded counts, positions, broad value-kind tokens, and
exact Graph structural values. Kernel integration remains future because its
dependency topology requires a separate decision. Dependency Injection's 25
schemas and 26 throw sites remain future because their reflection and graph
validation inputs require their own focused integration.

## Decision

At Phase 0Q, `kongono/application-graph` depended on `kongono/diagnostics` and
on `kongono/kernel` only for `Api` and `ApiStatus`; Diagnostics also depended on
Kernel only for the same metadata. Phase 0S implements ADR 0031: Graph and
Diagnostics now depend directly on `kongono/api-metadata`, Graph retains its
Diagnostics dependency, and neither depends on Kernel. The current direction is
acyclic:

```text
application-graph -> diagnostics -> api-metadata
application-graph ----------------> api-metadata
```

At Phase 0Q, the Public Graph package marker added exactly
`diagnosticContext(): DiagnosticContext`. This pre-alpha Public signature change
was intentional. At that phase, the Kernel, Dependency Injection, and Diagnostics
markers retained exactly their three string accessors. Phase 0T now adds the same
Public context-value accessor independently to Kernel for its six lifecycle
failures. All markers remain pairwise unrelated, and Diagnostics construction
failures remain context-free to avoid recursive construction.

The Internal `GraphDiagnostic` catalog owns one exact closed schema for each of
its 11 cases. Each schema fixes operation, violated invariant, field keys,
labels, scalar kinds, disclosures, order, and required or optional status. Throw
sites supply only the exact approved value map and cannot select schema metadata.

For non-list input, Graph walks keys in insertion order from expected integer
zero and stops at the first mismatch. An integer mismatch is captured as its
canonical base-10 string. A string mismatch is omitted entirely and no later key
is inspected. Numeric PHP-string keys already normalized to integers follow the
integer rule. This is conservative omission, not secret detection or
sanitization.

Invalid entry values use exactly these broad tokens:

```text
null
boolean
integer
float
string
array
object
resource
closed_resource
unknown
```

Tokens never expose object classes, resource types, paths, callable targets, or
value content. PHP `double` maps to `float`, `resource (closed)` maps to
`closed_resource`, and an otherwise branch maps to `unknown`.

The Internal Graph exception base constructs context eagerly and synchronously
before native exception initialization, stores the immutable result, and retains
no value map. Repeated access returns the same context object by identity. Every
reachable deliberate Graph failure supplies schema-approved keys and native
kinds, so sanctioned construction is total over that domain. A Diagnostics
construction failure would indicate an internal Graph implementation defect and
is not caught, wrapped, replaced, or represented alongside the Graph failure.
There is no fallback, nullable accessor, recursive context, or simultaneous-
failure container.

Existing Graph IDs, titles, remediation, messages, zero codes, optional causes,
failure priority, validation timing, canonical ordering, success semantics,
lookup, selectors, object identity, and complexity contracts remain unchanged.
Context observes an existing failure and does not enter the Graph snapshot.

Safe fields are eligible only for a later explicit policy. Protected Graph
keys, kinds, and numeric unexpected-key representations remain unavailable to
untrusted surfaces. Phase 0Q discloses nothing. Passwords, credentials, tokens,
cookies, authorization material, sessions, keys, connection strings, bodies,
headers, SQL values, provider payloads, file or source contents, serialized
objects, memory dumps, and uncertain secret material remain forbidden from
capture. Arbitrary string array keys are omitted rather than retained, hashed,
redacted, or labeled.

Each exception owns one eager detached context. No context or value map enters a
static property, global registry, service container, Kernel state, Graph
snapshot, operation scope, cache, logger, tracer, renderer, or transport.
Allocation is per failure. Schema construction and value capture perform linear
bounded work under the existing maximum of 16 fields and 512 represented text
bytes, with no I/O or ambient lookup. No cached or constant-time schema lookup is
promised.

## Rationale

Graph provides a complete but narrow vertical slice that tests the shared value
foundation while preserving package-local semantic and recovery ownership.
Catalog-owned schemas prevent arbitrary metadata bags, and value-only throw
sites keep security classification and field order reviewable. Eager immutable
exception ownership preserves deterministic behavior in persistent runtimes.

## Consequences

Consumers that catch the Graph marker can inspect one exact bounded context in
addition to the existing metadata and native exception state. This creates a
Phase 0Q pre-alpha Public compatibility obligation for the Graph marker; Phase
0T separately creates the corresponding Kernel-marker obligation. Consumers
still need a future explicit policy before presenting any field, and no common
catch boundary exists.

Phase 0T integrates Kernel's six diagnostics without changing this Phase 0Q
Graph decision. Phase 0U independently integrates Dependency Injection's 25
diagnostics across 26 throw sites. Diagnostics' own 11 construction failures
remain context-free. None of these phases provides a developer error page.

Phase 0R resolves the previously open Kernel dependency-topology question at
the architecture level through [ADR 0031](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md).
Phase 0S implements that exact neutral metadata package and removes the former
cycle. Phase 0T then adds the exact one-way Kernel-to-Diagnostics dependency,
all six Kernel context schemas and throw-site values, and the Kernel marker
accessor accepted by [ADR 0032](0032-kernel-lifecycle-diagnostic-context-integration.md).

## Rejected alternatives

- Arbitrary context maps or consumer-selected schemas are rejected because they
  evade package ownership, ordering, bounds, and disclosure review.
- A universal exception or diagnostic marker is rejected because recovery
  ownership remains package local.
- Nullable context, fallback context, and catching Diagnostics construction
  failure are rejected because sanctioned Graph construction is total and an
  internal mismatch must remain visible.
- Recursive Diagnostics context and simultaneous-failure representation are
  rejected as circular or premature.
- Secret detection, redaction, sanitization, classification inference, and
  automatic disclosure are rejected because Phase 0Q captures only the exact
  Accepted fields and selects no policy.
- Renderers, transports, logging, telemetry, incident storage, source access,
  documentation lookup, serialization, and ambient capture are rejected as
  later capability decisions.

## What remains open

Disclosure and production allowlist policy; trusted developer mode; renderers
and development or production surfaces; HTTP, CLI,
and other transports; serialization; source and stack providers;
version-matched documentation lookup; logging and telemetry; incidents; retry,
cancellation, cleanup, and simultaneous-failure representation all remain
future work.

## Related decisions

- [ADR 0026](0026-actionable-diagnostics-and-error-boundaries.md)
- [ADR 0027](0027-package-local-runtime-diagnostic-metadata.md)
- [ADR 0028](0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0029](0029-presentation-neutral-diagnostic-value-foundation.md)
- [ADR 0033](0033-dependency-injection-diagnostic-context-integration.md)
- [Error-handling guide](../engineering/error-handling.md)
