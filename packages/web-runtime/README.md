# Kongono Web Runtime

`kongono/web-runtime` is the Experimental, transport-neutral outer-delivery package for Kongono. Install it through the root workspace as `kongono/web-runtime`; its production namespace is `Kongono\Web\Runtime\`.

The intended integration surface is deliberately small:

- `WebRuntimeAdapter` is the Experimental outer orchestrator.
- `WebDeliveryInterface` is the Public per-delivery server-integration port.

Public status does not make a delivery implementation trusted or stable as a concrete runtime. The remaining package declarations are Internal or Experimental. See [ADR 0049](../../docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md) and the [Web Runtime model](../../docs/architecture/web-runtime-model.md).

## Delivery contract

Create one fresh delivery object for one inbound boundary. The adapter evaluates server-controlled trusted-presentation evidence before request conversion, converts exactly one request, calls `WebApplication::handle()` exactly once, sends exactly one selected response, and attempts `close()` exactly once. Bootstrap denial skips evidence evaluation but does not skip normal request handling.

The delivery implementation must keep trusted evidence independent of request, route, URI, headers, cookies, body, session, or other client-controlled values. It owns real authentication, proxy/listener policy, response transmission, framing, and release of delivery-owned resources.

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
