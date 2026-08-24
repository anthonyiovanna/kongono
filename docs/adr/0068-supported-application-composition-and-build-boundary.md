# ADR 0068: Supported Application Composition and Build Boundary

- **Status:** Accepted
- **Date:** 2026-08-23
- **Phase:** 5A

## Context

Phase 4 is complete at checkpoint
`358e412929d6bb7316195d3a1748c346a2de0e1b`. Kongono implements eighteen
acyclic production packages, including the Kernel, explicit typed dependency
resolver, Application Graph structural snapshot, HTTP messages, routing,
PSR-15 middleware, Web Application, Web Runtime, the PHP-CGI delivery profile,
and the optional Knowledge, Chie, Hantei, and Oshie developer tools.

Application construction is still caller-owned. The runnable web fixture is
test-only, dependency definitions are runtime values, and Kongono has no
supported application skeleton, deterministic composition build, generated
wiring artifact, or application build/check workflow. Phase 5 therefore needs
one explicit supported composition boundary without moving application
assembly into the Kernel or introducing request-time discovery.

## Decision

### Supported composition root

Kongono will provide one explicit supported application composition root. It
will assemble reviewed declarations and existing first-party capabilities and
will own construction, startup ordering, retained application-lifetime objects,
operation-lifetime factories, shutdown ordering, and cleanup after partial
boot. It will not expose a resolver or container to application services.

The composition root is not the Kernel. Kernel remains lifecycle-neutral and
does not acquire configuration, discovery, routing, transport, dependency
resolution, generated-code, or application-definition responsibilities.

### Deterministic build before runtime

Discovery, declaration validation, dependency-graph validation, route assembly,
and code generation belong to an explicit build/check step, never the first
request. Production startup must consume one complete current build artifact or
fail visibly before accepting work. Missing, stale, mixed-version, partial,
unsupported, ambiguous, or unverifiable build state fails closed.

Build inputs and outputs must be inspectable, bounded, versioned,
content-identified, and deterministic for the same source state. Publication
must be atomic, and output must be safe to discard and rebuild. Generated
artifacts are derived evidence rather than authoritative source.

The build may not use runtime `eval`, runtime download, a network requirement,
hidden cache mutation, nondeterministic timestamps, ambient environment
discovery, or request-time source scanning.

### Lifecycle, failure, and persistent runtimes

Later implementation must define application, operation, and transient
lifetime behavior before exposing those names as runtime API. Construction and
cleanup ownership must be explicit, with reverse-order release where required.
Application and operation boundaries must keep request, response, principal,
tenant, diagnostic failure, route match, scope token, and operation data out of
shared generated wiring.

Overlapping, reentrant, and sequential operations must not leak state. Partial
boot, constructor failure, operation cleanup failure, shutdown failure, and
combined failure require deterministic behavior. When safe continuation cannot
be proved, runtime-profile retirement remains an external responsibility.
Generated code does not make consumer services concurrency-safe; each service
retains responsibility for its documented concurrency contract.

### Security, provenance, diagnostics, and invalidation

Composition inputs are executable trust-boundary material. Before later phases
read or execute declarations, they must define path containment, source
authority, supported symbol forms, bounds, encoding, symlink and reparse-point
handling, alternate streams, duplicate and conflict selection, diagnostic
disclosure, artifact identity, and stale-state proof.

Generated source must not contain secrets, credentials, environment values,
request data, raw rejected input, or Protected diagnostic values. It cannot
grant authorization, capability, deployment, Git, network, provider, or
production authority. Chie may later propose candidate declaration text but
cannot apply or certify it; Hantei independently verifies accepted current
bytes.

Future build/check failures must use package-owned actionable diagnostics with
an exact declaration category, violated invariant, bounded Safe context, and
remediation. Developers must be able to inspect why a root, service, or route is
present without receiving a runtime container-introspection API or secret
values. Development may use an explicit watched build in a separate tool
process, but request handling never rebuilds. Production never auto-fixes,
downloads, or silently accepts stale output.

### Phase order

The dependency order is fixed:

| Phase | Boundary |
|---|---|
| `5A` | Accept the supported application-composition milestone, generated-wiring doctrine, security/lifecycle constraints, and fixed A-H roadmap. Documentation only. |
| `5B` | Apply and prove the Accepted rolling current-minor PHP Composer/platform migration across the root and all packages. No application-build capability. |
| `5C` | Define the explicit versioned composition-source, provenance, lifetime, root, binding, and failure contract. Architecture first; no generated runtime until accepted. |
| `5D` | Implement deterministic composition build/check and one inspectable versioned build artifact with exact stale-state and publication behavior. No request-time integration. |
| `5E` | Implement generated typed PHP constructor wiring with explicit lifetime and cleanup behavior. No service locator, property-hook resolution, or request-time reflection. |
| `5F` | Integrate the generated assembly with existing Kernel, routing, middleware, Web Application, Web Runtime, and PHP-CGI lifecycle boundaries. No new transport or application feature family. |
| `5G` | Provide the first supported minimal application skeleton and deterministic build/check/run developer workflow, with exact ownership and no deployment automation. |
| `5H` | Run external application acceptance, stale/missing/failure/isolation/security proof, documentation reconciliation, and create the Phase 5 checkpoint. |

Each subphase requires a separately authenticated directive and architect
review. No subphase may outrun an unaccepted prerequisite.

## Consequences

Kongono has Accepted architecture for a supported application assembly and
deterministic build boundary. Later production startup can become explicit and
inspectable without turning Kernel into an application builder or allowing
request-time discovery.

The architecture adds up-front build work, artifact invalidation duties,
explicit lifecycle modeling, and cleanup proof. Those costs are accepted to
keep runtime behavior deterministic and persistent-runtime-safe.

## Rejected alternatives

- Kernel-owned application construction or configuration.
- First-request discovery, validation, compilation, or generation.
- Runtime service location or a resolver passed into application services.
- Ambient or process-wide mutable containers.
- Request-time reflection, classpath or attribute scanning, hidden autowiring,
  and reflection-inferred constructor dependencies.
- Property-hook dependency resolution.
- Runtime `eval`, remote generated code, or hidden mutable build caches.
- Silent fallback to missing, stale, partial, or unverifiable build state.
- Treating generated output as authority or embedding secrets in it.

## Explicit non-goals

Phase 5A creates no package, API, source schema, declaration syntax, artifact,
generator, compiler, cache, configuration parser, application skeleton, CLI
command, runtime integration, deployment behavior, or Application Graph
integration. It changes no PHP or Composer metadata. Database, ORM, templates,
events, queues, extensions, agents, telemetry, frontend, deployment, and other
later capability families remain outside this phase.

## Related material

- [ADR 0069: Generated Typed Wiring and No Runtime Service Location](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [Application composition and build](../architecture/application-composition-and-build.md)
- [Phase 5 roadmap](../architecture/phase-5-roadmap.md)
- [Generated wiring engineering guide](../engineering/generated-wiring.md)
- [ADR 0006: Persistent-Runtime-First Architecture](0006-persistent-runtime-first.md)
- [ADR 0023: Explicit Typed Dependency Resolution Boundary](0023-explicit-typed-dependency-resolution.md)
- [ADR 0048: Web Application Bootstrap and Kernel Operation Ownership](0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [ADR 0059: Rolling Current-Minor PHP Baseline](0059-rolling-current-minor-php-baseline.md)
