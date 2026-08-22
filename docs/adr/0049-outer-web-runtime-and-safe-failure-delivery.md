# ADR 0049: Outer Web Runtime and Safe Failure Delivery

Status: Accepted

Date: 2026-08-20

## Context

Phase 2F established `kongono/web-application` as the transport-free composition boundary that owns the sole Kernel operation scope. Kongono still needed an explicit leaf boundary for one inbound server delivery, safe failure-response selection, response transmission, and exact transport cleanup without selecting a concrete SAPI or runtime server.

## Decision

Create the top-level leaf package `kongono/web-runtime`, namespace `Kongono\Web\Runtime\`. It depends directly on API Metadata, Application Graph, Dependency Injection, Diagnostic Activation, Diagnostic Handler, Diagnostics, HTTP Message, Kernel, Web Application, PHP, and PSR-7. No existing production package depends on it, so the thirteen-package graph remains acyclic and Kernel remains transport-neutral.

`WebDeliveryInterface` is the Public per-delivery integration port. One fresh implementation evaluates request-independent, server-controlled presentation evidence; returns one bounded PSR-7 server request; sends one response; and closes delivery-owned resources. Implementing the interface is not proof of trust: concrete authentication, listener, proxy, operator, and audience policy remain integration obligations.

`WebRuntimeAdapter` first refuses if it or its Web Application is retired. Bootstrap denial short-circuits evidence evaluation while normal work continues. Otherwise evidence is evaluated exactly once before request conversion. Evidence or conversion failure produces the generic response without application or trusted-presentation access. A converted request is passed unchanged to exactly one `WebApplication::handle()` call, which remains the sole owner of one Kernel operation scope.

The generic production response is a fresh status `500` response with exact body `Internal Server Error\n` and exactly these headers: `Content-Type: text/plain; charset=utf-8`, `Cache-Control: no-store`, and `X-Content-Type-Options: nosniff`. It is a separate fixed model, not a redacted trusted model.

Trusted output requires both typed permissions and an application failure implementing the Kernel, Application Graph, or Dependency Injection exception marker. The existing handler enforces denial before source access. Exact rendered HTML is returned as status `500` with `Content-Type: text/html; charset=utf-8`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, and `Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'`. Unsupported sources, denial, source-access failure, projection failure, rendering failure, or trusted response construction failure falls back once to the generic response without recursive trusted handling.

After preparation, `send()` is attempted exactly once and `close()` exactly once. Success/success returns normally; preparation-or-send failure/success rethrows the exact primary; success/cleanup failure rethrows the exact cleanup failure; simultaneous failures throw `DeliveryCleanupException`, preserving both exact identities and using the primary as `getPrevious()`. Escaped preparation, send, or cleanup failures retire the adapter. Destruction performs no cleanup or I/O, and delivery-local request, permission, response, and failure values are not retained.

Phase 2G deliberately selects no SAPI adapter, listener, runtime server, emitter, protocol framing, request parser, trusted network authorization mechanism, logging, telemetry, incident correlation, DI resolution, Graph traversal, retry, reset, or automatic application shutdown. Phase 2H later supplies only a test-owned concrete delivery and subprocess fixture; none of those production decisions is selected.

## Consequences

Security defaults fail closed and client-controlled request values cannot enable trusted presentation. Persistent workers can reuse an adapter only while it and its application remain healthy; retirement is irreversible. Per delivery, the orchestration performs at most one evidence evaluation, request conversion, application call, presentation attempt, send, and close. Compatibility obligations attach only to the two Public markers; concrete runtime behavior remains Experimental.

## Rejected alternatives

Rejected: adopting Symfony, Laravel, or another runtime-server substrate; error middleware owning outer delivery; request-controlled debug flags; deriving generic output by redacting trusted diagnostics; ambient current-request state; retrying delivery; destructor cleanup; and swallowing simultaneous send/cleanup failures. Each would blur ownership, create hidden authority, or lose exact failure evidence.

## Phase 2H current-state amendment

[ADR 0050](0050-phase-2-runnable-fixture-and-checkpoint.md) adds the minimal
test-owned delivery and runnable black-box proof. It exercises this adapter's
real request, failure-selection, send, and close path; proves bootstrap
short-circuiting, one current-delivery evidence evaluation, generic/trusted
separation, and sequential isolation; and keeps Web Application as the sole
Kernel-scope owner.

The fixture is not package-owned production behavior. Production server
selection, hardened transport authentication, response emission, logging,
telemetry, incident handling, deployment, and broader operational policy remain
later decisions.

## Phase 3F implementation

ADR 0056 now supplies the Internal PHP CGI implementation of the existing
Public delivery port and an Experimental thin entry. Web Runtime still owns
generic/trusted response selection, one send, and one close. PHP CGI depends on
Web Runtime without depending directly on Web Application, and every delivery
keeps its bounded native capture and cleanup state on one fresh object.

## Phase 3G reconciliation

ADR 0057 adds a stateless PHP CGI front controller outside Web Runtime. It
attempts the existing entry once and then the caller's explicit application
shutdown once, preserving either failure or both through diagnostic 1019. Web
Runtime's one-send/one-close ownership is unchanged, and PHP CGI still has no
direct Web Application edge. Uncertain emission or shutdown requires external
process retirement. The operator constraints are documented without a listener,
deployment artifact, or external proof; Phase 3H alone is next.

## Phase 3H outcome

Phase 3H now proves this unchanged Web Runtime boundary through the selected
external PHP-CGI listener. The root-owned harness observes one response and
FastCGI completion, explicit application shutdown, zero active Kernel
operations, sequential process reuse, and external retirement after uncertain
output. It adds no production dependency, listener, client, server, process
manager, or broader transport behavior. Production trusted diagnostics remain
denied, and Phase 3 is closed without a Stable API, 1.0, or hardening claim.
