# Phase 5 Roadmap

## Purpose and milestone boundary

Phase 5 is the Supported Application Composition and Deterministic Build
milestone. It establishes one supported assembly boundary, an explicit
build-before-runtime contract, generated inspectable PHP wiring, lifecycle and
security rules, a minimal supported application workflow, and external
acceptance.

Phase 5 remains smaller than a full framework, Stable API, 1.0, production
readiness, deployment automation, or production-hardening certification.

## Entry state

Phase 4 is complete at checkpoint
`358e412929d6bb7316195d3a1748c346a2de0e1b`. The repository has eighteen
acyclic production packages and the accepted external Knowledge, Chie, Hantei,
and Oshie boundary. Application composition remains caller-owned, the runnable
fixture remains test-only, dependency definitions remain runtime values, and no
supported composition build, generated wiring artifact, application skeleton,
or build/check/run workflow exists.

Phase 5A accepts architecture and documentation only. It adds no package,
runtime behavior, Composer change, source schema, generated artifact, or
application template.

Phase 5B now applies `>=8.5.0 <8.6.0` to the root and all eighteen packages.
PHP 8.5 is the one active compatibility-certified minor; PHP 8.5.9 on Windows
is the environment proved by this execution, and stable eligible 8.5 patches
remain within range. PHP 8.6, PHP 9, prerelease, nightly, and automatic
future-minor support are not claimed. This is installability metadata only: it
adds no runtime semantics, package, dependency, public API, polyfill, shim,
release/LTS promise, or composition/build capability. Phase 5A remains
accepted. Phase 5B is accepted and its bounded PHP constraint is current.

## Fixed A-H sequence

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

No subphase may outrun an unaccepted prerequisite. Every subphase receives a
separate authenticated directive and architect review.

## Dependency graph

```text
Phase 4 checkpoint
       |
      5A  architecture and fixed roadmap
       |
      5B  rolling current-minor Composer/platform migration
       |
      5C  composition-source and lifecycle contract
       |
      5D  deterministic build/check artifact
       |
      5E  generated typed PHP wiring
       |
      5F  existing runtime lifecycle integration
       |
      5G  supported minimal application workflow
       |
      5H  external acceptance and checkpoint
```

The graph is an execution dependency, not parallel permission. Phase 5B has
completed the already-Accepted PHP metadata migration. No new Phase 5
production package or generated-runtime API exists. Phase 5C now accepts the
version-1 source/declaration/lifecycle contract without implementing it.

## Fixed milestone invariants

Every later Phase 5 implementation must preserve these invariants:

1. Application assembly has one explicit supported composition root; Kernel
   remains lifecycle-neutral.
2. Expensive discovery, validation, route assembly, and generation occur in an
   explicit build/check step before runtime.
3. Production startup accepts only one complete current artifact and fails
   closed on missing, stale, partial, mixed, ambiguous, or unverifiable state.
4. The preferred production wiring is readable ordinary PHP with explicit
   constructor calls and edges.
5. Application services receive neither a resolver nor a general container.
6. No ambient container, runtime service location, property-hook resolution,
   request-time reflection/scanning/autowiring, runtime `eval`, or remote
   generated code is permitted.
7. Application, operation, and transient lifetime semantics remain explicit,
   including cleanup, reverse-order release, combined failure, and concurrency.
8. Build inputs and outputs remain bounded, deterministic, versioned,
   content-identified, inspectable, and free of secrets.
9. Generated artifacts are derived evidence, never authority.
10. The current Dependency Injection package and Application Graph remain
    unchanged until separately authorized integration work.

## Acceptance themes by phase

### 5A — architecture and documentation

Accept composition-root ownership, build-before-runtime, generated-wiring,
security, provenance, lifecycle, diagnostics, and the fixed sequence. Reconcile
current versus Accepted versus planned behavior. Implement nothing.

### 5B — rolling PHP baseline

Apply the exact `>=8.5.0 <8.6.0` Composer/platform migration across the root
and all packages under separate authentication. Prove the compatibility gate.
Do not add application-build behavior or claim PHP 8.6/PHP 9 support.

### 5C — composition-source contract

Accepted 2026-08-23. Accept exact source authority, canonical symbol rules, roots, bindings,
lifetimes, cleanup, failure, bounds, encoding, security, and provenance. Select
no generated runtime before the architecture is accepted.

The selected source is exactly
`<application-root>/kongono.composition.json`, with schema
`"kongono.composition"`, integer version `1`, and exact top-level members
`schema`, `version`, `inputs`, `services`, `bindings`, and `roots`. ADRs 0070
and 0071 define the full contract. Phase 5C itself added no parser, schema file,
package, command, artifact, generated wiring, or runtime integration.

### 5D — deterministic build artifact

Implemented 2026-08-23. The nineteenth package provides one explicit
build/check boundary and one inspectable versioned
artifact with exact identities, atomic publication, no-clobber behavior,
invalidation, and visible stale/missing/tampered failure. Do not integrate it
with requests.

Raw source identity is preserved separately from canonical semantic-model
identity.

### 5E — generated typed wiring

Implemented 2026-08-23. Generate readable ordinary PHP constructor wiring and
prove explicit lifetime, factory, cleanup, failure, static-analysis, and
reproducibility behavior. Version 1 uses only `service`, `input`, and `default`
argument sources; explicit typed aggregate services represent plural
collaboration. Runtime service location, request-time reflection, production
loading, and integration remain absent.

### 5F — lifecycle integration

Implemented 2026-08-23. Exact current generated files now load through one
explicit fail-closed boundary and the exact one-root web profile connects to
the existing Kernel, routing, middleware, Web Application, Web Runtime, and
PHP-CGI ownership. No concern moves between those components and no new
transport is added. See [ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md)
and [generated runtime integration](generated-runtime-integration.md).

### 5G — supported minimal application

Implemented 2026-08-23. One copy-once minimal skeleton now uses the reusable
content-addressed Composition build/check workflow and application-owned
`app:build`, `app:check`, and bounded one-request PHP-CGI `app:run` scripts.
Copied source is application-owned; derived output has no mutable pointer,
automatic cleanup, or automatic upgrade. No listener or deployment automation
is added. See [ADR 0075](../adr/0075-minimal-application-skeleton-and-workflow.md)
and [minimal application workflow](minimal-application-workflow.md).

### 5H — external acceptance and checkpoint

Prove real application assembly, missing/stale/tampered artifacts, failure and
cleanup composition, sequential/reentrant isolation, security and disclosure,
reproducibility, documentation, and unchanged lower-layer boundaries before
the Phase 5 checkpoint.

Phase 5H implements this proof as the root-owned
`composer acceptance:phase5` 28-scenario harness. It performs offline mirrored
external installation, exact build/check/run and response validation,
fail-closed state mutations, isolation, retirement, nondisclosure, and owned
cleanup before the exact checkpoint and post-commit gates. Phase 5A-5H now
form the implemented supported pre-1.0 application-composition milestone.

## Prohibited scope

Phase 5A selects no package name, declaration syntax, schema, attribute,
configuration format, public API, command grammar, artifact file layout, Graph
integration, or runtime class. Later phases may implement only their accepted
slice.

Database, SQL, ORM, migrations, models, repositories, transactions, templates,
assets, frontend, authentication, authorization, sessions, cache, events,
queues, jobs, scheduling, mail, storage, extensions, application agents,
MCP/A2A, telemetry, logging, deployment, cloud operations, autonomous mutation,
and full-framework feature parity remain outside this milestone unless a later
separately accepted directive says otherwise.

No Phase 5 result alone establishes Stable API, 1.0, full framework,
production readiness, zero overhead, fastest performance, PHP 8.6/PHP 9
support, or superiority over Laravel or Symfony.

## Related material

- [ADR 0068](../adr/0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](../adr/0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md)
- [Application composition and build](application-composition-and-build.md)
- [Composition source architecture](composition-source.md)
- [Composition declaration engineering guide](../engineering/composition-declarations.md)
- [Generated wiring engineering guide](../engineering/generated-wiring.md)
- [Generated composition wiring](generated-composition-wiring.md)
- [ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md)
- [ADR 0075](../adr/0075-minimal-application-skeleton-and-workflow.md)
- [Minimal application workflow](minimal-application-workflow.md)
- [ADR 0076](../adr/0076-phase-5-external-application-acceptance.md)
- [Phase 5 external acceptance](phase-5-external-acceptance.md)
- [PHP support policy](../engineering/php-support-policy.md)
