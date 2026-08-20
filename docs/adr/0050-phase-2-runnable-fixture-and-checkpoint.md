# ADR 0050: Phase 2 Runnable Fixture and Checkpoint

- Status: Accepted
- Date: 2026-08-20

## Context

Phase 2A through 2G established thirteen acyclic production packages covering
first-party HTTP messages, deterministic routing, explicit PSR-15 middleware,
Web Application composition, one Kernel operation per application call, and
outer Web Runtime delivery and failure handling. Those packages deliberately
selected no concrete server integration. Phase 2 needed an external proof that
the accepted path composes and runs without turning test infrastructure into a
fourteenth production package or prematurely selecting a production server.

## Decision

Phase 2 closes with a test-owned fixture at
`tests/BlackBox/Fixture/run.php`. It runs as a fresh PHP CLI subprocess and uses
the real first-party Kernel, Web Application, Routing, HTTP Server, HTTP Message,
Web Runtime, diagnostic activation, projection, rendering, and handler
boundaries. It does not mock or replace their ownership.

The fixed invocation is:

```text
php tests/BlackBox/Fixture/run.php <mode> <scenario> [<scenario> ...]
```

Modes are `production`, `developer-denied`, and `developer-trusted`. Scenarios
are `matched-a`, `matched-b`, `unmatched`, `generic-failure`, and
`diagnostic-failure`. One to sixteen scenarios are accepted. No arbitrary URI,
header, body, cookie, file, query, permission, serialized value, or JSON request
is accepted.

Invalid input writes only `Invalid Phase 2 fixture invocation\n` to standard
error and exits `64`. An unexpected execution failure writes only
`Phase 2 fixture execution failed\n` and exits `70`. Successful scenarios emit
one newline-delimited JSON object each with keys, in order: `scenario`, `status`,
`headers`, `body`, `permission_evaluations`, `request_calls`, `send_calls`,
`close_calls`, and `active_operations`.

Response snapshots allow status `100..599`, at most 64 headers, at most 32
values per header, and at most 16,384 body bytes. Header names are canonicalized
case-insensitively with an ordinal tie-break; value order is preserved. Every
delivery owns a fresh request and typed server-owned permission, performs one
request/send/close cycle, closes request and response bodies, and exposes only a
detached primitive result after cleanup.

Three routes exercise fixed greeting success and native/diagnostic failures.
Declaration-ordered `outer` and `inner` middleware prove before/after order.
Unmatched routing returns the fixed fixture 404. Native failure remains the
fixed generic production 500 even with both permissions. Supported diagnostic
failure remains generic under either denial and renders trusted HTML only when
both permissions are affirmative. `KGN-KERNEL-9001` is reserved solely as this
test-owned projector specimen and is not a production diagnostic assignment.

The fixture mode is operator-owned test setup chosen before application boot.
It is not request data, transport authentication, production authorization, or
a client-controlled debug switch. Production Web Runtime continues to require
independent bootstrap and current-delivery permission values.

Root-owned black-box tests use `proc_open()` with an array command and observe
only exit status, stdout, and stderr. They prove deterministic responses,
failure selection, permission short-circuiting, exact cleanup, sequential
same-process isolation, zero active Kernel operations after every delivery, and
byte-identical independent subprocess output.

The Phase 2 checkpoint commits the complete accepted post-Phase-1H worktree,
the fixture, its documentation, and historical caller records once with subject
`Establish Kongono runnable web framework milestone through Phase 2H`.

## Consequences

Kongono is runnable as a bounded deterministic server-side fixture. The fixture
provides inspectable framework integration evidence without network, clock,
randomness, repository writes, or an external listener. It adds constant bounded
request/response work and holds no delivery state between operations.

This does not select a SAPI adapter, listener, socket server, runtime worker,
emitter, deployment surface, proxy or TLS policy, production authentication or
authorization, logging, telemetry, incident handling, cancellation, or broader
resource cleanup. It is not a framework CLI, supported application template,
production-ready system, 1.0 release, or claim of Laravel/Symfony feature parity.

Fixture classes carry no `#[Api]` metadata or compatibility promise. Production
packages, projector grammar, dependencies, and package boundaries remain
unchanged. Later production integration and every Phase 3 decision require
separate architect authority.

## Rejected alternatives

- A fourteenth fixture/server/emitter package would incorrectly make acceptance
  infrastructure a production boundary.
- SAPI globals, a network listener, or arbitrary raw input would select
  production integration and input policy prematurely.
- Request-derived debug flags would violate the two-permission trust boundary.
- Ambient request, permission, route, response, or operation state would violate
  persistent-runtime isolation.
- Mock-only component proof would not establish that the real package path runs.
- Exception stringification would expose unsupported and request-derived data.
- Broader server, deployment, authentication, data, UI, telemetry, agent, or
  Phase 3 work would exceed the milestone.

## Related material

- [Phase 2 runnable fixture model](../architecture/phase-2-runnable-fixture.md)
- [Phase 2 roadmap](../architecture/phase-2-roadmap.md)
- [ADR 0043](0043-phase-2-runnable-web-framework-milestone.md)
- [ADR 0049](0049-outer-web-runtime-and-safe-failure-delivery.md)
- [Web Runtime model](../architecture/web-runtime-model.md)
- [Security policy](../../SECURITY.md)
