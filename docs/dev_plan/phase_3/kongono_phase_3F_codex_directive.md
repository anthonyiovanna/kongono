# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3F

## Supported PHP-CGI Delivery and Production Entry Composition

**Directive date:** 2026-08-21
**Repository:** `C:\Users\antho\Dev\kongono`
**Execution role:** executor, not architect
**Scope:** Phase 3F only
**Checkpoint:** none; do not stage or commit

---

## 1. Mission

Implement the smallest supported production composition and entry boundary for
the PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` profile already selected and
implemented through Phase 3E.

Phase 3F must add exactly two production roles inside the existing
`kongono/php-cgi` leaf:

1. one fresh concrete `WebDeliveryInterface` implementation that captures the
   closed native PHP-CGI input projection, converts one request, emits one
   response, and owns exact delivery-resource cleanup; and
2. one thin production entry call that creates that delivery and hands it to an
   already-composed `WebRuntimeAdapter`.

The result must use the existing ownership chain:

```text
consumer front controller / explicit composition root
    -> CgiOperatorBootstrap
    -> WebApplication
    -> WebRuntimeAdapter
    -> CgiWebEntry
        -> fresh CgiWebDelivery
            -> CgiServerRequestConverter
            -> CgiResponseEmitter
```

`WebRuntimeAdapter` remains the owner of failure selection plus one send and one
close attempt. `WebApplication` remains the sole owner of one Kernel operation
scope around an accepted application call. Kernel remains unaware of HTTP,
PHP-CGI, FastCGI, superglobals, emission, and deployment.

This is a thin vertical slice. It is not deployment hardening, an external
acceptance harness, a first-party listener, a process manager, or a complete
application template. Phase 3G alone follows Phase 3F.

---

## 2. Locked authority and doctrine

### 2.1 Read before any validation or mutation

Read these files completely:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
phpunit.xml.dist
phpstan.neon.dist
.php-cs-fixer.dist.php

docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md

docs/adr/0001-kongono-identity.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0050-phase-2-runnable-fixture-and-checkpoint.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md

packages/php-cgi/composer.json
packages/php-cgi/README.md
packages/php-cgi/src/Bootstrap/CgiOperatorBootstrap.php
packages/php-cgi/src/Bootstrap/CgiOperatorProfile.php
packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/src/Emission/CgiResponseEmitter.php
packages/php-cgi/src/Internal/CgiTrustedPresentationPermissionEvaluator.php
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Exception/CgiRequestReadException.php

packages/web-runtime/composer.json
packages/web-runtime/README.md
packages/web-runtime/src/Contract/WebDeliveryInterface.php
packages/web-runtime/src/WebRuntimeAdapter.php
packages/web-application/src/WebApplication.php

tests/BlackBox/Fixture/FixtureDelivery.php
tests/BlackBox/Fixture/Phase2FixtureApplication.php
tests/Architecture/ApiStatusConventionTest.php

docs/dev_plan/phase_3/kongono_phase_3E_codex_directive.md
docs/dev_plan/phase_3/kongono_phase_3E_1_codex_directive_correction.md
docs/dev_plan/phase_3/kongono_phase_3E_2_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3E_1_correction.md
docs/task_reports/phase_3/Phase_3E_2_correction.md
```

Do not substitute similarly numbered or similarly titled ADRs. If any required
path is absent, stop before quality or mutation and report the exact path.

### 2.2 Independent directive authentication

The architect must provide this directive's byte count and SHA-256 outside the
file. Compare those independently supplied values with the local copy before
continuing.

Do not treat the local file as self-authenticating. On mismatch or missing
external values:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT RUN QUALITY OR MUTATE THE REPOSITORY
```

### 2.3 Locked doctrine

Preserve all accepted Kongono doctrine, including:

- persistent-runtime-first lifecycle and no ambient current operation;
- explicit construction and typed call flow rather than service location;
- no global, static, singleton, container-current, thread-local, or fiber-local
  request, response, permission, failure, or operation state;
- fail-closed trusted diagnostics with independent bootstrap and current-delivery
  permission sources;
- actionable, bounded, non-sensitive diagnostics;
- package dependency direction and neutral Kernel ownership;
- exact API-status metadata;
- strict types, PSR-4, PHPStan without suppression, and canonical quality;
- historical directives and reports as immutable evidence; and
- stop rather than guess whenever an authenticated invariant differs.

Phase 3F does not authorize changing any previously locked contract unless this
directive states the exact change.

---

## 3. Authenticated starting state

### 3.1 Repository identity before the two Phase 3F caller records

The accepted repository state is:

```text
branch:                       main
local branches:                 1
HEAD:                         647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                         3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                       d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:                  10
remotes:                        0
tags:                           0
modified tracked paths:        28
deleted tracked paths:         185
untracked paths:               241
total status paths:            454
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    592
package directories:            14
package manifests:              14
production PHP files:          121
test PHP files:                137
style-covered PHP files:       258
```

The semantic index equals `HEAD`. Existing `refs/codex/**` application-managed
refs are permitted and must remain untouched. Other than `refs/heads/main`, no
branch, remote, tag, or unexpected non-application ref exists.

The accepted root lockfile is:

```text
bytes:   197483
SHA-256: 448956E068E0601B48EE81EF49489170A859302FB0BC62555A1C2C238A536259
```

### 3.2 Complete physical-file manifest

From every existing physical file returned by:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

ignore cached paths absent from the worktree. Normalize paths to `/`, sort with
`[StringComparer]::Ordinal`, and serialize each record as:

```text
path<TAB>byte-count<TAB>uppercase-SHA-256<LF>
```

Encode the manifest as UTF-8 without BOM. Before the Phase 3F caller records
are added, the exact result is:

```text
records:               592
manifest bytes:      74537
manifest SHA-256:    805792B6FA3C5E70334986A6683473348443525F66BCA2DB2F6D1CABC098F36A
aggregate file bytes: 6485980
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

During Gate A, when the two caller records already exist, exclude exactly those
two paths before reconstructing this 592-record baseline manifest.

### 3.3 Historical numbered archive

All 204 accepted historical records under the eight numbered folders are
immutable during Phase 3F. Using the same serialization as Section 3.2:

```text
records:               204
manifest bytes:      25591
manifest SHA-256:    A2690631F47C2631E9A0F01E5B9CD0982FD6CBBDAFA2AB6BFFEA8C0F4FFA1D69
aggregate file bytes: 4157431
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3E_2_correction.md
```

The Phase 3E.2 caller records within that manifest are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3E_2_codex_directive_correction.md` | 27,230 | `519AB6DA851C2F7669425D8B20E9108F0B25EAF3AC46F12B989CDEB452A9AEE7` |
| `docs/task_reports/phase_3/Phase_3E_2_correction.md` | 14,714 | `B5D7159527408B7FCE824C1B6A0F02D645C92C8FB4AEC195259B30774B9461B8` |

Do not rewrite embedded historical paths, wording, hashes, commands, or status
evidence. Historical content is not current navigation text.

### 3.4 Active Phase 3F caller records

Before execution the caller must place exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md
docs/task_reports/phase_3/Phase_3F.md
```

The directive must match the independently supplied identity. The report must
be an ordinary, untracked, unstaged, `HEAD`-absent zero-byte file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

With these two caller records present and before implementation, require:

```text
modified tracked paths:        28
deleted tracked paths:         185
untracked paths:               243
total status paths:            456
staged paths:                    0
physical Git-visible files:    594
tracked index paths:           536
docs/dev_plan/phase_3 files:    10
docs/task_reports/phase_3 files: 10
```

The 204-record historical manifest excludes the two active Phase 3F caller
records and must remain exact.

### 3.5 Authorized-start and protected manifests

The 33 existing paths authorized for possible Phase 3F modification are listed
in Section 5.3. Their exact starting manifest is:

```text
records:                33
manifest bytes:       3578
manifest SHA-256:     9241EE14AE7124FF95A0D21D0AA678C700732AE539548DEC1BC236B22D949F61
aggregate file bytes: 740483
first path:           ARCHITECTURE.md
last path:            tests/Architecture/ApiStatusConventionTest.php
```

Every other accepted physical file forms the protected manifest:

```text
records:               559
manifest bytes:      70959
manifest SHA-256:    1356F42336D439749A180968FB4F342DCD2BD213179B30C2DBD5565D9FDA687B
aggregate file bytes: 5745497
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

Reconstruct both using Section 3.2's exact serialization. The protected
manifest must remain identical through final report population. At final
validation exclude the two active caller records, all six Section 5.1 created
paths, and all 33 Section 5.3 mutable paths before reconstructing the protected
manifest.

If any Section 3 invariant differs, stop before quality or mutation. Do not
repair, restore, truncate, rename, stage, commit, or adopt a locally observed
replacement value.

---

## 4. Pre-change validation

After Sections 2 and 3 pass, run literal:

```powershell
composer quality
```

Require:

- Composer validation completes under existing policy;
- locked advisory audit reports no vulnerability advisories;
- platform requirements pass;
- PHP CS Fixer reports zero fixable files;
- PHPStan reports zero errors; and
- PHPUnit passes completely.

The established missing-license and local unbounded-development-constraint
recommendations remain non-failing policy state. If the unchanged locked audit
fails solely because the managed environment cannot access its normal cache or
Packagist, repeat the identical `composer quality` command once with approved
network/cache access. No other retry, audit skip, policy weakening, alternate
command, memory overlay, baseline, or suppression is authorized.

Normal PHP must remain at `memory_limit=128M`; the root `analyse` script already
owns the accepted 512 MiB PHPStan child limit. `PHP_INI_SCAN_DIR` must remain
absent.

---

## 5. Exact file boundary

### 5.1 Create exactly these six implementation paths

```text
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
packages/php-cgi/src/Delivery/CgiWebDelivery.php
packages/php-cgi/src/Entry/CgiWebEntry.php
packages/php-cgi/src/Exception/CgiDeliveryCleanupException.php
packages/php-cgi/tests/Unit/Delivery/CgiWebDeliveryTest.php
packages/php-cgi/tests/Unit/Entry/CgiWebEntryTest.php
```

### 5.2 The caller alone creates these two records

```text
docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md
docs/task_reports/phase_3/Phase_3F.md
```

Do not recreate, rename, truncate, or move them. Populate only the report and
only after every non-report gate passes.

### 5.3 Modify exactly these 33 existing paths

```text
ARCHITECTURE.md
README.md
SECURITY.md
composer.lock
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/architecture/runtime-model.md
docs/architecture/web-runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/diagnostic-presentation/README.md
packages/php-cgi/README.md
packages/php-cgi/composer.json
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
packages/web-runtime/README.md
tests/Architecture/ApiStatusConventionTest.php
```

Each listed path must receive a real Phase 3F change required by Sections 6
through 11. Do not touch another existing path. Do not run a broad formatter or
documentation rewriter.

---

## 6. Package and dependency contract

The package remains:

```text
directory: packages/php-cgi
Composer:  kongono/php-cgi
namespace: Kongono\PhpCgi\
role:      outer PHP-CGI leaf
```

Add exactly one direct production dependency:

```json
"kongono/web-runtime": "*"
```

The complete direct requirement map must otherwise remain unchanged. In
particular, do not add a direct dependency on Web Application, Kernel, Routing,
HTTP Server, Application Graph, Dependency Injection, Diagnostic Presentation,
Diagnostic Handler, a web server, or a FastCGI library.

The allowed production graph is:

```text
host / consumer composition root
    -> kongono/php-cgi
        -> kongono/web-runtime
            -> kongono/web-application and existing lower packages
```

No lower package may depend on `kongono/php-cgi`.

Update the lockfile with the narrow package command:

```powershell
composer update kongono/php-cgi --no-interaction
```

If it fails solely for managed network/cache access, repeat that identical
command once with approved access. The semantic lock delta may contain only:

- the `kongono/php-cgi` requirement addition for `kongono/web-runtime: *`;
- the resulting `kongono/php-cgi` path-package reference refresh; and
- no root `content-hash`, version, third-party package, policy, plugin, platform,
  stability, source URL, or unrelated local-package change.

Stop if Composer changes any unrelated path-package reference. Do not manually
edit `composer.lock` to force a desired result.

After the update run:

```powershell
composer validate --no-check-publish
composer dump-autoload --optimize --strict-psr
```

No dependency may be added, removed, upgraded, or downgraded beyond the exact
local edge above.

---

## 7. Concrete one-delivery boundary

Create:

```text
Kongono\PhpCgi\Delivery\CgiWebDelivery
```

### 7.1 Declaration and status

It must be:

- `final` and non-readonly because it owns an explicit one-delivery lifecycle;
- marked `#[Api(ApiStatus::Internal)]`;
- an implementation of Public
  `Kongono\Web\Runtime\Contract\WebDeliveryInterface`;
- non-cloneable;
- free of destructor and shutdown-function behavior; and
- free of static mutable state.

Its only declared public methods are:

```php
public static function capture(): self;

/**
 * @param array<array-key, mixed> $server
 * @param \Closure(): mixed       $openInput
 * @param null|\Closure(mixed): bool $closeInput
 */
public static function fromSnapshot(
    string $sapi,
    array $server,
    bool $postEmpty,
    bool $filesEmpty,
    \Closure $openInput,
    ?\Closure $closeInput = null,
): self;

public function evaluateTrustedPresentationPermission(): AdapterControlledDeliveryPermission;
public function request(): ServerRequestInterface;
public function send(ResponseInterface $response): void;
public function close(): void;
```

`fromSnapshot()` is an Internal deterministic construction seam for production
tests and package composition. Its optional closer exists only to prove native
close-result behavior without modifying PHP process state. A null closer uses
one warning-suppressed `fclose()` call whose false result maps to diagnostic
1018. This is not a consumer extension point,
configuration API, provider registry, or injectable transport framework.

### 7.2 Native capture boundary

Only `capture()` may read:

```text
PHP_SAPI
$_SERVER
whether $_POST is empty
whether $_FILES is empty
php://input, through a lazy opener used later by request()
```

It must never read or retain:

```text
$_ENV
$_REQUEST
$_GET
$_COOKIE
post values
file values
getenv()
arbitrary environment variables
an unfiltered whole-server snapshot
```

Project from `$_SERVER` only the existing Phase 3C allowlist: the required CGI
keys, authorized optional CGI keys, and string keys beginning `HTTP_`. Ignore
unselected string keys. A non-string key must force a fixed invalid sentinel
without retaining the original key or value so later evaluation denies and
request conversion rejects deterministically.

Copy selected entries by value into a detached array. Do not retain references
to the superglobal array or its elements. Preserve allowed values for the
existing converter to validate; do not coerce, stringify, trim, normalize, or
reinterpret them.

Represent nonempty `$_POST` and `$_FILES` only as the two booleans supplied to
`fromSnapshot()`. Do not copy or inspect nested values. `request()` must pass
`[]` for an empty source and one fixed non-sensitive sentinel entry for a
nonempty source so the existing converter emits its established
`post_data_present` or `upload_data_present` failure.

The input opener must not run during capture or trusted-permission evaluation.
Open `php://input` in binary read mode only when `request()` is called. This
keeps protected input access after Web Runtime's permission decision and inside
its generic-failure/cleanup orchestration.

Add exactly this closed token to `PhpCgiDiagnostic::InputReadFailed` without
changing its ID, schema, title, operation, invariant, or existing tokens:

```text
input_open_failed
```

If the lazy native open returns no stream, throw
`CgiRequestReadException('input_open_failed')`. An opener-thrown external
`Throwable` propagates unchanged. No raw path, warning, environment value,
request value, or failure message may enter package diagnostic context.

### 7.3 Permission, request, and emission flow

Each delivery instance represents one host invocation.

`evaluateTrustedPresentationPermission()` must:

- require an open delivery;
- run at most once;
- call `CgiTrustedPresentationPermissionEvaluator::evaluate()` with the exact
  captured SAPI label and detached projected server snapshot;
- return the exact typed decision; and
- never store the returned permission or combine it with bootstrap permission.

`request()` must:

- require an open delivery and run at most once;
- lazily acquire the input stream;
- call one fresh `CgiServerRequestConverter` exactly once with the captured
  SAPI, detached server snapshot, fixed empty/nonempty sentinels, and exact
  input stream;
- retain only the returned request for later stream cleanup;
- clear the SAPI/server/post/file/opener capture state in a `finally` path after
  the conversion attempt; and
- propagate converter and external failures unchanged.

The delivery's response-emission method is fixed to `HEAD` only when the raw
captured method is exactly the string `HEAD`; otherwise its safe pre-conversion
fallback is `GET`. After successful conversion it uses the exact validated
request method. This ensures a malformed `HEAD` request still receives no body
bytes when Web Runtime selects its fixed generic response.

`send()` must:

- require an open delivery and run at most once;
- capture the exact response body object for later close before emission;
- call one fresh `CgiResponseEmitter::emit()` exactly once with the exact
  response and effective request method;
- never retry, remove headers, roll back output, or emit a second fallback; and
- propagate response and emitter failures unchanged.

The entry/delivery layer must not catch converter failures and choose HTTP
semantics. Web Runtime already maps request-conversion failure to its fixed
generic 500 and still performs one send and close. Phase 3F adds no semantic
400, 405, 413, 415, or 431 mapping.

### 7.4 Cleanup and lifecycle

`close()` must:

- be attempted exactly once by Web Runtime;
- atomically detach all retained references before invoking external cleanup;
- close the owned first-party request body, captured response body, and native
  input stream exactly once each;
- close a shared request/response body object only once by object identity;
- attempt every distinct cleanup even after one fails;
- preserve and rethrow the first cleanup `Throwable` unchanged after all
  cleanup attempts;
- turn a native input-close false result into the package-owned cleanup failure
  from Section 7.5 without disclosing a warning;
- propagate an explicit closer-thrown `Throwable` unchanged while still
  completing the remaining distinct cleanup attempts;
  and
- retain no SAPI, server value, form/upload flag, opener, input, request,
  response body, permission, or failure after return or throw.

Repeated permission, request, send, or close access and use after close are
Internal contract violations. Reject them deterministically with fixed
package-neutral `LogicException` messages containing no request or failure
data. Do not add a public state-inspection API.

Do not rely on process teardown, garbage collection, a destructor, output
buffering, or a shutdown function for cleanup.

### 7.5 Actionable native-input cleanup failure

Add:

```text
Kongono\PhpCgi\Exception\CgiDeliveryCleanupException
PhpCgiDiagnostic::DeliveryCleanupFailed = KGN-PHP-CGI-1018
```

The exception must be `final`, Experimental, extend `RuntimeException`, and
implement the existing PHP-CGI exception marker. Its constructor is:

```php
public function __construct(string $violation, ?Throwable $previous = null);
```

Diagnostic 1018 has exactly one Safe text field named `violation` and this
single closed token:

```text
input_close_failed
```

Use fixed actionable metadata:

```text
title:      Unable to close PHP CGI delivery input
operation:  Close PHP CGI delivery resources
invariant:  Every delivery-owned native input stream must close exactly once
remediation: Retire the delivery and correct the selected host input-resource failure before serving again
```

The exception retains no resource, request value, warning text, host path, or
raw failure message. It owns the same four final declared diagnostic accessors
as the existing concrete PHP-CGI exceptions:

```text
diagnosticContext
diagnosticId
diagnosticTitle
diagnosticRemediation
```

Its only instance properties are the fixed diagnostic enum and eagerly built
detached diagnostic context. Request-body or response-body cleanup throwables
remain external owner failures and propagate unchanged; do not relabel them as
1018.

---

## 8. Thin production entry

Create:

```text
Kongono\PhpCgi\Entry\CgiWebEntry
```

It must be:

- `final` with a private constructor;
- marked `#[Api(ApiStatus::Experimental)]`;
- stateless and non-instantiable; and
- limited to this one declared public method:

```php
public static function run(WebRuntimeAdapter $runtime): void;
```

`run()` must perform exactly:

1. create one fresh `CgiWebDelivery::capture()`;
2. call the supplied `WebRuntimeAdapter::handle()` once with that delivery; and
3. return or propagate the exact runtime failure.

It must not catch, remap, log, retry, emit, close, shutdown, recover, terminate
the process, or retain the runtime/delivery. Web Runtime owns send and close.

The consumer's explicit composition order is:

```text
1. select literal CgiOperatorProfile
2. CgiOperatorBootstrap::load(profile)
3. construct and boot WebApplication explicitly
4. construct WebRuntimeAdapter(application, bootstrapPermission)
5. CgiWebEntry::run(runtime)
6. explicitly shut down the WebApplication under the caller's lifecycle policy
```

The PHP-CGI package must not construct Web Application, Kernel, routes,
middleware, handlers, DI definitions, or Application Graph nodes. It receives
only the already-composed runtime. Phase 3G owns the later bounded hardening of
front-controller failure/shutdown composition and deployment constraints.

Do not add a repository `public/index.php`, application template, executable,
server configuration, deployment file, CLI command, or framework-wide
bootstrap helper in Phase 3F.

---

## 9. Production tests

### 9.1 `CgiWebDeliveryTest`

Require focused tests for at least:

- final/Internal/interface declaration shape and exact public method inventory;
- detached closed server projection and exclusion of unselected globals;
- fixed invalid sentinel behavior for a non-string server key;
- nonempty post/file flags reject without retaining nested sentinel data;
- input opener remains untouched during construction and permission evaluation;
- the Internal explicit closer seam proves false-result and thrown-failure
  behavior without global function substitution;
- exact loopback permission allow and forwarded/nonloopback/invalid denial;
- valid snapshot conversion returns one first-party request with exact body;
- lazy input-open failure uses diagnostic 1010 and `input_open_failed`;
- opener `Throwable` identity propagates unchanged;
- exact method selection for successful requests, generic malformed `HEAD`, and
  non-HEAD malformed input;
- one response emission and no retry;
- request, response, and input resources close exactly once;
- a shared request/response body closes once;
- cleanup continues after one failure and rethrows the first failure object;
- native input-close failure uses diagnostic 1018 and `input_close_failed`
  without raw warning text;
- diagnostic catalog and package-exception inventories include the exact new
  ID, metadata, token, type, status, marker, constructor, and accessors;
- state references are released before failing cleanup;
- repeated/out-of-order/use-after-close calls reject deterministically; and
- alternating valid, invalid, trusted-denied, `HEAD`, and non-`HEAD` deliveries
  share no state.

Use actual package classes and bounded local streams. Test-only seams must not
be promoted to Public API. Do not use serialization, private-property mutation,
dynamic code generation, `eval`, a PHPStan suppression, or source rewriting.

### 9.2 `CgiWebEntryTest`

Require focused tests for at least:

- final/Experimental/private-constructor/static-method declaration shape;
- exact delegation to one fresh delivery and one `WebRuntimeAdapter::handle()`
  call through the real runtime path;
- native CLI mismatch reaching Web Runtime's fixed generic response without
  application handling, using process isolation where native header/output
  state requires it;
- no entry-owned catch, retry, shutdown, retained state, or second send; and
- a subsequent fresh composition remaining unaffected by an earlier delivery.

Phase 3F tests are production package tests, but they are not the Phase 3H
external CGI/FastCGI acceptance harness. Do not claim real web-server or
FastCGI process acceptance here.

### 9.3 Architecture enforcement

Update `tests/Architecture/ApiStatusConventionTest.php` to enforce:

- the two new production declarations and their exact statuses;
- the direct `php-cgi -> web-runtime` edge;
- absence of a direct `php-cgi -> web-application` or lower-layer reverse edge;
- confinement of superglobal/PHP-SAPI/php-input access to the concrete delivery
  capture boundary and the existing bootstrap validator as applicable;
- no environment, listener, socket, process-manager, container, or ambient
  current-request surface; and
- no undocumented API-status declaration.

Do not weaken any existing architecture assertion.

---

## 10. ADR 0056

Create:

```text
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
```

Use:

```text
Status: Accepted
Date: 2026-08-21
```

Record:

1. the Phase 3F problem and bounded decision;
2. why one concrete Internal delivery belongs in the existing outer PHP-CGI
   leaf;
3. why the entry accepts an already-composed `WebRuntimeAdapter` rather than
   adding a direct Web Application dependency;
4. the explicit consumer composition order and independent trust sources;
5. the exact native capture allowlist and lazy `php://input` ownership;
6. one-delivery state, response-method fallback, exact emission, cleanup, and
   reference-release behavior;
7. dependency direction and API statuses;
8. generic failure behavior for conversion rejection;
9. persistent-runtime isolation and no ambient current request;
10. consequences and limits, including bounded full buffering already selected
    in 3C/3D;
11. rejected alternatives: framework current-request globals, a service
    locator/container lookup, direct PHP-CGI-to-Web-Application dependency,
    a new package, dynamic profile parsing, automatic retry, a first-party
    listener, and production entry script generation; and
12. what remains for Phase 3G and Phase 3H.

Do not claim Stable API, production-hardening certification, broader server
support, or Phase 3 completion.

---

## 11. Documentation reconciliation

Modify every Markdown path in Section 5.3 truthfully and only as needed to:

- mark Phase 3F's concrete delivery and thin entry composition implemented;
- identify Phase 3G alone as next;
- preserve the fixed A-H roadmap table text exactly;
- document the direct PHP-CGI-to-Web-Runtime edge and continued absence of a
  direct Web Application edge;
- document the exact public/Internal/Experimental statuses;
- explain native projection, lazy body acquisition, one-delivery cleanup, and
  generic invalid-input behavior;
- show the explicit consumer composition order without presenting an incomplete
  deployment-ready template;
- distinguish implemented delivery/entry behavior from future 3G hardening,
  deployment constraints, and 3H external acceptance;
- preserve production trusted diagnostics as denied by the `Production`
  operator profile;
- state that `LoopbackDevelopment` still requires independent direct-loopback,
  non-forwarded evidence;
- keep historical numbered records out of current link rewriting; and
- preserve all unrelated current and future doctrine.

Update the ADR index with ADR 0056. Ensure every current local link resolves.

Do not modify the 204 historical records. Do not replace historical embedded
paths with numbered paths. Do not describe the package as a listener, server,
process manager, deployment solution, general SAPI adapter, or complete
framework.

---

## 12. Explicitly prohibited scope

Do not implement or modify:

- DI/container resolution, compile-time wiring, property-hook injection,
  autowiring, service location, or Application Graph behavior;
- Routing, HTTP Server, HTTP Message, Web Application, Web Runtime, Kernel, or
  diagnostic presentation/handler production PHP;
- route schemas, handlers, middleware, controllers, authentication,
  authorization, sessions, CSRF, rate limiting, or proxy trust;
- a first-party listener, FastCGI protocol parser, socket, worker pool, process
  manager, reload/restart mechanism, supervisor, or server;
- deployment configuration, TLS, reverse-proxy rules, web-server files,
  containers, images, or infrastructure;
- a framework-wide configuration system, `.env` parser, secrets manager,
  runtime profile string, file watcher, or mutable operator profile;
- logging, telemetry, tracing, metrics, incident storage, recovery, or automatic
  uncertain-runtime reuse;
- streaming responses, uploads, multipart forms, transfer decoding, response
  retry, semantic client-error mapping, or new body/header limits;
- ORM, database, migrations, cache, queue, events, mail, scheduler, templates,
  frontend, realtime, CLI, agents, extensions, MCP, or A2A;
- PHP 8.6/PHP 9 compatibility claims or syntax unavailable to PHP 8.5;
- dependency upgrades, policy changes, plugins, licenses, versioning, release
  claims, or Stable API; or
- staging, committing, tags, branches, remotes, refs, or history changes.

No source under another production package may change.

---

## 13. Validation gates

### Gate A - authentication and baseline

Require Sections 2 through 4 to pass before mutation.

### Gate B - exact implementation boundary

After implementation prove:

- exactly the six Section 5.1 paths were created;
- exactly the 33 Section 5.3 paths were modified;
- no other path changed;
- the two active caller records remain untracked and unstaged;
- the 204-record historical manifest remains exact; and
- the 559-record protected manifest remains exact.

### Gate C - dependency and autoload

Require:

- exact one-edge manifest delta;
- exact semantic lock delta from Section 6;
- fourteen package directories and manifests;
- no dependency cycle or lower reverse edge;
- strict optimized PSR-4 autoload pass; and
- no Composer policy change.

### Gate D - focused PHP checks

Run syntax checks for the four new PHP files and the modified diagnostic/API
architecture files. Then run, at minimum:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

Require zero PHPStan errors, zero style findings, and complete test success. Do
not use a suppression, baseline, ignore rule, test skip, incomplete test,
process-memory overlay, or weakened assertion.

### Gate E - semantic and security proofs

Prove directly:

- only the selected capture boundary reads the allowed native request sources;
- no `$_ENV`, `$_REQUEST`, `$_GET`, `$_COOKIE`, `getenv`, arbitrary environment,
  container lookup, static current request, listener, socket, or process API was
  added;
- permission evaluation occurs before lazy body opening when bootstrap allows;
- bootstrap denial skips delivery permission but still permits normal request
  conversion through existing Web Runtime behavior;
- malformed input reaches generic output through Web Runtime and never reaches
  Web Application;
- no raw host/request/failure value enters diagnostic context or fixed messages;
- no second emission occurs after preflight, committed, or uncertain failure;
- every owned resource is closed once and references are released after cleanup;
- alternating deliveries retain no state; and
- application/Kernel scope ownership remains unchanged.

### Gate F - documentation and file safety

Require:

- ADR 0056 exact status/date and required decisions;
- all current/future statements reconciled;
- fixed Phase 3 A-H table byte-identical;
- zero broken local links in current nonhistorical Markdown;
- strict UTF-8 without BOM, LF-only, final LF, no trailing whitespace, no NUL;
- ordinary default-stream files, no reparse points or nested repositories;
- unchanged tracked modes; and
- `git diff --check` pass.

### Gate G - canonical quality

Run literal:

```powershell
composer quality
```

Use only the one unchanged approved-access retry permitted in Section 4. Require
zero advisories, platform pass, zero style findings, zero PHPStan errors, and a
completely passing PHPUnit suite. Record exact counts.

### Gate H - final preservation and report

Before report population require exactly:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               249
total status paths:            464
staged paths:                    0
physical Git-visible files:    600
tracked index paths:           536
package directories:            14
package manifests:              14
production PHP files:          124
test PHP files:                139
style-covered PHP files:       263
docs/dev_plan/phase_3 files:    10
docs/task_reports/phase_3 files: 10
```

Also require unchanged branch, refs, `HEAD`, tree, parent, commit count,
semantic index, normal 128M PHP, absent `PHP_INI_SCAN_DIR`, exact caller
identities, exact historical/protected manifests, modes, and streams.

Only then populate `docs/task_reports/phase_3/Phase_3F.md`. After population,
rerun report integrity, exact inventories, historical/protected manifests, Git
identity/status, no-staging, and no-history-change proofs.

No staging or commit command is authorized.

---

## 14. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND DEPENDENCY CONTRACT
## 8. CONCRETE DELIVERY
## 9. NATIVE CAPTURE AND REQUEST CONVERSION
## 10. RESPONSE EMISSION AND CLEANUP
## 11. PRODUCTION ENTRY COMPOSITION
## 12. TRUST AND DISCLOSURE
## 13. PERSISTENT-RUNTIME ISOLATION
## 14. ARCHITECTURAL DECISIONS
## 15. DEVIATIONS FROM DIRECTIVE
## 16. DEPENDENCIES ADDED/REMOVED
## 17. ENVIRONMENT
## 18. COMMANDS RUN
## 19. VALIDATION RESULTS
## 20. DOCUMENTATION REVIEW
## 21. SECURITY AND FILE-SAFETY REVIEW
## 22. WARNINGS / KNOWN ISSUES
## 23. QUESTIONS / BLOCKERS
## 24. FINAL GIT STATUS
## 25. ACCEPTANCE CRITERIA
## 26. NEXT STEP
```

The final next step must be exactly:

```text
Await architect review before Phase 3G.
```

After completion return the populated report's byte count and SHA-256 outside
the report body. Do not place self-referential identity values inside it.

---

## 15. Strict acceptance criteria

All must pass:

1. Independent directive authentication matched.
2. Every required authority was read completely.
3. Repository identity, caller records, exact entry counts, semantic index,
   lockfile, 592-file baseline manifest, 204-record archive, 33-record
   authorized-start manifest, and 559-record protected manifest matched.
4. Pre-change canonical quality passed.
5. Exactly six implementation paths were created and 33 existing paths
   modified; no other path changed.
6. Exactly one direct local dependency was added and the semantic lock delta is
   confined to `kongono/php-cgi`.
7. Package count remains fourteen and dependency direction stays acyclic.
8. Strict optimized PSR-4 autoload passed.
9. Exactly three production and two test PHP files were added.
10. `CgiWebDelivery` is final, Internal, one-delivery, and implements the exact
    Public delivery contract.
11. Native capture reads only the authorized sources and retains no unselected
    global or nested post/file value.
12. Input opening is lazy and occurs inside Web Runtime's request path.
13. Permission evaluation is independent, current-delivery-only, and cannot be
    upgraded by request or prior-delivery state.
14. Request conversion calls the existing converter once and clears capture
    state after the attempt.
15. Generic malformed `HEAD` output suppresses body bytes; other malformed
    methods use the safe `GET` emission fallback.
16. Response emission calls the existing emitter once with no retry or rollback.
17. Cleanup releases references first, closes every unique owned resource once,
    attempts remaining cleanup after failure, and rethrows the first failure.
18. Diagnostic 1010 adds only `input_open_failed`; diagnostic 1018 adds only
    `input_close_failed`; neither permits raw disclosure.
19. `CgiWebEntry` is final, Experimental, stateless, and delegates one fresh
    delivery to one supplied runtime exactly once.
20. The PHP-CGI package does not construct or directly depend on Web
    Application, Kernel, routes, middleware, handlers, DI, or Graph.
21. Existing Web Runtime, Web Application, Kernel, converter, emitter,
    bootstrap, permission enum, and lower-package production bytes remain
    unchanged.
22. Repeated and alternating delivery tests prove no retained operation state.
23. Focused syntax, PHPStan, style, package tests, and architecture tests pass.
24. ADR 0056 and every authorized current document are consistent; Phase 3G
    alone is next and the fixed A-H table is unchanged.
25. No listener, process manager, deployment, hardening, external acceptance,
    general configuration, container wiring, or later capability was added.
26. Canonical quality passes completely.
27. Links, encoding, LF, final LF, whitespace, NUL, ordinary-file, stream, mode,
    and `git diff --check` proofs pass.
28. Normal PHP remains 128M and `PHP_INI_SCAN_DIR` remains absent.
29. Final state is exactly `30 modified / 185 deleted / 249 untracked / 464
    total / 0 staged`, 600 physical files, 536 tracked index paths, 124
    production PHP files, 139 test PHP files, and 263 style-covered PHP files.
30. Historical records, protected files, branch, refs, history, and semantic
    index remain exact.
31. Nothing was staged or committed.
32. Only the Phase 3F report was populated after every non-report gate passed.
33. Report structure, truthful evidence, exact next step, and external report
    identity handoff pass.

Any failure means Phase 3F is not complete. Preserve the exact reached state,
leave the report zero bytes unless all non-report gates passed, and request the
smallest authenticated correction. Do not begin Phase 3G.

---

## 16. Final instruction

Implement Phase 3F only.

Use modern explicit typed composition: one fresh delivery object, one thin
entry call, immutable typed permission values, bounded native projection, lazy
owned input, exact existing converter/emitter calls, and no ambient framework
state. Do not retrofit a container, service locator, property-hook injector, or
host lifecycle into the Kernel.

Authenticate first. Preserve caller-managed dirty state and all historical
evidence. Stop on every unapproved mismatch. Do not stage, commit, or begin
Phase 3G.

Stop after returning the completed report's independently computed byte count
and SHA-256.
