# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3H
## External PHP-CGI FastCGI Acceptance and Phase 3 Checkpoint

**Directive date:** 2026-08-21

**Repository:** `C:\Users\antho\Dev\kongono`

**Execution role:** executor, not architect

**Scope:** Phase 3H only

**Checkpoint:** required; one exact local commit, no tag, remote, or push

---

## 1. Mission

Complete Phase 3 by proving the implemented PHP `cgi-fcgi`, CGI/1.1,
FastCGI `RESPONDER` profile through the actual installed `php-cgi` listener
from outside every production package, reconciling current documentation, and
creating the exact Phase 3 local checkpoint.

Phase 3H adds no production framework capability. It adds a root-owned,
test-only external acceptance harness that:

1. starts an operator-supplied `php-cgi` executable in FastCGI listener mode on
   an ephemeral direct-loopback TCP endpoint;
2. drives it with a bounded test-owned FastCGI client;
3. executes the real PHP-CGI capture, conversion, Web Runtime, Web Application,
   Kernel, routing, middleware, handler, emission, close, and explicit shutdown
   path;
4. proves admission, input and response bounds, emission, trusted-diagnostic
   gates, disclosure, failure, cleanup evidence, and process reuse;
5. retires every listener process it creates and leaves no endpoint, process,
   temporary file, or environment mutation behind; and
6. records the accepted evidence in ADR 0058 and one external-acceptance guide.

The harness is test infrastructure. It is not production code, a supported
FastCGI client, listener, web server, process manager, application template,
deployment artifact, health system, monitoring agent, or general host adapter.
No production package may depend on it.

After every implementation, external, quality, documentation, security, and
file-safety gate passes, stage the exact accepted Phase 3 delta, create one
local checkpoint commit, rerun the complete post-commit proof, and only then
populate the Phase 3H report outside the commit.

Phase 3H closes Phase 3. Do not begin Phase 4 or implement Chie, Hantei, Oshie,
the rolling PHP baseline, database, ORM, templates, authentication, CLI,
telemetry, agents, or another capability.

---

## 2. Locked authority and doctrine

### 2.1 Read completely before validation or mutation

Read these exact files completely:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
phpunit.xml.dist
phpstan.neon.dist
.php-cs-fixer.dist.php

docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/architecture/http-message-model.md
docs/architecture/overview.md
docs/architecture/phase-2-roadmap.md
docs/architecture/phase-2-runnable-fixture.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/architecture/runtime-model.md
docs/architecture/web-application-model.md
docs/architecture/web-runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/php-cgi-deployment.md
docs/engineering/toolchain.md

docs/adr/0001-kongono-identity.md
docs/adr/0003-monorepo-initial-development.md
docs/adr/0013-documentation-doctrine.md
docs/adr/0014-php-engineering-toolchain.md
docs/adr/0016-strict-types-and-per-cs-style.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
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
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md

packages/php-cgi/composer.json
packages/php-cgi/README.md
packages/php-cgi/src/Bootstrap/CgiOperatorBootstrap.php
packages/php-cgi/src/Bootstrap/CgiOperatorProfile.php
packages/php-cgi/src/Delivery/CgiWebDelivery.php
packages/php-cgi/src/Emission/CgiResponseEmitter.php
packages/php-cgi/src/Entry/CgiFrontController.php
packages/php-cgi/src/Entry/CgiWebEntry.php
packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/src/Internal/CgiOperatorBootstrapValidation.php
packages/php-cgi/src/Internal/CgiTrustedPresentationPermissionEvaluator.php
packages/php-cgi/tests/Unit/Delivery/CgiWebDeliveryTest.php
packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
packages/php-cgi/tests/Unit/Entry/CgiFrontControllerTest.php
packages/php-cgi/tests/Unit/Inbound/CgiServerRequestConverterTest.php

packages/kernel/src/Kernel.php
packages/web-application/src/WebApplication.php
packages/web-runtime/src/WebRuntimeAdapter.php

tests/Architecture/ApiStatusConventionTest.php
tests/BlackBox/Fixture/DiagnosticFailureHandler.php
tests/BlackBox/Fixture/FixtureKernelDiagnosticException.php
tests/BlackBox/Fixture/GenericFailureHandler.php
tests/BlackBox/Fixture/GreetingHandler.php
tests/BlackBox/Fixture/NotFoundHandler.php
tests/BlackBox/Fixture/TraceMiddleware.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php

docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md
docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md
docs/dev_plan/phase_3/kongono_phase_3G_2_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3G.md
docs/task_reports/phase_3/Phase_3G_1_correction.md
docs/task_reports/phase_3/Phase_3G_2_correction.md
```

Do not substitute a similarly numbered or titled ADR. If any required path is
absent, stop before quality or mutation.

### 2.2 Independent directive authentication

The architect must provide this directive's exact byte count and uppercase
SHA-256 outside the file. Compare those supplied values with the local `.md`
file after the caller places it at:

```text
docs/dev_plan/phase_3/kongono_phase_3H_codex_directive.md
```

The `.md` extension and exact path are mandatory. Do not treat the local file
as self-authenticating. On absence, missing external values, or mismatch:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT RUN QUALITY OR MUTATE THE REPOSITORY
```

### 2.3 Locked doctrine

Preserve all accepted Kongono doctrine, including:

- persistent-runtime-first lifecycle and no ambient current operation;
- explicit typed construction rather than service location;
- Web Application as sole Kernel-operation owner;
- Web Runtime as owner of failure selection, one send attempt, and one close
  attempt;
- the PHP-CGI outer leaf, with no direct Web Application or Kernel dependency;
- fail-closed trusted diagnostics with independent bootstrap and current-
  delivery permissions;
- exact direct-loopback, non-forwarded development evidence;
- `Production` trusted diagnostics denied;
- actionable bounded diagnostics and no raw rejected-value disclosure;
- exact API-status metadata and package direction;
- strict types, PSR-4, PHPStan without suppression, canonical style, and locked
  dependency security;
- historical directives and reports as immutable evidence;
- semantic index/tree proof over raw `.git/index` cache-tree bytes; and
- `STOP / ASK / DO NOT GUESS` on an unapproved mismatch.

Kongono never parses HTTP wire bytes in production. The test-owned Phase 3H
client parses only FastCGI records for acceptance and must remain outside every
production package and namespace.

The existing PHP constraints and Composer platform remain unchanged. The
separate rolling-current-minor decision has not yet been integrated as an
Accepted repository ADR and authorizes no constraint change here.

---

## 3. Authenticated starting state

### 3.1 Repository identity before Phase 3H caller records

Require:

```text
branch:                       main
local branches:                 1
HEAD:                         647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                         3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                       d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:                  10
remotes:                        0
tags:                           0
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               262
total status paths:            477
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    613
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
docs/dev_plan/phase_3 files:     14
docs/task_reports/phase_3 files: 14
```

Existing `refs/codex/**` application-managed refs are permitted and must not be
deleted or rewritten by the executor. Other than `refs/heads/main`, require no
branch, remote, tag, or unexpected non-application ref. The semantic index
equals `HEAD`.

The root lockfile is:

```text
bytes:   197527
SHA-256: 823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
```

The tracked binary diff against `HEAD` is:

```text
bytes:   4045448
SHA-256: 001A25245612770640C314C14ED856D55E907123F119C707B9401DC9BAC05837
```

Generate that evidence from literal `git diff --binary HEAD --` bytes. Git's
inaccessible user-level global-ignore warning is environmental only; explicit
repository-local inventories remain authoritative.

### 3.2 Complete physical-file manifest

From physical files returned by:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

ignore cached paths absent from the worktree. Normalize paths to `/`, sort with
`[StringComparer]::Ordinal`, and serialize:

```text
path<TAB>decimal-byte-count<TAB>uppercase-SHA-256<LF>
```

Encode strict UTF-8 without BOM. Before Phase 3H caller records, require:

```text
records:               613
manifest bytes:      77202
manifest SHA-256:    CE923C5BA1F91844D72D6B363E49DF2B5A4826D0767748934F4395DCCF985464
aggregate file bytes: 6802219
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

### 3.3 Exact status manifest

Read `git status --porcelain=v1 -z -uall` as raw NUL-delimited bytes. Do not
parse quoted line output. Serialize each entry, then sort the complete
serialized records with `[StringComparer]::Ordinal`:

```text
XY<TAB>normalized-path<TAB>decimal-byte-count-or--<TAB>uppercase-SHA-256-or--<LF>
```

Existing files receive bytes and SHA-256; deleted paths receive `-` and `-`.
Require:

```text
records:        477
manifest bytes: 46847
manifest SHA-256: E4975CDED97F73A65B2CBA68981A887B2921B191FE1EFC075DAC119CFF9F6236
first record:    <space>D<TAB>docs/dev_plan/kongono_phase_0A_codex_directive.md<TAB>-<TAB>-
last path:       packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
```

No rename, copy, unmerged, staged, submodule, or unexpected status code is
permitted.

### 3.4 Untracked and numbered-archive manifests

The current untracked physical files have:

```text
records:               262
manifest bytes:      32957
manifest SHA-256:    9DF65CFC1329666A362C7BE14298BCC11EEB6BAE5A4156F334A564BADB9D4198
aggregate file bytes: 4711188
first path:           docs/adr/0051-phase-3-production-http-integration-milestone.md
last path:            packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
```

All numbered `docs/dev_plan/phase_*` and `docs/task_reports/phase_*` files form
the immutable accepted archive:

```text
records:               214
manifest bytes:      26851
manifest SHA-256:    82D4D9963C0DDC7849B798E7F3F717BF9DC9AC9EA2FEA41DBED17604E9FA8410
aggregate file bytes: 4361734
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3G_2_correction.md
```

### 3.5 Recent Phase 3G caller identities

Require:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md` | 44,636 | `25071053D424DF6B67D6170B82E5183186C3B9E73A68858F26FE06A1200674B8` |
| `docs/task_reports/phase_3/Phase_3G.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md` | 22,085 | `BAB511130C449756C1414B130E4BCAF9335ECC193F14A26B416A6B38C5C7BD55` |
| `docs/task_reports/phase_3/Phase_3G_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/phase_3/kongono_phase_3G_2_codex_directive_correction.md` | 18,124 | `C0E967E17F0FDB3BD6DE0DE38EB9F13C38951411D476A234150CCE0724FB996B` |
| `docs/task_reports/phase_3/Phase_3G_2_correction.md` | 14,615 | `CC6EAE7A34DB007C22264CEFC92F93BFDA87EB72F82C36C5A88C09E247AF3494` |

The two zero-byte reports remain accepted historical evidence and must be
committed as empty blobs at the Phase 3 checkpoint.

### 3.6 Authorized existing-file and protected manifests

Exactly 33 existing paths may change in Phase 3H, as listed in Section 5.3.
Their starting manifest is:

```text
records:                33
manifest bytes:       3576
manifest SHA-256:     C59C71C785A74082D8A48F5BAF00D5D2370ED0879D943B9A5CCEEF750C929F75
aggregate file bytes: 565553
first path:           ARCHITECTURE.md
last path:            tests/Architecture/ApiStatusConventionTest.php
```

Every other starting physical file is protected:

```text
records:               580
manifest bytes:      73626
manifest SHA-256:    3AF017E4591B444AF7B8468843E401B09BB5A9A0FEA00005A5C4ACEF3A8207AA
aggregate file bytes: 6236666
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

At later gates exclude the two active Phase 3H caller records and all thirteen
Phase 3H-created paths before reconstructing the 580-record protected set.

### 3.7 Installed external host tool

The accepted current external proof tool is:

```text
path:      C:\php\php-cgi.exe
bytes:     108544
SHA-256:   E2134411B7BBEA9651430BE9466D04886C17E169C11DB118675D176AB1F2B88E
version:   PHP 8.5.9 (cgi-fcgi)
platform:  ZTS Visual C++ 2022 x64
```

Require the exact ordinary, non-reparse file and `php-cgi -v` evidence before
implementation. The binary is operator-supplied host tooling, not a repository
dependency or committed artifact. `C:\php\php.exe` remains the normal CLI and
loads `C:\php\php.ini` with `memory_limit=128M`, no scanned INI files, and an
absent `PHP_INI_SCAN_DIR` variable.

The absence of an installed `cgi-fcgi`, `spawn-fcgi`, or other client is
expected. Do not install one. Phase 3H owns a test-only client.

### 3.8 Active Phase 3H caller records

The caller must place exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3H_codex_directive.md
docs/task_reports/phase_3/Phase_3H.md
```

The directive must match the independently supplied identity. The report must
be ordinary, untracked, unstaged, `HEAD`-absent, and zero bytes with the
empty-file SHA-256.

With both records present and before implementation, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               264
total status paths:            479
staged paths:                    0
physical Git-visible files:    615
tracked index paths:           536
docs/dev_plan/phase_3 files:     15
docs/task_reports/phase_3 files: 15
```

If any Section 3 invariant differs, stop before quality or mutation. Do not
repair, restore, rename, truncate, stage, commit, or adopt a local replacement.

---

## 4. Pre-change validation

After every authority and entry gate passes, run:

```powershell
composer quality
```

If and only if the first attempt fails solely because the managed environment
cannot access Composer's existing cache or Packagist, rerun the identical
command once with approved cache/network access. Do not change a command,
configuration, memory limit, dependency, policy, lockfile, or environment.

Any semantic, style, analysis, test, platform, validation, or security failure
is a hard stop. Record exact results for the final report.

Also run the installed binary's version command and one read-only listener
capability help command. Do not start a listener before the harness exists and
passes focused static validation.

---

## 5. Exact Phase 3H boundary

### 5.1 Create exactly these thirteen paths

```text
docs/adr/0058-phase-3-external-php-cgi-acceptance-and-checkpoint.md
docs/architecture/phase-3-external-acceptance.md
tests/External/PhpCgi/FastCgiRecordCodec.php
tests/External/PhpCgi/FastCgiClient.php
tests/External/PhpCgi/FastCgiResponse.php
tests/External/PhpCgi/PhpCgiProcess.php
tests/External/PhpCgi/Phase3AcceptanceRunner.php
tests/External/PhpCgi/run.php
tests/External/PhpCgi/Fixture/Phase3AcceptanceApplication.php
tests/External/PhpCgi/Fixture/Phase3ResponseHandler.php
tests/External/PhpCgi/Fixture/Phase3FastCgiEntry.php
tests/External/PhpCgi/Fixture/production.php
tests/External/PhpCgi/Fixture/development.php
```

The eleven PHP files are test code under the existing
`Kongono\Tests\` autoload-dev prefix. Create no production PHP file.

### 5.2 Caller records

The caller alone creates the Phase 3H directive and report. Do not recreate,
rename, or truncate either. Populate only the report, only after every
post-commit non-report gate passes.

### 5.3 Modify exactly these 33 existing paths

```text
composer.json
phpstan.neon.dist
tests/Architecture/ApiStatusConventionTest.php
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0049-outer-web-runtime-and-safe-failure-delivery.md
docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md
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
docs/engineering/php-cgi-deployment.md
docs/engineering/toolchain.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-handler/README.md
packages/diagnostic-presentation/README.md
packages/php-cgi/README.md
packages/web-runtime/README.md
```

Every listed documentation path must receive a real current-state
reconciliation. Do not touch another existing path.

### 5.4 Frozen production boundary

Every production PHP file and package manifest is frozen. Phase 3H must not:

- change a production symbol, API status, diagnostic, exception, package,
  dependency, or runtime behavior;
- add a production FastCGI parser, client, listener, entrypoint, process
  manager, or socket abstraction;
- change `composer.lock`;
- add a Composer dependency or plugin;
- change PHP constraints, platform policy, advisory policy, autoload mappings,
  preferred stability, or minimum stability; or
- add production configuration, environment parsing, telemetry, logging,
  deployment automation, or recovery.

---

## 6. Tooling and architecture changes

### 6.1 Composer script

Add exactly one root Composer script:

```json
"acceptance:phase3": "@php tests/External/PhpCgi/run.php"
```

It is a developer command, not part of `quality`, installation, autoload, or
runtime. Additional arguments are passed to the runner. Document the exact
current invocation:

```powershell
composer acceptance:phase3 -- C:\php\php-cgi.exe
```

Adding the script must not change the root lock content hash or any lockfile
byte. `composer validate --no-check-publish` and all package validation must
remain valid.

### 6.2 Static analysis and style

Add exactly:

```text
tests/External
```

to `phpstan.neon.dist` in the root-test path order. Do not add an ignore,
baseline, suppression, custom rule exception, or alternate memory setting.

The existing PHP CS Fixer finder already covers all of `tests`; do not change
`.php-cs-fixer.dist.php`.

### 6.3 Architecture enforcement

Extend `ApiStatusConventionTest` only as needed to prove:

- package count remains fourteen;
- production PHP count and declaration statuses remain unchanged;
- all eleven Phase 3H PHP files are test-only and outside package source;
- production files, manifests, and autoload do not reference
  `Kongono\Tests\External`, FastCGI test classes, the acceptance journal, the
  runner, or the two fixture scripts;
- no production FastCGI record parser/client/listener was introduced;
- the test classes define no Kongono API-status metadata;
- class-bearing PSR-4 files contain one matching declaration; and
- production dependency direction remains acyclic and unchanged.

Do not turn test fixtures into public API.

---

## 7. Test-owned FastCGI protocol boundary

### 7.1 `FastCgiRecordCodec`

Create a final, non-instantiable test class with no mutable static state. It
owns only the FastCGI v1 record grammar needed by this harness:

- `BEGIN_REQUEST`, `PARAMS`, `STDIN`, `STDOUT`, `STDERR`, `END_REQUEST`, and
  `ABORT_REQUEST`;
- `RESPONDER` role;
- `KEEP_CONN` flag;
- 16-bit request IDs from 1 through 65,535;
- 65,535-byte record content maximum;
- standard one-byte or four-byte name/value lengths; and
- zero-filled padding of zero through seven bytes to the next eight-byte
  boundary.

Encode caller-provided parameter names and values exactly as bytes. Split
PARAMS and STDIN content into legal records without truncation or reordering.
Emit the required empty PARAMS and STDIN terminators.

Decode only bounded FastCGI v1 response records. Reject wrong version, request
ID, padding, reserved byte, truncated header/content/padding, duplicate or
malformed END_REQUEST, unsupported record type for the current response,
nonzero protocol status, nonzero application status, output after END_REQUEST,
and limits exceeded.

This codec must not parse HTTP, open a socket, spawn a process, read an
environment variable, or enter a production namespace.

### 7.2 `FastCgiResponse`

Create a final readonly test value with exact public read access to:

- status code;
- ordered `array<string, list<string>>` response fields;
- body bytes;
- FastCGI stderr bytes;
- application status; and
- protocol status.

It receives only a completely decoded bounded response. It validates CGI
header grammar: CRLF line endings, one header/body separator, optional single
case-insensitive `Status`, valid field names and values, no NUL/control
injection, canonical decimal status, and bounded header/body totals. An absent
`Status` means status 200; no other status inference is permitted. It preserves
repeated field and per-field value order. It does not infer HTTP framing,
transfer coding, connection state, or proxy behavior.

### 7.3 `FastCgiClient`

Create a final test client that:

- connects only to literal `127.0.0.1` and a validated numeric port;
- uses a finite connection/read/write timeout;
- sends one request at a time;
- can keep one connection open for an explicit sequential reuse sequence;
- writes every byte completely or fails;
- reads at most 1,200,000 stdout bytes, 65,536 stderr bytes, and 4,096 response
  records for one request;
- accepts only closed caller-supplied CGI parameter maps and body bytes;
- never uses DNS, a proxy, TLS, remote hosts, or production code; and
- closes its socket explicitly on success and failure.

There is no concurrency or request multiplexing. A request ID cannot be reused
on an open connection. A failed or uncertain connection is closed and never
reused.

### 7.4 `PhpCgiProcess`

Create a final test-owned process wrapper. It accepts one absolute operator-
supplied `php-cgi` binary path after exact ordinary-file and basename
validation. It runs the version command without a shell and requires a
successful PHP 8.5 `cgi-fcgi` result before listener startup.

Start the listener with a command array, `bypass_shell`, the repository root as
working directory, and all ten required INI values supplied as literal `-d`
arguments:

```text
enable_post_data_reading=0
output_buffering=0
zlib.output_compression=0
display_errors=0
display_startup_errors=0
html_errors=0
expose_php=0
auto_prepend_file=
auto_append_file=
cgi.rfc2616_headers=0
```

Bind only `127.0.0.1:<ephemeral-port>` with `-b`. Never use `0.0.0.0`, `::`, a
hostname, Unix socket, remote address, fixed project port, shell command, or
production server configuration.

To obtain a port, bind a temporary test-owned loopback socket to port zero,
read the assigned port, close it, and immediately start `php-cgi`. A race may
exist between release and bind. Permit at most eight complete startup attempts;
each failed spawned process must be fully retired and its resources closed
before selecting another port. Do not retry a started acceptance request.

Create one fresh GUID-named directory directly beneath the system temporary
directory. It owns only the fixed bounded acceptance journal. Pass its exact
path to the child through the test-only process environment key:

```text
KONGONO_PHASE3_ACCEPTANCE_JOURNAL
```

This is test infrastructure, not operator or framework configuration. Do not
send the value as a FastCGI PARAM. Its unavoidable child-environment presence
must not project into the framework request's headers, attributes, diagnostic
context, client output, trusted diagnostics, production code, documentation
examples, or a committed file.

Do not pass the complete parent environment to the child. Require nonempty
existing `SystemRoot`, `TEMP`, and `TMP` host values, and build the child
environment from exactly those three keys plus
`KONGONO_PHASE3_ACCEPTANCE_JOURNAL`. Do not pass `PATH`, `USERPROFILE`, proxy,
credential, token, CI, Composer, Git, PHP configuration, or another parent
variable. The absolute executable and fixture paths make `PATH` unnecessary.
If the exact four-key child environment cannot start the verified listener,
report required host capability unavailable; do not widen it by assumption.

Track the exact direct child process handle and PID. In `finally`, close every
socket and pipe, request termination, wait a bounded interval, and verify the
process no longer exists. A second termination signal through the same direct
process handle is permitted only when the first bounded retirement did not
complete. Do not invoke `taskkill`, kill by name, scan unrelated processes, or
terminate a process not created by the current wrapper.

Delete the exact journal and empty GUID directory after all handles close.
Refuse deletion unless the canonical path remains directly beneath the system
temporary directory. No recursive deletion is authorized.

### 7.5 Test-only entry composition

`Phase3AcceptanceApplication` is the explicit test composition root. It uses
the real `Kernel`, `WebApplication`, `WebRuntimeAdapter`,
`CgiOperatorBootstrap`, and `CgiFrontController`. It may reuse the existing
Phase 2 test handlers, diagnostic exception, and trace middleware. It adds the
test-only `Phase3ResponseHandler` for bounded response cases.

The application:

1. receives only a literal `CgiOperatorProfile` selected by the fixture script;
2. loads the real bootstrap permission;
3. constructs and boots one real application;
4. constructs the real runtime;
5. runs the real front controller with that application's first-class
   `shutdown()` closure;
6. proves the real Kernel has zero active operations after completion; and
7. retains no state after the request script ends.

`production.php` must pass only `CgiOperatorProfile::Production`.
`development.php` must pass only
`CgiOperatorProfile::LoopbackDevelopment`. Client input cannot select or alter
the profile.

Each script loads the existing Composer autoloader by one exact repository-
relative path and delegates to `Phase3FastCgiEntry`. It defines no class,
function, mutable global, error handler, shutdown handler, or configuration
parser.

`Phase3FastCgiEntry` reads only the fixed test journal environment value needed
for out-of-band acceptance evidence. It validates containment within the
fresh test-owned temporary directory and appends one bounded JSON line after
the front controller returns or catches a failure. Journal records contain
only closed tokens, profile, completion outcome, shutdown outcome, and integer
active-operation count. They contain no request target, query, header, cookie,
body, host value, filesystem source path, exception message, trace, secret,
PID, or port.

The journal does not authorize behavior and is never client-visible. A journal
write failure fails acceptance. Do not register a shutdown function or hide a
front-controller failure. Record the closed failure token and rethrow after
the bounded write.

---

## 8. External acceptance runner

### 8.1 CLI contract

`run.php` loads the existing autoloader and delegates to one
`Phase3AcceptanceRunner`. Its closed grammar is exactly one argument: the
absolute `php-cgi` binary path.

Exit codes:

```text
0  complete acceptance pass
64 invalid invocation
69 required local host capability unavailable
70 acceptance failure
```

Invalid invocation writes only:

```text
Invalid Phase 3 PHP-CGI acceptance invocation
```

An acceptance failure writes only:

```text
Phase 3 PHP-CGI acceptance failed: <closed-case-token>
```

No exception string, command line, source path, port, PID, raw rejected value,
secret, environment value, or host message may enter stdout or stderr.

Success writes one LF-terminated JSON object and empty stderr. Exact keys:

```text
result
profile
php_minor
case_count
request_count
process_count
reused_request_count
journal_records
active_operations
disclosure_failures
orphan_processes
```

Require exact fixed values:

```text
result:                  pass
profile:                 cgi-fcgi-responder
php_minor:               8.5
active_operations:       0
disclosure_failures:     0
orphan_processes:        0
```

The five count fields are positive integers derived from the closed matrix and
must be reported exactly in the completion report. Do not output ephemeral
ports, PIDs, temporary paths, timings, or platform-specific binary paths.

### 8.2 Closed admission and application cases

Run external cases for:

- production matched success with exact middleware and repeated response-field
  order;
- production unmatched 404;
- production generic application failure with fixed generic 500;
- production diagnostic failure remaining generic with bootstrap denial;
- development diagnostic failure rendering trusted HTML only with exact
  direct-loopback, non-forwarded evidence;
- development denial for `Forwarded`, `X-Forwarded-For`, another
  `X-Forwarded-*` field, and a non-loopback `REMOTE_ADDR`;
- missing, wrong, and malformed `GATEWAY_INTERFACE`;
- missing, wrong, and malformed `FCGI_ROLE`;
- every unsupported `SERVER_PROTOCOL` category plus the six accepted spellings;
- a CLI invocation of the fixture entry proving non-`cgi-fcgi` rejection
  without a client response; and
- each of the ten required INI prerequisites made wrong one at a time in a
  fresh listener process, proving rejection before application handling.

The harness may select fixture script, one closed INI-violation token, and
process retirement policy. No request parameter may change those operator
choices.

### 8.3 Closed request grammar cases

Exercise accepted and rejected cases for:

- uppercase token method and lowercase/invalid method;
- origin-form target, absolute form, authority form, asterisk form, fragment,
  whitespace, control, NUL, and invalid percent triplet;
- exact target/query agreement and mismatch;
- exact `http`/`https` selection and invalid HTTPS token;
- mandatory host and invalid authority;
- CONTENT_TYPE/CONTENT_LENGTH projection and ambiguous HTTP duplicates;
- ordinary headers, CR, LF, NUL, invalid names, normalized duplicate names,
  `HTTP_PROXY`, `Forwarded`, and `X-Forwarded-*`;
- flat query parsing, `+`, `%HH`, empty field, nesting/brackets, invalid UTF-8,
  control bytes, and duplicate decoded keys;
- flat cookie parsing, optional single space, missing equals, invalid name,
  forbidden value bytes, and duplicate names;
- canonical content length, sign, leading whitespace, leading zero, early EOF,
  and extra STDIN bytes beyond the declared length remaining unread;
- transfer-encoding rejection;
- raw `application/x-www-form-urlencoded` body remaining unparsed;
- multipart rejection; and
- empty post/files admission under `enable_post_data_reading=0`.

### 8.4 Exact request-boundary triads

For every maximum, make separate accepted minus-one, accepted boundary, and
rejected plus-one requests:

| Concern | Minus one | Boundary | Plus one |
| --- | ---: | ---: | ---: |
| method bytes | 31 | 32 | 33 |
| request-target bytes | 8,191 | 8,192 | 8,193 |
| raw query bytes | 8,191 | 8,192 | 8,193 |
| query pairs | 127 | 128 | 129 |
| decoded query key bytes | 127 | 128 | 129 |
| decoded query value bytes | 4,095 | 4,096 | 4,097 |
| raw Cookie bytes | 4,095 | 4,096 | 4,097 |
| cookie pairs | 63 | 64 | 65 |
| cookie name bytes | 127 | 128 | 129 |
| cookie value bytes | 1,023 | 1,024 | 1,025 |
| distinct projected request fields | 127 | 128 | 129 |
| one request field value bytes | 8,191 | 8,192 | 8,193 |
| total request field name/value bytes | 65,535 | 65,536 | 65,537 |
| request body bytes | 1,048,575 | 1,048,576 | 1,048,577 |

Construct counts so required profile fields and `HTTP_HOST` are included in
the exact implemented total. Do not approximate with payload size alone.

### 8.5 Exact response and emission cases

The test response handler may select behavior only from a closed route
parameter table. Exercise:

- status 199 rejected, 200 accepted, 599 accepted, and 600 rejected;
- protocol `1.0`, `1.1`, and `2` accepted and another value rejected;
- exact repeated-field and per-field value order;
- adapter-owned and selected hop-by-hop field rejection;
- computed `Content-Length` exactly once;
- exact body bytes;
- `HEAD` complete preflight and no body emission;
- application response getter/read failure behavior already locked by package
  tests and unchanged external generic/disclosure outcome; and
- pre-commit validation failure with no response bytes.

Run separate minus-one, boundary, and plus-one cases:

| Concern | Minus one | Boundary | Plus one |
| --- | ---: | ---: | ---: |
| distinct response fields | 127 | 128 | 129 |
| values per response field | 31 | 32 | 33 |
| one response field value bytes | 8,191 | 8,192 | 8,193 |
| total response field name/value bytes | 65,535 | 65,536 | 65,537 |
| staged response body bytes | 1,048,575 | 1,048,576 | 1,048,577 |

### 8.6 Uncertain output and retirement proof

Use one dedicated listener process and request that produces the accepted
maximum body. After request submission, deliberately close the test client
connection before accepting the complete response. Treat output as uncertain
without claiming that PHP or Kongono observed a particular write error.

The harness must:

1. mark that exact test-owned process unsafe;
2. retire it through its direct process handle;
3. prove it no longer exists;
4. start a new process with a different observed PID;
5. run one complete matched request successfully; and
6. omit both PIDs from output and committed evidence.

This proves the external operator retirement obligation. It does not add
automatic production recovery or claim rollback.

### 8.7 Reuse, cleanup, and isolation

On one production listener and one kept-open client connection, run exactly:

```text
matched -> unmatched -> matched -> generic failure -> matched
```

Require five complete FastCGI END_REQUEST records, byte-identical first/third/
fifth matched responses, fixed generic output, no prior header/body/trust
leakage, and five bounded journal records. Every journal record must report
shutdown complete and zero active Kernel operations.

Run the same deterministic subset through two independent processes and
require byte-identical client-visible results after excluding no field; the
success envelope itself contains no PID, port, timing, or temporary path.

Use existing focused package tests as the authoritative internal proof of one
send, one close, primary-plus-cleanup composition, and exact emitter failure
classification. The external proof adds one client-visible response/end
request, zero active operations, explicit application shutdown, process reuse,
and host retirement. Do not claim external introspection of private counters.

### 8.8 Disclosure matrix

Include distinct sentinels in rejected target, query, header, cookie, body,
host, forwarded, and application-failure inputs. Assert they are absent from:

- generic response status, fields, and body;
- trusted response status, fields, and body;
- FastCGI stderr;
- runner stdout and stderr;
- journal records; and
- any committed acceptance document or fixture output.

Also assert absence of exception class names, messages, traces, local source
paths, `C:\Users\`, temporary paths, binary path, port, PID, environment
values, and credentials. The trusted response may contain only the already
accepted test diagnostic content.

---

## 9. External process and network safety

The harness must be safe under failure:

- loopback only;
- no DNS or remote network;
- no listening production artifact;
- finite connect, read, write, startup, request, and retirement timeouts;
- bounded record, PARAMS, STDIN, STDOUT, STDERR, header, body, journal, and
  scenario counts;
- direct command array, no shell;
- no inherited secret serialized into evidence;
- fresh process wrapper per isolated group;
- every handle, socket, pipe, stream, and process closed in `finally`;
- no child remains after success or failure;
- exact nonrecursive temp cleanup;
- no sleep longer than one second and no unbounded polling loop; and
- no automatic retry of an acceptance request.

Use exact ceilings: five seconds for listener startup, two seconds for connect,
five seconds for an individual read or write, fifteen seconds for one request,
and five seconds for process retirement. Poll intervals must not exceed 50
milliseconds. A journal line must not exceed 512 bytes, the journal must not
exceed 4,096 records or 2,097,152 bytes, and an over-limit condition is a hard
acceptance failure.

If the managed sandbox alone blocks process creation or direct loopback socket
access, one identical direct acceptance command may be rerun with approved
local-process/loopback access after proving no child process, socket, temp
directory, environment mutation, or repository mutation remains. Network
access to the internet is neither required nor authorized. Any harness,
semantic, assertion, timeout, cleanup, or production-path failure is a hard
stop, not an environmental retry.

---

## 10. Documentation and ADR 0058

### 10.1 ADR 0058

Create ADR 0058 with:

```text
Status: Accepted
Date: 2026-08-21
```

Record:

1. the exact external `php-cgi` FastCGI RESPONDER proof boundary;
2. the test-owned client/process/fixture dependency direction;
3. actual PHP 8.5.9 Windows proof as acceptance evidence, not a permanent
   binary path contract;
4. one reused listener process with sequential requests;
5. input, response, trust, disclosure, cleanup, Kernel, and retirement evidence;
6. the exact limits of that evidence;
7. no production FastCGI parser/client/listener or test dependency;
8. no general production-hardening, proxy, TLS, authentication, incident,
   telemetry, or broader SAPI claim;
9. the exact Phase 3 checkpoint commit subject; and
10. Phase 3 closed only after the post-commit gates and external report pass.

### 10.2 External acceptance guide

Create `docs/architecture/phase-3-external-acceptance.md` with:

- purpose, scope, and current status;
- exact host and fixture composition diagram;
- operator-supplied binary rule;
- direct command and success envelope;
- external matrix mapped to deterministic package tests where private internal
  counters cannot be observed externally;
- loopback/process/temp safety;
- trust and disclosure rules;
- process reuse and retirement evidence;
- reproducibility prerequisites;
- unsupported claims and future work; and
- links to ADR 0058, ADR 0052, ADR 0057, profile, deployment guide, Phase 2
  fixture, security, toolchain, and roadmap.

Do not commit generated execution output, a port, PID, timing, temporary path,
binary path-specific configuration, or host log.

### 10.3 Current-document reconciliation

Reconcile every Section 5.3 Markdown path so it states accurately:

- Phases 3A through 3H are complete after the checkpoint;
- the selected PHP-CGI FastCGI RESPONDER profile has external test-owned proof;
- the harness is not production or a supported server/client;
- Phase 3 supplies one supported synchronous production integration profile;
- Phase 3 is not the full framework, Stable API, 1.0, universal SAPI support,
  deployment automation, or production-hardening certification;
- production trusted diagnostics remain denied;
- process, proxy, TLS, credentials, logs, monitoring, incident, and deployment
  ownership remain external;
- no production package depends on acceptance code; and
- no next phase starts automatically.

Preserve historical context. Earlier ADR decision sections may describe what
was future when accepted, but add an explicit Phase 3H outcome/current-status
section where needed instead of rewriting the original rationale as if external
proof existed earlier.

The fixed Phase 3 A-H table remains exactly eight rows and changes only 3H from
future to complete/current text.

---

## 11. Focused validation before checkpoint

### 11.1 Syntax and static analysis

Run `php -l` for all eleven new PHP files. Then run:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress tests/External tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff tests/External tests/Architecture
```

Require zero PHPStan errors and zero fixable files. Do not suppress, ignore,
baseline, exclude, or weaken a finding.

### 11.2 Existing focused regression

Run:

```powershell
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/BlackBox
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer dump-autoload --optimize --strict-psr
```

Require complete passes, no warning/risky/incomplete/skipped test, and strict
PSR-4 success.

### 11.3 External acceptance

Run exactly:

```powershell
composer acceptance:phase3 -- C:\php\php-cgi.exe
```

Require exit zero, empty stderr, one valid success JSON line, every closed case
complete, zero disclosure failures, zero active operations, zero orphan
processes, full cleanup, and a normal PHP environment afterward.

The approved local-process/loopback retry in Section 9 is the only allowed
retry for this command.

### 11.4 Canonical validation

Run:

```powershell
composer quality
```

Permit only the unchanged Composer cache/Packagist retry rule from Section 4.
Require validation, package validation, locked audit, platform, style,
PHPStan, and the complete PHPUnit suite to pass. Record exact versions, file
counts, tests, and assertions.

### 11.5 Documentation and safety

Require:

- zero broken local links in current nonhistorical Markdown;
- no stale current claim that Phase 3H or external acceptance remains future;
- no claim of general server/client/deployment/hardening support;
- exact ADR index/order/status;
- strict UTF-8 without BOM, LF-only, final-LF, no trailing whitespace, no NUL;
- ordinary files, default streams only, no reparse points;
- tracked modes unchanged before staging;
- no nested repository;
- `git diff --check` success;
- normal PHP `memory_limit=128M`, `C:\php\php.ini`, no scanned INI, and absent
  `PHP_INI_SCAN_DIR`; and
- no `php-cgi` child, listener, socket, journal, or Phase 3H temp directory
  remains.

Any failure stops before staging. Keep the report zero bytes.

---

## 12. Exact pre-stage inventory and path set

After implementation and all pre-stage gates, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               277
total status paths:            492
staged paths:                    0
physical Git-visible files:    628
tracked index paths:           536
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                151
style-covered PHP files:       277
docs/dev_plan/phase_3 files:     15
docs/task_reports/phase_3 files: 15
```

Serialize every final status path alone as `normalized-path<LF>`, ordinal
sorted, UTF-8 without BOM. Require:

```text
records: 492
bytes:   24819
SHA-256: B383CE31B61122CD19700735C0EB4457006196037A06F19E76086944D799E0A2
first:   .php-cs-fixer.dist.php
last:    tests/External/PhpCgi/run.php
```

The exact set is the authenticated 477 starting status paths plus the two
Phase 3H caller records and thirteen Section 5.1 paths. No other path may
appear.

Reconstruct the 580-record protected manifest exactly. Every existing change
must remain confined to the 33 authorized paths. The active report remains
zero bytes.

---

## 13. Exact Phase 3 checkpoint

### 13.1 Staging set

Exclude exactly:

```text
docs/task_reports/phase_3/Phase_3H.md
```

The remaining path-only staging manifest must be:

```text
records: 491
bytes:   24781
SHA-256: 387F97E0BA82EF66B288395E659CE27EB73F6D20E4030534CFFCAEDC2DEA7ADE
first:   .php-cs-fixer.dist.php
last:    tests/External/PhpCgi/run.php
```

Materialize exactly those 491 normalized paths into a PowerShell string array
after verifying the 492-path manifest. Do not use a wildcard, directory path,
glob, implicit `git add -A`, `git add .`, shell expansion, or path from outside
the authenticated manifest.

Stage once with the pathspec-safe form:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' add -- $stagePaths
```

This directive explicitly authorizes that one exact staging mutation. If the
managed environment denies `.git/index.lock` before changing the semantic
index, verify the index remains equal to `HEAD`, then permit one identical
approved-filesystem-access retry. Any partial or semantic mismatch is a hard
stop; do not unstage or rebuild without new authority.

Require:

```text
staged paths:                  491
staged modified paths:         30
staged added paths:            276
staged deleted paths:          185
unstaged tracked paths:          0
untracked paths:                 1
index entries:                 627
index modes other than 100644:   0
```

The sole untracked path is the zero-byte Phase 3H report. Every staged blob for
an existing file equals current worktree bytes; every deletion is absent; no
conflict stage exists; all paths and modes are exact; and
`git diff --cached --check` passes.

### 13.2 Tree proof

Run:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' write-tree
```

The resulting tree must contain exactly 627 paths, all mode `100644`. Its delta
from parent `647053d4bddc4c76090bca9bf3d693faa5880c52` must be exactly:

```text
paths:     491
modified:   30
added:     276
deleted:   185
```

`write-tree` may update Git cache-tree metadata in `.git/index`. Raw index byte
identity is not an acceptance condition. Semantic staged entries, stages,
modes, blob IDs, worktree equality, and the resulting tree are authoritative.

If sandbox permissions alone block `write-tree` before a tree is returned,
verify the semantic index remains exact and permit one identical approved
filesystem-access retry. Do not restore or reconstruct the accepted index.

### 13.3 Commit

Require effective Git author/committer identity:

```text
Anthony Iovanna <anthonyiovanna@gmail.com>
```

Require no configured signing or hook override. Create exactly one commit with
subject:

```text
Complete Kongono Phase 3 production HTTP integration milestone
```

Use a normal non-amending commit. Do not set or invent author/committer dates.
Do not add a body, co-author, signature, tag, branch, remote, push, merge,
rebase, or second commit.

If managed filesystem permission alone rejects the commit before `main` moves,
verify `HEAD` is unchanged and the complete semantic index/tree remain exact,
then permit one identical approved-filesystem-access retry. If `HEAD` changes,
do not retry.

Require the new commit:

- has exactly one parent, the locked Phase 2H checkpoint;
- has the exact subject and effective identity;
- has the exact previously proven tree;
- produces commit count eleven;
- leaves `main` as the only local branch;
- creates no remote, tag, or unexpected ref;
- does not rewrite `refs/codex/**`; and
- has the exact 491-path, 30/276/185 parent delta.

Do not amend, reset, restore, or alter the commit after creation.

---

## 14. Mandatory post-commit validation

Before report population require:

```text
tracked files:           627
tracked modifications:     0
staged paths:               0
untracked paths:            1
total status paths:         1
physical Git-visible files: 628
```

The sole path must be the exact zero-byte Phase 3H report. The Phase 3H
directive and every earlier directive/report are in `HEAD`; the two earlier
Phase 3G reports remain exact empty blobs.

Rerun, in this order:

1. Git parent/tree/delta/subject/identity/history/ref proof;
2. tracked inventory, modes, no nested repository, streams, reparse, encoding,
   LF, whitespace, NUL, links, and current/future documentation proof;
3. `composer dump-autoload --optimize --strict-psr`;
4. focused PHPStan and focused style from Section 11.1;
5. focused PHP-CGI, BlackBox, and architecture tests from Section 11.2;
6. `composer acceptance:phase3 -- C:\php\php-cgi.exe`;
7. `composer quality`;
8. normal-PHP environment restoration proof; and
9. exact one-untracked-report status proof.

The same narrowly defined environment retries remain available only for a
fresh post-commit cache/Packagist denial or managed process/loopback denial,
after proving cleanup and no repository mutation. No semantic retry is allowed.

If any post-commit gate fails, the commit remains. Do not amend, reset, revert,
or populate the report. Stop and request the smallest authenticated correction.

---

## 15. Report contract

Only after every post-commit non-report gate passes, populate:

```text
docs/task_reports/phase_3/Phase_3H.md
```

Use exactly:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. EXTERNAL ACCEPTANCE ARCHITECTURE
## 8. EXTERNAL ACCEPTANCE RESULTS
## 9. ARCHITECTURAL DECISIONS
## 10. DEVIATIONS FROM DIRECTIVE
## 11. DEPENDENCIES ADDED/REMOVED
## 12. ENVIRONMENT
## 13. COMMANDS RUN
## 14. FOCUSED VALIDATION RESULTS
## 15. CANONICAL QUALITY RESULTS
## 16. DOCUMENTATION REVIEW
## 17. SECURITY AND FILE-SAFETY REVIEW
## 18. STAGING EVIDENCE
## 19. COMMIT EVIDENCE
## 20. POST-COMMIT VALIDATION RESULTS
## 21. WARNINGS / KNOWN ISSUES
## 22. QUESTIONS / BLOCKERS
## 23. FINAL GIT STATUS
## 24. ACCEPTANCE CRITERIA
## 25. NEXT STEP
```

The report must include:

- exact directive identity and all locked baseline manifests;
- exact thirteen created and 33 modified paths;
- installed PHP-CGI binary identity/version without presenting its local path
  as a portable framework contract;
- acceptance success JSON values and exact count fields;
- every focused and canonical command result;
- any permitted environmental retry and its evidence;
- exact staging manifest, semantic index, modes, tree, parent delta, commit
  hash, subject, identity, and history;
- post-commit external and canonical results;
- no orphan process/socket/temp/environment evidence;
- the remaining unsupported/future scope; and
- truthful deviations. Use `None` only when none occurred.

`NEXT STEP` must be exactly:

```text
Phase 3 is complete. Await architect direction for Phase 4; do not begin it automatically.
```

After population, verify the report is strict UTF-8 without BOM, LF-only,
final-LF, no trailing whitespace, no NUL, ordinary, default-stream-only,
non-reparse, untracked, unstaged, and absent from `HEAD`. Rerun final Git status,
history, report-only diff, and environment/process cleanup proofs.

Return the report's final byte count and uppercase SHA-256 outside the report
body. Do not embed a self-referential digest.

---

## 16. Prohibited scope

Phase 3H must not:

- modify production PHP or a package manifest;
- add a production FastCGI parser, client, listener, entrypoint, process
  manager, worker, server, or deployment artifact;
- add or change a Composer dependency, plugin, lockfile, PHP constraint,
  platform, security policy, or autoload mapping;
- add general configuration, secrets, `.env`, proxy trust, TLS, authentication,
  authorization, sessions, CSRF, rate limiting, logging, telemetry, monitoring,
  incident response, deployment, or automatic recovery;
- add HTTP/2 or HTTP/3 wire support, another SAPI, FPM, Apache/IIS module,
  RoadRunner, FrankenPHP, OpenSwoole, ReactPHP, Amp, Workerman, or CLI-server
  profile;
- add semantic 4xx mappings, multipart uploads, streaming, form parsing,
  decompression, or broader limits;
- expose a test class as framework API;
- connect to a non-loopback address or the internet;
- leave a child process, listener, socket, journal, temp file, temp directory,
  environment value, or generated output;
- weaken, skip, suppress, baseline, ignore, or exclude a quality finding;
- modify any path outside Sections 5.1, 5.3, and the active report;
- stage a path outside the exact 491-path manifest;
- stage the active report;
- create more than one commit;
- amend, reset, restore, checkout, clean, stash, rebase, merge, tag, remote,
  push, or rewrite history/refs;
- delete or rewrite `refs/codex/**`;
- populate the report before every post-commit gate passes;
- claim full framework, Stable API, 1.0, feature parity, universal deployment,
  or production-hardening certification; or
- begin Phase 4 or any later capability.

---

## 17. Acceptance criteria

Every criterion must pass:

1. Independent Phase 3H directive authentication passes.
2. Every required authority is read completely.
3. Exact repository, history, status, physical, untracked, archive, authorized,
   protected, lockfile, caller, and host-tool identities pass.
4. Pre-change canonical quality passes.
5. Exactly thirteen paths are created and 33 existing paths modified.
6. Every production PHP file and package manifest remains byte-identical.
7. Composer adds only the test acceptance script; lockfile remains exact.
8. PHPStan adds only `tests/External`; no suppression or policy weakening.
9. Eleven test PHP files are PSR-4/style/static-analysis clean.
10. The FastCGI codec is bounded, test-only, and exact for the required record
    grammar.
11. The client is loopback-only, sequential, bounded, timeout-controlled, and
    closes failed connections.
12. The process wrapper uses command arrays, exact INI prerequisites, bounded
    startup, direct handle retirement, and exact nonrecursive temp cleanup.
13. No external child, listener, socket, journal, temp path, or environment
    mutation remains after any run.
14. Production and development profile selection is literal and cannot be
    changed by a request.
15. Real Kernel, Web Application, Web Runtime, PHP-CGI delivery, front
    controller, emission, close, and shutdown execute through the listener.
16. Exact admission and operator-prerequisite rejection cases pass.
17. Matched, unmatched, generic, trusted, bootstrap-denied, delivery-denied,
    forwarded-denied, and nonloopback-denied cases pass.
18. Every request grammar case and exact boundary triad passes.
19. Every response/emission case and exact boundary triad passes.
20. HEAD emits no body and computed content length/order are exact.
21. Pre-commit failure emits no response bytes.
22. Uncertain client disconnect retires its exact process and a new process
    succeeds without claiming rollback or observed framework write failure.
23. Five sequential requests reuse one listener process without state leakage.
24. Every completed journal record reports shutdown and zero active Kernel
    operations.
25. Independent-process deterministic results pass.
26. All disclosure sentinels and local/exception/secret values remain absent
    from every client, runner, journal, and documentation surface.
27. No production dependency on test acceptance code exists.
28. ADR 0058 is Accepted, exact, and complete.
29. External acceptance guide is reproducible, bounded, and honest about
    unsupported scope.
30. Every current document is reconciled; fixed A-H roadmap has exactly eight
    rows and Phase 3 is closed without overclaiming.
31. Focused syntax, PHPStan, style, PHP-CGI tests, BlackBox tests, architecture
    tests, and strict autoload pass.
32. Pre-checkpoint external acceptance and canonical quality pass.
33. Final status path manifest is exactly 492 paths; protected state remains
    exact and report remains zero bytes before staging.
34. Exactly 491 paths are staged: 30 modified, 276 added, 185 deleted; active
    report is sole untracked path.
35. Semantic index contains exactly 627 ordinary 100644 entries with exact
    worktree/blob equality and no conflicts.
36. `git diff --cached --check` and exact write-tree proof pass.
37. Exactly one commit is created with the locked parent, subject, identity,
    tree, 491-path delta, and eleven-commit history.
38. No tag, remote, push, extra branch, unexpected ref, amend, reset, or second
    commit occurs.
39. Post-commit Git, documentation, safety, autoload, focused, external, and
    canonical gates all pass.
40. Post-commit repository has exactly 627 tracked files and only the zero-byte
    active report untracked before report population.
41. All earlier Phase 3 caller records are committed at exact bytes, including
    the two accepted empty reports.
42. Report remains zero bytes until all non-report gates pass, then is populated
    once with all exact headings and truthful evidence.
43. Final report integrity, report-only status, environment, process cleanup,
    index, history, and ref proofs pass.
44. Report byte count and SHA-256 are returned outside its body.
45. Phase 4 and all prohibited later capabilities remain unstarted.

Any failure means Phase 3H is not complete. Preserve the exact reached state,
leave the report zero bytes unless every non-report gate passed, and request
the smallest authenticated correction. A created checkpoint commit remains
immutable even if a later gate fails.

---

## 18. Final instruction

Implement Phase 3H only. Prove the actual operator-supplied PHP 8.5
`cgi-fcgi` FastCGI RESPONDER listener through bounded test-owned infrastructure
outside every production package. Exercise the complete accepted profile,
trust, disclosure, limits, reuse, cleanup evidence, and retirement boundary.

After every pre-stage gate passes, stage only the exact authenticated path set,
create the single Phase 3 checkpoint commit, and rerun every post-commit proof.
Populate the report only after complete success. Do not overclaim what the
external proof establishes, and do not begin Phase 4.
