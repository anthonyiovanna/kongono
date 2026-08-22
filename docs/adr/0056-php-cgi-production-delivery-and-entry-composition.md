# ADR 0056: PHP CGI Production Delivery and Entry Composition

Status: Accepted

Date: 2026-08-21

## Context

Phases 3C through 3E established the bounded CGI request converter, fully staged
response emitter, literal operator bootstrap, and independent current-delivery
permission evaluation. Web Runtime already owns failure selection, one send
attempt, and one close attempt through its Public `WebDeliveryInterface`, but
PHP CGI had no production implementation of that port and no thin entry into an
already-composed runtime.

Phase 3F must connect those existing pieces without making PHP CGI a server,
listener, process manager, deployment solution, or general SAPI abstraction.
It must preserve generic failure handling for rejected conversion and keep all
request-specific state out of ambient framework state.

## Decision

The existing outer `kongono/php-cgi` leaf owns one concrete Internal
`CgiWebDelivery`. It implements the Public Web Runtime delivery port and owns
exactly one native capture, permission evaluation, request-conversion attempt,
response-emission attempt, and cleanup. This placement keeps host-specific CGI
behavior at the outer leaf and creates no reverse dependency from lower
packages.

PHP CGI now depends directly on `kongono/web-runtime`. It does not depend
directly on `kongono/web-application`. Experimental `CgiWebEntry::run()` accepts
an already-composed `WebRuntimeAdapter`, creates one fresh delivery, and invokes
the runtime once. PHP CGI therefore does not construct Web Application, Kernel,
routes, middleware, handlers, Dependency Injection definitions, or Application
Graph nodes.

The consumer composition order is explicit:

1. select a literal `CgiOperatorProfile`;
2. call `CgiOperatorBootstrap::load()` with that profile;
3. explicitly construct and boot `WebApplication`;
4. construct `WebRuntimeAdapter` with the application and bootstrap permission;
5. call `CgiWebEntry::run()` with that runtime; and
6. explicitly shut down the application under the caller's lifecycle policy.

The bootstrap and delivery permissions remain independent. `Production`
continues to deny trusted presentation. `LoopbackDevelopment` supplies an
affirmative bootstrap permission only after its exact process checks pass, and
the delivery permission still requires direct canonical loopback evidence with
no forwarded marker.

## Native capture and request ownership

Only `CgiWebDelivery::capture()` reads `PHP_SAPI`, the selected `$_SERVER`
entries, whether `$_POST` is empty, whether `$_FILES` is empty, and a lazy
binary `php://input` opener. The server projection contains only the Phase 3C
required and optional CGI keys plus string keys beginning `HTTP_`. Unselected
string keys are ignored. A non-string key becomes one fixed invalid sentinel;
its original key and value are not retained.

Post and upload state is retained only as two booleans. Nonempty state becomes
one fixed non-sensitive converter sentinel. The input is not opened during
capture or permission evaluation; `request()` opens it immediately before one
fresh converter call. An open failure uses diagnostic `KGN-PHP-CGI-1010` with
`input_open_failed`, while an opener-thrown external failure propagates
unchanged.

## One-delivery behavior and cleanup

The delivery is final, non-readonly, non-cloneable, and has no destructor,
shutdown hook, or static mutable state. Permission evaluation, request
conversion, response emission, and close are one-attempt operations. Repeated,
out-of-order, and use-after-close access fails with fixed package-neutral
contract messages.

Before conversion, the effective response method is `HEAD` only when the raw
captured method is exactly `HEAD`; otherwise it is `GET`. Successful conversion
replaces that fallback with the validated request method. A malformed `HEAD`
therefore still suppresses the generic response body, while other malformed
methods use the safe `GET` fallback.

`send()` captures the exact response body and calls one fresh existing emitter
with the exact response and effective method. It does not retry, remove headers,
roll back output, or choose another response. Web Runtime continues to map
conversion rejection to its fixed generic 500; Phase 3F adds no semantic 4xx
mapping.

`close()` detaches every retained reference before external cleanup. It closes
the first-party request body, the captured response body, and the native input,
deduplicating a shared body by object identity. Every distinct cleanup is
attempted, and the first thrown failure is rethrown unchanged after the rest.
A false native close result uses Experimental
`CgiDeliveryCleanupException` and `KGN-PHP-CGI-1018` with the single Safe token
`input_close_failed`. No request value, resource, host path, warning text, or
raw failure message enters that diagnostic.

## API and dependency boundary

`CgiWebDelivery` is Internal. `CgiWebEntry` and
`CgiDeliveryCleanupException` are Experimental. The existing Web Runtime
delivery interface remains Public. This decision creates the one allowed edge
`php-cgi -> web-runtime`; it creates no `php-cgi -> web-application` edge and
no lower-package dependency on PHP CGI.

All per-delivery state stays on the fresh delivery object and is released by
explicit close. There is no global, static, singleton, container-current,
thread-local, or fiber-local current request, response, permission, failure, or
operation state. Sequential and reentrant runtime use therefore remain isolated
by object and call-frame ownership.

## Consequences and limits

The production composition now has a concrete bounded delivery and thin entry.
It reuses the full-buffering request and response limits already selected in
Phases 3C and 3D, so the response is completely staged before native commitment
and request body conversion remains bounded. This is deliberate and is not a
streaming server contract.

The implementation does not certify production hardening, deployment safety,
broader server support, or Stable API status. It is not a first-party listener,
FastCGI parser, process manager, or generated front controller.

## Rejected alternatives

- Framework-wide current-request globals were rejected because they leak state
  across persistent deliveries and hide ownership.
- Service-location or container lookup was rejected because the composition
  root must supply the already-built runtime explicitly.
- A direct PHP-CGI-to-Web-Application dependency was rejected because Web
  Runtime already owns delivery orchestration and failure selection.
- A new package was rejected because the concrete behavior belongs in the
  existing host-specific outer leaf.
- Dynamic profile parsing was rejected because operator profiles remain literal
  typed selections.
- Automatic retry was rejected because native response commitment can be
  uncertain and a second response is unsafe.
- A first-party listener was rejected because the selected host already owns
  accepting and framing FastCGI requests.
- Production entry-script generation was rejected because Phase 3F does not own
  deployment-specific lifecycle and failure policy.

## Remaining work

Phase 3G now owns the implemented bounded front-controller failure/shutdown
composition and deployment constraints. Phase 3H alone remains the separate external
CGI/FastCGI acceptance boundary. Neither is implemented or certified by this
decision.

## Related decisions

- [ADR 0049: Outer Web Runtime and Safe Failure Delivery](0049-outer-web-runtime-and-safe-failure-delivery.md)
- [ADR 0051: Phase 3 Production HTTP Integration Milestone](0051-phase-3-production-http-integration-milestone.md)
- [ADR 0052: PHP CGI FastCGI Responder Profile](0052-php-cgi-fastcgi-responder-profile.md)
- [ADR 0053: Bounded PHP CGI Inbound Conversion](0053-bounded-php-cgi-inbound-conversion.md)
- [ADR 0054: Bounded PHP CGI Response Emission](0054-bounded-php-cgi-response-emission.md)
- [ADR 0055: Immutable PHP CGI Operator Bootstrap and Trust Inputs](0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md)
- [Phase 3 roadmap](../architecture/phase-3-roadmap.md)
- [PHP CGI FastCGI profile](../architecture/php-cgi-fastcgi-profile.md)

## Phase 3G reconciliation

[ADR 0057](0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md)
adds the Experimental stateless `CgiFrontController` above this unchanged entry.
It invokes the entry once and an explicitly supplied application-shutdown
closure once. The four-way failure matrix uses diagnostic 1019 only when both
fail, without retry or a second response. PHP CGI still has no direct Web
Application edge; application construction, correct pairing, and pre-entry
partial-boot cleanup remain caller-owned. Uncertain emission or shutdown
requires external process retirement. The deployment contract adds no listener,
server configuration, artifact, or external proof. Phase 3H alone is next.

## Phase 3H outcome

Phase 3H now exercises this unchanged delivery and entry through the real
external PHP-CGI listener, including capture, conversion, permission evaluation,
Web Runtime selection, emission, close, and explicit shutdown. Five sequential
requests reuse one listener without cross-request leakage. The acceptance
composition remains root-owned test code; PHP CGI retains no direct Web
Application edge and gains no production listener, process manager, deployment,
monitoring, or recovery behavior.
