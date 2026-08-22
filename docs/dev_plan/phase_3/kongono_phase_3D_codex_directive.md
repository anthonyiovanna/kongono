# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3D

## Bounded PHP CGI Response Staging and Emission

**Role:** Executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-21

**Phase:** 3D only

---

## 1. Mission

Implement only the accepted Phase 3D response-emission boundary inside the
existing `kongono/php-cgi` package.

This phase must:

1. accept one caller-supplied PSR-7 response and the already-validated request
   method;
2. validate the complete status, protocol, header, and readable-body surface
   before any package-owned SAPI emission call;
3. stage the response body from its current stream position into one fresh,
   package-owned, bounded `php://temp` stream;
4. preserve response-field order and per-field value order;
5. reject adapter-owned and hop-by-hop fields;
6. compute and emit one exact `Content-Length`;
7. suppress body bytes only when the request method is exactly `HEAD`;
8. mark commitment attempted before the first SAPI call and treat every later
   failure as committed or uncertain;
9. emit no rollback, retry, second response, header removal, flush, or recovery;
10. add closed actionable diagnostics, tests, ADR 0054, and current-document
    reconciliation; and
11. leave Phase 3E operator/trust composition, Phase 3F production delivery and
    entry, Phase 3G hardening/deployment, and Phase 3H external acceptance
    unimplemented.

The result is an emission primitive. It is not a `WebDeliveryInterface`
implementation, ambient request capture, production front controller, listener,
process manager, configuration loader, or deployable application.

---

## 2. Mandatory stop rules

Stop immediately, preserve the repository, report expected and observed values,
and ask for architect direction if any locked identity, inventory, calling
contract, dependency boundary, test result, or file-safety predicate differs.

Do not:

- guess a missing value or silently adopt a locally observed mismatch;
- replace, truncate, normalize, or repair a caller record;
- stage, commit, amend, reset, restore, clean, switch branches, create refs, or
  rewrite history;
- change Composer dependencies, versions, plugins, audit policy, PHP baseline,
  PHPStan level, style policy, or PHPUnit policy;
- introduce a new package;
- implement `WebDeliveryInterface`, request capture, trusted-delivery evidence,
  operator configuration, environment loading, a front controller, deployment,
  a listener, a FastCGI parser/client, a process manager, or any Phase 3E+
  capability;
- add semantic 4xx mapping, streaming responses, ranges, sendfile, SSE,
  WebSocket, compression, transfer coding, output buffering, or automatic
  recovery;
- use `header_remove()`, `flush()`, `ob_*`, shutdown functions, destructors,
  global registries, static current-response state, Fiber/thread-local state,
  retry loops, or a second-response fallback; or
- weaken or bypass validation because a host/SAPI operation is difficult to
  test.

One unchanged retry of a Composer command is permitted only when its sole
failure is managed network/cache denial. Record both attempts. No non-network
retry is authorized merely to obtain a passing result.

---

## 3. Independent directive authentication

Before reading this file as authority beyond this section, the architect must
supply its expected byte count and SHA-256 outside the file. Calculate the local
identity and compare it exactly.

The directive is not self-authenticating. If either value is absent or differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

The caller must also create this ordinary zero-byte placeholder before
execution:

```text
docs/task reports/Phase_3D.md
```

Its required initial identity is:

```text
bytes:   0
SHA-256: E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

It must be untracked, unstaged, absent from `HEAD`, and remain zero bytes until
every non-report gate passes.

---

## 4. Required authorities

Read completely before mutation:

```text
AGENTS.md
ARCHITECTURE.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/architecture/overview.md
docs/architecture/http-message-model.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/adr/0001-kongono-identity.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/dev_plan/kongono_phase_3C_codex_directive.md
docs/task reports/Phase_3C.md
packages/web-runtime/src/Contract/WebDeliveryInterface.php
packages/web-runtime/src/WebRuntimeAdapter.php
packages/http-message/src/Message/Response.php
packages/http-message/src/Message/Stream.php
packages/php-cgi/README.md
packages/php-cgi/composer.json
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Exception/ExceptionInterface.php
packages/php-cgi/src/Exception/InvalidCgiRequestException.php
packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
packages/php-cgi/tests/Unit/Inbound/CgiServerRequestConverterTest.php
tests/Architecture/ApiStatusConventionTest.php
```

Do not substitute a similarly numbered or similarly titled ADR. Stop if an
exact path is absent.

---

## 5. Locked starting state

### 5.1 Git identity

After the caller copies this authenticated directive and creates the zero-byte
report, the expected repository state is:

```text
branch:                  main
HEAD:                    647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                    3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                  d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:            10
local branches:          1
remotes:                 0
tags:                    0
staged paths:            0
modified tracked paths:  21
untracked paths:         30
total status paths:      51
tracked files:           536
Git-visible files:       566
package directories:     14
package manifests:       14
production PHP files:    109
test PHP files:          133
style-covered PHP files: 242
.git/index bytes:        67,767
.git/index SHA-256:      24F87912D7307DC348B67FE9F1F33199FF8D20DD48E1969CFD156CDDA9A416A9
```

The two additional untracked paths relative to the accepted Phase 3C final
state must be exactly this directive and the Phase 3D report placeholder.

The 21 modified tracked paths must be exactly:

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/routing-model.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-handler/README.md
packages/http-server/README.md
packages/kernel/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

The 30 untracked paths must be the accepted 28 Phase 3C final paths plus:

```text
docs/dev_plan/kongono_phase_3D_codex_directive.md
docs/task reports/Phase_3D.md
```

Use `git status --short --untracked-files=all` for path counts. A collapsed
directory entry is not an inventory proof.

### 5.2 Locked Phase 3C authorities

```text
docs/dev_plan/kongono_phase_3C_codex_directive.md
bytes: 59,785
SHA-256: 9916516E28FD20A1BB06A6BCE34B7ED050650297CCF862B9D5AFD127A5C73E38

docs/task reports/Phase_3C.md
bytes: 20,752
SHA-256: 6972877D3E6CC037EAD40FE23438DD33EF32826F2185B5F40F367D5ED855B169

docs/adr/0052-php-cgi-fastcgi-responder-profile.md
bytes: 13,139
SHA-256: DCD53CC972FDE94AFE668254C43CF6A531F6FE6A178EC42D83917E84961E17A5

docs/adr/0053-bounded-php-cgi-inbound-conversion.md
bytes: 5,610
SHA-256: 83D26FE7E44A50913FCB949C6A15467248AF5CDFB2057798D2F379E6B135E036

docs/architecture/phase-3-roadmap.md
bytes: 13,028
SHA-256: 98822814BA2B73AA5E962F0B6A0FF85AA9ACB086653A68E6873FCC748516A1C5

docs/architecture/php-cgi-fastcgi-profile.md
bytes: 21,837
SHA-256: E07E5034E00CF8EDB35F6161A7E800DCF44352389B5167A580E2567CB56D2E8A

packages/php-cgi/composer.json
bytes: 491
SHA-256: EAAD0110ADD2AF5C9C35F237177687DB5A1C34B998AB385FCF70FB7A641CF685

composer.json
bytes: 4,468
SHA-256: B389C1E40B01A081B775B2288F8D750159133F6FB02894C320067FB430F7CD83

composer.lock
bytes: 197,391
SHA-256: 2C44558DB971885324A00761F4099AE901A6AAADDB4D243221E9BCEDFE7C2A75
```

Verify all Phase 3C-created production and test paths are ordinary files with
default streams only. Do not alter any accepted Phase 3C caller record.

### 5.3 Baseline quality

Run literal:

```powershell
composer quality
```

Expected accepted baseline evidence is:

```text
security advisories: 0
platform:            pass
style:               0 of 242 files fixable
PHPStan:             zero errors
PHPUnit:             499 tests, 10,148 assertions
```

Normal PHP remains `memory_limit=128M`; the root `analyse` Composer script alone
supplies its accepted `512M` child limit. `PHP_INI_SCAN_DIR` remains absent.

If a PHPStan parallel-worker memory/process failure occurs, stop and report the
exact command and process result. Do not change configuration or substitute a
different gate in this phase.

---

## 6. Exact authorized file boundary

### 6.1 Create exactly these ten paths

```text
docs/adr/0054-bounded-php-cgi-response-emission.md
packages/php-cgi/src/Emission/CgiResponseEmitter.php
packages/php-cgi/src/Exception/CgiResponseEmissionException.php
packages/php-cgi/src/Exception/InvalidCgiResponseException.php
packages/php-cgi/src/Internal/CgiEmissionTarget.php
packages/php-cgi/src/Internal/CgiNativeEmissionTarget.php
packages/php-cgi/src/Internal/CgiResponseEmission.php
packages/php-cgi/src/Internal/CgiStagedResponse.php
packages/php-cgi/tests/Fixture/RecordingEmissionTarget.php
packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
```

### 6.2 Modify only these eighteen existing paths

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/doctrine.md
docs/engineering/package-layout.md
docs/engineering/toolchain.md
docs/glossary.md
packages/php-cgi/README.md
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Exception/InvalidCgiRequestException.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
tests/Architecture/ApiStatusConventionTest.php
```

### 6.3 Caller report

Populate only `docs/task reports/Phase_3D.md`, and only after every other gate
passes. It remains untracked and unstaged.

### 6.4 Closed prohibited scope

No other path may change. In particular, do not modify:

```text
composer.json
composer.lock
packages/php-cgi/composer.json
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
packages/web-runtime/**
packages/web-application/**
packages/kernel/**
tests/BlackBox/**
```

The existing package-root tooling already covers the new source and test paths.
No dependency or autoload expansion is required.

---

## 7. Package and dependency contract

`kongono/php-cgi` remains the sole owner. Preserve its exact direct
requirements and lockfile record. Do not introduce an emitter package.

Production source may continue to depend only on:

- PHP 8.5;
- `kongono/api-metadata`;
- `kongono/diagnostics`;
- `kongono/http-message`; and
- `psr/http-message`.

No production dependency on Web Runtime, Web Application, Kernel, Routing,
HTTP Server, DI, Application Graph, a test seam, a FastCGI client/parser, or a
third-party emitter is permitted.

---

## 8. Exact new declaration inventory

Add exactly seven production declarations:

| FQCN | Shape | API status |
| --- | --- | --- |
| `Kongono\PhpCgi\Emission\CgiResponseEmitter` | final class | Experimental |
| `Kongono\PhpCgi\Exception\CgiResponseEmissionException` | final class | Experimental |
| `Kongono\PhpCgi\Exception\InvalidCgiResponseException` | final class | Experimental |
| `Kongono\PhpCgi\Internal\CgiEmissionTarget` | interface | Internal |
| `Kongono\PhpCgi\Internal\CgiNativeEmissionTarget` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiResponseEmission` | final class | Internal |
| `Kongono\PhpCgi\Internal\CgiStagedResponse` | final readonly class | Internal |

Add exactly two test declarations in matching PSR-4 paths:

```text
Kongono\PhpCgi\Tests\Fixture\RecordingEmissionTarget
Kongono\PhpCgi\Tests\Unit\Emission\CgiResponseEmitterTest
```

After implementation:

```text
package production PHP files: 16
package test PHP files:       5
repository production PHP:   116
repository test PHP:         135
style-covered PHP:           251
```

Do not add traits, abstract classes, anonymous production declarations,
aliases, generated files, or multiple named declarations per PSR-4 file.

---

## 9. Public emitter contract

`CgiResponseEmitter` is final, Experimental, stateless, and reusable. It has no
declared properties, explicit constructor, destructor, clone implementation,
cache, retained response, retained target, or current-delivery state.

Its only declared public method is:

```php
public function emit(
    ResponseInterface $response,
    string $requestMethod,
): void;
```

The method:

- validates `requestMethod` through the existing exact uppercase method grammar;
- creates one fresh `CgiNativeEmissionTarget`;
- delegates one complete operation to `CgiResponseEmission`;
- returns only after complete successful emission; and
- throws the exact package failures below or propagates consumer PSR response
  getter/body `Throwable` values unchanged where this directive says so.

The caller-supplied response and body stream remain caller-owned. The emitter
does not close, detach, rewind, seek, replace, or retain the response body. It
reads from the body's current position. A future Phase 3F delivery owns final
response-body cleanup.

`HEAD` is the only method that suppresses staged body bytes. It still performs
complete body staging and emits the computed length. Every other valid method
emits the staged bytes.

---

## 10. Response preflight and staging

Before the first status/header/body SAPI call:

1. `headers_sent()` through the target must be false.
2. Validate the request method.
3. Read and validate response status, protocol, headers, and body.
4. Stage the complete readable body from its current position.
5. Check `headers_sent()` again after every consumer-controlled response/body
   access and immediately before commitment.

### 10.1 Status and protocol

- Status is an integer from 200 through 599 inclusive.
- Supported response protocol strings are exactly `1.0`, `1.1`, `2`, `2.0`,
  `3`, and `3.0`.
- Protocol is validated but is not emitted as an HTTP status line; the selected
  CGI profile uses PHP's status-code API with `cgi.rfc2616_headers=0`.

### 10.2 Header projection

Read `ResponseInterface::getHeaders()` once. Detach its ordered map and every
ordered value list before commitment.

Require:

```text
distinct response headers:           <= 128
values per response header:          1 through 32
one response header value bytes:     <= 8,192
total emitted name plus value bytes: <= 65,536
```

The total counts `strlen(name) + strlen(value)` for every emitted value because
the field name is emitted for every line. It excludes the adapter-computed
`Content-Length` line.

Names use the HTTP token grammar:

```regex
^[!#$%&'*+\-.^_`|~0-9A-Za-z]+$
```

Values may contain visible bytes and horizontal tab, but reject NUL, CR, LF,
DEL, or any other control byte. Preserve the caller's original field-name
spelling, field order, and value order. Reject case-insensitive duplicate field
names even if a nonconforming PSR implementation returns them.

Reject these names case-insensitively:

```text
Status
Content-Length
Transfer-Encoding
Connection
Keep-Alive
Proxy-Authenticate
Proxy-Authorization
TE
Trailer
Upgrade
```

Never comma-split, combine, sort, normalize case, infer hop-by-hop fields, or
silently remove a field.

### 10.3 Body staging

Require a readable `StreamInterface`. Do not require seekability and do not
call `rewind()`, `seek()`, `tell()`, `getSize()`, `__toString()`, or
`getContents()` on the caller body.

Open one fresh `php://temp` stream in `w+b` mode and wrap it with the existing
first-party `Stream`. Read the caller body in requests no larger than 8,192
bytes until `eof()` is true.

- A read that returns `''` while `eof()` remains false is stalled and rejects.
- A read that returns more bytes than requested rejects as a nonconforming body.
- A caller body `read()` or `eof()` throwable propagates unchanged.
- A staged byte count above 1,048,576 rejects before the overflowing chunk is
  written.
- Each staging write must write the complete supplied chunk; false, zero, short,
  warning, or thrown package-owned write behavior rejects.
- Rewind only the package-owned staging stream to zero after successful staging.
- Close the staging stream in all success and failure paths.

The staged response stores only detached validated fields, the first-party
staging stream, exact byte length, status, and protocol. No raw rejected value
enters a package exception or diagnostic context.

---

## 11. Internal sequencing seam

`CgiEmissionTarget` exists solely so deterministic sequencing and failure paths
can be tested without mutating real process headers. It is Internal, may not
appear in the Experimental emitter signature, and may not be implemented or
injected by application code through a public factory.

Its exact public methods are:

```php
public function headersSent(): bool;
public function setStatus(int $status): void;
public function emitHeader(string $name, string $value, bool $replace): void;
public function writeBody(string $bytes): int|false;
```

`CgiResponseEmission` is final and Internal, has a private constructor, no
properties, and exactly one declared public method:

```php
public static function emit(
    ResponseInterface $response,
    string $requestMethod,
    CgiEmissionTarget $target,
): void;
```

`CgiStagedResponse` is final, readonly, and Internal. It has exactly these five
public promoted properties, in this constructor order, and no other declared
public method:

```php
/**
 * @param list<array{name: string, values: list<string>}> $headers
 */
public function __construct(
    public int $status,
    public string $protocol,
    public array $headers,
    public Stream $body,
    public int $length,
) {}
```

`CgiNativeEmissionTarget` implements only that interface, has no declared
properties or public constructor contract, and uses only:

- `headers_sent()`;
- `http_response_code()`;
- `header()`; and
- `file_put_contents('php://output', ..., FILE_APPEND)`.

Use unqualified PHP function calls inside its namespace so the implementation
remains directly inspectable. Do not read superglobals, environment state, or
output-buffer state.

A narrowly installed warning-to-failure bridge must wrap only one package-owned
native call at a time. It must:

- install immediately before that call;
- invoke the previous handler if one exists;
- preserve a previous-handler throwable as the native previous failure rather
  than swallowing it;
- otherwise throw a fixed package-neutral failure without retaining the native
  warning message, path, line, response field, or body bytes; and
- restore the prior handler in `finally`.

No process-global handler may remain changed after any outcome.

`RecordingEmissionTarget` is test-only. Production source may not reference its
namespace or file.

---

## 12. Exact emission sequence

After complete preflight and the second `headersSent()` proof:

1. Set local state `commitAttempted = true` before calling `setStatus()`.
2. Call `setStatus()` exactly once.
3. For each response field in original order:
   - emit its first value with `replace=true`;
   - emit every later value with `replace=false`.
4. Emit `Content-Length` exactly once, after application fields, with
   `replace=true` and the canonical decimal staged byte count.
5. If the request method is `HEAD`, perform no body-write call.
6. Otherwise read the package-owned staged stream in chunks no larger than
   8,192 bytes and call `writeBody()` once per non-empty chunk.
7. Every body write must return exactly `strlen($chunk)`. False, zero, short,
   warning, or throwable fails immediately without retrying the chunk.
8. Mark local state `completed=true` only after every required byte is written.

Do not emit a reason phrase, protocol line, transfer encoding, connection field,
trailer, terminal newline, flush, or any bytes beyond the staged body.

Once `commitAttempted` is true, every failure is committed or uncertain. Never:

- inspect `headers_sent()` to infer rollback;
- call `header_remove()`;
- retry status, headers, or body;
- emit a generic response;
- rewind or truncate host output;
- continue after a failed/short write; or
- report `completed=true`.

The public method throws. Existing Web Runtime behavior will retire when a
future delivery delegates `send()` to this emitter. That future composition is
not implemented now.

---

## 13. Diagnostics and exceptions

### 13.1 Diagnostic extension

Extend `PhpCgiDiagnostic` with exactly six new Internal cases:

| Case | ID |
| --- | --- |
| `InvalidResponseStatus` | `KGN-PHP-CGI-1011` |
| `InvalidResponseProtocol` | `KGN-PHP-CGI-1012` |
| `InvalidResponseHeaders` | `KGN-PHP-CGI-1013` |
| `InvalidResponseBody` | `KGN-PHP-CGI-1014` |
| `ResponsePreflightFailed` | `KGN-PHP-CGI-1015` |
| `ResponseEmissionFailed` | `KGN-PHP-CGI-1016` |

Use these exact prose contracts:

| Case | Title | Remediation | Operation | Violated invariant |
| --- | --- | --- | --- | --- |
| `InvalidResponseStatus` | `Invalid CGI response status` | `Supply a response status from 200 through 599` | `Validate CGI response status` | `The response status must be within the selected CGI profile range` |
| `InvalidResponseProtocol` | `Invalid CGI response protocol` | `Supply one of the supported response protocol labels` | `Validate CGI response protocol` | `The response protocol must use the selected closed label set` |
| `InvalidResponseHeaders` | `Invalid CGI response headers` | `Supply ordered bounded response fields without adapter-owned or hop-by-hop names` | `Validate CGI response headers` | `Response fields must be unique, bounded, safe, and adapter-compatible` |
| `InvalidResponseBody` | `Invalid CGI response body` | `Supply a readable non-stalling response body no larger than 1048576 bytes` | `Stage CGI response body` | `The response body must be readable and fit the selected staging bound` |
| `ResponsePreflightFailed` | `Unable to preflight CGI response` | `Correct the local response staging or prior-output condition before emission` | `Preflight CGI response emission` | `Complete bounded staging and uncommitted headers are required before emission` |
| `ResponseEmissionFailed` | `Unable to complete CGI response emission` | `Retire the uncertain delivery and correct the selected host output failure` | `Emit CGI response` | `Status, fields, and staged body must be emitted exactly once after commitment` |

Retain the existing exact one-field Safe schema:

```text
key:        violation
label:      Violation
kind:       Text
disclosure: Safe
```

Use these exact closed violation tokens:

```text
InvalidResponseStatus:
  status_out_of_range

InvalidResponseProtocol:
  protocol_unsupported

InvalidResponseHeaders:
  header_name_not_string
  header_values_not_list
  header_value_not_string
  header_name_invalid
  header_name_duplicate
  header_forbidden
  header_count_exceeded
  header_value_count_invalid
  header_value_invalid
  header_value_too_long
  header_bytes_exceeded

InvalidResponseBody:
  body_unreadable
  body_stalled
  body_read_exceeded_request
  body_too_large

ResponsePreflightFailed:
  headers_already_sent
  staging_open_failed
  staging_write_failed
  staging_rewind_failed

ResponseEmissionFailed:
  status_emit_failed
  header_emit_failed
  content_length_emit_failed
  staged_body_read_failed
  output_write_failed
```

Titles, remediation, operations, and violated invariants must be concise,
actionable, package-owned, and contain no raw response or host data. Update the
exact diagnostic tests and repository-wide unique-ID count.

`InvalidCgiRequestException` must explicitly accept only its existing request
diagnostics. It may not accept the new response cases merely because they share
the enum.

### 13.2 Invalid response exception

`InvalidCgiResponseException`:

- is final and Experimental;
- extends native `InvalidArgumentException`;
- implements only the existing package `ExceptionInterface`;
- stores exactly one readonly diagnostic and one readonly eager
  `DiagnosticContext`;
- accepts only diagnostics 1011 through 1014;
- preserves an optional previous failure;
- declares the four existing diagnostic accessors and no other public method;
- never stores the response, body, status, protocol, header map, field name,
  field value, or rejected raw data.

### 13.3 Emission exception

`CgiResponseEmissionException`:

- is final and Experimental;
- extends native `RuntimeException`;
- implements only the package `ExceptionInterface`;
- stores exactly one readonly diagnostic, one readonly eager context, and one
  readonly boolean `commitAttempted`;
- accepts only diagnostics 1015 and 1016;
- requires `commitAttempted=false` for 1015 and `true` for 1016;
- exposes the four diagnostic accessors plus
  `public function commitAttempted(): bool`;
- preserves an optional previous failure; and
- retains no native warning text, path, line, response data, or body bytes in
  package-owned surfaces.

Every failure raised after the local commit flag is set must be diagnostic 1016
and report `commitAttempted() === true`. Every package-owned failure before that
point must be an exact invalid-response failure or diagnostic 1015 with false.

Consumer response getter/body throwables propagate unchanged. Do not catch them
merely to change their class. A throwable from the package-owned native target,
including a previous warning-handler failure, is wrapped once only to attach
the exact fixed emission stage and committed state; the original remains the
native previous failure.

---

## 14. Tests

The new emitter test and modified diagnostic/exception/architecture tests must
prove at minimum:

### 14.1 Shape and dependency proofs

- exact declarations, final/readonly/interface shapes, API statuses, properties,
  constructors, methods, and native parents;
- the Experimental emitter surface does not expose the Internal target;
- no new dependency or manifest/lock/tooling change;
- production source references no Web Runtime, Web Application, Kernel, Routing,
  HTTP Server, DI, Graph, test fixture, listener, FastCGI parser, environment,
  superglobal, or later-phase type;
- native SAPI/output calls exist only in `CgiNativeEmissionTarget`;
- no prohibited rollback, output-buffer, retry, shutdown, destructor, global,
  static-current, or second-response surface.

### 14.2 Success behavior

- status lower and upper boundaries 200 and 599;
- all six accepted protocol labels;
- deterministic application-header order;
- first value replace and later values append;
- case and value order preservation;
- computed `Content-Length` after application headers;
- zero body, ordinary body, 1,048,576-byte body;
- exact 8,192-byte output chunks plus final remainder;
- `HEAD` stages and counts the full body but writes zero body chunks;
- non-`HEAD` valid methods write the full staged body;
- caller body is read from its current position, advanced to EOF, never rewound,
  never closed, and never retained;
- every call uses fresh staging and target state;
- success/failure/success repeated calls do not leak state.

### 14.3 Boundary and rejection behavior

- status 199/200/599/600;
- every accepted and one rejected protocol;
- 128/129 headers;
- 32/33 values;
- 8,192/8,193 value bytes;
- 65,536/65,537 total header bytes with the exact repeated-name formula;
- invalid/non-string names and values from nonconforming response doubles;
- invalid names, duplicates, forbidden fields in varied case, controls, CRLF,
  NUL, and DEL;
- unreadable, stalled, over-returning, exactly 1 MiB, and boundary-plus-one bodies;
- pre-existing headers-sent failure before response access;
- headers becoming sent during consumer body access, caught before commitment;
- response getter/body throwables remain identical;
- every package-owned precommit failure causes zero target status/header/body
  calls;
- status, first header, later header, computed length, staged-body read, false,
  zero, short, warning, and thrown output failures stop immediately;
- every post-commit failure reports committed/uncertain true;
- no status/header/body retry, header removal, or fallback response;
- staging cleanup occurs on success and every failure.

### 14.4 Disclosure and process-global behavior

Use distinct sentinels in invalid protocol, field name/value, body, target
failure, warning, path, and previous native failure. Prove absence from every
package-owned message, ID, title, remediation, operation, violated invariant,
context field key/label/value, and declared property other than the explicit
native previous chain.

Prove the warning handler before and after each native-target success/failure is
identical. A previous-handler throwable must be retained as the exact native
previous failure and must not be swallowed or replaced by warning text.

Native `header()`/status/output behavior that cannot be safely asserted in the
shared PHPUnit process must run in a PHPUnit separate process. Do not use
production `eval`, source generation, environment mutation, or a test-owned
production dependency.

---

## 15. ADR and documentation

Create ADR 0054 with:

```text
Status: Accepted
Date: 2026-08-21
```

It must record:

- package ownership and exact Experimental emitter signature;
- the Internal target seam and why it is not public injection/configuration;
- current-position, readable-only, bounded full-body staging;
- exact status/protocol/header/body limits;
- forbidden adapter-owned/hop-by-hop fields;
- computed length and `HEAD` behavior;
- the exact commit-attempt point and no-rollback/no-retry rule;
- consumer throwable propagation and package-owned diagnostic wrapping;
- response-body versus staging ownership;
- warning-handler restoration;
- deterministic memory/copying/latency consequences;
- rejected direct/unvalidated emission, buffering-dependent rollback,
  application-supplied content length, streaming, and premature delivery
  composition; and
- Phase 3E as the only next phase.

Update the package README and authorized current documents so they agree:

- Phase 3C inbound conversion and Phase 3D emission are implemented;
- there are still fourteen packages;
- no production delivery, ambient capture, operator profile, trust composition,
  entrypoint, listener, deployment, or external acceptance exists;
- `WebRuntimeAdapter` still owns one send and close attempt but has no concrete
  PHP CGI delivery;
- staged response bodies are bounded and streaming is unsupported;
- post-commit failure is uncertain, throws, and is never rolled back or retried;
- Phase 3E alone is next.

Preserve the fixed A-H roadmap table byte-for-byte with no change at all:

```text
LF bytes: 1,329
SHA-256: 653BE9BE8A64487C275FCEC935C724F1201D2BCCC398B6CAC4DFCCE0F3ED9B04
```

Phase 3D status updates belong outside that fixed table. Do not rewrite history
or describe future work as implemented.

---

## 16. Validation workflow

### Gate A - authentication and baseline

- Authenticate this directive externally.
- Read every authority completely.
- Verify Git/history/index/status/path inventories and locked caller records.
- Verify the Phase 3D report is the exact zero-byte caller placeholder.
- Run pre-change literal `composer quality`.

### Gate B - exact implementation boundary

- Create exactly ten paths and modify exactly eighteen existing paths.
- Verify no other path changed relative to Gate A.
- Verify Composer manifests, lockfile, dependencies, tooling configs, and all
  prohibited package bytes remain unchanged.
- Run `composer dump-autoload --optimize --strict-psr`.

### Gate C - focused validation

Run:

```powershell
php -l packages/php-cgi/src/Emission/CgiResponseEmitter.php
php -l packages/php-cgi/src/Exception/CgiResponseEmissionException.php
php -l packages/php-cgi/src/Exception/InvalidCgiResponseException.php
php -l packages/php-cgi/src/Internal/CgiEmissionTarget.php
php -l packages/php-cgi/src/Internal/CgiNativeEmissionTarget.php
php -l packages/php-cgi/src/Internal/CgiResponseEmission.php
php -l packages/php-cgi/src/Internal/CgiStagedResponse.php
php -l packages/php-cgi/tests/Fixture/RecordingEmissionTarget.php
php -l packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

Require zero syntax, PHPStan, style, PHPUnit failure, warning, risky, skipped,
incomplete, notice, or deprecation result.

### Gate D - semantic and safety inspection

Prove:

- exact declaration and file inventories;
- exact diagnostic IDs/tokens/schemas;
- exact ordering, bounds, HEAD, current-position, and commit semantics;
- no raw sentinel retention;
- no prohibited symbols or dependencies;
- no Composer/lock/tooling drift;
- all local Markdown links resolve;
- strict UTF-8 without BOM, LF-only, one final LF, no trailing whitespace, no
  NUL, ordinary files, default streams only, and tracked modes unchanged;
- `git diff --check` passes.

### Gate E - canonical quality

Run literal:

```powershell
composer quality
```

Require zero advisories, platform pass, zero style findings, zero PHPStan
errors, and a completely passing PHPUnit suite. Record exact final test and
assertion counts; do not predeclare them.

### Gate F - final preservation and report

Before report population, expected status is:

```text
modified tracked paths: 21
untracked paths:        40
total status paths:     61
staged paths:            0
Git-visible files:     576
tracked files:         536
```

This follows because every modified existing path was already dirty at Gate A,
and Phase 3D creates ten new paths. Verify the exact inventories rather than
trusting arithmetic.

Then populate only `docs/task reports/Phase_3D.md`. Re-run report-integrity,
status, index, branch, ref, history, file-safety, and no-staging proofs. Report
population changes no path count because the placeholder already existed.

No staging or commit is authorized.

---

## 17. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. PACKAGE AND API CONTRACT
## 8. RESPONSE PREFLIGHT AND STAGING
## 9. EMISSION AND COMMIT UNCERTAINTY
## 10. DIAGNOSTICS AND DISCLOSURE
## 11. RESOURCE OWNERSHIP AND ISOLATION
## 12. ARCHITECTURAL DECISIONS
## 13. DEVIATIONS FROM DIRECTIVE
## 14. DEPENDENCIES ADDED/REMOVED
## 15. ENVIRONMENT
## 16. COMMANDS RUN
## 17. VALIDATION RESULTS
## 18. DOCUMENTATION REVIEW
## 19. SECURITY AND FILE-SAFETY REVIEW
## 20. WARNINGS / KNOWN ISSUES
## 21. QUESTIONS / BLOCKERS
## 22. FINAL GIT STATUS
## 23. ACCEPTANCE CRITERIA
## 24. NEXT STEP
```

The final next step must be exactly:

```text
Await architect review before Phase 3E.
```

After the report is complete, return its byte count and SHA-256 outside the
report body. Do not put self-referential identity values inside the report.

---

## 18. Strict acceptance criteria

All must pass:

1. Directive identity was independently supplied and matched.
2. Every exact authority was read completely.
3. Git, history, index, caller records, and `21/30/51/0` entry state matched.
4. Pre-change canonical quality passed.
5. Exactly ten paths were created and eighteen existing paths modified.
6. No unauthorized path changed.
7. Composer manifests, lockfile, dependencies, and tooling remained identical.
8. Strict optimized PSR-4 autoload passed.
9. Exactly seven production and two test declarations were added.
10. API statuses, shapes, properties, and method inventories are exact.
11. Public emitter signature and Internal seam are exact.
12. Status/protocol/header/body validation and bounds are exact.
13. Header/value order, replace/append semantics, and computed length are exact.
14. Body staging reads only from the caller's current position and never closes it.
15. HEAD suppresses only output body bytes, not staging or computed length.
16. Commitment is marked before the first SAPI call.
17. Every post-commit failure is uncertain, typed, non-retried, and non-rollback.
18. Consumer PSR response/body throwables propagate as required.
19. Warning-handler installation is bounded and restoration exact.
20. Diagnostics, tokens, contexts, exception parents, and disclosure are exact.
21. Staging resources close on success and failure with no retained state.
22. Repeated operations are isolated.
23. Root architecture enforcement passes.
24. No delivery, ambient capture, trust, operator, entry, listener, deployment,
    external acceptance, or other Phase 3E+ capability exists.
25. ADR 0054 and all authorized current documents are consistent.
26. The fixed A-H roadmap table remains byte-identical.
27. Links, encoding, whitespace, modes, streams, and file safety pass.
28. Focused gates and final canonical quality pass.
29. Normal PHP remains 128M and no INI overlay remains.
30. Final state is `21/40/61/0`, 576 visible, 536 tracked, with unchanged
    branch, refs, history, and index.
31. The report contract and external report identity handoff pass.

Any failure means Phase 3D is not complete. Preserve the exact reached state,
leave the report zero-byte unless every non-report gate passed, and request the
smallest authenticated correction.

---

## 19. Final instruction

Implement Phase 3D only. Do not design or implement the concrete PHP CGI
delivery, operator profile, trust evidence, production entrypoint, listener,
process management, deployment, external harness, semantic client-error model,
streaming response model, database, ORM, templating, frontend, CLI, agents,
extensions, telemetry, or any other later capability.

Stop after the report and independent report digest.
