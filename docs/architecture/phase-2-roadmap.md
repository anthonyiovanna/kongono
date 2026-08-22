# Phase 2 Roadmap

## Purpose

Phase 2 is the bounded path from Kongono's tested Phase 1 component
architecture to its first runnable server-side web fixture application. This
roadmap makes the dependency order and finish line inspectable. Phase 2A through
2H are now complete; historical planning text below remains evidence of the
approved dependency order.

The accepted checkpoint is `647053d4bddc4c76090bca9bf3d693faa5880c52`.
Production HTTP integration is a separate planned milestone documented by the
[Phase 3 roadmap](phase-3-roadmap.md); Phase 3A adds no production runtime
behavior.

Phase 2A is architecture and documentation only. It creates no HTTP messages,
router, middleware, bootstrap, adapter, response emitter, package, dependency,
test, or runtime behavior.

Phase 2B is also architecture and documentation only. It accepts PSR-7 through
`psr/http-message` 2.x, the selected server-side PSR-17 factories, future
PSR-15, and the planned first-party `kongono/http-message` boundary. It creates
no package, dependency, implementation, test, or runtime behavior. Phases 2C
through 2H completed later under their separate authenticated authorities.

## Current starting point

Before Phase 2C, Phase 1 was complete at local checkpoint
`d7846f137a1d53f5306f48d19196b916fde44ac2`; that baseline had exactly eight
first-party packages, 53 production declarations, and 254 tests with
4,279 assertions. The current runtime includes Kernel lifecycle, the structural
Application Graph, explicit transient dependency resolution, bounded
package-owned diagnostics, typed trusted-diagnostic permissions, pure trusted
projection and HTML rendering, and transport-neutral eligibility-first
Diagnostic Handler sequencing.

Kongono is now runnable as a bounded deterministic server-side fixture. The
thirteen production packages provide messages, routing, middleware, Web
Application composition, one Kernel scope, and Web Runtime delivery/failure
ownership. The test-owned fixture proves their complete composition without a
network listener. It is not a production server or full framework.

## Fixed A-H sequence

| Phase | Planned boundary |
|---|---|
| `2A` | Accept the runnable-web milestone, security invariants, A-H roadmap, and end-to-end acceptance boundary; documentation only. |
| `2B` | Accepted HTTP message and interoperability architecture: PSR-7, selected server-side PSR-17, future PSR-15, and the first-party message boundary; documentation only. |
| `2C` | Complete: implemented only the accepted minimal HTTP message foundation and its production tests. |
| `2D` | Complete: implemented deterministic route declaration and matching in the separate tenth package. |
| `2E` | Complete: implemented the explicit bounded immutable PSR-15 middleware pipeline and terminal route-name mapping. |
| `2F` | Complete: implemented application bootstrap/composition with Kernel operation-scope ownership and fail-closed persistent-runtime rules. |
| `2G` | Complete: implemented the first outer web-runtime adapter, response delivery boundary, fail-closed trusted diagnostics integration, and generic non-trusted failure path. |
| `2H` | Complete: built the minimal test-owned fixture, external black-box acceptance, documentation reconciliation, and Phase 2 checkpoint. |

Phase 2 ends at Phase 2H unless a later architect-approved correction or ADR
explicitly changes the roadmap. Main phases do not automatically continue to
`Z`.

## Dependency sequence

Later work cannot outrun the earlier contracts on which it depends:

```text
2A milestone, invariants, and acceptance boundary
    -> 2B message and interoperability decision
        -> 2C message implementation
            -> 2D deterministic routing (implemented)
                -> 2E ordered middleware and terminal handling (implemented)
                    -> 2F composition, operation ownership, and cleanup
                        -> 2G outer adapter and safe failure delivery
                            -> 2H fixture application and black-box proof
```

At acceptance, this sequence did not select package names, namespaces,
dependency edges, exact APIs, or implementation size. Later Phase 2 authorities
made those choices within each applicable boundary.

## End-of-Phase-2 black-box acceptance

Phase 2H may call Kongono runnable only when a minimal fixture application is
exercised through a public outer entry boundary and independently proves all of
these scenarios:

1. The application bootstraps through an explicit composition root.
2. One inbound web operation becomes one explicit framework request value.
3. The Kernel owns one operation scope for that delivery.
4. Deterministic routing selects one terminal application handler.
5. Ordered middleware surrounds the terminal handler without ambient current-request state.
6. The handler returns an explicit framework response value.
7. The outer adapter converts or delivers that response without making transport a Kernel dependency.
8. An unmatched route follows a deterministic non-success path.
9. A normal application failure follows a generic non-trusted path by default.
10. Trusted developer diagnostics appear only when both typed permissions are affirmative and current-delivery evidence has been established by the outer boundary.
11. Denial occurs before diagnostic source access and never falls back to trusted output.
12. At least two sequential operations in one process prove no request, response, permission, route-match, middleware, exception, or application state leaks between operations.
13. Failure paths still close the owned Kernel operation scope exactly once.
14. Canonical tests require no external network listener and remain deterministic.
15. The entire path is covered by black-box tests from outside the internal packages.

The original acceptance boundary left outer unmatched/failure status, emitter
protocol, and adapter runtime unselected. Later Phase 2 work fixed only the
fixture's responses and ports-only delivery behavior; production host and
emission contracts remain Phase 3 decisions.

## Security gates for every subphase

- Treat every inbound component as untrusted until validated for its use.
- Define parsing, normalization, limits, duplicate handling, invalid syntax,
  and rejection behavior before implementing them.
- Prevent header and body injection, response splitting, unbounded buffering,
  confused canonicalization, and accidental secret disclosure.
- Never classify request data as diagnostic Safe merely because it is
  syntactically valid.
- Never allow query, path, header, cookie, form, body, session, token, or other
  client-controlled input to enable trusted diagnostics or upgrade either
  permission.
- Require the outer adapter to establish current-delivery evidence before
  diagnostic source access.
- Require both independent typed permissions to be affirmative; denial occurs
  before source access and never falls back to trusted output.
- Keep generic production failure output separate from the Protected trusted
  developer model.
- Never let authorization, evidence, activation, projection, rendering,
  transport, or cleanup failure recursively enable trusted output.

## Persistent-runtime gates for every subphase

- One process may handle sequential or overlapping operations.
- No core API may depend on process teardown for cleanup.
- Keep request, response, route, permission, principal, tenant, middleware,
  exception, and operation state out of ambient global, static, singleton,
  container-current, thread-local, fiber-local, or previous-operation storage.
- Give every inbound delivery explicit ownership and one Kernel operation
  scope.
- Do not retain request-specific state in long-lived objects without a later
  explicit scoped-lifetime contract that owns and clears it.
- Keep runtime integration in an outer adapter rather than making PHP-FPM,
  FrankenPHP, RoadRunner, OpenSwoole, SAPI globals, or a server a Kernel
  dependency.
- Close the owned operation scope exactly once on success and failure, preserve
  the primary failure, report cleanup failure, and retire uncertain runtime
  state.
- Prove sequential-operation isolation through deterministic black-box tests.

## Current behavior after Phase 2H

| Concern | Implemented state | Deliberately open production/general form |
|---|---|---|
| Framework packages | Exactly thirteen acyclic production packages; fixture remains root-owned test code | Any later package requires explicit authority |
| Messages | Selected PSR-7/PSR-17 foundation implemented and tested | Production SAPI parsing and arbitrary input policy |
| Routing | Bounded exact method/path matching and immutable route matches | Authorization and broader routing features |
| Middleware | Explicit declaration-ordered PSR-15 pipeline and complete terminal mapping | Supplied error middleware or ambient discovery |
| Composition | Validation-before-boot Web Application with one exact Kernel scope per call | Broader scoped resources and cancellation |
| Runtime integration | Web Runtime Public delivery port plus test-owned concrete fixture | Production listener, server, emitter, proxy/TLS policy |
| Failure delivery | Fixed generic response and two-permission trusted HTML selection | Production incident, logging, and operator policy |
| Runnable application | Deterministic CLI subprocess fixture and external black-box proof | Production-ready server, deployment, supported template, framework CLI |

ADR 0044 accepts PSR-7 through `psr/http-message: ^2.0`, the selected server-side
PSR-17 factories through `psr/http-factory: ^1.1`, and the two PSR-15 server
interface packages used by Phase 2E. Phase 2E supplies the bounded first-party
PSR-15 implementation recorded by ADR 0047. `RequestFactoryInterface`, PSR-18, and client
HTTP remain outside Phase 2.

## Deliberate exclusions

Phase 2 does not promise ORM, database access, migrations, authentication,
authorization, sessions, cookies, CSRF, templates, frontend tooling, CLI,
events, queues, cache, storage, mail, notifications, scheduling, realtime,
agents, extensions, telemetry, deployment, production hardening, ecosystem
parity, a public Stable API, a Kernel-owned network server, framework-wide
configuration, complete service-container behavior, full Application Graph
integration, release packaging, semantic versioning, or production readiness.

Phase 2 is not the full Kongono framework or a 1.0 release.

## Corrections and bounded subphases

A later explicit directive may divide an existing letter into a narrow
architecture and implementation subphase. Corrections use decimal suffixes.
Neither mechanism automatically adds another main phase or expands the A-H
milestone.

## Related documentation

- [ADR 0043: Phase 2 Runnable Web Framework Milestone](../adr/0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0047: Explicit PSR-15 Middleware and Terminal Routing](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](http-server-model.md)
- [ADR 0048: Web Application Bootstrap and Kernel Operation Ownership](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](web-application-model.md)
- [ADR 0044: HTTP Message and Interoperability Architecture](../adr/0044-http-message-and-interoperability-architecture.md)
- [HTTP message model](http-message-model.md)
- [Routing model](routing-model.md)
- [ADR 0046: Deterministic Route Declaration and Matching](../adr/0046-deterministic-route-declaration-and-matching.md)
- [Runtime model](runtime-model.md)
- [Error handling](../engineering/error-handling.md)
- [Package layout](../engineering/package-layout.md)
- [Architectural doctrine](../doctrine.md)
- [ADR index](../adr/README.md)
- [Phase 3 roadmap](phase-3-roadmap.md)

## Current milestone position

Phase 2A through 2H are complete. The thirteen-package acyclic graph includes
the Web Runtime leaf, while Web Application retains the sole Kernel operation
scope. Generic failure delivery is fixed and non-trusted; trusted HTML requires
both typed permissions and a supported source. The test-owned fixture proves the
full path without selecting a concrete SAPI/server/listener/emitter. Phase 2 is
the first runnable fixture milestone, not production readiness, 1.0, or the full
framework. See [ADR 0050](../adr/0050-phase-2-runnable-fixture-and-checkpoint.md).

[ADR 0051](../adr/0051-phase-3-production-http-integration-milestone.md)
accepts the next bounded milestone: one supported synchronous production HTTP
integration profile. Phase 3A establishes architecture, gates, roadmap,
documentation reconciliation, and contributor tooling only. Exact host,
package, API, parsing, emission, configuration, entrypoint, and deployment
decisions begin under later authenticated authority.
