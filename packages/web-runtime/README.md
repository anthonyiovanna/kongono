# Kongono Web Runtime

`kongono/web-runtime` is the Experimental, transport-neutral outer-delivery package for Kongono. Install it through the root workspace as `kongono/web-runtime`; its production namespace is `Kongono\Web\Runtime\`.

The intended integration surface is deliberately small:

- `WebRuntimeAdapter` is the Experimental outer orchestrator.
- `WebDeliveryInterface` is the Public per-delivery server-integration port.

Public status does not make a delivery implementation trusted or stable as a concrete runtime. The remaining package declarations are Internal or Experimental. See [ADR 0049](../../docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md) and the [Web Runtime model](../../docs/architecture/web-runtime-model.md).

## Delivery contract

Create one fresh delivery object for one inbound boundary. The adapter evaluates server-controlled trusted-presentation evidence before request conversion, converts exactly one request, calls `WebApplication::handle()` exactly once, sends exactly one selected response, and attempts `close()` exactly once. Bootstrap denial skips evidence evaluation but does not skip normal request handling.

The delivery implementation must keep trusted evidence independent of request, route, URI, headers, cookies, body, session, or other client-controlled values. It owns real authentication, proxy/listener policy, response transmission, framing, and release of delivery-owned resources.

Phase 3E PHP CGI supplies only the two independent permission sources selected
by [ADR 0055](../../docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md): a literal
operator profile validated against fixed process prerequisites and a total
current-delivery evaluator that allows exact canonical direct loopback only when
all forwarded markers are absent. Public/production bootstrap remains denied.
No concrete `WebDeliveryInterface`, ambient delivery capture, Web Runtime
composition, production entrypoint, or authentication mechanism is added. Phase
3F alone is next.

## Failure delivery

Generic failures produce status `500`, body `Internal Server Error\n`, and only `Content-Type: text/plain; charset=utf-8`, `Cache-Control: no-store`, and `X-Content-Type-Options: nosniff`. This fixed production model is separate from trusted diagnostics.

Trusted HTML is possible only when bootstrap and per-delivery permissions are both `Allowed` and the application failure implements the Kernel, Application Graph, or Dependency Injection exception marker. The response adds the fixed restrictive CSP documented in the architecture model. Denial, unsupported sources, or any presentation failure falls back once to the generic model without recursion.

## Cleanup and retirement

Successful preparation/send is followed by one close attempt. A send/preparation failure is rethrown exactly if close succeeds; a close-only failure is rethrown exactly; simultaneous failures become `DeliveryCleanupException` preserving both objects. Any escaped preparation/send/close failure retires the adapter. A retired application also retires it, and later deliveries are refused before delivery access.

Phase 2G supplies no production SAPI adapter, runtime server, listener, emitter,
logging, telemetry, incident store, authorization mechanism, or shutdown
automation. Phase 2H's root-owned fixture implements the Public delivery port
only for external acceptance and proves exact permission, failure, send, close,
body cleanup, and sequential-isolation behavior. It is not a production runtime
or package API. See [ADR 0050](../../docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md)
and the [fixture model](../../docs/architecture/phase-2-runnable-fixture.md).

Phase 3F now supplies `CgiWebDelivery`, the Internal production PHP CGI
implementation of this package's Public delivery port, and Experimental
`CgiWebEntry` for one call into an already-composed adapter. PHP CGI depends on
Web Runtime; it does not depend directly on Web Application. The adapter still
owns fixed generic or eligible trusted response selection, one send, and one
close. The concrete delivery projects bounded native evidence, opens input only
after permission evaluation, and releases all one-delivery resources explicitly.
Phase 3G adds a stateless PHP CGI front controller above the existing entry. It
attempts entry once and then one caller-supplied application shutdown, preserving
simultaneous failure through diagnostic 1019. Web Runtime's failure selection,
one send, and one close remain unchanged. PHP CGI has no direct Web Application
edge; application composition and partial-boot cleanup remain caller-owned.
`Production` trust remains denied. Uncertain emission or shutdown requires
external process retirement. Phase 3H's root-owned harness proves the selected
synchronous PHP-CGI FastCGI RESPONDER profile externally without adding a
dependency from this package. It is not a production or supported server/client.
Process, proxy, TLS, credentials, logs, monitoring, incidents, and deployment
ownership remain external. Phase 3 is complete, is not the full framework,
Stable API, 1.0, universal SAPI support, deployment automation, or
production-hardening certification, and starts no next phase automatically. See [ADR
0056](../../docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md)
and [ADR 0057](../../docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md).
