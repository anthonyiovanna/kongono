# Web Runtime Model

## Purpose and package position

Phase 2G implements `kongono/web-runtime` as the top-level leaf for one outer web delivery. The direct graph is:

```text
concrete server integration
        |
        v
WebDeliveryInterface -> WebRuntimeAdapter -> WebApplication -> HTTP Server -> Routing
                              |                    |
                              |                    v
                              |                  Kernel
                              v
 Diagnostic Activation -> Diagnostic Handler -> HTTP Message
           |                    |
           +---- supported Kernel / Application Graph / DI failures
```

No production package has a reverse dependency on Web Runtime. Web Application still owns the sole Kernel operation scope; Web Runtime neither calls Kernel nor opens a second scope.

## Lifecycle and exact call sequence

One fresh `WebDeliveryInterface` implementation owns one inbound transport boundary. For an accepted call:

1. Refuse before delivery access if the adapter is retired; otherwise consult application retirement once.
2. If bootstrap permission is denied, use local delivery denial. If allowed, evaluate server-controlled evidence exactly once.
3. Convert exactly one request unless evidence evaluation failed.
4. Call `WebApplication::handle()` exactly once unless evidence or conversion failed.
5. Select the unchanged success response, fixed generic response, or eligible trusted response.
6. Call `send()` exactly once.
7. Call `close()` exactly once, including after preparation or send failure.

Permission, request, response, application failure, and cleanup values remain local to that call frame. Sequential and reentrant deliveries therefore do not share current-delivery state. The adapter has no destructor and does not own application shutdown.

Phase 3E implements the PHP CGI sources for the two independent permission
inputs under [ADR 0055](../adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md).
Trusted bootstrap is selected by a literal operator profile and fixed
for the bootstrap lifetime. Current-delivery permission is freshly derived from
exact CGI/1.1 RESPONDER and canonical direct-loopback evidence, with forwarded,
proxied, invalid, or uncertain evidence denied. Neither source constructs
combined eligibility. Phase 3F now implements `CgiWebDelivery`, its bounded
native capture and cleanup, and `CgiWebEntry` delegation into an
already-composed Web Runtime. Web Runtime still constructs and evaluates
combined eligibility in its existing call sequence.

## Trust gates

Trusted presentation requires both `TrustedDeveloperBootstrapPermission::Allowed` and `AdapterControlledDeliveryPermission::Allowed`. The per-delivery evaluator accepts no request input and must use only server-controlled transport evidence. Query strings, routes, paths, URIs, headers, cookies, forms, bodies, sessions, application values, prior deliveries, and ambient mutable state cannot enable trust.

Only failures implementing the Kernel, Application Graph, or Dependency Injection exception marker are supported. Support is checked with `instanceof` before any source accessor. The Diagnostic Handler returns `null` under denial before source access. Any failure while establishing eligibility, reading a supported source, projecting, rendering, or creating the trusted response produces one generic fallback without recursion.

## Response table

| Model | Status | Body | Exact headers |
|---|---:|---|---|
| Generic | 500 | `Internal Server Error\n` | `Content-Type: text/plain; charset=utf-8`; `Cache-Control: no-store`; `X-Content-Type-Options: nosniff` |
| Trusted | 500 | exact presenter HTML bytes | `Content-Type: text/html; charset=utf-8`; `Cache-Control: no-store`; `X-Content-Type-Options: nosniff`; `Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'` |

Neither response adds `Content-Length`; delivery owns framing. Responses are fresh and never cached. Generic output contains no diagnostic, incident, exception, trace, request, version, server, cookie, or redirect data and is not derived from trusted output.

## Send and close matrix

| Preparation/send | Close | Observable result | Adapter state |
|---|---|---|---|
| success | success | `void` | healthy unless application retired |
| failure | success | exact primary throwable | retired |
| success | failure | exact cleanup throwable | retired |
| failure | failure | `DeliveryCleanupException` preserving both | retired |

Mapped evidence, conversion, or application failures are handled if the selected safe response is sent. A later send failure is the escaped primary. A delivery already in progress completes its one send and close attempt even if the application becomes retired; every later call is refused before delivery access.

## Integration obligations and limits

The selected PHP CGI integration supplies bounded request conversion,
server-controlled evidence evaluation, response emission, and deterministic
release of delivery-owned resources through one fresh delivery object. The
external host still owns protocol framing, listening, and process policy. Any
other integration must provide equivalent explicit ownership and must not treat
the PHP interface itself as authorization.

Phase 2G provides production ports only: no SAPI/server/listener/emitter,
request parser, runtime-server dependency, logging, telemetry, incident storage,
authorization framework, DI resolution, Graph traversal, retry, reset, recovery,
or broader cleanup. Phase 2H adds only a root-owned concrete fixture for
acceptance; it is not production package behavior. API stability is Experimental
except `WebDeliveryInterface` and the package exception marker, which are Public.

## Example boundary

```php
$adapter->handle($delivery);
```

The integration constructs both objects under server policy. The adapter does not discover a delivery globally, infer trust from the request, emit headers itself, or shut down the application.

## Phase 2H boundary

Phase 2H supplies the separately authorized minimal fixture and end-to-end
black-box proof. It demonstrates exact request/send/close counts, permission
short-circuiting, fixed failure output, trusted HTML only under both permissions,
body cleanup, and persistent-process isolation. Phase 3F now adds the Internal
PHP CGI implementation of this Public delivery port plus an Experimental entry
that accepts an already-composed adapter. Web Runtime still owns fixed generic
or eligible trusted response selection, one send, and one close. PHP CGI opens
input only after permission evaluation and retains no delivery state after
cleanup. Listener/process integration, broader transport authentication,
Phase 3G now documents operational constraints and adds a stateless PHP CGI
front controller that attempts the existing entry once and a supplied
application shutdown once. Web Runtime's send/close and failure-selection
ownership is unchanged. Simultaneous failure uses 1019; uncertain emission or
shutdown requires external process retirement. PHP CGI still has no direct Web
Application edge, and caller construction/partial-boot cleanup remain outside
it. Phase 3H externally proves the selected path without adding a production
listener, server configuration, process manager, or deployment artifact. See [ADR
0050](../adr/0050-phase-2-runnable-fixture-and-checkpoint.md), [ADR
0056](../adr/0056-php-cgi-production-delivery-and-entry-composition.md), and the
[fixture model](phase-2-runnable-fixture.md).

The root-owned harness observes one response and FastCGI completion, explicit
shutdown, zero active Kernel operations, five-request listener reuse, and
external retirement after uncertain output. Private one-send/one-close and
cleanup-composition details remain package-test evidence. Production trust stays
denied, and Phase 3 closes without Stable API, 1.0, deployment, or hardening
claims.
