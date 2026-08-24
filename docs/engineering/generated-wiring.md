# Generated Wiring Engineering Guide

## Purpose and status

This guide gives contributors concrete rules for the Experimental Phase 5E
generated application wiring. The `kongono/composition` generator, artifact,
validator, publisher, isolated lifetime interfaces, and Phase 5F exact-current
loader are implemented. The loader is explicit and one-use; it is not a command,
application skeleton, container, service locator, or general runtime system.

## Explicit constructor dependencies

Composition inputs state every constructor dependency explicitly.
Constructor injection is the default. A dependency declaration identifies a
reviewed contract, binding, lifetime intent, and applicable root without
reflection inference.

Use exact canonical declared symbol spelling. Do not accept leading-slash
variants, aliases, case correction, fallback binding, class-name guessing, or
silent normalization. A frontend may use a later accepted syntax only if it
compiles deterministically into one canonical explicit composition input.

## Generated ordinary PHP

Generated wiring is readable ordinary PHP containing explicit constructor
calls and dependency edges. A reviewer and static-analysis tool must be able to
follow the construction graph without executing a hidden registry or decoding
an opaque closure format.

Generated output must:

- have deterministic UTF-8/LF bytes for the same validated input state;
- contain no nondeterministic timestamp, random identifier, host path, ambient
  environment discovery, or network-derived value;
- remain compatible with PHP parsing, configured style checks, maximum-level
  PHPStan, and OPcache/preload where supported;
- avoid `eval`, remote code, runtime download, hidden cache mutation, and
  request-time source scanning; and
- contain no secret, credential, environment value, request data, raw rejected
  input, or Protected diagnostic value.

Generated output is derived evidence, not authoritative source. It must carry
or be accompanied by exact source and output identity sufficient to prove that
the complete artifact is current before startup.

## Runtime behavior

The planned production runtime path may load only a complete current build artifact. It may not
discover definitions, reflect constructors, scan classes or attributes,
autowire, repair declarations, regenerate output, download content, or select a
stale fallback during a request.

Application services must not receive a general resolver or container. The
supported production path excludes global, static, ambient, thread-local, and
fiber-local containers; string or class-name service lookup; mutable
process-wide registries; and implicit lazy resolution.

PHP property hooks may enforce local class invariants or value behavior when
appropriate. They must not fetch or resolve dependencies and are not Kongono's
dependency resolution mechanism.

## Lifetime and cleanup ownership

Application lifetime, operation lifetime, and transient lifetime are implemented
inside the generated assembly. The wiring identifies construction ownership,
sharing, operation-factory ownership, cleanup obligations, and deterministic
reverse-order release.

Application-shared generated state must never retain a request, response,
principal, tenant, diagnostic failure, route match, Kernel scope token, or
operation-specific value. Overlapping, reentrant, and sequential operations
must remain isolated. Generated code does not make consumer services
concurrency-safe; each consumer retains responsibility for its own state and
concurrency contract.

Any later accepted lazy dependency must use an explicit typed generated factory
or proxy. It must document lifetime, failure, cleanup, concurrency, and the
measured benefit. Hidden lazy container lookup is prohibited.

## Failure and diagnostics

Build/check must fail before publication for invalid symbols, missing
bindings, cycles, lifetime conflicts, invalid roots, cleanup inconsistencies,
duplicate declarations, ambiguous source authority, unsupported input, or
non-deterministic output.

Diagnostics must be package-owned and actionable. They identify the exact
declaration category, violated invariant, bounded Safe context, and remediation
without copying rejected text, secrets, Protected context, native messages, or
absolute paths. Production startup fails visibly on missing, stale, partial,
mixed-version, tampered, ambiguous, or unverifiable artifacts.

Partial construction and shutdown must preserve the primary failure, attempt
every explicitly owned cleanup in deterministic reverse order, retain cleanup
failure without replacing it silently, and expose uncertainty requiring
external runtime-profile retirement.

## Reproducible build expectations

For an exact admitted source state, Phase 5E tests prove:

1. two clean builds produce byte-identical output and identities;
2. input order cannot change canonical output or selected failure;
3. every output identity binds to the complete admitted input identity;
4. atomic publication exposes either the previous complete artifact or the new
   complete artifact, never a partial state;
5. stale, missing, mixed, changed-during-read, and tampered states fail closed;
6. generated PHP parses and passes configured style and static analysis;
7. runtime startup performs no source discovery or generation;
8. application, operation, and transient lifetime/cleanup behavior matches the
   accepted contract;
9. sequential, overlapping, and reentrant work does not leak state; and
10. generated bytes and diagnostics exclude forbidden material.

Tests must exercise real process and filesystem boundaries where those
boundaries matter. Temporary output must stay outside the repository and be
removed on success and failure. No generated artifact is checked in unless a
later explicit decision authorizes it.

## Performance claims

Generated wiring is intended to remove dynamic resolution and discovery from
the request path after a successful build. That is the only Accepted current
performance statement.

Before any further claim, benchmark the generated boundary against the
existing resolver using named runtime, source state, workload, warmup, OPcache
or preload state, sample method, memory measure, and limitations. Do not claim
zero overhead, fastest performance, universal improvement, or superiority over
another framework from design alone.

## Current Dependency Injection relationship

The current Experimental resolver remains unchanged. It validates explicit
runtime definitions and constructs transient objects per edge. It is not the
generated boundary, but its canonical symbol, explicit dependency, and
deterministic validation disciplines remain relevant evidence. Do not deprecate
or alter it as part of Phase 5E.

## Accepted Phase 5C input contract

Phase 5C accepts one inert version-1 source at
`<application-root>/kongono.composition.json` with explicit canonical inputs,
services, bindings, roots, constructor arguments, lifetimes, cleanup, bounds,
validation order, safe-read behavior, and provenance. It does not generate PHP
or select an artifact encoding, package, command, public API, or runtime
integration. Phase 5B is accepted and its bounded PHP constraint is current.

Phase 5D implements validation plus one inspectable provenance-bearing
artifact. Phase 5E implements generated typed wiring and isolated lifecycle
support. Its version-1 emitter accepts only individual `service`, typed `input`,
and `default` arguments; plural collaboration uses an application-owned typed
aggregate. Phase 5F supplies the exact-current loader, one-use startup boundary,
and exact single-root web integration. Web Application retains Kernel-operation
ownership, Web Runtime retains delivery ownership, and PHP-CGI retains entry
and shutdown ownership. Current composition remains caller-owned and DI and
Graph remain unintegrated.

## Related material

- [ADR 0069](../adr/0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0068](../adr/0068-supported-application-composition-and-build-boundary.md)
- [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Composition source architecture](../architecture/composition-source.md)
- [Composition declarations](composition-declarations.md)
- [Phase 5 roadmap](../architecture/phase-5-roadmap.md)
- [Explicit typed dependency resolution](dependency-injection.md)
- [Composition build](composition-build.md)
- [ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md)
- [ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [Generated composition wiring](../architecture/generated-composition-wiring.md)
- [ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md)
- [Generated runtime integration](../architecture/generated-runtime-integration.md)
- [ADR 0075](../adr/0075-minimal-application-skeleton-and-workflow.md)
- [Application workflow](application-workflow.md)
