# ADR 0051: Phase 3 Production HTTP Integration Milestone

Status: Accepted

Date: 2026-08-20

## Context

Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Kongono has exactly thirteen
acyclic production packages and a deterministic root-owned, test-owned fixture
that exercises the real Web Runtime, Web Application, HTTP Server, Routing, HTTP
Message, and Kernel path from a CLI subprocess. Fixture-runnable means the
framework composition is externally proved; it does not mean that a production
host can invoke it or that the fixture is a production application, supported
template, deployment entrypoint, or server.

The repository supplies no production SAPI profile, server, listener, raw host
input conversion, response emitter, operator bootstrap profile, deployment
entrypoint, or production integration acceptance proof. The next milestone must
close that exact edge before Kongono expands into ORM, authentication, frontend,
agent, or ecosystem breadth. A supported production integration profile gives
those later capabilities an explicit host boundary without importing transport
or runtime concerns into Kernel.

Phase 3A also reconciles current documentation that retained pre-checkpoint
progress labels. Historical ADRs, directives, reports, and checkpoint evidence
remain unchanged. Its contributor-only PHPStan 512 MB child-process command is
tooling stabilization, not runtime architecture or application configuration.

## Decision

Phase 3 is the bounded path from fixture-runnable Kongono to its first supported
synchronous production HTTP integration profile. Host integration remains an
outer leaf. Kernel stays transport- and runtime-independent; Web Application
continues to own the sole Kernel operation scope for each accepted application
call; and Web Runtime continues to own generic/trusted failure selection and
send/close orchestration.

Phase 3A accepts only this milestone, the fixed A-H dependency order, its
security and persistent-runtime gates, and current-document reconciliation. It
selects and implements no host or SAPI, package, namespace, dependency, class,
method, configuration key, parser, emitter, front controller, deployment file,
permission source, or production runtime behavior. Phase 3B must make the first
exact selections or explicitly defer them.

## Rationale

A supported production edge is the smallest milestone that turns the proved
fixture composition into an operator-usable integration without importing a
host into Kernel or pretending that unrelated framework breadth is complete.
Fixing the A-H dependency order keeps input, emission, trust, composition,
hardening, and acceptance work behind the architecture each depends on.

## Phase 3 finish line

Phase 3 is complete only when all of these requirements are met under later
authenticated authority:

1. One host-provided synchronous PHP web execution profile is explicitly selected.
2. One outermost production boundary captures only the selected raw host inputs and converts them into the existing first-party HTTP message model.
3. Syntax, normalization, duplication, count, byte, body, stream, upload, cookie, query, header, and invalid-input policies are explicit before parsing code.
4. One exact response-emission contract covers status, headers, body, duplicate header values, committed-output uncertainty, and cleanup.
5. One immutable operator-owned bootstrap profile is parsed and validated before serving without introducing a framework-wide configuration system.
6. Trusted-bootstrap and current-delivery permission sources are independent and cannot be upgraded by client input.
7. Existing Web Runtime, Web Application, and Kernel ownership remains unchanged.
8. Fixed generic failure output is used whenever trusted presentation is ineligible or a production trust input is absent, invalid, ambiguous, or failed.
9. A minimal supported production entry composition exists without ambient framework current-request or service-locator state.
10. External acceptance through the selected production boundary covers success, unmatched requests, generic failure, trusted and denied diagnostics, malformed input, partial or failed emission, cleanup, and repeated-operation isolation where the host permits it.
11. Deployment and security documentation states exact prerequisites and limitations without broader production-hardening claims.
12. A Phase 3 checkpoint is created only after every planned subphase passes.

This finish line is smaller than a first-party listener or process manager;
support for every SAPI or runtime; proxy or TLS termination; production
authentication or authorization; sessions, CSRF, templates, frontend, data,
ORM, migrations, cache, queue, events, mail, scheduler, realtime, telemetry,
agents, extensions, or CLI; a general configuration framework or secrets
manager; automatic recovery after uncertain state; an application generator or
template ecosystem; Stable API, semantic versioning, 1.0, full-framework,
feature-parity, or production-hardening certification.

### Phase 3A non-guarantees

Phase 3A adds no production package, namespace, class, interface, enum,
exception, diagnostic, API status, Composer dependency, SAPI, PHP-FPM/CGI or
Apache integration, built-in server profile, RoadRunner, FrankenPHP, OpenSwoole,
ReactPHP, Amp, Workerman, socket, listener, or process manager. It reads no
superglobal, parses no request/upload/cookie/query/body, emits no header or body,
copies no stream, and adds no output buffering.

It adds no front controller, deployment file, application template, framework
CLI, environment or configuration key, `.env` loader, secrets loader,
configuration repository, reload/watch mechanism, proxy/TLS trust, host
validation, authentication, authorization, session, CSRF, rate limiting,
incident handling, logging, telemetry, tracing, metrics, retry, or recovery.

It also adds no ORM, database, migration, model, template, frontend, cache,
queue, event, mail, scheduler, realtime, agent, extension, MCP, A2A, marketplace,
release packaging, semantic-versioning, 1.0, feature-parity, full-framework,
Stable API, or production-readiness guarantee. No Symfony or Laravel framework
component or other framework kernel becomes production architecture. No
dependency, lockfile, plugin, platform policy, PHPStan baseline, suppression,
ignore, exclusion, reduced level, or unlimited memory follows from this ADR.

## Fixed A-H sequence

| Phase | Boundary |
| --- | --- |
| `3A` | Accept the production HTTP integration milestone, security/persistent-runtime gates, A-H roadmap, current-status reconciliation, and stable contributor PHPStan memory command. No production runtime implementation. |
| `3B` | Architecture-only selection of the exact synchronous PHP host profile, raw-input boundary, package ownership, dependency direction, API status, limits, invalid-input behavior, and acceptance surface. |
| `3C` | Implement only the accepted bounded inbound host-snapshot-to-first-party-server-request conversion and production tests. |
| `3D` | Implement only the accepted bounded response-emission boundary, committed-output uncertainty rules, and production tests. |
| `3E` | Implement only the accepted immutable operator bootstrap profile and fail-closed trusted bootstrap/current-delivery input composition; no general configuration system. |
| `3F` | Implement only the supported production composition/entry boundary using the existing Web Runtime and Web Application ownership. |
| `3G` | Add bounded integration hardening, failure/cleanup behavior, deployment/security documentation, and exact selected-profile operational constraints. |
| `3H` | Run external production-profile acceptance, reconcile documentation, and create the Phase 3 checkpoint. |

This order is locked. A later phase cannot outrun the decisions it depends on,
and Phase 3 ends at 3H unless an architect-approved ADR changes the roadmap.

## Security invariants

- Every host or request value remains untrusted until validated for its exact use.
- Client-controlled request data cannot enable or widen trusted diagnostics.
- Trusted output requires independent affirmative bootstrap and current-delivery permissions before diagnostic source access.
- Missing, invalid, ambiguous, failed, denied, or uncertain trust input fails closed to the fixed generic path.
- Denial occurs before protected source access and never falls back to trusted output.
- Production and trusted developer failure models remain separate; the trusted model is never redacted into a production model.
- Input conversion must reject injection, response splitting, ambiguous normalization, confused canonicalization, secret disclosure, and unbounded work according to policies fixed before implementation.
- Proxy/TLS trust, authentication, authorization, incident response, logging, telemetry, and broader operational hardening are not selected or guaranteed by Phase 3A.

## Persistent-runtime and cleanup invariants

- Request, response, route, permission, principal, configuration, exception, and operation state remain explicit call-flow values, never ambient current state.
- Web Application remains the sole owner of one Kernel scope per accepted application call; production integration opens no second scope.
- Web Runtime remains the generic/trusted failure and send/close orchestration boundary.
- Success and failure paths require deterministic exact-once cleanup while preserving the primary failure and representing cleanup failure explicitly.
- Committed-output uncertainty must fail closed under a contract selected before emission implementation.
- Uncertain runtime state cannot be silently reused.
- Repeated operations must prove isolation where the selected host permits reuse.

## Consequences

Phase 3 has an inspectable finish line and dependency order without prematurely
stabilizing a host adapter or API. Phase 3B must resolve the host profile,
raw-input ownership, package direction, API status, resource limits,
invalid-input rules, and acceptance surface before implementation begins.

Current documentation may describe Phase 2 as checkpointed and fixture-runnable
while identifying production integration as planned. Historical records retain
their point-in-time wording. Contributor PHPStan now runs through an explicit
bounded 512 MB child process; normal PHP runtime policy is unchanged.

No production runtime capability, package, dependency, lockfile change,
configuration API, parser, emitter, front controller, deployment entrypoint, or
supported production environment follows from this decision alone.

## Rejected alternatives

- Immediately adding a first-party network listener or process manager is rejected because Phase 3 targets one host-provided synchronous profile.
- Importing Symfony HttpKernel, Laravel runtime components, or another framework integration layer is rejected because Kongono retains its own Kernel and application ownership.
- Parsing raw superglobals before ownership, limits, duplication, normalization, and invalid-input policies are accepted is rejected.
- Implementing an emitter before committed-output failure semantics and cleanup ownership are accepted is rejected.
- Using a request flag or other client value to enable trusted diagnostics is rejected because trust must remain operator- and adapter-controlled.
- Introducing ambient current-request, container, or service-locator state is rejected because it violates inspectability and persistent-runtime isolation.
- Replacing one bounded operator bootstrap profile with a general configuration framework is rejected as unnecessary and premature scope.
- Claiming production readiness after one adapter is rejected because one supported integration profile is not production-hardening certification.
- Jumping directly to ORM, authentication, frontend, agents, or ecosystem features is rejected because Kongono first needs a supported production edge.

## What remains open

Phase 3B and later must decide the exact synchronous host profile, raw host
inputs, server/software prerequisites, package and namespace ownership,
dependency direction, API status, types and signatures, configuration and
permission-source names, syntax and normalization rules, limits, duplicate and
invalid-input behavior, request mapping, response status/header/body mapping,
emission mechanics, committed-output semantics, cleanup and retirement behavior,
production entry composition, deployment files or commands, and the exact
external acceptance harness. No item in this list is implicitly selected by
Phase 3A.

## Related material

- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)
- [ADR 0043: Phase 2 Runnable Web Framework Milestone](0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0049: Outer Web Runtime and Safe Failure Delivery](0049-outer-web-runtime-and-safe-failure-delivery.md)
- [ADR 0050: Phase 2 Runnable Fixture and Checkpoint](0050-phase-2-runnable-fixture-and-checkpoint.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [Phase 2 runnable fixture](../architecture/phase-2-runnable-fixture.md)
- [Runtime model](../architecture/runtime-model.md)
- [Web Application model](../architecture/web-application-model.md)
- [Web Runtime model](../architecture/web-runtime-model.md)
- [Security policy](../../SECURITY.md)
- [Architectural doctrine](../doctrine.md)
- [Package layout](../engineering/package-layout.md)

## Phase 3F status

The milestone now includes the Internal `CgiWebDelivery` and Experimental
`CgiWebEntry` decided by [ADR 0056](0056-php-cgi-production-delivery-and-entry-composition.md).
PHP CGI projects bounded native values, defers input acquisition until request
conversion, calls the existing converter and emitter once, and releases
one-delivery resources explicitly. The direct package edge is PHP CGI to Web
Runtime; there is no direct PHP CGI to Web Application edge.

## Phase 3G reconciliation

ADR 0057 implements one stateless front-controller entry attempt followed by
one explicit supplied application-shutdown attempt. Its deterministic four-way
matrix uses 1019 only when both fail. Correct application/runtime/closure pairing
and pre-entry partial-boot cleanup remain consumer responsibilities. The
selected-profile operator constraints now exist as documentation, not as a
listener, server configuration, deployable artifact, or external production
proof. `Production` trust remains denied; uncertain emission or shutdown
requires external process retirement. Phase 3H alone remains next.

## Phase 3H outcome

Phase 3H completes this milestone with external test-owned proof through the
actual selected PHP-CGI FastCGI profile and the exact Phase 3 checkpoint. The
harness remains outside every production package. It proves the frozen trust,
input, response, cleanup, reuse, and retirement contracts without supplying a
production listener, deployment system, proxy/TLS policy, monitoring, recovery,
Stable API, 1.0 release, or production-hardening certification. No later phase
starts automatically.
