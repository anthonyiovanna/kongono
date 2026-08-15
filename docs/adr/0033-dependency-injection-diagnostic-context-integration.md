# ADR 0033: Dependency Injection Diagnostic Context Integration

## Status

Accepted

## Date

2026-08-14

## Context

Application Graph and Kernel already owned bounded context for their 11 and six
deliberate failures. Dependency Injection retained 25 package-owned diagnostic
identities across 26 throw statements but exposed only identifier, title, and
remediation. Its exact schemas and values were Accepted, and the neutral API
Metadata topology permits Dependency Injection to consume Diagnostics without a
cycle or a reverse semantic dependency.

## Decision

Add exactly `kongono/dependency-injection -> kongono/diagnostics: *` while
retaining the direct API Metadata dependency. Kernel, Application Graph, and
Dependency Injection consume Diagnostics independently. Their Public exception
markers remain unrelated package-local recovery boundaries, and Diagnostics
construction failures remain context-free.

The Internal `DependencyInjectionDiagnostic` catalog owns one closed schema for
each of its 25 identifiers. The existing 26 deliberate throws supply only the
authorized bounded values; both post-construction invariant guards continue to
share `KGN-DI-1025` and one schema. Exact operations, invariants, keys, labels,
kinds, disclosures, order, and optionality follow the accepted schema table in
the error-handling guide.

Service and dependency contract failures capture the explicit closed
`service_contract` or `dependency_contract` role and the exact input contract.
Non-list inputs capture the entry count, inspect keys in insertion order only
until the first strict mismatch, represent an integer mismatch as canonical
base-10 Protected text, and omit a string mismatch. Invalid entries use closed
native-kind tokens without class, resource-type, or value detail. Constructor
mode tokens include every true predicate in fixed
`optional`, `defaulted`, `variadic`, `by_reference` order. Type-shape tokens use
the fixed missing, union, intersection, nullable, builtin, then defensive
non-interface precedence. Requested-contract rejection uses `noncanonical`
before `resolver_contract`.

The Public Dependency Injection marker adds
`diagnosticContext(): DiagnosticContext`. The Internal base eagerly constructs
and owns one immutable detached context before native exception initialization,
retains no input value map, and returns the same context object on repeated
access. Separate exceptions share neither context nor schema objects. A
Diagnostics construction failure propagates unchanged as an internal integration
defect; it is not caught, wrapped, replaced, or combined with the intended DI
failure.

Existing definition validation, deterministic duplicate/missing/cycle
selection, requested-service validation, transient top-level and per-edge
construction, messages, identifiers, titles, remediation, zero codes, optional
causes, and package ownership remain unchanged. Registered-autoloader and
consumer-constructor failures continue to propagate natively. The two defensive
post-construction sites remain in source and are verified without fabricated
invalid definition state.

## Rationale

Catalog-owned schemas preserve DI's semantic authority while Diagnostics
provides presentation-neutral bounded values. Explicit throw-site maps keep
capture reviewable and deterministic without reflection discovery, traversal,
autoloading, state, or side effects beyond work the existing failure already
performed. Eager exception ownership prevents later mutation or another
persistent-runtime operation from changing failure context.

## Consequences

All 42 original Kernel, Graph, and DI deliberate diagnostics now expose bounded
context through three independent package markers: 6 Kernel, 11 Graph, and 25
DI. The 11 Diagnostics construction failures remain context-free. Consumers
still require a later explicit disclosure and presentation policy; neither Safe
nor Protected authorizes production output.

The DI failure path performs bounded schema and context allocation. Successful
definition validation and resolution construct no schema or context and retain
their existing behavior and complexity.

Phase 0Y architecture note: [ADR 0036](0036-accessible-trusted-developer-html-diagnostic-page.md)
selects the planned trusted-developer HTML page for the already-implemented
projection of DI context. Every DI schema, throw-site map, and resolver fact
remains unchanged. Phase 0Z implements the pure renderer outside DI. Trusted
activation, production presentation, transports, and optional providers remain
future.

## Compatibility impact

The Public DI marker gains one method, and the Internal base constructor gains
the context-values argument. This is an intentional pre-alpha Public signature
addition. No Internal catalog type enters the Public marker, and no concrete
exception identity or resolver API changes.

## Security considerations

Safe values are limited to bounded counts, positions, and closed tokens.
Protected values are exact already-available contract or implementation names.
Neither classification authorizes disclosure. Context captures no object or
service instance, constructor value, native message, cause, stack, file, source,
credential, secret, request, session, authorization data, environment data, or
arbitrary payload. It enters no definition, resolver state, constructed service,
Kernel scope, Graph, cache, static property, global registry, logger, renderer,
or transport.

## Performance implications

Failure-only work is eager and linear in at most four fields plus the existing
bounded Diagnostics text representation. List-key inspection stops at the first
mismatch. Context creation adds no sorting, traversal, autoloading,
instantiation, reflection, I/O, ambient lookup, cache, or global accumulation.
Successful paths remain context-allocation free.

## Rejected alternatives

- Universal exception or diagnostic markers are rejected because recovery and
  semantic ownership remain package local.
- Arbitrary maps, consumer-selected schemas, inferred reflection metadata, raw
  values, and new diagnostic splits are rejected because the 25 schemas and 26
  maps are closed and already Accepted.
- Locator injection, invalid-state fabrication, serialization tricks, generated
  declarations, and test backdoors are rejected because they would alter or
  evade the explicit immutable definition contract.
- Lazy, nullable, cached, shared, or fallback context and swallowed construction
  failure are rejected because sanctioned capture is eager, detached, and total.
- Rendering, transports, sanitizer or trusted-mode policy, logging, telemetry,
  scopes, autowiring, and PSR-11 are rejected as later capability decisions.

## What remains open

Simultaneous primary and context-construction failure representation;
disclosure and trusted activation; other renderers and transports;
serialization, localization, source,
stack, and documentation providers; logging, telemetry, and incidents; retry,
cancellation, cleanup, and recovery; and later framework capabilities remain
open. Dependency Injection context integration is complete.

## Related decisions

- [ADR 0023](0023-explicit-typed-dependency-resolution.md)
- [ADR 0026](0026-actionable-diagnostics-and-error-boundaries.md)
- [ADR 0027](0027-package-local-runtime-diagnostic-metadata.md)
- [ADR 0028](0028-bounded-diagnostic-context-and-disclosure.md)
- [ADR 0029](0029-presentation-neutral-diagnostic-value-foundation.md)
- [ADR 0030](0030-application-graph-diagnostic-context-integration.md)
- [ADR 0031](0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md)
- [ADR 0032](0032-kernel-lifecycle-diagnostic-context-integration.md)
- [Error-handling guide](../engineering/error-handling.md)
