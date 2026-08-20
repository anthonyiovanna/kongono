# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2H
## Minimal Runnable Fixture, External Black-Box Proof, and Phase 2 Checkpoint

**Directive type:** bounded fixture implementation, black-box acceptance, documentation reconciliation, and exact checkpoint

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H

**Depends on:** accepted Phase 2A-2G architecture and implementation, including completed Phase 2G.2 validation correction

---

## 1. Mission

Complete Kongono's fixed Phase 2 A-H milestone by adding only the minimal,
deterministic fixture needed to exercise the existing thirteen-package web path
through its Public outer delivery port.

Phase 2H must:

1. create a test-owned, CLI-runnable fixture composition root outside every
   production package;
2. implement one fresh concrete `WebDeliveryInterface` fixture per delivery;
3. convert fixed bounded fixture scenarios into explicit first-party PSR-7
   server requests;
4. exercise the real Kernel, Web Application, routing, middleware, Web Runtime,
   generic failure, and trusted diagnostic paths without replacing or mocking
   those production boundaries;
5. emit bounded newline-delimited JSON fixture results through a fresh PHP
   subprocess, without an external network listener;
6. prove the full Phase 2 runnable definition exclusively from root-owned
   black-box tests outside the package test suites;
7. prove sequential operations in one process do not leak request, response,
   permission, route-match, middleware, failure, or Kernel-operation state;
8. reconcile the Phase 2 roadmap and architecture documentation from “planned”
   to the exact implemented fixture milestone;
9. preserve the thirteen production packages and every accepted Phase 2A-2G
   production contract unchanged; and
10. stage the complete accepted post-Phase-1H worktree, create the exact Phase 2
    checkpoint commit, run post-commit validation, and leave only the populated
    Phase 2H report outside `HEAD`.

This phase makes Kongono **runnable as a deterministic server-side web fixture
and black-box framework milestone**. It does not make Kongono production-ready,
select a production server, or claim the broader framework is complete.

Do not add another package. Do not implement a general CLI, HTTP listener,
socket server, SAPI adapter, PHP-FPM integration, FrankenPHP worker,
RoadRunner/OpenSwoole adapter, production emitter, deployment system,
configuration framework, authentication, authorization, ORM, database,
templates, frontend, telemetry, agents, extensions, or any Phase 3 capability.

---

## 2. Locked doctrine and ownership

Preserve all accepted Kongono doctrine, including:

- PHP 8.5, strict types, PER-CS, max-level PHPStan with strict rules, strict
  PHPUnit, locked advisory audit, and empty Composer plugin authorization;
- neutral API Metadata ownership and the exact acyclic thirteen-package graph;
- Public, Experimental, and Internal status meaning without promotion by
  implication;
- transport-neutral Kernel lifecycle and instance-local operation ownership;
- exactly one Kernel operation scope owned by `WebApplication::handle()` for one
  accepted application call;
- no second Kernel scope in Web Runtime or the fixture delivery;
- no ambient global/static/singleton/container-current/thread-local/fiber-local
  request, response, route, permission, principal, exception, or operation;
- deterministic byte-exact routing and explicit declaration-ordered PSR-15
  middleware;
- response construction remaining separate from delivery;
- generic production failure output remaining fixed and distinct from trusted
  developer HTML;
- both independent typed permissions being affirmative before trusted source
  access;
- bootstrap denial skipping delivery-evidence evaluation while normal work
  continues;
- current-delivery evidence being server-owned and request-independent;
- unsupported, denied, ambiguous, or failed trusted paths falling back once to
  generic output without recursive presentation;
- one `send()` attempt and one `close()` attempt per accepted delivery;
- fail-closed retirement and exact failure identity preservation;
- no secret, credential, request value, path parameter, query value, header,
  cookie, body, session, token, or client flag enabling trusted diagnostics;
- test fixtures never becoming production APIs or package-owned behavior; and
- stop-and-ask behavior whenever this directive does not select a decision.

Phase 2H selects only the deterministic fixture protocol and checkpoint defined
here. Production listener, SAPI parsing, proxy trust, TLS/operator identity,
audience policy, incident recording, logging, telemetry, cancellation, broader
resource cleanup, deployment, and operational hardening remain open.

---

## 3. Independent directive authentication

Before broad validation, a temporary file, repository mutation, staging, or a
Git write, compare this directive with the byte count and SHA-256 supplied
independently by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_codex_directive.md
```

The local file is not self-authenticating. If either independently supplied
value is absent or differs, stop before quality and mutation.

---

## 4. Mandatory authority reading

Read these files completely before mutation:

```text
AGENTS.md
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/adr/0001-kongono-identity.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0006-persistent-runtime-first.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0045-first-party-http-message-and-factory-implementation.md
docs/adr/0046-deterministic-route-declaration-and-matching.md
docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/architecture/http-message-model.md
docs/architecture/http-server-model.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/http-message/README.md
packages/http-server/README.md
packages/kernel/README.md
packages/routing/README.md
packages/web-application/README.md
packages/web-runtime/README.md
docs/dev_plan/kongono_phase_2F_codex_directive.md
docs/task reports/Phase_2F.md
docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md
docs/task reports/Phase_2F_1_correction.md
docs/dev_plan/kongono_phase_2G_codex_directive.md
docs/task reports/Phase_2G.md
docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
docs/task reports/Phase_2G_1_correction.md
docs/dev_plan/kongono_phase_2G_2_codex_directive_correction.md
docs/task reports/Phase_2G_2_correction.md
docs/dev_plan/kongono_phase_2H_codex_directive.md
```

Historical directives and reports are immutable evidence. A zero-byte accepted
report is intentional historical evidence, not permission to populate or
delete it.

---

## 5. Caller records and accepted identities

### 5.1 Phase 2H report placeholder

The caller must provide:

```text
docs/task reports/Phase_2H.md
```

At entry it must be an ordinary, untracked, unstaged file absent from `HEAD`,
zero bytes, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte through implementation, every pre-commit gate, staging,
commit creation, and every post-commit non-report gate. Populate it only after
all other acceptance criteria pass. Never stage it.

### 5.2 Immediate accepted records

Verify these exact immutable identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2F_codex_directive.md` | 53418 | `5D25D4FA8A34F410548836B673AD3F77845F509DD45DB0039C1858572DF58EB4` |
| `docs/task reports/Phase_2F.md` | 25844 | `FD59AC570E5B2115DCBDB1A30C787D100EACC2B4A903748FB94CC7A2FFDDFF29` |
| `docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md` | 16390 | `9995BDB628FD7DC65F4527DD8F5611EB7DE90BC20D5D59562A56A24C3DFE5EBA` |
| `docs/task reports/Phase_2F_1_correction.md` | 12153 | `141154168F1776B0AD0234391E4E0ECE0118F6F2E3FF33346C03BBCA8B449057` |
| `docs/dev_plan/kongono_phase_2G_codex_directive.md` | 54564 | `F59EFB941D96C5052C45494D1AFC161F55DDC7EF7900CFBD500BAEC8DCBADAFE` |
| `docs/task reports/Phase_2G.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md` | 15687 | `40EBB8C5C425B3504B517823E56146FEF19F399413DBC98A7ED5F691E1C3E1CA` |
| `docs/task reports/Phase_2G_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_2G_2_codex_directive_correction.md` | 21896 | `EE2181EDEE4EF2DDF8B89505CC942D0B83D5D4F8176E305E190FA0653D7AFFDB` |
| `docs/task reports/Phase_2G_2_correction.md` | 13565 | `3CC4F789D27B82AB784757D912F42F1ABC7C4FC7F86088DADB44C183E18B3C20` |

Verify all earlier `docs/dev_plan/` and `docs/task reports/` records retain
their entry identities and remain ordinary files. Do not normalize, rewrite,
truncate, or “complete” any historical record.

---

## 6. Exact starting state and authenticated worktree snapshot

### 6.1 State before new Phase 2H caller records

The accepted post-Phase-2G.2 state is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        147
total status paths     174
staged paths           0
Git-visible paths      505
package directories    13
package manifests      13
production PHP files   100
test PHP files         118
style-covered PHP      218
diagnostic IDs         113 unique
locked production      17
locked development     66
normal PHP memory      128M
PHP_INI_SCAN_DIR       absent
```

Tracked diff evidence is:

```text
files changed          27
insertions             1952
deletions              83
binary diff bytes      168417
binary diff SHA-256    1E74FEC61E4D149C911114542104AC9A8FFF7F9E9ABBBBB0E39191448B183B19
```

The complete 505-path Git-visible snapshot is authenticated by the following
manifest algorithm:

1. enumerate `git ls-files --cached --others --exclude-standard`;
2. normalize separators to `/`;
3. sort paths with ordinal bytewise string comparison;
4. for each path append UTF-8 without BOM:
   `path + NUL + invariant-decimal-byte-length + NUL + uppercase-SHA-256 + LF`;
5. hash the complete manifest bytes with SHA-256.

The exact result is:

```text
records                 505
manifest bytes          62292
manifest SHA-256        A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
aggregate file bytes    5395934
first ordinal path      .editorconfig
last ordinal path       tests/Tooling/EnvironmentTest.php
```

When the two Phase 2H caller records are already present, exclude exactly their
paths to reconstruct and verify this accepted snapshot. Do not adopt a locally
observed different digest as authority.

### 6.2 Expected entry state after caller setup

After the caller adds only the authenticated Phase 2H directive and zero-byte
report placeholder, require:

```text
tracked modifications  27
untracked paths        149
total status paths     176
staged paths           0
Git-visible paths      507
tracked files          358
```

The only two additions beyond the authenticated 505-path snapshot are:

```text
docs/dev_plan/kongono_phase_2H_codex_directive.md
docs/task reports/Phase_2H.md
```

Require one local branch, no remotes, no tags, and no unexpected refs.
Environment-managed `refs/codex/turn-diffs/**` are exempt from count stability;
enumerate them separately and do not mutate them.

All Git-visible paths must be ordinary non-reparse files with only the default
data stream. Tracked modes must be `100644`; new caller files must be normal
files intended to stage as `100644`. Require strict UTF-8/LF/final-LF/no-
trailing-whitespace/no-NUL for non-empty text records; the intentional zero-byte
historical reports and active placeholder are exempt only from the final-LF
requirement.

Stop on any mismatch. Do not repair, restore, clean, stage, regenerate, or infer
a new baseline.

---

## 7. Phase 2H ownership boundary

### 7.1 No fourteenth package

Keep exactly thirteen first-party package directories and manifests. Create no
`packages/fixture`, `packages/web-server`, `packages/emitter`, or other package.

All Phase 2H PHP belongs under:

```text
tests/BlackBox/
```

with namespace:

```text
Kongono\Tests\BlackBox\
```

The existing root dev-autoload mapping `Kongono\Tests\ => tests/` owns these
classes. Do not add or change Composer dependencies, repositories, scripts,
autoload mappings, manifests, or `composer.lock`.

Fixture types are test-owned and have no `#[Api]` attribute or compatibility
promise. They must never be imported by production source or package tests.

### 7.2 Fixture-only runnable meaning

The executable fixture entry is:

```text
tests/BlackBox/Fixture/run.php
```

It is a deterministic CLI subprocess harness for black-box acceptance. It is
not a Kongono CLI feature, production HTTP server, listener, SAPI adapter,
deployment entrypoint, or supported application template.

“Runnable after Phase 2” means the entire accepted server-side framework path
can be booted and exercised from a fresh process with bounded fixture input and
observable status, headers, body, delivery cleanup, and operation-count output.
It does not mean an external network client can connect to Kongono.

---

## 8. Exact fixture inventory

Create exactly these twelve PHP files:

```text
tests/BlackBox/Fixture/DiagnosticFailureHandler.php
tests/BlackBox/Fixture/FixtureDelivery.php
tests/BlackBox/Fixture/FixtureKernelDiagnosticException.php
tests/BlackBox/Fixture/FixtureResponse.php
tests/BlackBox/Fixture/GenericFailureHandler.php
tests/BlackBox/Fixture/GreetingHandler.php
tests/BlackBox/Fixture/NotFoundHandler.php
tests/BlackBox/Fixture/Phase2FixtureApplication.php
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Fixture/TraceMiddleware.php
tests/BlackBox/Fixture/run.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

Every named class must be final, PSR-4 compliant, and declared one class per
matching file. Use no trait, enum, alias, eval, generated declaration, anonymous
fixture class, namespace overlap, global mutable value, or static request state.
`run.php` is the only non-class PHP file.

---

## 9. Concrete fixture delivery contract

### 9.1 `FixtureResponse`

`FixtureResponse` is final readonly and owns a detached primitive snapshot:

```php
/** @param array<string, list<string>> $headers */
public function __construct(int $statusCode, array $headers, string $body);
public function statusCode(): int;
/** @return array<string, list<string>> */
public function headers(): array;
public function body(): string;
```

Validate status `100..599`, at most 64 headers, at most 32 values per header,
and total body length at most 16,384 bytes. Header names and values are supplied
only by already-validated PSR-7 responses. Detach both array levels, sort header
names with case-insensitive ASCII comparison and an ordinal tie-break, preserve
value order, and return fresh detached arrays. Do not serialize an exception,
request, route parameter, object, resource, or arbitrary metadata.

### 9.2 `FixtureDelivery`

`FixtureDelivery` is final and implements exactly
`WebDeliveryInterface`. Its constructor accepts exactly:

```php
public function __construct(
    ServerRequestInterface $request,
    AdapterControlledDeliveryPermission $permission,
);
```

It retains only the current request, current server-owned permission, eventual
response/body ownership, eventual `FixtureResponse`, four integer call counts,
and a closed flag. It exposes the interface methods plus exactly:

```php
public function result(): FixtureResponse;
public function permissionEvaluationCount(): int;
public function requestCount(): int;
public function sendCount(): int;
public function closeCount(): int;
public function isClosed(): bool;
```

Required behavior:

- permission evaluation returns the exact constructor value and increments its
  count;
- `request()` returns the exact constructor request and increments its count;
- `send()` accepts exactly one response, increments once, materializes its
  status, detached headers, and complete body into `FixtureResponse`, and
  retains only the body stream needed for exact cleanup;
- response materialization rejects a body over 16,384 bytes before exposing a
  partial result;
- `close()` increments exactly once, closes the request body and any sent
  response body exactly once, clears retained delivery objects, and marks the
  delivery closed;
- a duplicate send/close, use after close, missing result, partial read, invalid
  count, or other fixture invariant throws a fixed test-only `RuntimeException`
  with no caller value in its message;
- no destructor performs cleanup; and
- no call accesses SAPI globals, environment variables, network, filesystem,
  time, randomness, static state, logging, or telemetry.

The runner may obtain `result()` only after `WebRuntimeAdapter::handle()` has
returned and `close()` is proven complete.

---

## 10. Fixture application composition

### 10.1 Fixed handlers

All handlers implement `RequestHandlerInterface`.

`GreetingHandler` must require the reserved
`RoutingRequestHandler::ROUTE_MATCH_ATTRIBUTE`, prove it is a `RouteMatch` for
route `fixture.greeting`, and return a fresh status `200` response with:

```text
Content-Type: text/plain; charset=utf-8
Cache-Control: no-store
X-Content-Type-Options: nosniff
X-Kongono-Fixture-Trace: <values built by Section 10.2>
body: Kongono Phase 2 fixture\n
```

The path parameter must be present and valid but must not be copied into the
response, diagnostics, logs, static state, or another operation.

`GenericFailureHandler` throws one fresh native `RuntimeException` with fixed
message `fixture-generic-failure-sentinel`. It implements no Kongono exception
marker.

`DiagnosticFailureHandler` throws one fresh
`FixtureKernelDiagnosticException`.

`NotFoundHandler` returns a fresh status `404` response with exactly the first
three fixed security/content headers above and body:

```text
Not Found\n
```

### 10.2 Ordered middleware

`TraceMiddleware` implements `MiddlewareInterface`. Its constructor accepts
only fixed token `outer` or `inner` and rejects any other value with fixed
test-only prose.

It stores trace state only in the current immutable request attribute named by
one class constant. The incoming attribute must be absent or a list containing
only the fixed trace tokens. It appends `<token>-before`, delegates once, then
adds `<token>-after` to the response's `X-Kongono-Fixture-Trace` header.

With middleware passed to `WebApplication::boot()` as `outer`, then `inner`, a
matched response must expose exactly:

```text
outer-before
inner-before
handler
inner-after
outer-after
```

No trace list, handler, request, response, route match, or failure may be kept
in middleware state after `process()` returns.

### 10.3 Test-only supported diagnostic source

`FixtureKernelDiagnosticException` is final, extends `RuntimeException`, and
implements the Public Kernel exception marker only to exercise the already-
accepted interoperability surface.

It uses a zero-argument constructor, fixed native message, one eagerly built
empty-field `DiagnosticContext`, and these fixed values:

```text
diagnostic ID           FIXTURE-KERNEL-0001
title                   Phase 2 fixture diagnostic failure
remediation             Inspect the deterministic Phase 2 fixture
operation               Run Phase 2 fixture diagnostic route
violated invariant      Trusted fixture output requires both typed permissions
```

This test-only identifier is not a production `KGN-*` assignment and must not
be added to the runtime diagnostic catalog. No request, route parameter, CLI
argument, exception message, trace, file path, or environment value enters its
diagnostic context.

### 10.4 Exact composition root

`Phase2FixtureApplication` is final and owns one real `Kernel`, one real
`WebApplication`, one real `WebRuntimeAdapter`, and one
`ServerRequestFactory`. It exposes exactly:

```php
public static function boot(
    TrustedDeveloperBootstrapPermission $bootstrapPermission,
): self;

public function deliver(
    string $method,
    string $uri,
    AdapterControlledDeliveryPermission $deliveryPermission,
): FixtureDelivery;

public function activeOperationCount(): int;
public function shutdown(): void;
```

`boot()` creates exactly these routes:

| Name | Method | Template | Handler |
|---|---|---|---|
| `fixture.diagnostic_failure` | `GET` | `/fail/diagnostic` | `DiagnosticFailureHandler` |
| `fixture.generic_failure` | `GET` | `/fail/generic` | `GenericFailureHandler` |
| `fixture.greeting` | `GET` | `/hello/{name}` | `GreetingHandler` |

It creates the fixed unmatched handler, passes the complete canonical handler
map, passes `outer` then `inner` middleware, and calls `WebApplication::boot()`
exactly once with the real Kernel. It then constructs one `WebRuntimeAdapter`
with the supplied typed bootstrap permission.

`deliver()` constructs a fresh first-party server request from only the supplied
method and URI with empty server parameters, creates one fresh
`FixtureDelivery`, calls the real runtime adapter exactly once, and returns that
delivery only after adapter completion. It retains no delivery-specific value.

`activeOperationCount()` delegates only to the owned real Kernel.
`shutdown()` delegates exactly once to `WebApplication::shutdown()`. There is no
automatic destructor shutdown, retry, reset, recovery, container, Graph, DI,
configuration, or service lookup.

---

## 11. Deterministic runnable protocol

### 11.1 Entry and argument grammar

Run the fixture as:

```powershell
php tests/BlackBox/Fixture/run.php <mode> <scenario> [<scenario> ...]
```

Require PHP CLI SAPI. Accept exactly one mode:

| Mode | Bootstrap permission | Delivery permission |
|---|---|---|
| `production` | `Denied` | `Denied` |
| `developer-denied` | `Allowed` | `Denied` |
| `developer-trusted` | `Allowed` | `Allowed` |

The mode is fixture/operator setup, not request data or a production security
mechanism. It must be selected before application boot and remain immutable for
that process.

Require between 1 and 16 scenario tokens. Accept exactly:

| Scenario | Method | URI |
|---|---|---|
| `matched-a` | `GET` | `https://fixture.kongono.test/hello/alpha` |
| `matched-b` | `GET` | `https://fixture.kongono.test/hello/beta` |
| `unmatched` | `GET` | `https://fixture.kongono.test/missing` |
| `generic-failure` | `GET` | `https://fixture.kongono.test/fail/generic` |
| `diagnostic-failure` | `GET` | `https://fixture.kongono.test/fail/diagnostic` |

Do not accept raw headers, bodies, cookies, query strings, files, environment
configuration, debug flags, arbitrary routes, permission names, serialized
objects, or JSON input.

Invalid SAPI, mode, count, or scenario writes only:

```text
Invalid Phase 2 fixture invocation\n
```

to standard error, writes nothing to standard output, boots no application, and
returns exit code `64`.

### 11.2 Output envelope

For each valid scenario, process it in declaration order through the same
long-lived fixture application and runtime adapter, using a fresh request and
fresh delivery. After successful adapter return and cleanup, write exactly one
UTF-8 JSON object plus LF to standard output.

Use `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR` and
insert keys in this exact order:

```text
scenario
status
headers
body
permission_evaluations
request_calls
send_calls
close_calls
active_operations
```

`headers` is the detached canonical `FixtureResponse` map. The four call-count
fields must reflect only that fresh delivery. `active_operations` is read from
the real Kernel after delivery completion and must be `0`.

After all scenarios, shut down the real Web Application exactly once and exit
`0`. Produce no banner, timing, random ID, stack trace, path, warning, log,
telemetry, or extra whitespace.

Any unexpected boot, delivery, serialization, output, or shutdown failure must
write only:

```text
Phase 2 fixture execution failed\n
```

to standard error, must not stringify the exception, must attempt no retry or
trusted fallback beyond the production adapter behavior, and returns exit code
`70`. Do not claim recoverability after an uncertain failure.

`Phase2FixtureRunner` owns this exact protocol. `run.php` contains only strict
bootstrap code: require the root Composer autoloader by an exact path derived
from its own directory, construct the runner, pass the CLI arguments and exact
standard streams, and exit with the returned code.

---

## 12. Exact response and trust expectations

Black-box proof must establish:

### 12.1 Matched success

- status `200`;
- fixed plain-text body;
- exact five-value middleware trace order;
- one request, one send, one close;
- in `production` mode, zero permission evaluations because bootstrap denial
  short-circuits evidence;
- active operations `0` after delivery.

### 12.2 Unmatched path

- status `404` and exact `Not Found\n` body;
- no route-specific response content;
- deterministic middleware wrapping still occurs;
- exact delivery counts and active operations `0`.

### 12.3 Unsupported generic failure

Even in `developer-trusted` mode, native `RuntimeException` is unsupported and
must produce the fixed generic response:

```text
status        500
Content-Type  text/plain; charset=utf-8
Cache-Control no-store
X-Content-Type-Options nosniff
body          Internal Server Error\n
```

The sentinel, class, trace, and request data must be absent from all emitted
fields.

### 12.4 Supported diagnostic with bootstrap denial

In `production` mode, the supported fixture diagnostic still produces the
fixed generic response, with zero permission evaluations. Trusted source data
must not appear.

### 12.5 Supported diagnostic with delivery denial

In `developer-denied` mode, evidence is evaluated exactly once, permission is
denied, and the response remains exactly generic. There is no trusted fallback.

### 12.6 Supported diagnostic with both permissions

In `developer-trusted` mode, evidence is evaluated exactly once and the fixed
status-500 trusted HTML response must contain the test-only diagnostic ID,
title, remediation, operation, and invariant. It must include the exact trusted
security headers already selected by Phase 2G. It must not contain a request
URI, route parameter, CLI mode/token, native trace, local path, or secret.

### 12.7 Sequential isolation

One fresh process must handle at least this exact sequence with one application:

```text
matched-a
unmatched
matched-b
generic-failure
matched-a
```

Assert five output lines in order. Every delivery has independent `1/1/1`
request/send/close counts, expected permission count, and zero active Kernel
operations. The first and final matched results are byte-identical except for
their fixed `scenario` key. No route match, trace value, response header/body,
failure, permission, or prior scenario appears in the next result.

---

## 13. Required external black-box tests

`Phase2RunnableWebFrameworkTest` must use `proc_open()` with an array command,
`PHP_BINARY`, `bypass_shell`, and separate pipes. It must invoke only the
runnable `run.php` boundary and observe exit code, stdout, and stderr. It must
not instantiate package internals, use reflection, read source text, access
PHPUnit caches as behavioral proof, or reach into fixture object state.

Include focused tests for at least:

1. invalid invocation fails fixed and does not boot;
2. matched success and exact middleware order;
3. deterministic unmatched response;
4. native application failure stays generic even with both permissions;
5. supported diagnostic stays generic under bootstrap denial and skips
   evidence evaluation;
6. supported diagnostic stays generic under delivery denial;
7. supported diagnostic renders trusted HTML only when both permissions are
   affirmative;
8. request/path/scenario values never enable or enter trusted output;
9. the exact five-operation sequential isolation sequence;
10. success and failure deliveries close exactly once and leave zero active
    Kernel operations; and
11. two independent subprocesses produce byte-identical output for identical
    invocations.

Decode each JSON line with `JSON_THROW_ON_ERROR`, assert exact key order and
closed schemas, reject extra stdout/stderr, and compare exact headers and bodies.
Tests must remain deterministic, parallel-safe, network-free, clock-free,
randomness-free, and independent of repository write permission.

Do not weaken PHPUnit strictness or skip on Windows. Use portable PHP process
APIs and exact repository-relative paths.

---

## 14. Documentation and ADR requirements

### 14.1 New ADR 0050

Create:

```text
docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md
```

Use `Status: Accepted` and `Date: 2026-08-20`. Record:

- why a test-owned subprocess fixture, rather than a fourteenth production
  package or production server, closes Phase 2;
- the exact fixture entry, mode/scenario grammar, result envelope, bounds, and
  exit behavior;
- use of the thirteen real packages and Public outer delivery port;
- no mock replacement of Kernel/Web Application/Web Runtime ownership;
- exact routing, middleware, generic/trusted failure, cleanup, and isolation
  proofs;
- why fixture mode is operator-owned test setup and not request-derived
  authorization;
- why the fixture is runnable but not a production listener, CLI framework,
  deployment surface, 1.0 release, or full framework;
- the Phase 2 checkpoint boundary and exact commit intent;
- security, persistent-runtime, performance, compatibility, and testing
  consequences;
- rejected production-server adoption, SAPI globals, request debug flags,
  ambient state, mock-only proof, extra package, external listener, raw arbitrary
  input, exception stringification, and broader framework scope; and
- later-open production integration and Phase 3 decisions.

### 14.2 New runnable-fixture model

Create:

```text
docs/architecture/phase-2-runnable-fixture.md
```

Document the composition graph, call sequence, mode truth table, scenario
table, response schema, limits, black-box proof, sequential isolation, exact
cleanup, failure behavior, security properties, commands for running the
fixture, and the line between fixture-runnable and production-ready.

### 14.3 Reconcile existing documentation

Modify only these existing documentation files:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/README.md
docs/architecture/http-server-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/web-application/README.md
packages/web-runtime/README.md
```

Reconcile all of them consistently:

- Phase 2A-2H is complete after successful checkpoint;
- Kongono now has thirteen production packages and a test-owned runnable
  fixture, not a fourteenth fixture package;
- the public outer entry is exercised through the concrete fixture delivery;
- fixed fixture status/header/body mappings are fixture acceptance behavior,
  not a general production emitter contract;
- one real Web Application owns the sole Kernel operation scope;
- the Web Runtime retains delivery/trust/cleanup ownership;
- generic and trusted failure rules remain exact;
- sequential isolation and exact cleanup are black-box proven;
- the CLI runner is test infrastructure, not a framework CLI;
- no external listener or production server was selected;
- Phase 2 is the first runnable web milestone, not the full framework, 1.0, or
  production readiness; and
- production runtime/server/authentication/operations and all broader framework
  capabilities remain future architect-owned work.

In ADR 0043, replace stale open items that say already implemented composition,
adapter, response delivery, generic failure, or retirement remain wholly open.
Retain only their genuinely unselected production/general forms. In the Phase 2
roadmap, update the stale “current after Phase 2F” table and 2G-as-next prose to
the exact post-2H state.

Do not rewrite historical phase evidence as though the fixture existed earlier.
Use dated/current-state amendment sections where necessary.

---

## 15. Exact authorized file boundary

### 15.1 Create exactly these fourteen implementation/documentation paths

```text
docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md
docs/architecture/phase-2-runnable-fixture.md
tests/BlackBox/Fixture/DiagnosticFailureHandler.php
tests/BlackBox/Fixture/FixtureDelivery.php
tests/BlackBox/Fixture/FixtureKernelDiagnosticException.php
tests/BlackBox/Fixture/FixtureResponse.php
tests/BlackBox/Fixture/GenericFailureHandler.php
tests/BlackBox/Fixture/GreetingHandler.php
tests/BlackBox/Fixture/NotFoundHandler.php
tests/BlackBox/Fixture/Phase2FixtureApplication.php
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Fixture/TraceMiddleware.php
tests/BlackBox/Fixture/run.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

The caller-created directive and report are not executor-created paths.

### 15.2 Modify exactly these twenty-two existing paths

```text
phpstan.neon.dist
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/README.md
docs/architecture/http-server-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/web-application/README.md
packages/web-runtime/README.md
```

Many of these are already accepted modified/untracked Phase 2 paths. Phase 2H
may change their current bytes only for the reconciliation in Section 14.

### 15.3 Tooling change

In `phpstan.neon.dist`, add exactly:

```text
tests/BlackBox
```

to analyzed paths. Root PHPUnit already includes `tests`; root PHP CS Fixer
already includes `tests`; root dev autoload already owns `Kongono\Tests\`.
Do not modify those configurations merely to restate the existing coverage.

### 15.4 Closed outside scope

No other path may be created, modified, deleted, renamed, generated, or
normalized. In particular, production PHP under `packages/**/src`, package
tests, all manifests, `composer.lock`, `.php-cs-fixer.dist.php`,
`phpunit.xml.dist`, and `tests/Architecture/ApiStatusConventionTest.php` are
byte-frozen.

Temporary Composer/PHPStan/PHPUnit/PHP-CS-Fixer caches remain ignored tooling
state and may change only through the required commands. They are not staging
targets or proof of repository content.

---

## 16. Expected final inventories before checkpoint

After implementation and before staging, require:

```text
package directories       13
package manifests         13
production PHP files      100
test PHP files            130
style-covered PHP files   230
production diagnostic IDs 113 unique
locked production         17
locked development        66
tracked modifications     27
untracked paths           163
total status paths        190
staged paths              0
Git-visible paths         521
tracked files             358
```

The increase from 505 to 521 Git-visible paths is exactly:

- Phase 2H directive;
- zero-byte Phase 2H report;
- fourteen authorized created fixture/documentation paths.

All 22 authorized existing paths were already in the accepted 174-path status
set, so modifying them does not add status paths. No dependency or production
declaration count changes.

Do not turn these totals into authority to add filler files or tests. The exact
path boundary and behavior control.

---

## 17. Validation-only 512 MB PHP overlay

Canonical PHPStan requires more than the workstation's normal `128M`. Phase 2H
explicitly authorizes a fresh temporary process-scoped `512M` scanned-INI
overlay for each required `composer quality` run. This does not authorize a
permanent or repository configuration change.

For each run:

1. require `PHP_INI_SCAN_DIR` absent;
2. create one new GUID directory directly under normalized process temp;
3. create only `99-kongono-phase-2h-memory.ini` containing exactly
   `memory_limit=512M` plus one LF;
4. set the current PowerShell process `PHP_INI_SCAN_DIR` to that directory;
5. validate through `ini_get()`, `php_ini_loaded_file()`, and
   `php_ini_scanned_files()` that memory is `512M`, main INI is exactly
   `C:\php\php.ini`, and the sole scanned file is the exact temporary INI;
6. run literal `composer quality`;
7. in `finally`, first execute exactly
   `Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue`;
8. prove the variable absent, delete only the exact INI, then non-recursively
   delete only the exact empty GUID directory; and
9. prove both paths absent and fresh normal PHP restored to `128M`.

Do not parse quoted `php --ini` display text as an acceptance predicate. Do not
print environment values. If a run fails only because managed network/cache
access is denied, the identical fully cleaned-up run may be repeated once with
approved access. No other retry, limit, worker change, baseline, ignore, or
variant is authorized.

This procedure is authorized for exactly:

1. pre-change canonical quality;
2. final pre-staging canonical quality; and
3. post-commit canonical quality.

Every run uses a fresh temporary directory and completes cleanup independently.

---

## 18. Validation gates

### Gate A - authenticated preflight

Before mutation:

- authenticate this directive externally;
- read all authorities;
- verify caller identities and zero-byte report;
- verify Git identity, counts, modes, refs, branch/remotes/tags;
- reconstruct the authenticated 505-path manifest excluding only Phase 2H
  caller paths;
- verify exact `27/149/176/0`, 507 visible, 358 tracked entry state;
- verify package/PHP/diagnostic/lock inventories;
- verify frozen production/tooling hashes and ordinary-file safety;
- verify all local Markdown links; and
- verify normal PHP `128M` and absent scan variable.

Stop on mismatch.

### Gate B - pre-change quality

Run the exact overlay-backed literal:

```powershell
composer quality
```

Expected accepted baseline evidence is zero advisories, platform pass, 0 of 218
style findings, zero PHPStan errors, and 464 tests with 8,337 assertions.

### Gate C - implementation and focused proof

Implement only Sections 8-15. Run:

```powershell
php -l <each of the twelve new PHP files>
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress tests/BlackBox
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff tests/BlackBox
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/BlackBox
php tests/BlackBox/Fixture/run.php production matched-a unmatched matched-b generic-failure matched-a
php tests/BlackBox/Fixture/run.php developer-trusted diagnostic-failure
```

The two direct fixture commands must exit zero with exact bounded output and no
stderr. Do not use direct output as a substitute for PHPUnit assertions.

### Gate D - architecture and preservation

Verify:

- exact created/modified/frozen paths;
- no production source or dependency change;
- exact final inventories in Section 16;
- no test fixture is referenced by production source;
- no new API status declaration is required or added;
- 113 production diagnostic IDs remain unique;
- package graph remains thirteen-package and acyclic;
- root/package Composer validation remains semantically unchanged;
- all local Markdown links resolve;
- current/planned/runnable-fixture/production-ready wording reconciles;
- strict UTF-8, LF, one final LF, no trailing whitespace/NUL;
- ordinary files, default streams, no reparse points, intended `100644` modes;
- `git diff --check` passes; and
- report remains zero-byte.

### Gate E - final pre-staging quality

Run the fresh overlay-backed literal `composer quality`. Require all stages to
pass, 0 of exactly 230 style-covered PHP files fixable, zero PHPStan errors,
and all tests/assertions passing. Record the exact new PHPUnit totals; do not
manufacture a target count.

Then run optimized strict development autoload proof and no-development proof
without changing dependency versions:

```powershell
composer dump-autoload --optimize --strict-psr
composer dump-autoload --optimize --strict-psr --no-dev
composer dump-autoload --optimize --strict-psr
```

Require no PSR-4 violation. Prove fixture classes exist with dev autoload,
remain absent with `--no-dev`, and dev autoload is restored before checkpoint.

---

## 19. Exact Phase 2 checkpoint

### 19.1 Pre-stage conditions

Before staging, require exact `27/163/190/0`, 521 Git-visible, 358 tracked;
zero-byte Phase 2H report; no unstated path; all prior gates passed; no lock,
dependency, or generated file delta; and all configured Git author identity
available without printing a private email in the report.

Require commit signing disabled/unset and no repository hook or configuration
override that changes commit semantics. Do not bypass hooks or signing.

### 19.2 Exact staging set

The staging set is exactly:

1. all 174 accepted status paths authenticated at Phase 2H entry;
2. `docs/dev_plan/kongono_phase_2H_codex_directive.md`; and
3. the fourteen paths in Section 15.1.

It excludes exactly:

```text
docs/task reports/Phase_2H.md
```

Materialize the 189 staging paths as a literal, ordinally sorted path array and
use one pathspec-safe `git add -- <literal paths>` operation. Do not use `.`, a
directory, wildcard, recursive glob, shell-generated unverified path, `-A`,
`-u`, or `--all`.

After staging require:

```text
staged paths              189
staged modified paths      27
staged added paths        162
staged deleted paths        0
unstaged tracked paths      0
untracked paths             1
index entries             520
index modes other than 100644 0
```

The sole untracked path must be the zero-byte Phase 2H report.

For every staged path, prove the index blob equals the working file bytes.
Require no conflict stages, submodules, symlinks, executable modes, deletions,
renames, or unexpected paths.

Run:

```powershell
git diff --cached --check
```

and require exit zero with no output. Generate the staged tree with
`git write-tree`; require exactly 520 paths, all mode `100644`, and report the
tree hash. Semantic index entries, modes, blobs, and tree are authoritative;
raw `.git/index` byte identity is not an acceptance gate because Git may update
cache-tree metadata.

### 19.3 Commit

Create exactly one commit on `main` with exact subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Use ordinary configured authorship. Do not override author/committer, amend,
reuse a message, sign, bypass hooks, create a tag, push, fetch, create/delete a
branch or ref, rebase, merge, cherry-pick, reset, restore, clean, stash, or
rewrite history.

The new commit must have:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      520
tree modes      all 100644
delta paths     189
modified        27
added           162
deleted         0
```

The commit's tree must exactly equal the pre-commit `git write-tree` result.

### 19.4 Post-commit gates

After commit and before report population:

- require `main`, ten commits, exact parent/subject/tree equality;
- require zero staged paths and zero tracked worktree changes;
- require the sole untracked path is the zero-byte Phase 2H report;
- require exactly 520 tracked files, all `100644`;
- require one local branch, zero remotes/tags, no unexpected repository-owned
  refs;
- rerun focused black-box PHPUnit and the two direct fixture commands;
- rerun fresh overlay-backed literal `composer quality`;
- require exact final package/PHP/diagnostic/lock inventories;
- require links, file safety, UTF-8/LF, and documentation consistency;
- prove no Phase 3 path exists; and
- prove all accepted historical records are in `HEAD` with exact staged bytes.

Only after all post-commit non-report gates pass may the report be populated.

---

## 20. Prohibited scope

Do not implement or select:

- a fourteenth production package;
- production SAPI/global conversion, PHP-FPM, built-in-server deployment,
  FrankenPHP, RoadRunner, OpenSwoole, ReactPHP, Amp, Workerman, socket, listener,
  network client, process manager, server, or emitter library;
- a framework CLI, command bus, console application, config loader, `.env`
  reader, environment policy, service discovery, or deployment entrypoint;
- production authentication, authorization, proxy trust, operator/audience
  verification, TLS policy, IP allowlist, or request-derived debug flag;
- arbitrary fixture headers, body, cookies, query, files, uploaded resources,
  server parameters, or raw JSON request format;
- request/path/scenario material in diagnostics or trusted output;
- logging, telemetry, incident IDs, error reporting, tracing, metrics, retry,
  recovery, reset, cancellation, or automatic restart;
- supplied error middleware or changes to generic/trusted response factories;
- changes to Kernel, HTTP Message, Routing, HTTP Server, Web Application, Web
  Runtime, diagnostics, activation, handler, presentation, DI, or Graph
  production source;
- new dependencies, versions, plugins, Composer scripts, aliases, polyfills,
  baselines, ignores, suppressions, exclusions, reduced analysis, skipped tests,
  or weakened warnings;
- ORM, database, migrations, models, sessions, CSRF, templates, frontend,
  events, queues, cache, storage, mail, notifications, scheduling, realtime,
  agents, extensions, full Application Graph integration, or DI expansion;
- API promotion to Stable, a 1.0/versioning claim, production-readiness claim,
  Laravel/Symfony parity claim, or “full framework” claim;
- any Phase 3 implementation; or
- any Git action not explicitly authorized in Section 19.

---

## 21. Stop rules

Stop immediately and preserve state if:

- directive authentication or caller identity fails;
- the authenticated 505-path snapshot, Git state, counts, hashes, modes, refs,
  streams, or file safety differs;
- pre-change quality fails for a non-network/cache reason;
- an unselected fixture/protocol/security decision is required;
- any production source, manifest, lockfile, frozen tooling file, or package test
  would need modification;
- the fixture cannot satisfy bounded input/output and denial-before-source
  access;
- a black-box scenario fails or needs platform skipping;
- canonical quality, strict autoload, links, encoding, or documentation fails;
- final status or staging inventory differs;
- `git diff --cached --check` reports any finding;
- `git write-tree` or semantic index proof differs;
- commit configuration/hook/signing is unexpected;
- commit parent, subject, tree, delta, count, or mode differs;
- post-commit quality or preservation fails; or
- the report would need population before all other gates pass.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

If a failure occurs after staging, preserve the exact index and stop; do not
unstage, rebuild, restore, or retry a Git write without new architect authority.
If it occurs after commit, do not amend or reset the commit.

---

## 22. Standard executor completion report

After all non-report criteria, populate only:

```text
docs/task reports/Phase_2H.md
```

Use exactly these headings:

```text
# PHASE 2H TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. RUNNABLE FIXTURE BOUNDARY
## 8. DELIVERY AND OUTPUT CONTRACT
## 9. BLACK-BOX ACCEPTANCE
## 10. SECURITY AND TRUST PROOF
## 11. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 12. ARCHITECTURAL DECISIONS
## 13. DEVIATIONS FROM DIRECTIVE
## 14. DEPENDENCIES ADDED/REMOVED
## 15. ENVIRONMENT
## 16. COMMANDS RUN
## 17. PRE-COMMIT VALIDATION RESULTS
## 18. STAGING EVIDENCE
## 19. COMMIT EVIDENCE
## 20. POST-COMMIT VALIDATION RESULTS
## 21. DOCUMENTATION REVIEW
## 22. SECURITY AND FILE-SAFETY REVIEW
## 23. WARNINGS / KNOWN ISSUES
## 24. QUESTIONS / BLOCKERS
## 25. FINAL GIT STATUS
## 26. ACCEPTANCE CRITERIA
## 27. NEXT STEP
```

Report exact:

- independent directive identity;
- authenticated baseline manifest and caller records;
- pre/post implementation inventories and status;
- every created/modified/frozen path result;
- exact fixture modes, scenarios, envelopes, limits, exit behavior;
- black-box process commands and all response/security/isolation assertions;
- denial-before-source and request-independent permission proof;
- exact request/send/close/active-operation counts;
- pre-change, focused, final precommit, and postcommit validation totals;
- every temporary 512M overlay and exact return to 128M;
- dependency/lock immutability;
- exact 189-path staged inventory classification;
- staged blob/worktree equality, modes, whitespace, and staged tree hash;
- new commit hash, parent, subject, tree, delta, count, modes, and authorship
  source without printing a private email;
- final one-path untracked status and report absence from `HEAD`;
- documentation reconciliation and deliberately open production concerns;
- whether Phase 2 is fully closed;
- no Phase 3 work; and
- final report byte count and SHA-256 returned outside the report body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The populated report must be UTF-8 without BOM, LF-only, exactly one final LF,
no trailing whitespace/NUL, ordinary, untracked, unstaged, absent from `HEAD`,
and the only status path.

---

## 23. Strict acceptance criteria

Phase 2H is Complete only if:

1. the directive matched independently supplied byte count and SHA-256;
2. all authorities were read completely;
3. caller records and the zero-byte active report matched;
4. the authenticated 505-path snapshot and exact Git baseline matched;
5. pre-change canonical quality passed under an exact temporary overlay;
6. exactly fourteen authorized implementation/documentation paths were created;
7. exactly twenty-two authorized existing paths were modified for reconciliation
   and analysis coverage;
8. no other path or production package/source/test changed;
9. no dependency, manifest, script, lock entry, or API-status contract changed;
10. exactly twelve new PHP files exist under `tests/BlackBox`;
11. all fixture classes are final, PSR-4 compliant, test-owned, and free of
    ambient delivery state;
12. the concrete delivery implements the exact Public port and bounded detached
    response snapshot;
13. every delivery has exact request/send/close counts and exact body cleanup;
14. the real Kernel, Web Application, routing, middleware, and Web Runtime are
    used without a second scope or mock replacement;
15. matched routing and exact middleware order pass black-box proof;
16. unmatched routing produces the exact deterministic 404 fixture result;
17. unsupported application failure remains exact generic output;
18. bootstrap denial skips evidence and prevents trusted source output;
19. delivery denial prevents trusted output without fallback;
20. both affirmative permissions plus supported source produce exact trusted
    HTML and security headers;
21. request/path/scenario/native failure material is absent from trusted and
    generic output;
22. sequential same-process operations prove no state leakage and zero active
    operations after every delivery;
23. independent subprocesses are deterministic and no listener/network is
    required;
24. exact invalid invocation and unexpected-failure output/exit behavior exists;
25. all focused tests and direct fixture probes pass;
26. documentation and ADR 0050 accurately distinguish fixture-runnable from
    production-ready/full-framework claims;
27. final inventories are exactly `13/13/100/130/230`, diagnostics 113 unique,
    locks `17/66`;
28. final pre-staging canonical quality passes with 0/230 style findings, zero
    PHPStan errors, zero advisories, platform pass, and all tests/assertions;
29. strict optimized dev/no-dev/dev autoload proof passes;
30. links, UTF-8/LF, whitespace, NUL, stream, reparse, and mode checks pass;
31. pre-stage status is exactly `27/163/190/0`, 521 visible, 358 tracked;
32. exactly 189 paths are staged: 27 modified, 162 added, zero deleted;
33. the active report is the sole untracked path and remains zero-byte;
34. staged blobs equal working bytes, modes are all `100644`, and no conflict
    stage exists;
35. `git diff --cached --check` passes with no finding;
36. `git write-tree` contains exactly 520 `100644` paths;
37. exactly one commit with the required subject and parent is created;
38. commit count is ten, commit tree equals the staged tree, and delta is exactly
    189 paths/27 modified/162 added/zero deleted;
39. no branch, remote, tag, unexpected ref, history rewrite, or prohibited Git
    action occurred;
40. post-commit focused and canonical validation pass;
41. post-commit tracked tree contains exactly 520 `100644` files;
42. every historical accepted record is committed with its exact bytes;
43. no Phase 3 work began;
44. the report is populated only after criteria 1-43 pass;
45. the report is the sole status path, untracked/unstaged/absent from `HEAD`;
46. it has the exact headings, encoding, and final sentence; and
47. its final byte count and SHA-256 are returned independently.

If any criterion fails, the report status must not be Complete.

---

## 24. Required final outcome

Kongono ends Phase 2 with thirteen production packages unchanged from the
accepted Phase 2G implementation, plus a bounded test-owned runnable fixture
that proves the real server-side framework path from explicit composition and
request creation through Kernel scope ownership, deterministic routing,
ordered middleware, terminal handling, response delivery, generic/trusted
failure selection, and exact cleanup.

The black-box milestone is runnable without a network listener and proves
sequential persistent-process isolation. Documentation states plainly that this
is the first runnable fixture milestone—not a production server, full framework,
Laravel/Symfony replacement in feature breadth, 1.0 release, or production-
ready platform.

The exact accepted post-Phase-1H work is committed once as the Phase 2
checkpoint. Only the populated Phase 2H report remains outside `HEAD`. Then
return the report and await architect review before Phase 3 planning.
