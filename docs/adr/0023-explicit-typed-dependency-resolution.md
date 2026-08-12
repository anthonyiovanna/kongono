# ADR 0023: Explicit Typed Dependency Resolution Boundary

## Status

Accepted

## Date

2026-08-12

## Context

Kongono needs a first deterministic way for an application composition root to
construct typed object graphs. The mechanism must remain separate from Kernel
lifecycle, avoid hidden state in persistent processes, and not prematurely
select a general container ecosystem, Application Graph schema, or runtime
integration.

## Decision

Create `kongono/dependency-injection` as a separate production package. A service
identity is exactly the canonical declared name of an existing PHP interface. A
definition explicitly supplies a canonical concrete implementation and an
ordered list of canonical interface dependencies.

Reflection validates only the supplied names, implementation compatibility, and
exact constructor signature; it does not autowire or discover dependencies. A
constructor must be public and match the dependency list position for position
with required, non-defaulted, non-variadic, by-value, non-nullable named interface
parameters. An absent or zero-parameter constructor requires an empty list.

Resolver construction validates list shape and types, canonical-order duplicate
contracts, every missing dependency definition, and every direct or indirect
cycle before publishing immutable state. Validation is deterministic independent
of definition input order.

Resolution is transient on every edge: every top-level request, repeated
dependency occurrence, and diamond branch constructs a new instance. The
resolver retains only definitions and never retains a resolved service object.

`ResolverInterface` is composition-root-only. It and its subinterfaces cannot be
defined or injected, and any implementation satisfying
`is_a($implementation, ResolverInterface::class, true)` is rejected even under
another service contract. No global, ambient, or Kongono-provided service locator
exists.

Deliberate package failures use a separate Public exception marker, an Internal
abstract `RuntimeException` base, and Experimental final invalid-definition and
unknown-service exceptions. Native, autoloader, and consumer-constructor failures
are not broadly wrapped.

The package temporarily requires `kongono/kernel: *` only for `Api` and
`ApiStatus` metadata while the packages are unreleased and developed atomically.
The Kernel remains a separate lifecycle component with no Kongono-supplied,
special, or preconfigured binding or resolution behavior. A consumer may
explicitly choose a Kernel interface as an ordinary contract without creating a
framework-owned integration.

Phase 0F creates no dependency on or from `kongono/application-graph` and defines
no Graph service/dependency schema, metadata, discovery, compilation, or resolved
instance relationship.

## Rationale

Explicit definitions make correctness-critical application wiring inspectable
and deterministic. Canonical interface identities avoid ambiguous aliases and
class-key coupling. Complete graph validation moves structural failure to
resolver construction, while transient-only construction avoids hidden shared
service state before lifetime and cleanup semantics are designed.

Keeping dependency resolution outside the Kernel preserves the Kernel's small,
runtime-neutral lifecycle responsibility. Excluding resolver injection prevents
Kongono's first composition API from becoming an ambient service locator.

## Consequences

Composition roots must enumerate the complete definition graph. Constructor
changes require corresponding explicit definition changes. Repeated and diamond
dependencies create repeated allocation and constructor work. Consumer
constructors own their side effects, failures, and concurrency safety.

The package is Experimental except for its Public catch marker and Internal
base. Exact exception prose, future interoperability, lifetime systems, and
runtime composition are not compatibility promises.

## Rejected alternatives

- Kernel-owned resolution, a Kernel container property, and preconfigured Kernel
  bindings are rejected because lifecycle and construction have separate owners.
- PSR-11 implementation in Phase 0F is rejected; possible future interoperability
  remains unselected rather than permanently rejected.
- Reflection autowiring, source discovery, and inferred dependencies are rejected
  because explicit definitions must remain authoritative.
- Class keys, aliases, names, values, factories, callables, qualifiers, tags,
  decorators, proxies, and mutable registration are rejected for this boundary.
- Shared, singleton, scoped, or custom lifetimes and cleanup hooks are rejected
  until ownership and lifecycle contracts are explicitly selected.
- Application Graph service kinds or integration are rejected because the Graph
  domain model and compilation relationship remain open.

## What remains open

PSR-11 or other interoperability; shared and scoped lifetimes; ownership,
cleanup, reset, disposal, cancellation, and context propagation; factories and
advanced definition forms; aliases, qualifiers, tags, decorators, lazy services,
proxies, and compiled containers; configuration and scalar values; Application
Graph representation, discovery, compilation, and validation; runtime and Kernel
composition; extension contribution; independent package version constraints;
API promotion; performance budgets; and all later framework capabilities remain
open. This decision authorizes none of them.
