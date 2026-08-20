# Phase 2 Runnable Fixture

## Purpose and boundary

The Phase 2 fixture is deterministic test infrastructure that proves the real
server-side Kongono path is runnable. Its entrypoint is
`tests/BlackBox/Fixture/run.php`. It is outside all thirteen production packages,
has no API-status declaration, accepts no network connection, and is not a
production server, SAPI adapter, framework CLI, deployment entrypoint, or
supported application template.

## Composition and call sequence

```text
fixed CLI grammar
  -> Phase2FixtureRunner
  -> Phase2FixtureApplication
  -> fresh first-party ServerRequest
  -> fresh FixtureDelivery (Public WebDeliveryInterface)
  -> WebRuntimeAdapter
  -> WebApplication (sole Kernel operation scope)
  -> outer middleware -> inner middleware
  -> deterministic routing -> fixed terminal handler
  -> response selection -> FixtureDelivery::send()
  -> FixtureDelivery::close()
  -> detached JSON result with active_operations = 0
```

One real Kernel, Web Application, Web Runtime adapter, and request factory are
booted per process. A fresh request and delivery are created per scenario. Web
Runtime opens no Kernel scope; `WebApplication::handle()` remains the sole owner
of exactly one scope for each accepted application call. Shutdown is explicit
and occurs once after all scenarios succeed.

## Mode truth table

| Mode | Bootstrap permission | Delivery permission | Trusted diagnostic |
|---|---|---|---|
| `production` | Denied | Denied | Generic; delivery evidence is not evaluated |
| `developer-denied` | Allowed | Denied | Generic after one evidence evaluation |
| `developer-trusted` | Allowed | Allowed | Trusted HTML for a supported source only |

Mode is immutable operator-owned fixture setup selected before boot. Request
values cannot change either permission.

## Scenario table

| Scenario | Method | URI | Expected role |
|---|---|---|---|
| `matched-a` | GET | `https://fixture.kongono.test/hello/alpha` | Fixed 200 greeting |
| `matched-b` | GET | `https://fixture.kongono.test/hello/beta` | Same fixed 200 greeting; parameter is not emitted |
| `unmatched` | GET | `https://fixture.kongono.test/missing` | Fixed fixture 404 |
| `generic-failure` | GET | `https://fixture.kongono.test/fail/generic` | Unsupported native failure; fixed generic 500 |
| `diagnostic-failure` | GET | `https://fixture.kongono.test/fail/diagnostic` | Permission-gated trusted diagnostic specimen |

Routes are declared as `fixture.diagnostic_failure`,
`fixture.generic_failure`, and `fixture.greeting`. The greeting handler requires
the reserved `RouteMatch` request attribute and a valid `name` parameter but
never copies the parameter to output. Outer and inner middleware keep trace data
only in the current immutable request and append exact response values:
`outer-before`, `inner-before`, `handler`, `inner-after`, `outer-after`.

## Output schema and limits

Each successful delivery emits one UTF-8 JSON object plus LF. Keys are inserted
in this exact order:

```text
scenario, status, headers, body, permission_evaluations,
request_calls, send_calls, close_calls, active_operations
```

Headers are detached and sorted by case-insensitive ASCII name with an ordinal
tie-break. Header value order remains unchanged. Status is bounded to `100..599`,
headers to 64 names and 32 values per name, body to 16,384 bytes, scenarios to
16 per invocation, and input to the fixed grammar above.

Invalid invocation emits only `Invalid Phase 2 fixture invocation\n` on stderr
and exits `64` without booting. Unexpected execution emits only
`Phase 2 fixture execution failed\n` and exits `70`. Successful execution exits
`0`, writes no stderr, and emits no banner, timing, identifier, trace, path, log,
or whitespace outside the JSON lines.

## Responses, trust, and failure

Matched success returns fixed plain text and the five-value trace. Unmatched
routing returns fixed `Not Found\n`; only middleware after-values are added.
Unsupported native failure always produces the separate generic response:
status 500, `Internal Server Error\n`, `Content-Type: text/plain; charset=utf-8`,
`Cache-Control: no-store`, and `X-Content-Type-Options: nosniff`.

The supported test source uses `KGN-KERNEL-9001`, reserved only for this fixture,
with fixed title, remediation, operation, invariant, and empty context. It is not
in a production enum or catalog. Bootstrap denial skips evidence access;
delivery denial evaluates evidence once; either denial returns generic output.
Both affirmative permissions produce trusted HTML with the existing restrictive
CSP and security headers. URI, route parameter, scenario, CLI mode, native
message, trace, local path, secret, credential, header, cookie, query, body, and
session data never enter the trusted source.

## Cleanup and persistent-runtime isolation

`FixtureDelivery` owns the request body and sent response body until `close()`.
Every accepted scenario has exactly one request call, send attempt, and close
attempt. Cleanup closes both bodies, clears retained delivery objects, and marks
the delivery closed before a detached result is read. There is no destructor
cleanup, retry, reset, global current request, static permission, route cache,
response singleton, thread/fiber local, clock, randomness, filesystem, network,
logging, or telemetry.

The exact sequence `matched-a`, `unmatched`, `matched-b`, `generic-failure`,
`matched-a` runs through one application. Every result independently reports
zero active operations and fresh counts. The first and last result are
byte-identical, and route, trace, permission, response, and failure data do not
leak between deliveries. Independent subprocesses also produce byte-identical
output for identical invocations.

## Running and testing

```powershell
php tests/BlackBox/Fixture/run.php production matched-a unmatched matched-b generic-failure matched-a
php tests/BlackBox/Fixture/run.php developer-trusted diagnostic-failure
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/BlackBox
```

Root-owned tests use `proc_open()` with an array command, `PHP_BINARY`, shell
bypass, and separate pipes. They observe only process output and exit status;
they do not instantiate production internals or use reflection/source inspection
as behavioral proof.

## Runnable fixture versus production readiness

Phase 2 is complete when this bounded external proof and its checkpoint pass.
Kongono is then fixture-runnable, not production-ready. Production request
parsing, listener/server selection, emission, TLS/proxy trust, authentication,
authorization, deployment, logging, telemetry, incident response, cancellation,
and operational hardening remain architect-owned future work. The milestone is
not the full framework, a 1.0 release, or a feature-parity claim.

Related: [ADR 0050](../adr/0050-phase-2-runnable-fixture-and-checkpoint.md),
[Web Runtime model](web-runtime-model.md), [Web Application model](web-application-model.md),
and [Phase 2 roadmap](phase-2-roadmap.md).
