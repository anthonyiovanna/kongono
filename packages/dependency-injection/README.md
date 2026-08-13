# Kongono Dependency Injection

## Purpose and Phase 0F boundary

`kongono/dependency-injection` implements Kongono's first explicit typed
constructor-resolution boundary. It maps canonical existing PHP interface names
to explicit concrete implementations and ordered interface dependencies. It is
an Experimental, pre-alpha component, not a complete container platform.

Use it at an application composition root when the complete service graph is
known explicitly. Do not pass the resolver into arbitrary resolved services or
expose it through global or ambient state. `ResolverInterface`, its
subinterfaces, and implementation classes that implement that resolver contract
are deliberately excluded from definitions.

## Definitions and validation

`ServiceDefinition` is final, readonly, and immutable. A definition contains
exactly:

- one canonical declared interface name as its contract;
- one canonical concrete instantiable class implementing that contract;
- one ordered zero-based list of canonical interface dependency names.

Type names must use their exact declared spelling with no leading backslash,
alias, or case variation. Construction validates the supplied names and requires
the implementation constructor to match the dependency list position for
position. Parameters must be public-constructor, required, non-defaulted,
non-variadic, passed by value, non-nullable named interface types. An absent or
zero-parameter constructor requires an empty dependency list.

Reflection validates only the explicit names and signature. It does not discover
or infer dependencies. Registered autoloaders may run during validation; an
autoload failure propagates unchanged.

`DependencyResolver` validates the complete immutable definition set before it
can be used. It rejects malformed lists, duplicate contracts, missing dependency
definitions, direct or indirect cycles, and invalid requested contracts through
the package exception boundary. Definition input is processed in canonical
binary contract order so invalid graph selection is deterministic.

## Resolution and lifetime

Resolution follows the declared constructor order. Every top-level call and
every dependency-list occurrence creates a fresh object. Repeated dependencies
and diamond branches therefore receive distinct lower-level instances. The
resolver retains definitions only and never stores a resolved service instance.

This transient-per-edge rule is safe to retain in a persistent process, but it
does not claim that consumer constructors are thread-safe. Constructor side
effects and exceptions belong to the consumer implementation. A constructor
failure is propagated unchanged and construction is not retried inside the same
call.

## Exceptions and API status

`Kongono\DependencyInjection\Exception\ExceptionInterface` is the Public catch
boundary for failures deliberately raised by this package. It exposes
`diagnosticId()`, `diagnosticTitle()`, and `diagnosticRemediation()` as strings.
The abstract base is
Internal; definitions, resolver contracts and implementations, and concrete
package failures are Experimental. Public does not mean Stable or
production-ready under Kongono's pre-alpha maturity.

Native engine failures, registered-autoloader failures, and consumer-constructor
failures are not broadly wrapped. Exception text is diagnostic rather than a
Stable compatibility contract.

The Internal
`Kongono\DependencyInjection\Diagnostic\DependencyInjectionDiagnostic` catalog
owns the twenty-five exact `DI` cases. The package's 26 deliberate throw sites
emit their assigned identifiers; both post-construction invariant defenses share
`KGN-DI-1025`. The identifier is stable machine identity; title and remediation
are diagnostic prose and may improve without changing that identity. Existing
messages remain unchanged and may contain caller-supplied contract or
implementation names; those names are not presumed safe and the messages must
not be exposed directly to an untrusted production surface. See [ADR
0026](../../docs/adr/0026-actionable-diagnostics-and-error-boundaries.md), [ADR
0027](../../docs/adr/0027-package-local-runtime-diagnostic-metadata.md), and the
[error-handling engineering guide](../../docs/engineering/error-handling.md).

## Package separation and non-goals

Production source depends on `kongono/kernel` only for `Api` and `ApiStatus`
metadata. The package supplies no special or preconfigured Kernel binding and
has no boot, shutdown, operation-scope, cleanup, or other Kernel lifecycle
behavior. A consumer may explicitly define a Kernel interface as an ordinary
service contract if every general definition rule passes; that creates no
framework-owned integration.

The package has no dependency on `kongono/application-graph`, and Phase 0F adds
no Graph schema, service/dependency kinds, discovery, compilation, metadata, or
resolved-instance integration.

Phase 0F also provides no PSR-11 implementation, autowiring, attributes,
factories, callables, values, scalar injection, aliases, qualifiers, tags,
decorators, lazy services, proxies, mutable registration, shared or scoped
lifetimes, disposal, reset, context propagation, runtime adapters, compiled
containers, source scanning, logging, telemetry, I/O, or later framework
capability.

## Related documentation

- [Dependency-injection engineering guide](../../docs/engineering/dependency-injection.md)
- [ADR 0023](../../docs/adr/0023-explicit-typed-dependency-resolution.md)
- [Kernel package](../kernel/README.md)
- [Application Graph package](../application-graph/README.md)
