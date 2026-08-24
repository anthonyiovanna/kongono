# ADR 0073: Generated Typed Wiring and Lifecycle Boundary

- **Status:** Accepted
- **Date:** 2026-08-23
- **Decision scope:** Phase 5E generated build evidence and isolated lifecycle support
- **Stability:** Experimental

## Context

Phase 5D made `kongono/composition` the deterministic build boundary for one
application-owned version-1 composition source and its provenance-bearing
artifact. It deliberately stopped before constructor wiring and runtime
lifetime behavior. Phase 5E must turn one exact current artifact into readable,
typed PHP while preserving the build/runtime split and the three accepted
lifetimes.

ADR 0071 also described a `services` constructor-argument list. PHP cannot
represent an arbitrary heterogeneous or repeated collection as one exact
native constructor parameter type without widening to `array`/`iterable`,
depending on PHPDoc, or inventing a framework collection contract. Those
choices conflict with the accepted typed-wiring boundary.

## Decision

The existing `kongono/composition` package owns generation, structural/current
validation, content-addressed publication, and the small lifecycle-support
interfaces used by generated classes. No new package or dependency is added.

Version 1 admits exactly three constructor argument sources: one `service`, one
typed `input`, or `default: true`. This decision narrowly supersedes only ADR
0071's `services`-list argument form. All other ADR 0071 decisions remain
Accepted. Applications needing plural collaboration declare an ordinary,
application-owned typed aggregate service. Its constructor names each member
as a separate `service` edge; constructor parameter order is the complete
member order. Kongono supplies no aggregate interface or base class.

Automatic multibinding, tags, priorities, discovery, array or iterable
injection, variadic injection, PHPDoc collection typing, and container widening
remain prohibited.

## Generated build boundary

`GeneratedWiringGenerator` first validates one supplied Composition artifact as
exactly current for an explicit application root. It emits one canonical PHP
file in:

```text
KongonoGenerated\Composition\V1\A<composition-artifact-sha256>
```

The file contains exactly the final `ApplicationAssembly` and
`OperationAssembly` classes. Metadata binds the Composition artifact, semantic
model, generator implementation, namespace, and both class names. The complete
file SHA-256 is the wiring identity. Same exact inputs produce the same bytes;
a different exact Composition artifact produces a different namespace and
wiring identity even when its semantic model is equal.

The generated application has a private constructor, explicitly typed
`start()` inputs, typed application-root accessors, `beginOperation()`,
`shutdown()`, and retirement state. The generated operation receives its owner
and only its exact typed application/input dependencies, exposes typed
operation-root accessors, and owns `close()` and closed state. Direct operation
construction is not a supported workflow.

Generation uses explicit named constructor calls. Application and operation
roots are eager. Application services are singletons within one assembly and
may be shared with operations; operation services are singletons only within
one operation; transients are fresh per explicit edge. `default: true` omits
the argument so PHP applies the declared default. Bindings are resolved before
emission.

## Lifetimes and cleanup

Every successfully built owned instance with declared cleanup is registered
once immediately after construction. The active boundary offers cleanup once
in strict reverse construction order and continues after failures. Partial
construction cleans every prior owned instance. Operation cleanup never owns
or releases application instances.

Each operation owns one application lease. Shutdown with active leases fails
before cleanup and does not retire the application. Once shutdown begins the
application is retired even if cleanup fails; once close begins the operation
is closed even if cleanup fails. Later root access and new operation creation
fail with `KGN-COMPOSITION-1016`. Repeated shutdown or close is an idempotent
no-op and never retries cleanup.

An exact primary with no cleanup failure is rethrown. One cleanup-only failure
is rethrown exactly. Combined or multiple cleanup failures use
`CompositionLifecycleException`, retain Throwable identity and deterministic
reverse-cleanup observation order, and disclose no native message through the
package diagnostic.

## Validation, publication, and loading

Structural validation is bounded and non-executing. Current validation
regenerates from the exact current Composition evidence and requires byte
equality. Publication is explicit, atomic, content-addressed, idempotent for
identical bytes, and no-clobber. The caller supplies an existing safe output
directory. Generated output is not checked into this repository.

Phase 5E intentionally adds no production loader, latest pointer, cache
selector, bootstrap, command, application skeleton, or Kernel/route/runtime
integration. Tests may load already validated generated bytes only inside an
isolated temporary boundary. Phase 5F owns any explicit loading and application
integration decision.

## Security and persistent runtimes

Generated metadata and diagnostics exclude raw source, runtime input values,
secrets, credentials, absolute paths, environment values, native failure
messages, and Protected context. Runtime values pass directly through typed
parameters and are never serialized into generated bytes.

Application assemblies retain no operation assembly, root, or failure data.
Leases prevent application cleanup while an operation is active. Failure after
cleanup begins permanently closes or retires that boundary, preventing silent
reuse in persistent workers.

## Rejected alternatives

- A runtime container, resolver, service locator, registry, or opaque closure
  map would hide the typed constructor graph.
- Property-hook dependency resolution, runtime reflection/scanning,
  variable-class construction, and hidden autowiring would move build work into
  runtime.
- `array`, `iterable`, variadic, PHPDoc-only collections, automatic
  multibinding, tags, or priorities would weaken the version-1 type contract.
- Checked-in generated output would mix derived evidence with source history.
- A Phase 5E loader would prematurely select the Phase 5F integration boundary.

## Consequences and non-goals

Applications express plural collaborators with their own explicit typed
aggregate and remain responsible for its behavior. Generated classes are
inspectable derived evidence, not Stable consumer APIs. This phase does not add
a CLI, schema file, application skeleton, route assembly, deployment contract,
OPcache/preload behavior, production bootstrap, resolver migration, or
Application Graph integration.

## Related material

- [ADR 0068](0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0070](0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](0071-roots-bindings-lifetimes-and-cleanup.md)
- [ADR 0072](0072-deterministic-composition-artifact-and-publication.md)
- [Generated composition wiring](../architecture/generated-composition-wiring.md)
- [Generated wiring engineering guide](../engineering/generated-wiring.md)
