# Explicit Typed Dependency Resolution

## Purpose and use boundary

Phase 0F introduces a deliberately narrow dependency-injection mechanism so a
composition root can build typed object graphs without hidden discovery or
shared mutable service state. Use it when every service interface,
implementation, and constructor dependency is explicitly known. Do not use it
as a runtime registry, configuration system, service locator, general container,
or source-discovery mechanism.

`ResolverInterface` belongs at the composition root. Neither it nor a
subinterface may be a service contract or dependency, and an implementation that
also implements the resolver contract is rejected even under another interface.
Application services must not receive Kongono resolver access through an ambient
or package-provided mechanism.

## Definition contract

Each final readonly `ServiceDefinition` stores exactly a canonical existing PHP
interface, a canonical concrete instantiable implementation, and an ordered list
of canonical interface dependencies. Canonical means the exact declared fully
qualified name: no leading backslash, alias, case variation, normalization, or
rewriting.

The implementation must implement its contract. Its public constructor must have
exactly as many parameters as the dependency list, in the same order. Every
parameter must be required, non-defaulted, non-variadic, passed by value, and
typed as exactly the named non-nullable interface at that position. Untyped,
builtin, nullable, union, intersection, `mixed`, `object`, `self`, and `parent`
parameters are invalid. No constructor or a public zero-parameter constructor is
valid only with an empty dependency list.

Reflection checks supplied declarations and their exact signatures only. The
dependency list remains authoritative; no autowiring or reflection inference
occurs. Input dependencies are detached value by value before autoloading can
execute external code, so caller-held references cannot mutate published state.

## Complete graph validation

Resolver construction accepts a runtime list, copies definitions into canonical
binary `strcmp()` contract order, and validates in this sequence:

1. list shape;
2. element type;
3. canonical contract order;
4. duplicate contracts;
5. missing dependencies by owning contract and dependency position;
6. cycles by canonical root and declared dependency order.

The complete graph is valid before the resolver becomes usable. Input order does
not change the stored contract graph or which canonical invalid condition is
reported.

## Transient construction examples

Every requested root and every dependency occurrence is constructed anew:

```text
Root(Leaf, Leaf)
     |     |
     v     v
   Leaf A Leaf B       distinct instances
```

Diamond branches do not converge on shared identity:

```text
       Root
      /    \
    Left  Right
     |      |
     v      v
  Leaf A  Leaf B       distinct instances
```

No instance is memoized during a call or cached between calls. Allocation and
constructor work therefore scale with the expanded dependency-edge construction
tree, not the number of unique definitions. Repeated diamond branches multiply
work, and deep graphs use recursive resolution. This is a design warning, not a
benchmark or performance claim.

## Failure, lifecycle, and concurrency

Invalid definitions and graphs raise `InvalidDefinitionException`; an invalid,
noncanonical, non-interface, or undefined requested contract raises
`UnknownServiceException`. Both implement the package's Public marker.
Registered-autoloader, native engine, and consumer-constructor failures propagate
unchanged. A failed constructor leaves no cached or partial service and a later
call begins a fresh construction attempt.

All 25 DI diagnostics now own exact closed bounded schemas across the existing
26 deliberate throw statements. The Public marker exposes one eager immutable
`DiagnosticContext`; messages, zero codes, optional causes, selection priority,
and package-local catch ownership remain unchanged. Safe fields are bounded
counts, positions, and closed role, kind, mode, type-shape, or rejection tokens.
Protected fields are exact already-available contract and implementation names.
Neither classification authorizes disclosure.

For non-list definitions or dependencies, validation records the entry count and
stops at the first strict key mismatch. An integer mismatch becomes canonical
base-10 Protected text; a string mismatch is omitted. Invalid entries expose
only broad native-kind tokens. Constructor mode tokens use fixed
`optional+defaulted+variadic+by_reference` ordering for the predicates that are
true, and type-shape tokens use missing, union, intersection, nullable, builtin,
then defensive non-interface precedence. Context creation adds no reflection,
autoloading, traversal, sorting, instantiation, ambient state, or I/O.

The resolver is immutable and retains definitions only, so overlapping calls
share no resolved instances. It is safe to retain across persistent operations
under that narrow guarantee. Consumer constructor side effects and thread safety
remain the consumer's responsibility. The package defines no cleanup, reset,
disposal, cancellation, context propagation, or recovery hook.

The resolver performs no filesystem, network, environment, time, randomness,
process, logging, telemetry, or global-state access. Normal autoloading and
consumer constructors may perform external work outside that guarantee.

Production source uses `kongono/api-metadata` for API-status metadata and has no
Kernel source dependency. It has no special Kernel lifecycle binding or
behavior, and an explicitly consumer-chosen Kernel interface is treated only as
an ordinary contract. The Application Graph has no Phase 0F dependency, schema,
discovery, or integration relationship.

## Security and stability

Definitions are executable trust-boundary input: their names can invoke
registered autoloaders, and resolution executes consumer constructors. Supply
only reviewed definitions and implementations. API status describes
compatibility intent, not authorization or runtime access control.

The exception marker is Public; the resolver, definitions, and concrete package
failures are Experimental; the abstract exception base is Internal. Kongono is
pre-alpha and none of these types is Stable or production-certified.

PSR-11 interoperability, autowiring, service lifetimes, Graph representation,
runtime composition, and all advanced container features remain unselected or
deferred. Phase 0F rejects their implementation, not every possible future
decision.
