# ADR 0071: Roots, Bindings, Lifetimes, and Cleanup

- **Status:** Accepted
- **Date:** 2026-08-23
- **Phase:** 5C

## Context

ADR 0070 selects one inert versioned composition source. Phase 5C must also fix
the semantic declaration and lifecycle model before Phase 5D validates it and
before Phase 5E generates typed constructor wiring. Current application
composition is caller-owned, the Experimental resolver remains transient per
edge, and no generated lifetime or cleanup runtime exists.

## Decision

### Canonical symbols and declarations

Every PHP symbol is its exact fully qualified declared name without a leading
backslash. Spelling and case must match the declaration exactly. Aliases,
imports, namespace-relative names, normalization, case repair, nicknames, and
arbitrary service IDs are invalid.

Service classes must exist, autoload, and be concrete and instantiable. Input
and binding contracts must be existing classes or interfaces. Traits, enums as
services, anonymous classes, reserved words, internal runtime-only classes, and
non-autoloadable symbols are invalid. Build-time inspection may verify only
declared symbols and constructor/cleanup signatures; it cannot discover
services or infer missing dependencies.

### Inputs and services

An input declaration has exactly `contract`, naming a canonical class or
interface. It represents a typed object supplied explicitly by the future
application host. The source contains no input value. Inputs are the only v1
route for credentials, handles, host-owned resources, or environment-derived
configuration; artifacts may record the contract but never the runtime value.

A service declaration has exactly `class`, `lifetime`, `arguments`, and
`cleanup`. `lifetime` is `"application"`, `"operation"`, or `"transient"`.
`cleanup` is `null` or an exact method name. `arguments` is authoritative,
ordered, and covers every public-constructor parameter in exact order.

Each argument has exactly `parameter` and exactly one source form:

- `service`: one canonical contract;
- `input`: one declared input contract;
- `services`: one ordered non-empty list of canonical service contracts; or
- `default: true`: an explicit choice to use a declared default.

The parameter name must match exactly. `default: true` is legal only when the
declared parameter has a default.

Version 1 constructors are public, constructor-only, passed by value,
non-variadic, and use named non-nullable class/interface parameter types.
Untyped, builtin, `mixed`, `object`, nullable, union, intersection, `self`,
`parent`, `static`, reference, variadic, scalar-literal, factory, closure,
property, setter, and magic construction are unsupported v1 forms.

### Bindings and roots

A binding has exactly `contract` and `service`, mapping one canonical class or
interface to one declared concrete service. Each contract has zero or one
binding. A declared concrete service can be referenced directly; an interface
or abstract contract requires one exact binding. Chains, aliases, contextual or
named bindings, priorities, fallbacks, wildcards, implicit concrete creation,
and automatic multi-binding are prohibited.

`roots` is non-empty. Each root has exactly `kind` and `contract`; kind is
`"application"` or `"operation"`. Application roots must resolve to
application-lifetime services and operation roots to operation-lifetime
services. Transient or duplicate roots, arbitrary root names, and general
runtime lookup are prohibited. Future generated code exposes typed root entry
points only, never a general `get(string)` or resolver.

### Lifetime reachability and transient ownership

Application means one instance per successfully composed application, shared
across operations, and cleaned during shutdown. Operation means at most one
instance per declared service in one operation, never shared across operations,
and cleaned when that operation ends. Transient means one fresh instance for
every explicit construction edge, owned and cleaned by the application or
operation boundary that requested that edge.

An application-reachable graph may contain application and transient services,
but cannot reach an operation service. An operation-reachable graph may contain
all three lifetimes. A transient inherits its active owning boundary for
reachability and cleanup validation. Every cycle is invalid; v1 has no lazy
proxy or cycle breaker.

Application objects remain responsible for documented concurrency safety and
must not retain request, response, principal, tenant, route match, diagnostic
failure, Kernel scope token, operation input, or other operation-specific state.

### Cleanup and deterministic failure composition

When present, `cleanup` names an exact public, non-static, zero-argument
instance method declared to return `void`. Destructors, magic methods,
ambiguous inherited names, arguments, non-void returns, and inferred cleanup
are invalid.

Every successfully constructed cleanup-owning instance is registered exactly
once with its active boundary. Cleanup occurs at most once in strict reverse
successful-construction order. Construction failure cleans all already-built
owned instances before escaping. Operation cleanup never releases application
instances.

Failure precedence is exact:

1. a construction, operation-work, or shutdown-work failure remains primary;
2. every remaining owned instance is still offered cleanup in reverse order;
3. cleanup failures are retained in deterministic cleanup order and do not
   replace or erase a primary failure;
4. without another primary, the first cleanup failure becomes primary and later
   cleanup failures remain retained; and
5. uncertain cleanup remains visible to the external runtime profile, which
   decides retirement; generated wiring neither conceals nor retries it.

Phase 5C selects no exception, diagnostic ID, failure aggregation type, or
runtime-profile API. Those decisions belong to the future implementing package.

## Security and persistent-runtime implications

Typed inputs prevent values and secrets from entering the source or artifact.
Exact reachability prevents operation state from being captured by shared
application objects. Reverse cleanup and visible failure composition prevent
silent reuse after uncertain teardown. Declarations grant no authorization or
capability beyond the future caller's explicit typed objects.

## Consequences

Version 1 is intentionally narrow. Constructors and lifetimes are statically
reviewable, roots are explicit, bindings cannot hide chains, and cleanup has one
ownership order. More expressive dependency mechanisms require a later schema
version and separately accepted architecture.

## Rejected alternatives

General service locators, ambient containers, hidden autowiring, runtime
reflection, factories, aliases, decorators, priorities, conditional bindings,
lazy proxies, cycle breakers, property/setter injection, and inferred cleanup
are rejected from v1 because they hide edges, authority, ownership, or failure.

## Explicit non-goals

This ADR creates no runtime lifetime API, resolver integration, package,
factory, root accessor, cleanup interface, exception, diagnostic ID, parser,
schema file, generated wiring, Application Graph integration, route assembly,
or runtime behavior. Phase 5D has not begun.

## Related material

- [ADR 0068](0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0070](0070-versioned-composition-source-and-provenance.md)
- [Composition source architecture](../architecture/composition-source.md)
- [Composition declaration engineering guide](../engineering/composition-declarations.md)
- [Dependency Injection guide](../engineering/dependency-injection.md)
