# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 2G
## Outer Web Runtime Adapter, Safe Failure Delivery, and Exact Transport Cleanup

**Directive type:** bounded architecture and implementation

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2G

**Depends on:** accepted Phase 2F and Phase 2F.1 closure

---

## 1. Mission

Implement the first outer Kongono web-runtime adapter as the thirteenth
first-party package.

Phase 2G must establish one explicit per-delivery boundary that:

1. obtains a server-owned delivery permission independently of request data;
2. obtains one already-converted PSR-7 server request;
3. invokes the existing Phase 2F `WebApplication` exactly once and therefore
   opens no second Kernel operation scope;
4. sends the exact successful response;
5. maps evidence, conversion, and application failure to a fixed generic
   production response by default;
6. permits the existing trusted developer HTML only when both typed permissions
   are affirmative and the failure is supported by the existing Diagnostic
   Handler;
7. denies trusted presentation before diagnostic source access;
8. closes the exact per-delivery transport boundary exactly once on success and
   failure;
9. preserves exact send and cleanup failures, including a two-failure composite;
10. retires uncertain runtime-adapter state and refuses later work before
    dependency access; and
11. retains no request, response, permission, exception, or delivery object in
    long-lived or ambient state.

This is still not the runnable Phase 2 milestone. Phase 2G supplies no concrete
SAPI, server, listener, socket, emitter, deployment adapter, or fixture
application. Phase 2H will provide the minimal concrete fixture delivery and
black-box proof through the Phase 2G boundary.

Do not implement anything beyond the exact package, behavior, tests,
documentation, dependency, and tooling boundaries in this directive.

---

## 2. Locked doctrine

All previously accepted Kongono doctrine remains binding, including:

- the Kernel remains transport-neutral;
- `WebApplication` alone owns one Kernel scope around each application call;
- no outer adapter may open a second Kernel scope for that call;
- trusted diagnostic presentation requires independent affirmative bootstrap
  and current-delivery permission;
- denial, absence, invalidity, ambiguity, failure, or uncertainty fails closed;
- permission denial occurs before diagnostic metadata/context access,
  projection, copying, rendering, or delivery;
- client-controlled request data can never enable trusted presentation;
- generic production output is a separate fixed model, not a redacted trusted
  developer model;
- failures in evidence, presentation, response construction, delivery, or
  cleanup cannot recursively enable trusted output;
- direct PHP construction of an `Allowed` enum remains possible and is not
  proof of authorization;
- one delivery's permission cannot authorize another delivery;
- no global, static, singleton-current, container-current, thread-local,
  fiber-local, or previous-operation request/permission state;
- long-lived objects retain no delivery-specific data;
- runtime integrations remain outer packages, not Kernel dependencies;
- PSR interfaces are interoperability contracts, not Kongono's substrate;
- public API status is explicit and does not itself authorize disclosure;
- errors must preserve exact causal identities where this directive defines a
  composite and must never expose raw failure/request data through package
  diagnostics; and
- stop rather than guess when an authority, caller record, inventory, hash,
  generated lock delta, or exact contract differs.

Phase 2G deliberately resolves only the first transport-neutral outer adapter
contract. It does not select real transport authentication, listener policy,
proxy trust, operator identity, IP allowlists, TLS identity, SAPI parsing,
deployment, incident storage, logging, or telemetry.

---

## 3. Authentication and mandatory preflight

### 3.1 Independent directive identity

Before any quality command or mutation, compare this directive with the byte
count and SHA-256 supplied independently by the architect outside this file.

Required repository path:

```text
docs/dev_plan/kongono_phase_2G_codex_directive.md
```

Do not derive expected values from the local file. If either independently
supplied value is missing or differs, stop before validation or mutation.

### 3.2 Required completion-report placeholder

The caller must provide:

```text
docs/task reports/Phase_2G.md
```

At entry it must be ordinary, untracked, unstaged, absent from `HEAD`, zero
bytes, and have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every non-report acceptance gate passes.

### 3.3 Accepted immediate caller records

These records must initially match exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2F_codex_directive.md` | 53418 | `5D25D4FA8A34F410548836B673AD3F77845F509DD45DB0039C1858572DF58EB4` |
| `docs/task reports/Phase_2F.md` | 25844 | `FD59AC570E5B2115DCBDB1A30C787D100EACC2B4A903748FB94CC7A2FFDDFF29` |
| `docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md` | 16390 | `9995BDB628FD7DC65F4527DD8F5611EB7DE90BC20D5D59562A56A24C3DFE5EBA` |
| `docs/task reports/Phase_2F_1_correction.md` | 12153 | `141154168F1776B0AD0234391E4E0ECE0118F6F2E3FF33346C03BBCA8B449057` |

Treat every older directive and report as immutable historical evidence. Do not
normalize, rewrite, stage, or repair them.

### 3.4 Accepted repository state before new caller records

The independently verified starting state before the Phase 2G directive and
report are copied is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  20
untracked paths        124
total status paths     144
staged paths           0
Git-visible paths      482
local branches         1 (`main`)
remotes                0
tags                   0
```

After the caller places only the authenticated Phase 2G directive and zero-byte
report, expected entry status is:

```text
tracked modifications  20
untracked paths        126
total status paths     146
staged paths           0
Git-visible paths      484
```

Environment-managed `refs/codex/turn-diffs/**` may exist and may change count.
They are exempt from the repository-owned ref invariant. There must be no
unexpected ref outside `refs/heads/main` and `refs/codex/turn-diffs/**`.

### 3.5 Accepted inventories and critical identities

Before Phase 2G:

```text
first-party packages    12
package manifests       12
production PHP files    92
test PHP files          113
style-covered PHP files 205
lock production entries 16
lock development entries 66
diagnostic identifiers  111 unique
```

Critical starting files must match:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `composer.json` | 4075 | `BE2D52E143C500979C90935242B2BB9309978B59ABB5F9AEA1E6BA6211DF284A` |
| `composer.lock` | 194941 | `EAFD08ACA95D663BEB1D1C6A78CB4BA4C0F25D5A1296BD86131546FC68DE1470` |
| `.php-cs-fixer.dist.php` | 2017 | `BC8503DA29274DA27B8FFF02C8DD9F31490DF9A6C86BA2132A8563AE40DF64CA` |
| `phpstan.neon.dist` | 1150 | `80CD2EA6A884D321C431BA0B737CBDAB082D351C67EE481B739F340E3B0073AE` |
| `phpunit.xml.dist` | 2324 | `93918F6FDC10264B8E995D3D0A421D51DE25EBA71F4082DA98B802085384AC84` |
| `tests/Architecture/ApiStatusConventionTest.php` | 71679 | `E7EF04657FDA122D890E5C330CC3CB95ADA3245BC13C3247F4BD1ECF1F95BDF9` |
| `packages/web-application/composer.json` | 650 | `27F0996BD30430AE12DE926A278553E2108306E0717D53FC19C4C5C028516908` |
| `packages/web-application/src/WebApplication.php` | 3313 | `813B5498B0F514CBD991A0C4227EBE4A94FB0196D2838E72F25E75F8685916CA` |
| `packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php` | 690 | `938D1BCEE66F647045C5EF9D45426482E662992D3FF2E994687CEE7A0BE83732` |
| `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php` | 707 | `0B83C99CA936CEB4264CCD6433367C976B5912FD952287F9F5F068226E4B00DC` |
| `packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php` | 910 | `DE10F378C77BC44997D415EA3F02F904FF1A7F98087EC04AA9656F39BA37F7D6` |
| `packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php` | 1608 | `BAE619B2B5278707186569257C5A717B8A44887B732775236FB25570859C3C5C` |
| `packages/http-message/src/Message/Response.php` | 1754 | `5A901B0D61BC8DD6AC54774D340988D90AF8593079D15BA87AE782DBADE5495F` |
| `packages/http-message/src/Factory/StreamFactory.php` | 3071 | `32908C5AB4F7C691899C6D5C34DB4DC4310099DB9496C3833ABD3D74B9A28B23` |

Stop before mutation if any locked fact differs. Do not infer that a nearby
file, alternate ADR title, local hash, or status count was intended.

---

## 4. Mandatory authority reading

Read completely before implementation:

```text
AGENTS.md
ARCHITECTURE.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/architecture/phase-2-roadmap.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/http-message/README.md
packages/web-application/README.md
docs/dev_plan/kongono_phase_2F_codex_directive.md
docs/task reports/Phase_2F.md
docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md
docs/task reports/Phase_2F_1_correction.md
docs/dev_plan/kongono_phase_2G_codex_directive.md
```

Also read every production declaration that Phase 2G imports and every existing
test/tooling file that Phase 2G modifies. Do not rely on remembered signatures.

---

## 5. Exact package boundary

Create exactly one package:

```text
directory              packages/web-runtime
Composer package       kongono/web-runtime
production namespace   Kongono\Web\Runtime\
test namespace         Kongono\Web\Runtime\Tests\
```

The package manifest must be exactly scoped to:

```json
{
  "name": "kongono/web-runtime",
  "description": "Fail-closed outer web delivery and diagnostic response orchestration for Kongono.",
  "type": "library",
  "require": {
    "kongono/api-metadata": "*",
    "kongono/application-graph": "*",
    "kongono/dependency-injection": "*",
    "kongono/diagnostic-activation": "*",
    "kongono/diagnostic-handler": "*",
    "kongono/diagnostics": "*",
    "kongono/http-message": "*",
    "kongono/kernel": "*",
    "kongono/web-application": "*",
    "php": "^8.5",
    "psr/http-message": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Kongono\\Web\\Runtime\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kongono\\Web\\Runtime\\Tests\\": "tests/"
    }
  }
}
```

The production graph is a new top-level leaf. No existing package may gain a
dependency on Web Runtime. Web Runtime has no direct dependency on Routing,
HTTP Server, Diagnostic Presentation, Diagnostic Activation configuration,
PSR-15, a runtime server, or an emitter library. Its direct Graph, DI, and
Kernel dependencies exist only to recognize the exact three source markers
already supported by Diagnostic Handler; it performs no Graph traversal, DI
resolution, or Kernel lifecycle call.

No third-party dependency may be added, removed, or updated.

---

## 6. Exact production declarations and API status

Create exactly these eight production declarations:

| Declaration | Kind | API status |
|---|---|---|
| `Kongono\Web\Runtime\Contract\WebDeliveryInterface` | interface | Public |
| `Kongono\Web\Runtime\Diagnostic\WebRuntimeDiagnostic` | string-backed enum | Internal |
| `Kongono\Web\Runtime\Exception\ExceptionInterface` | interface | Public |
| `Kongono\Web\Runtime\Exception\WebRuntimeException` | abstract class | Internal |
| `Kongono\Web\Runtime\Exception\RetiredWebRuntimeException` | final class | Experimental |
| `Kongono\Web\Runtime\Exception\DeliveryCleanupException` | final class | Experimental |
| `Kongono\Web\Runtime\Internal\FailureResponseFactory` | final readonly class | Internal |
| `Kongono\Web\Runtime\WebRuntimeAdapter` | final class | Experimental |

Each declaration must have exactly one `#[Api(...)]` attribute with the stated
status and `declare(strict_types=1);`.

Do not add another production declaration, trait, enum, attribute, helper,
outcome, result, emitter, evidence token, configuration type, lifecycle state,
factory interface, or alias.

---

## 7. Per-delivery contract

`WebDeliveryInterface` is the Public server-integration port. Its exact public
contract is:

```php
interface WebDeliveryInterface
{
    public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission;

    public function request(): ServerRequestInterface;

    public function send(ResponseInterface $response): void;

    public function close(): void;
}
```

It extends no other interface and declares no constants or properties.

The contract means:

- one fresh delivery object owns one inbound boundary;
- `evaluateTrustedPresentationPermission()` evaluates only server-controlled
  transport evidence for this delivery and returns the already-decided typed
  permission;
- that method accepts no request or scalar input;
- it must not inspect a query, route, path, URI, header, cookie, form, body,
  session, token, application value, provider payload, prior delivery, mutable
  ambient state, or other client-controlled input to enable `Allowed`;
- `request()` performs or exposes the concrete runtime's already-bounded
  conversion into one `ServerRequestInterface`;
- `send()` delivers exactly one response and returns only after its delivery
  attempt is complete;
- `close()` releases delivery-owned transport resources, including any response
  body retained by the delivery implementation, and is safe to call exactly
  once under this contract;
- the runtime adapter retains none of these values after `handle()` returns or
  throws; and
- an implementation remains responsible for its real authentication, listener,
  proxy, operator, and audience policy. Implementing this PHP interface is not
  proof of trust.

Phase 2G provides no concrete implementation of this interface. Phase 2H may
provide only the minimal fixture implementation under its own authority.

---

## 8. Generic and trusted failure responses

`FailureResponseFactory` is an Internal package-owned constructor. It has:

```php
public function __construct()
public function generic(): ResponseInterface
public function trusted(string $document): ResponseInterface
```

It owns exactly one private readonly `StreamFactory` property and constructs it
in its zero-argument constructor. It uses the existing first-party `Response`
and `StreamFactory`; it reads no environment, request, configuration, clock,
random source, filesystem path, or network service.

### 8.1 Generic production response

`generic()` returns a fresh response with exactly:

```text
status                   500
body                     Internal Server Error\n
Content-Type             text/plain; charset=utf-8
Cache-Control            no-store
X-Content-Type-Options   nosniff
```

It adds no diagnostic identifier, incident identifier, exception class,
exception message, trace, file, line, request value, framework version, server
header, cookie, redirect, or other field. This is the separate deny-by-default
production model. It is not derived from or redacted from the trusted model.

### 8.2 Trusted developer response

`trusted($document)` receives only the exact non-null string returned by
`TrustedDeveloperDiagnosticHtmlPresenter::renderIfEligible()`. It returns a
fresh response with exactly:

```text
status                   500
body                     exact $document bytes
Content-Type             text/html; charset=utf-8
Cache-Control            no-store
X-Content-Type-Options   nosniff
Content-Security-Policy  default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'
```

It performs no additional projection, interpolation, escaping, redaction,
authorization, logging, storage, or delivery. The existing renderer's inline
style requires the narrowly stated `style-src 'unsafe-inline'`; all other
resource loading and framing remain denied.

Neither response sets `Content-Length`; concrete delivery owns protocol
framing. Neither response is cached or reused across deliveries.

---

## 9. Runtime diagnostics and exception foundation

`WebRuntimeDiagnostic` has exactly these cases and values, in this order:

```php
case RuntimeRetired = 'KGN-WEB-RUNTIME-1001';
case DeliveryAndCleanupFailed = 'KGN-WEB-RUNTIME-1002';
```

It exposes exactly the same six public metadata methods as the existing Web
Application catalog:

```php
context(): DiagnosticContext
contextFields(): array
contextOperation(): string
contextViolatedInvariant(): string
title(): string
remediation(): string
```

Both contexts have an empty field list. Use fixed package-owned prose only:

| Case | Title | Operation | Violated invariant | Remediation |
|---|---|---|---|---|
| `RuntimeRetired` | `Web runtime adapter is retired` | `Use web runtime adapter` | `A retired runtime adapter must not accept another delivery` | `Replace the retired runtime adapter before accepting more deliveries` |
| `DeliveryAndCleanupFailed` | `Web delivery and transport cleanup both failed` | `Complete web runtime delivery` | `Delivery and cleanup failures must both remain observable` | `Retire the adapter and inspect both exact failure objects` |

The package exception marker extends `Throwable`, mirrors the other Public
package markers, and exposes:

```php
diagnosticContext(): DiagnosticContext
diagnosticId(): string
diagnosticTitle(): string
diagnosticRemediation(): string
```

It must remain separate in both directions from every existing package marker.

`WebRuntimeException` is abstract, extends `RuntimeException`, implements the
marker, stores exactly the diagnostic and its eagerly created empty context in
private readonly properties, and makes all four metadata accessors final. Its
protected constructor is exactly:

```php
protected function __construct(
    WebRuntimeDiagnostic $diagnostic,
    ?Throwable $previous = null,
)
```

It uses the diagnostic title as the native message, integer code `0`, and the
supplied previous object. Its diagnostic surface contains no raw failure or
delivery data.

`RetiredWebRuntimeException` has a zero-argument constructor and no additional
property.

`DeliveryCleanupException` has:

```php
public function __construct(Throwable $primaryFailure, Throwable $cleanupFailure)
public function primaryFailure(): Throwable
public function cleanupFailure(): Throwable
```

It stores both exact objects in private readonly properties, uses the primary
as native `getPrevious()`, and exposes neither object through diagnostic
metadata/context. It is used only when an escaped response-preparation or send
failure and the exact delivery `close()` call both fail.

Do not override `__toString()`. Native exception stringification remains
governed by the existing safe runtime configuration doctrine.

---

## 10. `WebRuntimeAdapter` exact contract

The class is final, non-readonly, has no parent, and implements no interface.
It has exactly these five private instance properties:

```php
private readonly WebApplication $application;
private readonly TrustedDeveloperBootstrapPermission $bootstrapPermission;
private readonly TrustedDeveloperDiagnosticHtmlPresenter $presenter;
private readonly FailureResponseFactory $failureResponses;
private bool $retired = false;
```

Its constructor is exactly:

```php
public function __construct(
    WebApplication $application,
    TrustedDeveloperBootstrapPermission $bootstrapPermission,
)
```

It stores the first two inputs and constructs one presenter and one failure
response factory. It performs no application work, evidence evaluation,
request conversion, response creation, or I/O.

Its exact public methods are:

```php
public function handle(WebDeliveryInterface $delivery): void
public function isRetired(): bool
```

It has a private `__clone(): void`, no destructor, no serializer, no getters,
and no reset/recover/retry/shutdown method. The composition root that already
owns the `WebApplication` remains responsible for explicit application shutdown
after serving stops.

### 10.1 Entry refusal

At the start of `handle()`:

1. if the adapter is already retired, throw a new
   `RetiredWebRuntimeException` before consulting the application or any
   delivery method;
2. otherwise consult `WebApplication::isRetired()` once; if true, set the
   adapter retired and throw `RetiredWebRuntimeException` before any delivery
   method; and
3. otherwise accept the delivery and guarantee one later `close()` attempt.

### 10.2 Bootstrap denial and delivery evidence

- If bootstrap permission is `Denied`, do not call
  `evaluateTrustedPresentationPermission()` at all; use delivery permission
  `Denied` locally and continue to request conversion.
- If bootstrap permission is `Allowed`, call the delivery evaluator exactly
  once before `request()`.
- If evaluation returns `Denied`, continue normally with a local denied value.
- If evaluation returns `Allowed`, it is only the second typed input; it does
  not itself prove authorization beyond the delivery implementation's contract.
- If evaluation throws any `Throwable`, do not call `request()` or the
  application, do not attempt trusted presentation, construct the fixed generic
  response, then proceed to send and close.

No permission is stored in an object property, global, static, cache, prior
delivery, thread-local, or fiber-local location.

### 10.3 Request conversion

After permission selection, call `request()` exactly once.

- If it returns, pass that exact request object to `WebApplication::handle()`.
- If it throws any `Throwable`, do not invoke the application or trusted
  presentation. Construct the fixed generic response, then send and close.
- Do not inspect request method, URI, path, query, headers, cookies, body,
  attributes, server parameters, or uploaded files in Web Runtime.

This preserves the distinct conversion port while refusing to invent SAPI or
runtime parsing rules in Phase 2G.

### 10.4 Application call

Call `WebApplication::handle()` exactly once with the exact converted request.
Do not call Kernel, Routing, HTTP Server, middleware, or a handler directly and
do not open another operation scope.

- On response, send the exact same response object.
- On `Throwable`, consult `WebApplication::isRetired()` once. If true, set the
  adapter retired, but continue safe response selection for the current
  delivery.
- Keep the application failure only in the local call frame.
- Only an application-call failure may be considered for trusted developer
  presentation. Evidence and request-conversion failures always use generic
  output.

### 10.5 Trusted selection

For an application failure:

1. determine support only with `instanceof` against the existing Kernel,
   Application Graph, and Dependency Injection package exception markers;
2. do not call any source accessor during that support check;
3. unsupported failures use the generic response even if both permissions are
   `Allowed`;
4. for a supported source, construct a fresh
   `TrustedDeveloperPresentationEligibility` from the retained bootstrap value
   and local delivery value;
5. call `renderIfEligible()` exactly once;
6. if it returns `null`, use the generic response;
7. if it returns HTML, use the exact trusted response; and
8. if eligibility evaluation, source access, projection, rendering, or trusted
   response construction throws, catch that failure once and use the generic
   response without recursively invoking trusted presentation.

Under denial, the existing handler must return `null` before source access.
Web Runtime must not pre-access diagnostic ID, title, remediation, context,
fields, message, code, previous exception, trace, file, or line.

### 10.6 Send and exact cleanup

After response selection, call `send()` exactly once with the exact response.
Then call `close()` exactly once whether response selection or send returned or
threw. Never retry either operation.

Observable escaped-failure matrix:

| Preparation/send | `close()` | Observable result | Adapter retired |
|---|---|---|---|
| success | success | `void` | only if application retired during the call |
| `Throwable` | success | exact primary `Throwable` | yes |
| success | `Throwable` | exact cleanup `Throwable` | yes |
| `Throwable` | `Throwable` | `DeliveryCleanupException` with both identities | yes |

For this matrix, an application, evidence, or conversion failure that was
successfully mapped and sent is handled, not an escaped primary. A later send
failure is the escaped primary. Incident correlation, logging, and a future
three-cause handled-source/send/cleanup record remain explicitly unselected.

If the adapter becomes retired while finishing the current delivery, it must
still complete that delivery's one send attempt and one close attempt. Every
later `handle()` fails before dependency access.

---

## 11. Security and persistent-runtime requirements

The implementation must prove all of these:

- generic failure output is byte-fixed and contains no caller or exception
  value;
- trusted HTML is returned only for a supported package marker and two
  affirmative permissions;
- bootstrap denial skips delivery evaluation;
- delivery denial reaches the existing eligibility-first handler without
  source access;
- evaluation failure and request-conversion failure never attempt trusted
  presentation;
- a client request is never passed to the delivery evaluator;
- no request component can upgrade either permission;
- a trusted presenter/source-accessor/projection/rendering failure falls back
  only to generic output and never recurses;
- cache prevention, nosniff, and the exact trusted CSP are present;
- response-splitting input cannot enter fixed headers;
- no request, response, source, permission, HTML, delivery, or failure becomes
  a property of the adapter;
- sequential and reentrant calls keep all per-delivery values in local frames;
- exact delivery identity is closed once;
- no destructor performs I/O or cleanup;
- no Kernel operation is opened outside `WebApplication`;
- no filesystem path, network listener, socket, SAPI global, environment
  variable, clock, random source, logger, metric, trace, event, incident store,
  queue, persistence layer, session, authorization service, container, Graph
  traversal, DI resolution, or service locator is used; and
- direct construction of typed `Allowed` inputs is documented as an integration
  obligation rather than misrepresented as a security boundary.

The new package may use `php://temp` only through the already accepted
first-party `StreamFactory` for fresh response bodies.

---

## 12. Required tests

Create exactly these five test PHP files:

```text
packages/web-runtime/tests/Fixture/ExplosiveKernelDiagnosticException.php
packages/web-runtime/tests/Unit/Diagnostic/WebRuntimeDiagnosticTest.php
packages/web-runtime/tests/Unit/Exception/WebRuntimeExceptionTest.php
packages/web-runtime/tests/Unit/Internal/FailureResponseFactoryTest.php
packages/web-runtime/tests/Unit/WebRuntimeAdapterTest.php
```

The fixture is a PSR-4-compliant named `RuntimeException` implementing only the
Kernel exception marker. Its diagnostic accessors increment an observable
counter and then throw fixed sentinel failures so denial-before-access and
allowed-path fallback can be proven. It stores no request data.

Minimum focused coverage:

### 12.1 Diagnostic tests

- exact case order, IDs, prose, empty context schemas, API status, public method
  inventory, no parameters, and no `__toString()`;
- repository diagnostic uniqueness at exactly 113 IDs after adding two; and
- exact package split remains Kernel 6, Graph 11, DI 25, Presentation 15,
  Diagnostics 11, HTTP Message 28, Routing 10, HTTP Server 3, Web Application 2,
  Web Runtime 2.

### 12.2 Exception tests

- marker separation in both directions from all existing package markers;
- exact abstract/concrete inheritance, finality, API status, properties,
  constructors, accessors, and native previous identity;
- retired diagnostic is fixed and empty-context;
- composite preserves exact primary and cleanup objects;
- neither raw failure sentinel, class name, trace data, request data, nor
  delivery data appears in package diagnostic surfaces; and
- no custom exception stringification.

### 12.3 Failure response tests

- a fresh generic response has exact status, three headers, body, and no extra
  disclosure;
- a fresh trusted response has exact status, four headers including exact CSP,
  and the exact supplied renderer document bytes;
- generic and trusted responses and bodies are distinct objects across calls;
- no `Content-Length`, `Server`, cookie, redirect, or diagnostic header; and
- factory shape/status/properties/public method inventory is exact.

### 12.4 Adapter tests

At minimum prove:

1. exact class shape, statuses, properties, constructor, public methods, private
   clone, and absence of destructor/serialization/getters/reset/shutdown;
2. successful response identity, event order, one application Kernel scope,
   one send, and one close;
3. bootstrap denial skips delivery evidence evaluation yet still serves normal
   work;
4. delivery evaluation occurs before request conversion and exactly once only
   when bootstrap is allowed;
5. evaluation failure performs no request/application/source access and sends
   generic output;
6. conversion failure performs no application/source access and sends generic
   output;
7. bootstrap denial plus supported explosive source sends generic output with
   zero diagnostic accessor calls;
8. delivery denial plus supported explosive source sends generic output with
   zero diagnostic accessor calls;
9. both permissions allowed plus a real supported Kernel exception sends exact
   trusted HTML and exact trusted headers;
10. both permissions allowed plus an unsupported `RuntimeException` sends only
    generic output;
11. an allowed supported source whose accessor throws attempts source access
    once, never recurses, and sends generic output;
12. generic output contains none of multiple raw request/exception sentinels;
13. application primary failure with successful Kernel cleanup and delivery is
    mapped without retiring either application or adapter;
14. application simultaneous work/Kernel-cleanup failure retires the
    application and adapter, sends generic output for the current delivery,
    closes once, and makes the next delivery fail before all dependency access;
15. send-only failure propagates the exact send object, closes once, and retires;
16. close-only failure propagates the exact close object and retires;
17. send plus close failure throws the exact composite with exact object
    identities and retires;
18. response-preparation failure, if induced without changing production,
    still closes exactly once and follows the same escaped-primary matrix;
19. sequential deliveries use distinct request, response, permission, and
    delivery objects with no cross-call retention;
20. reentrant accepted deliveries retain local identities and close inner
    before outer;
21. pre-retired Web Application causes refusal before delivery access;
22. already-retired adapter causes refusal before application or delivery
    access; and
23. object destruction performs no send, close, application shutdown, or I/O.

Use real existing values where practical. Mocks/stubs may prove call count and
order. Do not fabricate production-invalid private state, use serialization to
bypass invariants, define non-PSR named fixtures inside test files, or test source
text as a substitute for behavior.

Exact test/assertion totals are observational evidence, not targets. Do not add
empty assertions merely to reach a predicted count.

---

## 13. ADR and documentation requirements

### 13.1 New ADR

Create:

```text
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
```

It must have:

```text
Status: Accepted
Date: 2026-08-20
```

Record:

- exact package identity and dependency graph;
- why Web Runtime is a top-level leaf and Kernel remains transport-neutral;
- the per-delivery interface and its integration/security limits;
- permission-before-request sequencing and bootstrap-denial short-circuit;
- generic 500 response bytes and exact headers;
- trusted 500 response and exact headers/CSP;
- exact supported diagnostic source families;
- denial-before-access via the existing handler;
- trusted failure fallback to generic without recursion;
- one Web Application call and no second Kernel scope;
- send/close exact-once matrix and adapter retirement;
- why no concrete SAPI/server/emitter is selected;
- why Phase 2G remains non-runnable;
- security, persistent-runtime, performance, and compatibility consequences;
- rejected Symfony/Laravel/runtime-server substrate, error middleware,
  request-controlled debug flags, redacted trusted models, ambient current
  request, delivery retry, destructor cleanup, and swallowed simultaneous
  send/cleanup failure; and
- what remains open for Phase 2H and later.

### 13.2 New architecture model

Create:

```text
docs/architecture/web-runtime-model.md
```

It must explain the dependency graph, lifecycle, exact call sequence, trust
gates, response tables, send/close failure matrix, retention rules, integration
obligations, limits, examples, security properties, and Phase 2H boundary.

### 13.3 New package README

Create `packages/web-runtime/README.md` with concise installation/package
identity, API-status warning, exact two-class entry surface, per-delivery
contract, generic/trusted behavior, cleanup matrix, security obligations,
non-runnable status, and links to ADR 0049 and the model.

### 13.4 Existing documentation reconciliation

Modify only these existing documentation paths:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/glossary.md
docs/adr/README.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/phase-2-roadmap.md
docs/architecture/web-application-model.md
docs/architecture/http-message-model.md
docs/architecture/routing-model.md
docs/architecture/http-server-model.md
docs/engineering/package-layout.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
packages/kernel/README.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/web-application/README.md
```

Reconcile current behavior so every current document states accurately:

- Phase 2G and ADR 0049 are implemented;
- exactly 13 first-party packages exist and the graph is acyclic;
- Web Runtime owns outer orchestration only;
- Web Application still owns the sole Kernel operation scope;
- Generic production failure output is fixed, separate, and non-trusted;
- trusted HTML requires both typed permissions and supported sources;
- evidence evaluation is server-transport-owned and request-independent;
- the adapter provides ports but no concrete SAPI/server/listener/emitter;
- no runnable fixture exists yet;
- no logging, telemetry, incidents, authorization, DI resolution, Graph
  traversal, or broader cleanup was added;
- Phase 2H is next and remains the first runnable black-box milestone; and
- the package/API stability remains Experimental except the two Public markers.

Preserve clearly labeled historical statements. Reconcile stale current claims
such as `Phase 2G is next`, `exactly twelve packages`, `no generic production
failure path`, `trusted delivery remains wholly unimplemented`, and `no outer
adapter boundary`. Do not claim a production-ready emitter or trusted network
authorization mechanism.

---

## 14. Exact created and modified file boundary

### 14.1 Create exactly these 17 implementation/documentation paths

```text
packages/web-runtime/composer.json
packages/web-runtime/README.md
packages/web-runtime/src/Contract/WebDeliveryInterface.php
packages/web-runtime/src/Diagnostic/WebRuntimeDiagnostic.php
packages/web-runtime/src/Exception/ExceptionInterface.php
packages/web-runtime/src/Exception/WebRuntimeException.php
packages/web-runtime/src/Exception/RetiredWebRuntimeException.php
packages/web-runtime/src/Exception/DeliveryCleanupException.php
packages/web-runtime/src/Internal/FailureResponseFactory.php
packages/web-runtime/src/WebRuntimeAdapter.php
packages/web-runtime/tests/Fixture/ExplosiveKernelDiagnosticException.php
packages/web-runtime/tests/Unit/Diagnostic/WebRuntimeDiagnosticTest.php
packages/web-runtime/tests/Unit/Exception/WebRuntimeExceptionTest.php
packages/web-runtime/tests/Unit/Internal/FailureResponseFactoryTest.php
packages/web-runtime/tests/Unit/WebRuntimeAdapterTest.php
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/architecture/web-runtime-model.md
```

### 14.2 Modify exactly these 34 existing paths

```text
.php-cs-fixer.dist.php
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/glossary.md
docs/adr/README.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/phase-2-roadmap.md
docs/architecture/web-application-model.md
docs/architecture/http-message-model.md
docs/architecture/routing-model.md
docs/architecture/http-server-model.md
docs/engineering/package-layout.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
packages/kernel/README.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/web-application/README.md
```

The Phase 2G report is a caller record populated after all non-report gates; it
is not counted among the 17 created implementation/documentation paths.

No deletion, rename, move, symlink, junction, reparse point, alternate stream,
mode change, or path outside this closed boundary is authorized.

---

## 15. Composer, autoload, tooling, and architecture expansion

### 15.1 Root Composer

Add exactly:

```json
"kongono/web-runtime": "@dev"
```

to root `require`, and:

```json
"Kongono\\Web\\Runtime\\Tests\\": "packages/web-runtime/tests/"
```

to root `autoload-dev.psr-4`.

Append the package validation command to `validate:packages` in package order.
Do not change scripts, stability, repository options, audit policy, plugins,
platform configuration, or any other constraint.

### 15.2 Lock update

After the package manifest and root Composer changes, run exactly:

```powershell
composer update kongono/web-runtime --with-dependencies --no-interaction
```

If sandbox network/cache access alone blocks it, retry the identical command
with approved access.

The only authorized package-entry delta is one new local path package
`kongono/web-runtime`. Existing 16 production entries and all 66 development
entries, versions, source/dist references, requirements, licenses, and metadata
must remain exact. A root content-hash change and new Web Runtime path reference
are mechanical and authorized. Stop if Composer changes another package entry.

### 15.3 Tooling

Add the exact Web Runtime `src` and `tests` directories to PHP CS Fixer,
PHPStan, and PHPUnit source/test coverage using the existing ordering/style.
Do not change rule levels, exclusions, suppressions, baselines, process limits,
or quality gates.

### 15.4 API architecture enforcement

Extend `ApiStatusConventionTest.php` to enforce:

- exactly 100 production PHP declarations/files after Phase 2G;
- exact eight Web Runtime declarations and API statuses;
- exact class/interface/property/method shapes from this directive;
- exception-marker separation from all existing package markers;
- diagnostic uniqueness at exactly 113 IDs;
- exact two new diagnostic schemas and empty contexts;
- allowed direct package dependencies and absence of reverse edges;
- no Web Runtime dependency/import of Routing, HTTP Server, Diagnostic
  Presentation, PSR-15, a runtime server, Symfony, or Laravel;
- no existing production package dependency/import of Web Runtime;
- exact root/package PSR-4 mappings and tool coverage; and
- existing architecture assertions retained and extended, not replaced.

Do not use source-text assertions where reflection, Composer data, or runtime
behavior can prove the contract.

---

## 16. Validation gates

### Gate A - authenticated preflight

Verify directive identity, caller records, placeholder, Git/history/index/refs,
closed status inventory, hashes, inventories, modes, ordinary files, streams,
reparse points, encoding, and protected baseline before quality or mutation.

### Gate B - pre-change quality

Run unchanged:

```powershell
composer quality
```

Expected accepted baseline evidence is:

```text
security advisories  0
style findings       0 of 205
PHPStan errors       0
PHPUnit              437 tests, 7853 assertions
```

If only managed network/cache access blocks the locked audit, retry the
identical command with approved access.

### Gate C - implementation and focused proof

After exact edits, run:

```powershell
Get-ChildItem packages/web-runtime -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist packages/web-runtime/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --memory-limit=512M packages/web-runtime/src packages/web-runtime/tests tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff
```

The executor may correct only authorized Phase 2G source/test/document/tooling
paths when a focused gate exposes an implementation defect. Do not weaken the
contract or mutate protected prior implementation.

### Gate D - repository and documentation proof

Verify:

- exact 17 created and 34 modified paths;
- no deletions or unauthorized differences;
- exactly `13/13/100/118/218` for package directories, manifests, production
  PHP, test PHP, and style-covered PHP files;
- exactly 113 unique diagnostics with the required split;
- lock totals exactly `17/66` and only the new local entry;
- all local Markdown links resolve;
- strict UTF-8 without BOM, LF-only, one final LF, no trailing whitespace, no
  NUL, ordinary files, no reparse points/alternate streams, and mode `100644`
  for every changed/created path;
- current/future documentation is reconciled; and
- `git diff --check` passes.

### Gate E - final canonical quality

Run unchanged:

```powershell
composer quality
```

Record actual test/assertion totals. Require zero advisories, successful
platform requirements, zero style findings, zero PHPStan errors, and complete
PHPUnit success. Do not force predicted totals.

### Gate F - final preservation and report

Before report population, verify:

```text
tracked modifications  27
untracked paths        143
total status paths     170
staged paths           0
Git-visible paths      501
tracked files          358
```

The final counts derive from seven previously clean tracked documentation paths
entering the authorized modified set, the two caller records, and 17 new Phase
2G paths. Verify the exact path inventory rather than relying only on counts.

Branch, HEAD, tree, parent, commit count, index semantics, remotes, tags, and
repository-owned refs must remain unchanged. Only after all non-report gates
pass may the executor populate `Phase_2G.md`, then reverify its format and return
its byte count and SHA-256 outside the body.

---

## 17. Prohibited scope

Do not implement or select:

- a concrete SAPI, PHP-FPM, FrankenPHP, RoadRunner, OpenSwoole, ReactPHP,
  Workerman, socket, listener, server, process manager, emitter library, or
  deployment integration;
- direct superglobal/environment/filesystem configuration parsing;
- proxy, IP, TLS, certificate, operator, listener, audience, authentication,
  authorization, session, cookie, CSRF, or tenant policy;
- request-derived diagnostic activation or a debug query/header/cookie;
- a new permission/token/capability type or raw configuration grammar;
- a runnable fixture or end-to-end black-box Phase 2H application;
- a supplied error middleware or changes to existing middleware/routing;
- new routes, route grammar, status mappings other than exact 500 failure
  responses, redirects, 404 policy, or application handlers;
- logging, telemetry, tracing, metrics, events, incident IDs/storage,
  persistence, serialization, localization, documentation lookup, or source
  code display;
- changes to trusted projector, renderer, handler, activation enums, HTTP
  messages, Web Application, Kernel, Graph, or DI implementation;
- a second Kernel scope, Kernel transport hook, DI binding, Graph node, service
  locator, container-current request, registry, global, static, thread-local,
  or fiber-local state;
- response retry, cleanup retry, recovery/reset, worker/process termination,
  destructor I/O, cancellation, body/upload cleanup policy beyond the delivery
  contract, or broader resource lifetimes;
- third-party dependency changes, Symfony/Laravel components, plugins,
  suppressions, baselines, weakened analysis/style/audit policy;
- staging, commit, amend, reset, restore, clean, branch/tag/ref mutation, or
  history rewrite; or
- any Phase 2H or Phase 3 capability.

---

## 18. Stop rules

Stop immediately and preserve the current state when:

- directive or caller identity differs;
- a required authority/path is absent or ambiguous;
- the report placeholder is populated early;
- baseline Git/status/inventory/hash/file-safety differs;
- pre-change quality fails for a non-environmental reason;
- Composer changes any package entry other than the authorized new path package;
- implementation requires a path outside the exact boundary;
- trusted denial cannot be proven before source access;
- a failure path cannot close exactly once without violating existing contracts;
- a test exposes a production defect in a frozen prior package;
- final quality, links, encoding, streams, modes, inventories, or protected state
  differ; or
- any acceptance criterion cannot be satisfied exactly.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
IDENTIFY THE SMALLEST ARCHITECT DECISION REQUIRED
DO NOT GUESS OR REPAIR ADJACENT STATE
```

---

## 19. Strict acceptance criteria

Phase 2G is Complete only if all are true:

1. directive identity matched independently supplied values;
2. every mandatory authority was read completely;
3. caller records and zero-byte report matched;
4. Git/history/index/ref/status/file-safety baseline matched;
5. pre-change canonical quality passed at the accepted baseline;
6. exactly one `kongono/web-runtime` package was created;
7. exactly eight production declarations have exact API statuses and shapes;
8. `WebDeliveryInterface` has the exact four-method contract;
9. bootstrap denial skips delivery evaluation;
10. delivery permission is evaluated before request conversion and never from
    a request value;
11. evidence and conversion failure follow generic output without application
    or trusted source access;
12. one exact request reaches one exact Web Application call;
13. no second Kernel operation scope exists;
14. success sends the exact application response;
15. generic response status, headers, and bytes are exact and disclosure-free;
16. trusted response status, headers, CSP, and exact HTML bytes are exact;
17. only supported Kernel/Graph/DI source families can reach trusted handling;
18. both permissions must be affirmative;
19. denial occurs before every diagnostic source accessor;
20. presentation failure falls back generic without recursion;
21. send and close each occur at most/exactly once as specified;
22. send-only and close-only failures propagate by exact identity;
23. simultaneous send/preparation and close failure preserves both exact
    identities in `DeliveryCleanupException`;
24. uncertain application or delivery state retires the adapter;
25. retired refusal occurs before all dependency access;
26. sequential/reentrant tests prove no cross-delivery retention;
27. two Web Runtime diagnostics are unique, fixed, and empty-context;
28. exception marker/base/concrete boundaries are exact and separate;
29. ADR 0049 is Accepted and documentation is current/future accurate;
30. no concrete runtime, listener, emitter, request-derived activation, logging,
    incident system, or runnable fixture exists;
31. Composer added only the local Web Runtime entry and preserved every existing
    entry;
32. package/manifests/production/test/style inventories are exactly
    `13/13/100/118/218`;
33. diagnostic and lock totals are exactly `113` and `17/66`;
34. strict autoload, focused tests, architecture tests, PHPStan, style, links,
    encoding, file safety, and `git diff --check` pass;
35. final canonical quality passes without weakened policy;
36. exactly 17 paths were created, 34 existing paths modified, and zero paths
    deleted outside report population;
37. final status is exactly 27 modified, 143 untracked, 170 total, zero staged,
    and 501 Git-visible paths;
38. Git branch/history/tree/index/repository-owned refs remain unchanged;
39. the completion report was populated only after criteria 1-38 passed;
40. the report has the exact required structure, supported evidence, and final
    sentence;
41. report byte count and SHA-256 are returned independently; and
42. no Phase 2H or later work began.

If any item fails, status must not be Complete.

---

## 20. Standard executor completion report

Populate `docs/task reports/Phase_2G.md` only after every other gate passes.
Use exactly these top-level headings:

```text
# PHASE 2G TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE BOUNDARY
## 8. PRODUCTION IMPLEMENTATION
## 9. DELIVERY CONTRACT AND ORDER
## 10. GENERIC AND TRUSTED FAILURE DELIVERY
## 11. TRANSPORT CLEANUP AND RETIREMENT
## 12. TEST COVERAGE
## 13. ARCHITECTURAL DECISIONS
## 14. DEVIATIONS FROM DIRECTIVE
## 15. DEPENDENCIES ADDED/REMOVED
## 16. ENVIRONMENT
## 17. COMMANDS RUN
## 18. VALIDATION RESULTS
## 19. DOCUMENTATION REVIEW
## 20. SECURITY AND FILE-SAFETY REVIEW
## 21. WARNINGS / KNOWN ISSUES
## 22. QUESTIONS / BLOCKERS
## 23. GIT DIFF SUMMARY
## 24. ACCEPTANCE CRITERIA
## 25. NEXT STEP
```

The report must record:

- directive and caller identities;
- exact baseline and final Git/inventory state;
- every created and modified path;
- package graph and lock delta;
- exact production/API/diagnostic contracts;
- permission/request/application/send/close ordering;
- generic and trusted response bytes/headers;
- denial-before-access proof;
- failure and retirement matrix;
- focused and canonical test/assertion totals;
- PHPStan/style/audit/platform/link/file-safety results;
- any approved identical network/cache retry;
- no staging/commit/ref/history mutation;
- no concrete runtime or Phase 2H work;
- final report byte count/SHA returned outside the body; and
- candid deviations, warnings, or blockers.

Its exact final sentence must be:

```text
Await architect review before Phase 2H.
```

The populated report must be UTF-8 without BOM, LF-only, have exactly one final
LF, no trailing whitespace/NUL, remain ordinary, untracked, unstaged, absent
from `HEAD`, and not contain its own final byte count or digest.

---

## 21. Required final outcome

Kongono must finish Phase 2G with one explicit, inspectable outer web-runtime
boundary that safely coordinates existing request handling and diagnostic
components without becoming a server or framework substrate.

Normal responses are delivered unchanged. Generic production failure output is
fixed and non-disclosing. Trusted developer HTML is available only through the
existing eligibility-first path when both typed decisions are affirmative.
Transport cleanup is exact-once, simultaneous escaped failures preserve both
objects, and uncertain adapter state retires fail-closed.

The framework remains non-runnable until Phase 2H supplies the minimal fixture
delivery and black-box acceptance proof. After successful Phase 2G completion,
return the completion report and await architect review before Phase 2H.
