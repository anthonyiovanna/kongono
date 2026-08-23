# Phase 3 Roadmap

## Purpose and milestone boundary

Phase 3 is the dependency-ordered path from Kongono's completed, test-owned
Phase 2 runnable fixture to its first supported synchronous production HTTP
integration profile. It connects one selected host-provided PHP web execution
profile to the existing first-party HTTP message, Web Runtime, Web Application,
and Kernel path with bounded input conversion, exact response emission,
fail-closed operator trust, deterministic cleanup, deployment documentation,
and external acceptance proof.

Phase 3 is not a first-party listener or process manager, support for every SAPI
or server, a general configuration system, production authentication or
authorization, broader framework capability, Stable API, 1.0 release, full
framework, feature-parity, or production-hardening certification.

Phase 3A accepted this roadmap and reconciled current documentation. Phase 3B
selects PHP `cgi-fcgi`, CGI/1.1, and FastCGI `RESPONDER` and locks the profile's
bounded input, emission, trust, cleanup, and acceptance architecture. Phase 3C
and ADR 0053 implement bounded explicit inbound conversion. Phase 3D and ADR
0054 implement fully staged bounded response emission. Phase 3E and ADR 0055
implement literal typed operator bootstrap, fixed native prerequisites, and
independent current-delivery trust evidence. No concrete delivery, Web Runtime
composition, or production entry existed before Phase 3F. Phase 3F and ADR 0056
now implement the Internal one-delivery adapter and Experimental thin entry.
Phase 3G and ADR 0057 implement the bounded front-controller lifecycle and
document the selected-profile deployment constraints. Phase 3H and ADR 0058
complete external test-owned acceptance through an operator-supplied listener
and create the Phase 3 checkpoint. The harness is not a production listener,
client, process manager, or deployment artifact.

## Current starting point after Phase 2

Phase 2A through 2H are complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52`. Exactly fourteen acyclic production
packages implement the real message, routing, middleware, application, Kernel,
diagnostic, and outer delivery path. The root-owned
[Phase 2 fixture](phase-2-runnable-fixture.md) proves that path externally with
fixed CLI scenarios and black-box tests.

That fixture is test infrastructure, not a production application, supported
template, framework CLI, SAPI adapter, server, listener, emitter, deployment
entrypoint, or authorization mechanism. Explicit bounded inbound conversion,
bounded response emission, immutable operator trust inputs, concrete delivery,
and bounded per-delivery capture are implemented in Phases 3C through 3F. Phase
3G implements the front-controller lifecycle and documents selected-profile
constraints. Phase 3H proves the selected profile externally; deployed
hardening remains outside Phase 3.

## Current phase status

| Phase | Status |
| --- | --- |
| `3A` | Complete: milestone, gates, roadmap, current-status reconciliation, and contributor-tooling stabilization accepted. |
| `3B` | Complete: PHP `cgi-fcgi` FastCGI Responder architecture selected; no runtime implementation. |
| `3C` | Complete: bounded inbound conversion implemented. |
| `3D` | Complete: bounded response staging/emission implemented. |
| `3E` | Complete: immutable operator bootstrap and independent current-delivery permission evaluation implemented. |
| `3F` | Complete: concrete one-delivery PHP CGI adapter and thin entry composition implemented. |
| `3G` | Complete: bounded front-controller lifecycle, simultaneous-failure diagnostic, and selected-profile deployment constraints implemented. |
| `3H` | Complete: external test-owned PHP-CGI acceptance and the exact Phase 3 checkpoint. |

The [PHP CGI FastCGI profile](php-cgi-fastcgi-profile.md) is the authoritative
profile. ADRs 0053 through 0058 record Phases 3C through 3H. Phase 3 is closed;
no later phase starts automatically.

## Fixed A-H roadmap

| Phase | Boundary |
| --- | --- |
| `3A` | Accept the production HTTP integration milestone, security/persistent-runtime gates, A-H roadmap, current-status reconciliation, and stable contributor PHPStan memory command. No production runtime implementation. |
| `3B` | Architecture-only selection of the exact synchronous PHP host profile, raw-input boundary, package ownership, dependency direction, API status, limits, invalid-input behavior, and acceptance surface. |
| `3C` | Implement only the accepted bounded inbound host-snapshot-to-first-party-server-request conversion and production tests. |
| `3D` | Implement only the accepted bounded response-emission boundary, committed-output uncertainty rules, and production tests. |
| `3E` | Implement only the accepted immutable operator bootstrap profile and fail-closed trusted bootstrap/current-delivery input composition; no general configuration system. |
| `3F` | Implement only the supported production composition/entry boundary using the existing Web Runtime and Web Application ownership. |
| `3G` | Add bounded integration hardening, failure/cleanup behavior, deployment/security documentation, and exact selected-profile operational constraints. |
| `3H` | Complete external production-profile acceptance, reconcile documentation, and create the Phase 3 checkpoint. |

## Dependency graph

```text
3A milestone, gates, roadmap, and current-status reconciliation
    -> 3B exact host and boundary architecture
        -> 3C bounded inbound conversion
            -> 3D exact response emission
                -> 3E immutable operator bootstrap and trust inputs
                    -> 3F supported production composition and entry
                        -> 3G integration hardening and deployment constraints
                            -> 3H external acceptance and checkpoint
```

Later work cannot outrun the accepted decisions on which it depends. Main
phases do not automatically continue to `Z`; Phase 3 ends at `3H` unless an
architect-approved ADR changes the roadmap.

## End-of-Phase-3 external acceptance checklist

External proof through the selected production boundary must show:

1. Selected host-profile invocation reaches the public production entry.
2. Bounded raw input becomes one first-party server request.
3. Malformed, oversized, or ambiguous input fails deterministically before the application path where required.
4. One accepted input becomes one Web Application call and one Kernel scope.
5. Routing, middleware, and handler behavior remains deterministic.
6. One response is emitted with exact status, header, and body behavior.
7. Partial or committed-output failure follows explicit fail-closed semantics.
8. Generic failures expose no sensitive data.
9. Trusted diagnostics require both non-client-controlled permissions.
10. Bootstrap or delivery denial occurs before protected source access.
11. Cleanup occurs exactly once on success and failure.
12. Repeated operations do not leak request, permission, response, or failure state.
13. No client input changes operator configuration or trust.
14. Deployment prerequisites and unsupported environments are explicit.
15. Tests exercise the real package path from outside internal packages.

The exact status codes, header policies, raw input keys, configuration names,
server commands, and acceptance implementation remain later decisions.

## Security gates for every subphase

- Treat every raw host and request component as untrusted until validated for its exact use.
- Define syntax, normalization, duplication, count, byte, body, stream, upload, cookie, query, header, and invalid-input policies before implementing the affected parser or emitter.
- Prevent injection, response splitting, unbounded buffering, confused canonicalization, and accidental secret disclosure.
- Keep syntactic validity separate from trust and diagnostic Safe classification.
- Never allow client-controlled input to enable or widen trusted diagnostics or operator configuration.
- Require independent affirmative trusted-bootstrap and current-delivery permissions before protected source access.
- Fail closed to the fixed generic output on absence, invalidity, ambiguity, failure, denial, or uncertainty.
- Keep generic production output separate from the trusted developer model and prevent recursive trusted fallback.
- Record exact prerequisites and unsupported environments without broader security or hardening claims.

## Persistent-runtime and cleanup gates

- Keep request, response, route, permission, principal, configuration, failure, and operation state in explicit call flow rather than ambient storage.
- Preserve Web Application as the sole owner of one Kernel scope for each accepted application call.
- Preserve Web Runtime as the outer generic/trusted failure and send/close orchestration boundary.
- Give each host invocation and transport resource explicit ownership.
- Attempt cleanup exactly once on success and failure, preserve the primary failure, and represent cleanup failure explicitly.
- Define committed-output uncertainty before emission implementation and never silently reuse uncertain runtime state.
- Prove repeated-operation isolation wherever the selected host can retain a process or integration object.
- Do not make a selected host, SAPI, server, or process lifecycle a Kernel dependency.

## Current versus planned behavior

| Concern | Implemented now | Planned Phase 3 boundary | Still future |
| --- | --- | --- | --- |
| Framework path | Fourteen production packages plus a root-owned test fixture | One selected supported synchronous production HTTP profile | Broader framework and ecosystem capability |
| Input | Phase 3C converts a closed caller-supplied host snapshot and caller-owned bounded stream into a first-party request | Phase 3F delivery captures selected process inputs before calling the converter | Support for every SAPI/server and arbitrary adapters |
| Output | Phase 3D stages and emits bounded responses with committed-output failure rules | Phase 3F delivery calls the emitter after application handling | General protocol/runtime emission framework |
| Trust | Phase 3E owns literal operator bootstrap plus independent canonical-loopback current-delivery evidence | Phase 3F combines the two permissions without collapsing them | General configuration, secrets, authentication, and authorization systems |
| Lifecycle | Web Application owns one Kernel scope; Web Runtime owns send/close | Production composition preserves that ownership and cleanup | Automatic recovery, arbitrary scoped resources, and universal runtime reuse |
| Operations | Deterministic black-box fixture acceptance | Deployment/security prerequisites and external production-profile proof | Production-hardening certification, observability, and incident systems |

## Deliberate exclusions

Phase 3 does not include a first-party socket listener or process manager; every
SAPI, server, or runtime; proxy or TLS termination; production authentication,
authorization, sessions, CSRF, rate limiting, or incident response; ORM,
database, migrations, templates, frontend, cache, queue, events, mail,
scheduler, realtime, telemetry, agents, extensions, MCP, A2A, or CLI; a general
configuration framework or secrets manager; automatic uncertain-runtime
recovery; an application generator or template ecosystem; Stable API, semantic
versioning, 1.0, full-framework, feature-parity, or production-readiness claim.

## Correction and subphase policy

An authenticated directive may divide one roadmap letter into bounded
architecture or implementation subphases. Decimal suffixes correct an existing
boundary; they do not add a new main phase or expand the milestone. Formatting,
link, or wording corrections remain bounded to their explicit authority and
cannot select open architecture.

## Relationship to the Phase 2 fixture

The Phase 2 fixture remains the deterministic test-owned proof of the existing
package composition. Phase 3 production work must use the real package path but
must not promote the fixture runner, fixed scenarios, test diagnostic, or
delivery implementation into production contracts. Phase 3H adds a separate
external proof through the selected production boundary.

## Web Runtime, Web Application, and Kernel ownership

The selected production edge remains outside Web Runtime. The implemented
`kongono/php-cgi` converter accepts only its explicit closed CGI host input, the
emitter owns the accepted staged-commit contract, and the trust-input boundary
owns immutable bootstrap and fresh delivery permissions. A Phase 3F delivery
will capture input, call conversion and emission, and compose those permissions. Web Runtime
continues to coordinate one delivery, generic or trusted failure selection, one
send attempt, and one close attempt. Web Application continues to own the sole
Kernel operation scope around one application call. Kernel remains unaware of
HTTP, PSRs, hosts, SAPIs, servers, listeners, emitters, and deployment.

## Relationship to future product milestones

A completed Phase 3 supplies one supported production integration profile, not
the complete Kongono framework. Data, authentication, user experience, agents,
extensions, CLI, operations, hardening, release policy, and ecosystem work
remain separately authorized future milestones.

## Related material

- [ADR 0051: Phase 3 Production HTTP Integration Milestone](../adr/0051-phase-3-production-http-integration-milestone.md)
- [ADR 0052: PHP cgi-fcgi FastCGI Responder Profile](../adr/0052-php-cgi-fastcgi-responder-profile.md)
- [PHP CGI FastCGI profile](php-cgi-fastcgi-profile.md)
- [ADR 0043: Phase 2 Runnable Web Framework Milestone](../adr/0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0049: Outer Web Runtime and Safe Failure Delivery](../adr/0049-outer-web-runtime-and-safe-failure-delivery.md)
- [ADR 0050: Phase 2 Runnable Fixture and Checkpoint](../adr/0050-phase-2-runnable-fixture-and-checkpoint.md)
- [Phase 2 roadmap](phase-2-roadmap.md)
- [Phase 2 runnable fixture](phase-2-runnable-fixture.md)
- [Runtime model](runtime-model.md)
- [Web Application model](web-application-model.md)
- [Web Runtime model](web-runtime-model.md)
- [Security policy](../../SECURITY.md)
- [Architectural doctrine](../doctrine.md)
- [Package layout](../engineering/package-layout.md)

## Current implementation status

Phases 3A through 3H are implemented. Phase 3G adds the Experimental stateless
front controller, one entry attempt followed by one supplied application-
shutdown attempt, deterministic four-way failure semantics, and diagnostic
1019 for simultaneous failure. PHP CGI still has no direct Web Application
edge; application-specific construction and partial-boot cleanup remain caller
responsibilities. `Production` trusted diagnostics remain denied. Uncertain
emission or shutdown requires external process retirement. The deployment
constraints add no listener, server configuration, or deployment artifact.
Phase 3H adds external test-owned proof through an operator-supplied listener
without adding production acceptance infrastructure. Phase 3 is closed and
remains smaller than a full framework, Stable API, 1.0, or production-hardening
certification. The fixed A-H phase table above remains the controlling roadmap.

The intrinsic `QUERY_STRING` ceiling remains 8,192 bytes. Because its exact
nonempty value must also be embedded after `/?` inside an 8,192-byte complete
target, the effective externally representable maximum is 8,190 bytes. The
external matrix proves 8,189 and 8,190 accepted and 8,191 rejected as
`target_too_long`; package source and tests remain authoritative for the
intrinsic ceiling.

## Checkpoint and forward boundary

This roadmap remains historical Phase 3 evidence. Phase 3 completed at
checkpoint `6aacafc92f07b567818173d660e2e182dfa83d23`; its fourteen-package
topology and selected PHP-CGI profile are unchanged by later planning.

Phase 4A separately accepts documentation architecture for an optional,
AI-independent developer-knowledge foundation and planned Kongono Chie,
Kongono Hantei, and Kongono Oshie roles. It implements no knowledge artifact,
assistant, patch proposal, package, command, provider, auto-fix, autonomous
operation, or production AI. See the [Phase 4 roadmap](phase-4-roadmap.md).
