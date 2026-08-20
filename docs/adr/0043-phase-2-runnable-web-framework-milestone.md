# ADR 0043: Phase 2 Runnable Web Framework Milestone

## Status

Accepted

## Date

2026-08-16

## Context

Phase 1 is complete at local checkpoint
`d7846f137a1d53f5306f48d19196b916fde44ac2`, whose tree is
`bfa1908c619069b699bc9b08668db17ea2555eb3`. The checkpoint preserves exactly
eight first-party packages and the tested Phase 1H component architecture.
Kongono has 53 production declarations and 254 tests with 4,279 assertions, but
it has no HTTP message foundation, router, middleware pipeline, web composition
root, runtime adapter, response delivery, generic production web failure path,
or runnable fixture application.

A bounded milestone is needed to connect those components into the first
runnable server-side web framework without implying the full Kongono framework,
a 1.0 release, Laravel feature parity, or production readiness. The boundary
must preserve persistent-runtime ownership, deterministic routing and
middleware order, transport-neutral Kernel responsibilities, explicit input
trust rules, and the existing fail-closed trusted-diagnostic doctrine.

Phase names are planning boundaries, not a requirement to continue every
numbered phase through `Z`. A fixed A-H sequence gives the milestone an
inspectable finish line while leaving exact APIs and package ownership to the
explicit decisions that have evidence to select them.

## Decision

### Milestone boundary

Phase 2 is Kongono's first runnable server-side web-framework milestone. Its
main sequence ends at Phase 2H unless a later architect-approved correction or
ADR explicitly changes it. Decimal suffixes may correct or divide an authorized
letter boundary but do not add another main phase or expand the milestone.

Phase 2A accepts only this architecture, its security and lifecycle invariants,
the roadmap, and the end-to-end acceptance boundary. It implements no request,
response, URI, header, body, stream, route, middleware, terminal handler,
bootstrap, adapter, emitter, configuration mechanism, production failure model,
package, dependency, source declaration, test, or runtime behavior.

### Fixed Phase 2 sequence

| Phase | Planned boundary |
|---|---|
| `2A` | Accept the runnable-web milestone, security invariants, A-H roadmap, and end-to-end acceptance boundary; documentation only. |
| `2B` | Decide the minimal HTTP message and interoperability architecture, including an explicit PSR-7/PSR-17 decision; no broader router or runtime implementation. |
| `2C` | Implement only the accepted minimal HTTP message foundation and its production tests. |
| `2D` | Establish and implement deterministic route declaration and matching in bounded slices. |
| `2E` | Establish and implement the explicit middleware and terminal-handler pipeline in bounded slices. |
| `2F` | Establish and implement application bootstrap/composition with Kernel operation-scope ownership and persistent-runtime cleanup rules. |
| `2G` | Establish and implement the first outer web-runtime adapter, response delivery boundary, fail-closed trusted diagnostics integration, and generic non-trusted failure path. |
| `2H` | Build the minimal fixture application, run black-box framework acceptance tests, reconcile documentation, and create the Phase 2 checkpoint. |

This sequence fixes ownership and dependency order for the milestone. It does
not select package names, namespaces, dependency edges, exact APIs, or
implementation size.

### End-of-Phase-2 runnable definition

Phase 2H may call Kongono runnable only when a minimal fixture application is
exercised through a public outer entry boundary and black-box tests prove all of
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

Exact HTTP status codes, headers, body types, URI and stream models, route
syntax, middleware signature, emitter protocol, and adapter runtime are not
selected by this definition.

### Persistent-runtime and ownership invariants

- One process may serve multiple sequential or overlapping operations.
- No core API may depend on process teardown for cleanup.
- No global, static, singleton, thread-local, fiber-local, or container-current
  request, response, route, principal, tenant, permission, or operation exists.
- Every inbound delivery has explicit ownership and one explicit Kernel
  operation scope.
- A long-lived framework object cannot retain request-specific state unless a
  later explicit scoped-lifetime contract owns and clears it.
- Runtime integrations remain outer adapters. PHP-FPM, FrankenPHP, RoadRunner,
  OpenSwoole, SAPI globals, and any specific server remain outside the Kernel.

### Trust and input invariants

- Every request component is untrusted until validated for its use.
- Client-controlled query, path, header, cookie, form, body, session, token, or
  other input cannot enable trusted diagnostics or upgrade either permission.
- Parsing, normalization, limits, duplicate handling, invalid syntax, and
  rejection behavior must be explicit before implementation.
- Header and body handling must prevent injection, response splitting,
  unbounded buffering, confused canonicalization, and accidental secret
  disclosure.
- Syntactically valid request data does not thereby become a diagnostic Safe
  field.

### Request and delivery separation

Message values, route matching, middleware orchestration, terminal application
handling, outer adaptation, and response emission remain separately owned.
Routing must be deterministic for the same immutable inputs and declarations.
Middleware order must be explicit and inspectable without discovery or priority
guessing. Constructing a response value does not emit it; emission remains
adapter-owned I/O and cannot occur in pure message or routing components.
Business logic must not be structurally trapped in controllers or transport
adapters.

### Error and diagnostic invariants

Generic production failure output is separately modeled and never reuses or
redacts the trusted developer model. Trusted HTML remains Protected developer
output, not a production response model. Bootstrap and current-delivery
permissions remain independent affirmative inputs, and neither a true
eligibility value nor non-null rendered HTML proves authorization.

The outer adapter must establish current-delivery evidence before diagnostic
source access. Failure in authorization, evidence, activation, projection,
rendering, transport, or cleanup cannot recursively enable trusted output.
Exact simultaneous-failure representation remains open.

### Interoperability decision boundary

Phase 2A neither adopts nor rejects PSR-7, PSR-15, or PSR-17. Phase 2B must make
an explicit evidence-backed interoperability decision before message
implementation. Adoption would make those interfaces interoperability
contracts, not Kongono's architecture. A decision not to adopt them directly
must document adapter cost, ecosystem impact, and future compatibility.
Symfony, Laravel, another framework, or a runtime server cannot become a
production foundation through an implicit dependency.

### Phase 2B amendment

[ADR 0044](0044-http-message-and-interoperability-architecture.md) resolves
that boundary. Kongono accepts PSR-7 through `psr/http-message: ^2.0`, the
selected server-side PSR-17 factories through `psr/http-factory: ^1.1`, and
PSR-15 through its two `^1.0` server interface packages. The future first-party
implementation boundary is `kongono/http-message` at
`packages/http-message` under `Kongono\Http\Message\`.

These are interface contracts, not an implementation or framework substrate.
Phase 2B installs no dependency, creates no package, claims no conformance, and
does not change the eight-package current runtime. `RequestFactoryInterface`,
PSR-18, client HTTP, SAPI conversion, routing, middleware implementation,
bootstrap, adapters, emission, and production failure behavior remain outside
this decision.

## Rationale

The A-H boundary orders decisions so implementation cannot outrun the contracts
on which it depends. Message and interoperability decisions precede message
implementation; message values precede routing; routing precedes middleware;
those execution contracts precede composition and operation ownership; and the
outer adapter follows only after the internal flow and cleanup obligations are
defined. The final fixture and black-box tests then prove the integrated result
from outside the packages rather than relying on component-level claims.

Keeping Phase 2A documentation-only makes the milestone reviewable without
creating accidental package names, APIs, protocol semantics, or dependencies.

## Consequences

### Security

All inbound data remains untrusted, parsing and resource limits require explicit
future contracts, and client data cannot activate trusted diagnostics. Generic
production failure output remains separate from Protected trusted HTML. Denial
must precede source access, and failure at any diagnostic or delivery layer must
remain fail-closed.

### Lifecycle and concurrency

Every future web delivery requires explicit Kernel operation ownership and
exact-once cleanup on success and failure. At least two sequential operations
must prove isolation in one process. No ambient current-request mechanism or
request-specific long-lived retention is authorized. Scoped storage, cleanup
APIs, cancellation, and simultaneous-failure representation remain open.

### Performance

Future request parsing, header and body handling, routing, middleware, response
construction, and emission require explicit bounds and measured costs. Phase 2A
creates no runtime path, allocation, cache, I/O, latency, or throughput promise.

### Testing

Component tests are insufficient for the milestone finish line. Phase 2H must
exercise the complete path through deterministic black-box tests outside the
internal packages without requiring an external listener, including unmatched
routes, generic failure, trusted denial and allowance, exact-once cleanup, and
sequential-operation isolation.

### Interoperability

ADR 0044 accepts the narrow PSR interface contracts and future first-party HTTP
message package without selecting a third-party implementation or framework
substrate. Concrete class APIs, routing, middleware implementation, bootstrap,
adapter, emission, and production failure contracts remain later decisions.

## Rejected alternatives

- Implementing HTTP before accepting the architecture boundary is rejected
  because it would create APIs and dependencies before their ownership and
  invariants are reviewable.
- Placing transport behavior in the Kernel is rejected because runtimes remain
  outer adapters and the Kernel stays transport-neutral.
- Ambient current-request or current-response state is rejected because it
  risks cross-operation leakage in persistent runtimes.
- Making Diagnostic Handler an HTTP adapter is rejected because its implemented
  responsibility is transport-neutral eligibility-first sequencing.
- Treating trusted HTML as the production failure model is rejected because
  Protected developer output and generic production output require separate
  models and policies.
- Selecting a client-controlled debug flag is rejected because untrusted input
  cannot authorize expanded disclosure.
- Prematurely locking all Phase 2 APIs or package names is rejected because the
  later decisions need focused evidence and create compatibility obligations.
- Assuming every numbered phase must reach `Z` is rejected because milestones
  need explicit bounded outcomes.
- Claiming Phase 2 is the full framework or 1.0 is rejected because the
  milestone deliberately excludes most framework capabilities and production
  hardening.

## Current-state amendments

- Phase 2D now implements the separate tenth package, `kongono/routing`, with
  bounded canonical declarations, exact encoded method/path matching,
  specificity independent of declaration order, structural conflict rejection,
  immutable match values, and `null` for ordinary unmatched requests. A match
  selects no handler and proves neither authentication nor authorization.
- Phase 2E now implements the PSR-15 middleware and terminal-handler contracts,
  exact declaration-order request flow, reverse response flow, complete
  canonical route-name mapping, reserved match attribute, and unmatched
  delegation in the eleventh package.

## What remains open

- Composition mechanism, configuration ownership, and service lifetime rules.
- Runtime adapter, request conversion, response delivery, and emitter protocol.
- Current-delivery evidence, authentication, listener, proxy, operator, and
  audience policy.
- Generic production failure model, incident behavior, and exact status,
  header, and body mappings.
- Cleanup APIs, cancellation, uncertain-runtime retirement, and simultaneous
  primary, activation, projection, rendering, transport, and cleanup failure
  representation.
- API status and future compatibility promotion for every new contract.

Phase 2F and ADR 0048 implement explicit Web Application composition and the
sole application-call Kernel scope. Phase 2G implements the outer adapter,
delivery port, fixed generic response, eligible trusted response, and exact
close orchestration. Phase 2H and ADR 0050 now add only the test-owned concrete
delivery and subprocess fixture needed for external black-box proof. Routing,
ordered middleware, response delivery, generic/trusted failure selection,
cleanup, and sequential isolation are therefore implemented and proved.

The remaining open items are their production/general forms: listener or SAPI
integration, request parsing, response emission, transport authentication,
proxy/TLS policy, deployment, operational hardening, broader cleanup, logging,
telemetry, and incident handling. The fixture is runnable without supplied error
middleware or a network listener; it is not production-ready or the full
framework.

## Related decisions

- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [ADR 0044](0044-http-message-and-interoperability-architecture.md)
- [HTTP message model](../architecture/http-message-model.md)
- [ADR 0046](0046-deterministic-route-declaration-and-matching.md)
- [Routing model](../architecture/routing-model.md)
- [ADR 0047](0047-explicit-psr-15-middleware-and-terminal-routing.md)
- [HTTP server model](../architecture/http-server-model.md)
- [ADR 0048](0048-web-application-bootstrap-and-kernel-operation-ownership.md)
- [Web Application model](../architecture/web-application-model.md)
- [Runtime model](../architecture/runtime-model.md)
- [ADR 0050](0050-phase-2-runnable-fixture-and-checkpoint.md)
- [Phase 2 runnable fixture](../architecture/phase-2-runnable-fixture.md)
- [Error handling](../engineering/error-handling.md)
- [Package layout](../engineering/package-layout.md)
- [Architectural doctrine](../doctrine.md)
- [ADR index](README.md)
