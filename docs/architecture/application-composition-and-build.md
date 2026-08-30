# Application Composition and Build

> **Phase 11F current boundary:** Experimental `kongono/application-graph-artifact`
> now provides a bounded neutral structural inspection, and the supported
> `kongo application-graph:inspect` command consumes one explicit validated
> artifact without disclosing individual facts. Phase 11G remains unstarted;
> no registry, AI access, runtime integration, authorization, or production-
> readiness claim exists.


## Purpose and status

This guide explains the Accepted Phase 5 architecture for a supported
application composition root and deterministic build boundary. Phase 5A is
documentation only. It implements no composition root, build artifact,
generated wiring, application template, command, package, or runtime
integration.

## Current implemented framework

Phase 4 is complete at checkpoint
`358e412929d6bb7316195d3a1748c346a2de0e1b`. The repository contains eighteen
acyclic production packages. Current runtime components include the
transport-neutral Kernel, Application Graph structural snapshot, explicit
typed runtime dependency resolver, HTTP messages, routing, PSR-15 middleware,
Web Application, Web Runtime, and one PHP-CGI delivery profile. Knowledge,
Chie, Hantei, and Oshie are optional Experimental developer tools.

The existing resolver consumes explicit runtime values, validates their
complete dependency graph, and constructs transient instances on every edge.

Phase 11E does not change this workflow. Its separate Graph artifact package
accepts only an already-built authoritative Graph and neither reads Composition
source nor attests to source-code bytes. Exact currentness remains relative to
the Graph explicitly supplied by the caller.
The Application Graph remains unchanged and unintegrated as its current
structural snapshot. Neither is integrated with a build artifact or generated
wiring.

## Current caller-owned composition

Application construction is currently caller-owned. Consumers construct and
pair the Kernel, routes, middleware, handlers, Web Application, Web Runtime,
and PHP-CGI entry lifecycle themselves. The Phase 2 runnable fixture proves one
test composition, but it is not a supported application template. Dependency
definitions are runtime values, and no supported application build/check/run
workflow exists.

## Accepted Phase 5 architecture

Kongono will add one explicit supported application composition root. It will
own construction, startup order, retained application-lifetime objects,
operation-lifetime factories, shutdown order, and partial-boot cleanup. It
must not pass a resolver or container into application services.

Kernel remains lifecycle-neutral. It will not acquire configuration, discovery,
routing, transport, dependency resolution, generated code, or application
definition responsibilities.

The preferred production mechanism is generated ordinary PHP with explicit
constructor calls and dependency edges. Build inputs remain authoritative;
generated output is inspectable derived evidence tied to exact input state.

## Implemented build flow

```text
reviewed explicit declarations
        |
        v
bounded input and provenance validation
        |
        v
dependency, root, lifetime, and cleanup checks
        |
        v
deterministic generated ordinary PHP plus versioned identity
        |
        v
explicit content-addressed publication of complete artifacts and wiring
```

Declaration validation, dependency validation, and generation occur only in an
explicit build/check step. Route assembly is not implemented. Equal validated
inputs must produce deterministic bytes. Output contains no nondeterministic
timestamp or ambient environment discovery and requires no network.

Missing, stale, mixed-version, partial, unsupported, ambiguous, or
unverifiable output fails closed. Publication is atomic; a previous complete
artifact cannot be silently mutated into a partial one. Artifacts are safe to
discard and rebuild.

Phase 5C selects the singular JSON source; Phase 5D implements its artifact;
Phase 5E implements generated wiring. Phase 5F implements one explicit exact-
current loader and exact web-profile startup adapter. No command or general
loader selection mechanism exists. Later frontends may exist only if they
compile into the same explicit composition model under separate authority.

## Planned runtime flow

```text
production startup
        |
        v
verify one complete current build identity
        |
        v
load generated explicit constructor wiring
        |
        v
composition root starts application-lifetime objects
        |
        +--> operation factory creates isolated operation-lifetime objects
        |        |
        |        +--> existing Web Application / Web Runtime / PHP-CGI flow
        |
        v
reverse-order shutdown and explicit cleanup
```

Production startup consumes a complete current artifact before accepting work.
Request handling never scans source, reflects constructors, discovers
attributes, autowires, rebuilds, downloads, auto-fixes, or mutates a hidden
cache.

## Current resolver versus generated wiring

| Concern | Existing resolver | Phase 5E generated boundary |
|---|---|---|
| Input | Explicit runtime `ServiceDefinition` values | One canonical version-1 JSON source and exact artifact |
| Validation | Complete runtime definition-graph validation | Explicit current artifact and generated-byte validation |
| Construction | Recursive transient construction on every edge | Generated ordinary PHP constructor calls |
| Lifetimes | Transient per edge only | Application, operation, and transient behavior in isolated generated assemblies |
| Runtime lookup | Root resolution through the Experimental resolver | No runtime service location in application services |
| Graph relationship | None | None; Application Graph integration remains absent |

The current resolver is not deprecated or changed by Phase 5E. The separate
generated boundary is implemented and proved as build/lifecycle evidence, but
it is not yet loaded or integrated into the production runtime path.

## Lifetime concepts

These lifetimes are implemented by generated assemblies but are not integrated
with the production runtime:

- **Application lifetime:** an object retained by one composed application from
  successful startup until explicit shutdown.
- **Operation lifetime:** an object created for and confined to one request,
  job, command, or other operation, including overlapping or reentrant work.
- **Transient lifetime:** a fresh object for each explicit construction edge or
  factory request.

Phase 5E defines construction ownership, sharing, operation-factory behavior,
cleanup, reverse-order release, failure composition, and leases. Generated
wiring must never retain request,
response, principal, tenant, diagnostic failure, route match, scope token, or
operation data in application-shared state.

## Failure, cleanup, and invalidation

The Phase 5D/5E composition boundary specifies deterministic outcomes for:

- invalid or conflicting declarations;
- missing, stale, partial, mixed-version, or tampered artifacts;
- constructor or factory failure;
- partial boot and reverse-order cleanup;
- operation work and cleanup failing together;
- application shutdown failure; and
- uncertainty that requires external runtime-profile retirement.

Cleanup is explicit and never delegated to process teardown. A primary failure
must not be silently replaced, and cleanup failure must not be discarded. A
failed build or startup cannot fall back to request-time discovery.

## Security and provenance

Composition declarations are executable trust-boundary material. Later phases
must define exact source authority, path containment, encoding and bounds,
symbol forms, symlink/reparse/alternate-stream handling, duplicate and conflict
selection, diagnostics, disclosure, content identity, and changed-during-read
behavior before input is admitted.

Generated source contains no secrets, credentials, environment values, request
data, raw rejected input, or Protected diagnostic values. It cannot grant
authorization, capabilities, tool access, Git, network, deployment, provider,
or production authority. Source text is inert until an accepted deterministic
frontend validates and compiles it.

Build diagnostics must identify the exact declaration category, violated
invariant, bounded Safe context, and remediation. An explanation of why a root,
service, or route exists must be inspectable without becoming a runtime
container-introspection service or disclosing secret values.

## Prohibited mechanisms and scope

The supported path prohibits ambient/global/static/thread-local/fiber-local
containers, resolver injection, string or class-name service lookup, mutable
process-wide registries, request-time reflection or scanning, hidden autowiring,
property-hook dependency resolution, runtime `eval`, remote generated code, and
implicit lazy lifecycle behavior.

Property hooks may serve local class invariants or value behavior when
appropriate; they are not a dependency engine. Phase 5A adds no database, ORM,
templates, events, queues, extensions, application agents, telemetry, frontend,
deployment, authentication, authorization, or other capability family.

## Application Graph separation

Phase 5A defines no Graph schema, stable ID, serialization, provenance field,
service or route kind, or compilation integration. The current structural Graph
is unchanged. A future build artifact may become a source for a separately
Accepted authoritative Graph, but no such relationship is selected or
implemented here.

## Accepted Phase 5C source contract

Phase 5B established `>=8.5.0 <8.6.0`; the root and all nineteen current
packages use that constraint. Phase 5C accepts exactly one inert application-owned source
at `<application-root>/kongono.composition.json`. Its root has exactly
`schema`, `version`, `inputs`, `services`, `bindings`, and `roots`; schema is
`"kongono.composition"` and version is integer `1`.

The contract fixes canonical symbols, typed host inputs, concrete services,
single-hop bindings, application/operation roots, ordered constructor
arguments, application/operation/transient lifetimes, reverse cleanup,
deterministic failure precedence, bounded safe reading, canonicalization, and
provenance. See the [complete source guide](composition-source.md) and
[declaration guide](../engineering/composition-declarations.md).

Phase 5D implements this source-validation and artifact slice in the
Experimental `kongono/composition` package. The public PHP API builds,
structurally validates, checks exact currentness, and explicitly publishes one
content-addressed artifact. Application composition remains caller-owned; the
current DI resolver, Application Graph, Kernel, routes, Web Application, Web
Runtime, and PHP-CGI path remain unchanged. Phase 5E implements the separate
canonical generated-PHP and isolated lifecycle-support slice in that package.
It admits only `service`, typed `input`, and `default` arguments; applications
use explicit typed aggregate services for plural collaboration. No schema file
or framework CLI exists. Phase 5F adds only the exact-current loader and
existing web lifecycle integration described by ADR 0074. Phase 5G adds the
copy-once minimal skeleton and reusable build/check workflow. Phase 5H adds
only the root-owned 28-scenario external acceptance harness and exact
checkpoint; it does not add production behavior.

## Related material

- [ADR 0068](../adr/0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](../adr/0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0070](../adr/0070-versioned-composition-source-and-provenance.md)
- [ADR 0071](../adr/0071-roots-bindings-lifetimes-and-cleanup.md)
- [ADR 0074](../adr/0074-generated-assembly-web-runtime-integration.md)
- [Generated runtime integration](generated-runtime-integration.md)
- [ADR 0073](../adr/0073-generated-typed-wiring-and-lifecycle-boundary.md)
- [Generated composition wiring](generated-composition-wiring.md)
- [ADR 0072](../adr/0072-deterministic-composition-artifact-and-publication.md)
- [Composition build artifact](composition-build-artifact.md)
- [Composition source architecture](composition-source.md)
- [Composition declaration engineering guide](../engineering/composition-declarations.md)
- [Phase 5 roadmap](phase-5-roadmap.md)
- [Generated wiring engineering guide](../engineering/generated-wiring.md)
- [Explicit typed dependency resolution](../engineering/dependency-injection.md)
- [ADR 0075](../adr/0075-minimal-application-skeleton-and-workflow.md)
- [Minimal application workflow](minimal-application-workflow.md)
- [Application workflow engineering guide](../engineering/application-workflow.md)
- [ADR 0076](../adr/0076-phase-5-external-application-acceptance.md)
- [Phase 5 external acceptance](phase-5-external-acceptance.md)

## Phase 8G supported application build edge

Phase 8G exposes the existing Composition build capabilities through exactly
`kongo application:build`. Planning resolves and validates the application
source, builds the artifact and generated wiring in memory, computes their
content-addressed basenames and SHA-256 identities, and assesses exact-current
output without filesystem mutation. Both outputs must be absent or both must
be exact current; partial, tampered, linked, outside, or changed targets fail
closed.

Apply authority is explicit. `--apply` replans, invokes the existing atomic,
contained, initial-only, no-clobber publishers for exactly those two files, and
then replans to prove the predicted identities current. Existing current output
is idempotent and prior content-addressed output is never deleted or selected
after the source changes. `--plan` never creates, deletes, renames, touches, or
rewrites any file or metadata.

The application has no prompt, and `--no-interaction` is automation-safe. It
adds no schema change, runtime loader, service locator, daemon, watcher,
installer, deployment manager, or second command. See
[ADR 0103](../adr/0103-supported-kongo-edge-and-honest-application-build.md).

## Phase 9E operation-input evolution

Composition version 2 requires every input to declare `application` or
`operation` lifetime. Version-1 inputs remain application-lifetime compatible.
Generated startup retains only application inputs; bounded positional operation
inputs are validated before lease or construction and pass directly through
ordinary constructor calls. Application services and roots cannot reach an
operation input.

The outer `kongono/security-runtime` profile uses this boundary for one exact
operation-scoped `SecurityContext`. Generated application state never retains
that context, and authentication success grants no authorization. See
[ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md)
and the [operation-scoped Security Context guide](operation-scoped-security-context.md).

Phase 8H externally verifies the same boundary through the direct source and
Composer proxy. Plan writes nothing; apply publishes exactly the predicted
pair; repeated apply is idempotent; and a conflicting target exits 3 while
preserving no-clobber behavior. See [ADR 0104](../adr/0104-phase-8-external-cli-acceptance-and-checkpoint.md)
and the [Phase 8 external acceptance guide](phase-8-external-acceptance.md).

## Observability workflow relationship

Phase 10G observability build evidence is separate from Composition evidence.
The application host may construct the explicit observability factory from one
exact `CurrentObservabilityConfiguration` and caller-owned runtime
collaborators, but Composition does not discover, infer, or globally register
them. See the [observability application workflow](observability-application-workflow.md)
and [ADR 0121](../adr/0121-deterministic-observability-configuration-and-supported-application-workflow.md).
