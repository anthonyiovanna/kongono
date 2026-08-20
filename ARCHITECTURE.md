# Kongono Architecture

## Status and scope

This document is an index of Kongono's authorized architectural direction.
Kongono is pre-alpha. Phase 1C adds the exact Diagnostic Activation package and
typed trusted-bootstrap permission value. Phase 1D adds the neutral typed
adapter-controlled delivery permission for one current delivery, still without
configuration, evidence evaluation, combined enforcement, or an adapter.
Phase 1H implements the transport-neutral trusted diagnostic handler as the
eighth leaf package and its exact Experimental eligibility-first sequencing
contract, without adding an adapter, transport, delivery, or production model.
Phase 1 is complete at local checkpoint
`d7846f137a1d53f5306f48d19196b916fde44ac2`. Phase 2A records the bounded
Phase 2A-H first-runnable-web milestone and its black-box acceptance boundary.
Phase 2B accepts PSR-7 through `psr/http-message` 2.x, the selected server-side
PSR-17 factories, and PSR-15 as narrow interoperability contracts. Phase 2C
implements the five selected message roles and five stateless factories in the
first-party `kongono/http-message` boundary. Phase 2D adds the separate
`kongono/routing` package for bounded, byte-exact method/path declaration and
deterministic matching. Phase 2E adds `kongono/http-server`, installs and uses
the PSR-15 handler and middleware interfaces, and implements an explicit,
bounded, immutable, transport-free middleware pipeline plus terminal route
mapping. A selected route match is stored under the reserved
`RouteMatch::class` request attribute but remains untrusted and establishes no
authentication or authorization. Unmatched output is delegated to an explicit
handler. Phase 2F adds `kongono/web-application`, an explicit transport-free
composition boundary that validates Routing and HTTP Server inputs before
booting one Kernel and owns exact-once Kernel scope closure for each application
call. Phase 2G adds `kongono/web-runtime`, the outer delivery port and adapter,
fixed generic or eligible trusted failure responses, and exact send/close
orchestration. No concrete SAPI/server/listener/emitter or runnable app has been
added. Kongono is now a thirteen-package tested component architecture, not yet
a runnable web framework.
Phase 0X adds the exact Diagnostic Presentation leaf and
pure trusted-developer projection above Phase 0U, which adds the exact acyclic DI-to-Diagnostics
edge and integrates all 25 Dependency Injection diagnostics across 26 throw
sites. Phase 0T integrates all six Kernel lifecycle failures, and Phase 0Q
integrates all 11 Application Graph failures, with exact package-owned bounded
diagnostic context on the presentation-neutral
`kongono/diagnostics` value foundation: bounded immutable schemas and values,
deterministic byte-safe text representation, structural closed-schema capture,
and package-local construction failures. All 42 original failures now own exact
bounded context as 6 Kernel, 11 Graph, and 25 DI schemas. Diagnostics
construction failures remain context-free. Current production behavior otherwise remains the
Phase 0J implementation: the PHP quality toolchain, the narrow
`kongono/kernel` lifecycle package, the immutable structural core with exact
direct-relation and whole-snapshot kind selectors in
`kongono/application-graph`, and explicit typed constructor resolution in
`kongono/dependency-injection`. Other statements about framework behavior
describe planned architecture unless explicitly identified as current
repository behavior.

## Package dependency topology

The current runtime has thirteen packages. `kongono/api-metadata` requires
PHP only and owns `Kongono\ApiMetadata\Api` and `ApiStatus`. Kernel,
Diagnostics, and Dependency Injection each require API Metadata; Kernel,
Application Graph, Dependency Injection, HTTP Message, Routing, and HTTP Server
also require Diagnostics. Routing consumes only the PSR-7 message interfaces
and does not depend on the HTTP Message package. HTTP Server depends on API
Metadata, Diagnostics, Routing, and the two PSR-15 interface packages; it does
not depend on the HTTP Message package. Web Application is a leaf above API
Metadata, Diagnostics, HTTP Server, Kernel, Routing, and the PSR interfaces; it
does not depend on the concrete HTTP Message package. Web Runtime is the top-level
leaf above Web Application and the trusted-diagnostic foundations. The root
consumes all thirteen. This
graph is acyclic, and no package depends on Kernel merely for compatibility
metadata.

Phase 1C implements `kongono/diagnostic-activation` as an acyclic package that
depends only on API Metadata and PHP. Phase 1D gives it exactly two Experimental
unbacked permission enums:
`Kongono\DiagnosticActivation\TrustedDeveloperBootstrapPermission` and
`Kongono\DiagnosticActivation\AdapterControlledDeliveryPermission`. Each has
ordered `Denied`, `Allowed` cases and `allowsTrustedPresentation(): bool`. The
first represents an already-validated decision fixed for a future adapter
instance; the second represents the future adapter's already-determined
permission for one current delivery. Only Diagnostic Handler depends on
Diagnostic Activation, and the framework root consumes all eleven packages.

Phase 0X implements `kongono/diagnostic-presentation` as a leaf above this
graph. It depends directly on API Metadata, Diagnostics, Kernel, Application
Graph, and Dependency Injection. Only Diagnostic Handler depends on it, and it
creates no binding, service registration, Graph node, ambient discovery, or
reverse edge. Diagnostic Presentation does not depend on Diagnostic Activation;
the framework root consumes both independently.

Phase 1H implements `kongono/diagnostic-handler` as the eighth leaf package under
`Kongono\DiagnosticHandler\`. It requires PHP 8.5 or later and depends
directly only on API Metadata, Diagnostic Activation, Diagnostic Presentation,
Kernel, Application Graph, and Dependency Injection. The three originating
packages are direct dependencies because their marker interfaces form the
handler's accepted source union; it has no direct dependency on Diagnostics.
No package depends on Diagnostic Handler. The root consumes it, and Handler is
the only current package depending on both Diagnostic Activation and Diagnostic
Presentation. The implemented topology remains acyclic.

The former `Kongono\Kernel\Api\*` identities are unavailable and no alias or
forwarder exists. Graph and Kernel retain their diagnostic integrations exactly.
DI now independently requires Diagnostics for its 25 schemas. Phase 0U adds no
renderer, error page, transport, or other runtime capability.

Phase 2C implements the ninth package identity `kongono/http-message` at
`packages/http-message` under `Kongono\Http\Message\`. That low-level package
depends only on PHP, API Metadata, Diagnostics, `psr/http-message: ^2.0`, and
`psr/http-factory: ^1.1`.
The Kernel remains transport-neutral and PSR-free, and no third-party HTTP
message implementation or framework substrate owns Kongono's implementation.

Phase 2D implements `kongono/routing` at `packages/routing` under
`Kongono\Routing\`. Its dependencies are limited to PHP, API Metadata,
Diagnostics, and `psr/http-message`. Immutable route definitions and tables are
validated within strict byte/count bounds; matching reads only method and URI
path, compares exact encoded bytes, and selects by literal/parameter specificity
independent of declaration order. It performs no handler, middleware, DI, Graph,
Kernel, authorization, or I/O work and retains no request-specific state.

Phase 2E implements `kongono/http-server` at `packages/http-server` under
`Kongono\HttpServer\`. Its immutable pipeline compiles no more than 128 explicit
PSR-15 middleware layers around one terminal handler. Its routing handler
validates one complete canonical route-name-to-handler map, places a selected
untrusted `RouteMatch` under the reserved `RouteMatch::class` attribute, and
delegates unmatched requests without choosing status or body. It catches no
`Throwable`, supplies no error middleware, and owns no bootstrap, Kernel scope,
adapter, transport, emission, or ambient request state. Phase 2F implements the
separate Web Application composition and application-call scope boundary;
Phase 2G and ADR 0049 implement the outer delivery adapter without changing Web
Application's sole Kernel-scope ownership. Phase 2H remains the first runnable
black-box milestone.

Kongono Framework targets PHP 8.5+ and is designed for three first-class consumption surfaces: Human Experience, Software/API Experience, and Agent Experience. The framework is not a Laravel clone. It aims to preserve strong developer productivity while favoring explicit, typed, deterministic, observable, modular, persistent-runtime-safe, and AI-native architecture.

## Foundational model

The planned Application Graph is the authoritative, machine-readable representation of an application. It will derive from a language-neutral Intermediate Representation so that PHP, the first source-language frontend, does not permanently define the conceptual model. Phase 0J retains the source-language-neutral in-memory structural snapshot introduced in Phase 0D, the exact outgoing and incoming direct-relation selectors from Phase 0H, and adds only exact whole-snapshot node-kind and relation-kind selection. `nodesOfKind(NodeKind): array` and `relationsOfKind(RelationKind): array` compare kind values exactly within separate domains, return `[]` for absent kinds, and preserve canonical order and stored-object identity in fresh lists. Node selection scans all nodes in `O(N)` time, relation selection scans all relations in `O(R)` time, result allocation is `O(k)`, and no kind index or query cache is retained. Open kind labels define no built-in vocabulary or schema. This is not combined endpoint-and-kind filtering, multiple-kind selection, predicates, traversal, reachability, neighbor selection, or a generic query system. Stable identifiers, domain schemas, metadata, provenance, visibility, serialization, discovery, compilation mechanics, persistence, mutation, framework integration, optimization promises, and the complete authoritative representation remain unresolved.

Kongono uses `packages/kernel` as its first physical monorepo package. Its Experimental state machine boots once, owns overlapping opaque operation scopes by object identity, rejects shutdown while work is active, and shuts down terminally. It is transport-, runtime-, and container-neutral. Independently installable capabilities and application architecture beyond this boundary remain planned.

`packages/dependency-injection` is a separate Experimental component. It maps canonical existing interfaces to explicit concrete implementations and ordered constructor dependencies, validates complete definition sets, and constructs transient instances on every dependency edge. It does not make the Kernel a container, provide PSR-11 or autowiring, select shared/scoped lifetimes, or integrate with the Application Graph.

The lifecycle is persistent-runtime-first: framework design may not assume all application state disappears after each request or operation. Runtime-specific integrations must remain adapters rather than kernel dependencies.

Phase 0M realizes the 42 package-owned diagnostic assignments through three
Internal backed-enum catalogs. The three separate Public package exception
markers expose `diagnosticId()`, `diagnosticTitle()`, and
`diagnosticRemediation()` as strings; no universal catch interface exists.
Existing messages remain unchanged, protected detail, and native causes retain
their identity. No structured context, renderer, development error page,
production error surface, HTTP or CLI mapping, logging, telemetry, retry,
cleanup orchestration, incident system, or localization exists. See the [error-handling engineering
guide](docs/engineering/error-handling.md) and the [ADR
index](docs/adr/README.md).

The presentation-neutral `kongono/diagnostics` package now owns Public
`DiagnosticContext`, `DiagnosticField`, their closed schema types, and the exact
Safe/Protected and text/integer/boolean vocabularies. It validates structural
schemas and creates eager, ordered, detached snapshots with at most 16 fields.
Text is represented deterministically as bounded ASCII tokens with an exact
512-byte ceiling and visible `...[truncated]` marker. Construction uses no ambient
state or I/O and retains no global per-failure values. Diagnostics has its own
fourth package-local marker and 11 construction diagnostic IDs.

Originating packages retain identifier meaning, exact catalog schemas,
throw-site values, and separate Public markers; structural schema validity does
not confer semantic ownership. Phase 0Q makes Graph the first semantic consumer:
its Internal catalog owns all 11 schemas, its throw sites supply only approved
values, and its Public marker adds `diagnosticContext()`. Graph messages and
catch ownership remain local. Phase 0T makes Kernel the second semantic consumer
for its six lifecycle failures. Phase 0U makes DI the third semantic consumer
for 25 schemas across 26 throw sites. Kernel, Graph, and DI markers independently
expose context; Diagnostics construction failures expose no recursive context.
Forbidden-material detection, redaction, activation enforcement, production
allowlists, transport, serialization, source and documentation lookup, logging,
telemetry, and incidents remain future decisions.

The implemented Presentation boundary provides a pure Experimental trusted-
developer projection over exactly the Kernel, Graph, or DI contextual marker.
It derives the exact owner `kernel`, `application_graph`, or
`dependency_injection` and copies only identifier, title, operation, invariant,
remediation, and canonically ordered bounded fields with their unchanged kind,
Safe/Protected disclosure, and truncation state. Multi-marker sources are
rejected before accessor use; arbitrary `Throwable`, the Diagnostics marker,
messages, causes, traces, source, requests, environment, transports, and
metadata bags are excluded. The projection retains no source object, cache, or
cross-operation state and performs no I/O.

Phase 1A accepts the fail-closed activation policy outside Diagnostic
Presentation. A future outer composition boundary must hold an explicit
immutable trusted bootstrap permission for its adapter instance and the adapter
must independently authorize the current delivery. Both must be affirmative
before presentation access, projection, rendering, or delivery; absence,
invalidity, ambiguity, evaluation failure, denial, and uncertainty prohibit the
trusted path. Client input, raw environment labels, heuristics, AI output, and
ambient or previous-operation state cannot enable either permission. The
trusted developer model cannot be reused and redacted for production;
production requires a separate deny-by-default model. Projection and rendering
remain separate. Phase 0Z implements the Experimental
`Kongono\DiagnosticPresentation\Developer\Html\TrustedDeveloperHtmlRenderer`
in the existing Diagnostic Presentation leaf. It consumes only the trusted
projection and returns one complete escaped HTML5 document without adding a
package edge, runtime binding, Graph participation, or reverse dependency. It
is stateless and fixed by two golden fixtures.

The accepted conceptual composition is:

```text
future outer adapter establishes both typed permissions
    -> TrustedDeveloperPresentationEligibility
        -> Diagnostic Handler checks eligibility
            false -> null to the future adapter's still-unselected non-trusted path
            true -> projector -> trusted HTML renderer -> exact HTML unchanged
                -> future adapter owns delivery
```

Both permissions remain outside Diagnostic Presentation. Phase 1A added no
package edge, service, runtime integration, configuration mechanism, activation
type, adapter, served page, or production surface. Phase 1C implemented only
the first typed input in the separate Diagnostic Activation package. Phase 1D
implements only the second neutral typed input. Both values now exist, but no
configuration reader, delivery-evidence evaluation, combined enforcement,
adapter, transport, or production surface exists. `Allowed` on either enum is
insufficient by itself, and direct case selection or presentation calls do not
prove authority.

Phase 1E implements only the pure combined calculation as the final readonly
Experimental `TrustedDeveloperPresentationEligibility` in Diagnostic Activation.
It stores both typed decisions for one current delivery and returns true only
when both are `Allowed`. Direct construction or a true result does not prove
authority. Evidence evaluation, presentation access, and enforcement remain
future outer-adapter responsibilities; the package graph is unchanged.

Phase 1H implements the Experimental final readonly
`Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter` as the
transport-neutral consumer of that eligibility value. Its sole method,
`renderIfEligible(TrustedDeveloperPresentationEligibility,
KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface):
?string`, checks eligibility before any source accessor. A false result returns
`null` immediately. A true result invokes the existing projector and then the
existing HTML renderer and returns the renderer's string unchanged. It catches
nothing; every `Throwable` propagates unchanged. `null` says only that trusted
developer HTML was not produced because combined eligibility was false. The
handler validates no evidence, proves no authority, chooses no transport or
non-trusted response, and retains no ambient or cross-delivery state. Direct use
of it or the lower-level presentation APIs is not authorization. Diagnostic
Activation and Diagnostic Presentation remain directly callable independent
lower layers. No adapter, configuration reader, evidence evaluator, transport,
delivery, response, or production model exists.

## Boundaries

- PHP 8.5+ is the primary application language.
- TypeScript and JavaScript belong to browser/frontend integration.
- Rust may later support high-performance framework tooling.
- Go may be selected later for specific infrastructure or runtime integrations.
- Vite is the intended default bundler integration; Kongono will own an Asset Platform abstraction, not a bundler.
- OpenTelemetry is the intended instrumentation model; exporters will remain replaceable.
- OpenAPI is intended to be generated from authoritative application metadata.
- MCP and A2A are adapters around Kongono concepts and do not define its domain model.

No runtime adapter, protocol adapter, domain Graph schema, complete Graph compiler, capability package, or framework API for those planned boundaries exists yet. The implemented structural snapshot and Kernel lifecycle must not be read as selecting any of them.

## Detailed documentation

- [Architecture overview](docs/architecture/overview.md)
- [Phase 2 roadmap](docs/architecture/phase-2-roadmap.md)
- [HTTP message model](docs/architecture/http-message-model.md)
- [Routing model](docs/architecture/routing-model.md)
- [HTTP server model](docs/architecture/http-server-model.md)
- [Web Application model](docs/architecture/web-application-model.md)
- [Application Graph](docs/architecture/application-graph.md)
- [Microkernel and capabilities](docs/architecture/microkernel.md)
- [Explicit typed dependency resolution](docs/engineering/dependency-injection.md)
- [Error handling and actionable diagnostics](docs/engineering/error-handling.md)
- [Progressive architecture](docs/architecture/progressive-architecture.md)
- [Runtime model](docs/architecture/runtime-model.md)
- [Agent model](docs/architecture/agent-model.md)
- [Extension platform](docs/architecture/extension-platform.md)
- [Architectural doctrine](docs/doctrine.md)
- [Architecture Decision Records](docs/adr/README.md)

## Phase 2 runnable fixture milestone

Phase 2A through 2H are complete. [ADR 0050](docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md)
records the test-owned runnable fixture that exercises the thirteen-package path
through the Public `WebDeliveryInterface`. One real Web Application retains the
sole Kernel operation scope; Web Runtime retains trust, response delivery, and
cleanup ownership. External black-box tests prove routing, middleware order,
generic and trusted failure selection, exact stream cleanup, and sequential
isolation.

The CLI subprocess runner is bounded test infrastructure, not a framework CLI,
production SAPI/server/listener/emitter, deployment surface, supported template,
full framework, 1.0 release, or production-ready system. Production runtime,
transport authentication, operations, and broader capabilities remain open.
