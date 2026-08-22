# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 3G

## Bounded Front-Controller Lifecycle and PHP-CGI Deployment Constraints

**Directive date:** 2026-08-21
**Repository:** `C:\Users\antho\Dev\kongono`
**Execution role:** executor, not architect
**Scope:** Phase 3G only
**Checkpoint:** none; do not stage or commit

---

## 1. Mission

Implement the smallest production-safe front-controller lifecycle boundary for
the PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` profile completed through Phase
3F.1.

Phase 3G must add exactly four capabilities:

1. an Experimental, stateless `CgiFrontController` that runs the existing
   `CgiWebEntry` once and invokes one caller-supplied application-shutdown
   closure exactly once;
2. deterministic four-way failure behavior that preserves the primary entry
   failure, the shutdown failure, or both without retrying or emitting another
   response;
3. one actionable, non-sensitive PHP-CGI diagnostic and exception for the
   simultaneous entry-and-shutdown-failure case; and
4. exact deployment and security documentation for the already-selected host
   profile, including what Kongono owns and what the external operator owns.

The supported composition becomes:

```text
consumer-owned composition root
    -> select literal CgiOperatorProfile
    -> CgiOperatorBootstrap::load(profile)
    -> explicitly construct and boot WebApplication
    -> construct WebRuntimeAdapter(application, bootstrap permission)
    -> CgiFrontController::run(runtime, application shutdown Closure)
        -> CgiWebEntry::run(runtime)
            -> one fresh CgiWebDelivery
        -> invoke shutdown Closure exactly once
```

The shutdown closure is the narrow composition seam that keeps PHP-CGI from
depending directly on Web Application. It must be supplied only after a fully
booted application and runtime exist. The consumer remains responsible for
partial construction cleanup if bootstrap or application boot fails before the
front controller is entered.

This is integration hardening and an operational contract. It is not a
generated `public/index.php`, listener, server configuration, process manager,
deployment automation, application template, logger, incident system, external
host proof, or production-hardening certification. Phase 3H alone follows and
owns external CGI/FastCGI acceptance plus the Phase 3 checkpoint.

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
docs/architecture/http-message-model.md
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

packages/php-cgi/composer.json
packages/php-cgi/README.md
packages/php-cgi/src/Bootstrap/CgiOperatorBootstrap.php
packages/php-cgi/src/Bootstrap/CgiOperatorProfile.php
packages/php-cgi/src/Delivery/CgiWebDelivery.php
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/src/Entry/CgiWebEntry.php
packages/php-cgi/src/Exception/CgiDeliveryCleanupException.php
packages/php-cgi/src/Exception/ExceptionInterface.php
packages/php-cgi/tests/Unit/Delivery/CgiWebDeliveryTest.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Entry/CgiWebEntryTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php

packages/web-application/src/WebApplication.php
packages/web-runtime/src/WebRuntimeAdapter.php
packages/web-runtime/src/Exception/DeliveryCleanupException.php

tests/Architecture/ApiStatusConventionTest.php

docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md
docs/dev_plan/phase_3/kongono_phase_3F_1_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3F.md
docs/task_reports/phase_3/Phase_3F_1_correction.md
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
- explicit typed construction rather than service location;
- no global, static, singleton, container-current, thread-local, or fiber-local
  request, response, permission, failure, lifecycle, or operation state;
- Web Application as the sole Kernel-operation owner and Web Runtime as the
  owner of failure selection plus one send and one delivery-close attempt;
- fail-closed trusted diagnostics with independent bootstrap and current-
  delivery permissions;
- actionable, bounded, non-sensitive diagnostic metadata;
- package dependency direction and neutral Kernel ownership;
- exact API-status metadata;
- strict types, PSR-4, PHPStan without suppression, and canonical quality;
- historical directives and reports as immutable evidence; and
- stop rather than guess whenever an authenticated invariant differs.

The current PHP constraints, Composer platform value, and supported-PHP policy
remain unchanged in Phase 3G. The separately discussed rolling PHP baseline
must receive its own future Accepted doctrine/ADR before any existing `^8.5`,
`>=8.5`, or platform constraint changes. Do not implement that policy here.

---

## 3. Authenticated starting state

### 3.1 Repository identity before the two Phase 3G caller records

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
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               251
total status paths:            466
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    602
package directories:            14
package manifests:              14
production PHP files:          124
test PHP files:                139
style-covered PHP files:       263
docs/dev_plan/phase_3 files:     11
docs/task_reports/phase_3 files: 11
```

The semantic index equals `HEAD`. Existing `refs/codex/**` application-managed
refs are permitted and must remain untouched. Other than `refs/heads/main`, no
branch, remote, tag, or unexpected non-application ref exists.

The root lockfile is immutable in Phase 3G:

```text
bytes:   197527
SHA-256: 823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
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

Encode the manifest as UTF-8 without BOM. Before the Phase 3G caller records
are added, the exact result is:

```text
records:               602
manifest bytes:      75796
manifest SHA-256:    3A3A268DC714C36A36AB40A96D44885A2A7D3E5B4E994110B1DD48CD1D4F6B67
aggregate file bytes: 6655770
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

During Gate A, when the two active caller records already exist, exclude
exactly those two paths before reconstructing this 602-record baseline.

### 3.3 Historical numbered archive

All 208 accepted historical records under the numbered `docs/dev_plan/phase_*`
and `docs/task_reports/phase_*` folders are immutable during Phase 3G. Using the
same serialization as Section 3.2:

```text
records:               208
manifest bytes:      26093
manifest SHA-256:    88CC71C9CECF05CC9C2B63F6963F57103281E078656AC446494E297074AF5A81
aggregate file bytes: 4262274
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3F_1_correction.md
```

The four most recent immutable records are:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3F_codex_directive.md` | 43,927 | `B9AC0C2D78918BF95E351376788F51082168F35481EAED52278C3FC523D8B71A` |
| `docs/task_reports/phase_3/Phase_3F.md` | 21,223 | `3C4AB076AE5B5A03553C9F9AC9B752F003D7CCFAA76ECF092101C32E0DDD6E90` |
| `docs/dev_plan/phase_3/kongono_phase_3F_1_codex_directive_correction.md` | 23,333 | `06F55E61D5B75F20B14D1704D17807B7F0ACCBEDAB6E754B4AFFB0B454D19B56` |
| `docs/task_reports/phase_3/Phase_3F_1_correction.md` | 16,360 | `89003BBC7C42172AF5823E421665C9C77E8B5608B15EC8FE63F615D90BD96D18` |

Do not rewrite embedded historical paths, wording, hashes, commands, or status
evidence. Historical content is not current navigation text.

### 3.4 Active Phase 3G caller records

Before execution the caller must place exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md
docs/task_reports/phase_3/Phase_3G.md
```

The directive must match the independently supplied identity. The report must
be an ordinary, untracked, unstaged, `HEAD`-absent zero-byte file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

With those records present and before implementation, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               253
total status paths:            468
staged paths:                    0
physical Git-visible files:    604
tracked index paths:           536
docs/dev_plan/phase_3 files:     12
docs/task_reports/phase_3 files: 12
```

The 208-record historical manifest excludes the active Phase 3G pair and must
remain exact.

### 3.5 Authorized-start and protected manifests

The 32 existing paths authorized for possible Phase 3G modification are listed
in Section 5.3. Their exact starting manifest is:

```text
records:                32
manifest bytes:       3530
manifest SHA-256:     1274D70BA4F3CCE13F033670D9D9F4D32795BC683E2CF6CEBE247399E40E0E52
aggregate file bytes: 572031
first path:           ARCHITECTURE.md
last path:            tests/Architecture/ApiStatusConventionTest.php
```

Every other accepted physical file forms the protected manifest:

```text
records:               570
manifest bytes:      72266
manifest SHA-256:    DB2AB4C0520651D3C019FECF34B5383FC3C64484FB647583D8999CEBFA8F64EC
aggregate file bytes: 6083739
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

At final validation exclude the two active caller records, all five Section 5.1
created paths, and all 32 Section 5.3 mutable paths before reconstructing the
protected manifest.

If any Section 3 invariant differs, stop before quality or mutation. Do not
repair, restore, truncate, rename, stage, commit, or adopt a locally observed
replacement value.

---

## 4. Pre-change validation

After Sections 2 and 3 pass, run literal:

```powershell
composer quality
```

Require Composer validation, locked audit with zero advisories, platform pass,
zero PHP CS Fixer findings, zero PHPStan errors, and a completely passing
PHPUnit suite.

The existing missing-license and local unbounded-development-constraint
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

### 5.1 Create exactly these five implementation paths

```text
docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md
docs/engineering/php-cgi-deployment.md
packages/php-cgi/src/Entry/CgiFrontController.php
packages/php-cgi/src/Exception/CgiEntryShutdownException.php
packages/php-cgi/tests/Unit/Entry/CgiFrontControllerTest.php
```

### 5.2 The caller alone creates these two records

```text
docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md
docs/task_reports/phase_3/Phase_3G.md
```

Do not recreate, rename, truncate, or move them. Populate only the report and
only after every non-report gate passes.

### 5.3 Modify exactly these 32 existing paths

```text
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
packages/web-runtime/README.md
packages/php-cgi/src/Diagnostic/PhpCgiDiagnostic.php
packages/php-cgi/tests/Unit/Diagnostic/PhpCgiDiagnosticTest.php
packages/php-cgi/tests/Unit/Exception/PhpCgiExceptionTest.php
tests/Architecture/ApiStatusConventionTest.php
```

Each listed path must receive a real Phase 3G change required by Sections 6
through 11. Do not touch another existing path. Do not run a broad formatter or
documentation rewriter.

---

## 6. Package, dependency, and API boundary

Phase 3G stays entirely inside the existing `kongono/php-cgi` outer leaf plus
root architecture enforcement and documentation.

Requirements:

- fourteen package directories and fourteen package manifests remain exact;
- `packages/php-cgi/composer.json`, every other package manifest,
  `composer.json`, and `composer.lock` remain byte-identical;
- no dependency is added, removed, upgraded, downgraded, or re-resolved;
- the accepted direct edge `php-cgi -> web-runtime` remains sufficient;
- no direct `php-cgi -> web-application`, Kernel, HTTP Server, Routing,
  Dependency Injection, or Application Graph edge is added;
- no lower package depends on PHP-CGI;
- the dependency graph remains acyclic;
- `CgiFrontController` and `CgiEntryShutdownException` are Experimental;
- `PhpCgiDiagnostic` remains Internal;
- `CgiWebEntry`, `CgiWebDelivery`, Web Runtime, Web Application, Kernel, the
  converter, the emitter, bootstrap, permissions, and lower-package production
  bytes remain unchanged; and
- strict optimized PSR-4 autoload still passes.

The new front controller may import only the exact types needed for API
metadata, `Closure`, `Throwable`, `WebRuntimeAdapter`, the existing
`CgiWebEntry`, and the new package-local exception. It must not import Web
Application merely to obtain shutdown behavior.

---

## 7. `CgiFrontController` contract

Create:

```text
Kongono\PhpCgi\Entry\CgiFrontController
```

Exact declaration:

- `declare(strict_types=1);`
- `#[Api(ApiStatus::Experimental)]`;
- `final class`;
- private constructor;
- zero properties, constants, traits, or interfaces;
- not cloneable through any public surface;
- exactly one declared public method; and
- no retained or static mutable state.

Exact public method contract:

```php
/** @param Closure(): void $shutdown */
public static function run(
    WebRuntimeAdapter $runtime,
    Closure $shutdown,
): void
```

The closure type is deliberate. Do not widen it to `callable`, accept method
names or service identifiers, inspect closure internals, bind scope, use
reflection, or add a container lookup. The caller may pass a first-class
callable closure for its already-booted application's explicit `shutdown()`.
The framework cannot infer that an arbitrary closure belongs to the same
application wrapped by the supplied runtime; that pairing is an explicit
consumer composition-root contract. Passing a different or no-op closure is an
unsupported caller violation, not a condition Kongono silently repairs.

The method must:

1. call `CgiWebEntry::run($runtime)` exactly once;
2. catch any `Throwable` from that call only to preserve it as the primary
   failure while shutdown is attempted;
3. invoke `$shutdown()` exactly once after the entry attempt, on success or
   failure;
4. if entry succeeded and shutdown succeeded, return `void`;
5. if entry failed and shutdown succeeded, rethrow the exact primary object;
6. if entry succeeded and shutdown failed, rethrow the exact shutdown object;
7. if both failed, throw one new `CgiEntryShutdownException` containing the
   exact two objects in their original roles; and
8. perform no other external call after the shutdown attempt.

Use two explicit guarded attempts. Do not use a `finally` block in a way that
masks the primary failure. Do not retry entry or shutdown. Do not call shutdown
before entry. Do not swap failure roles. Do not convert an `Error` into a
different category.

The front controller owns no bootstrap, application construction, Kernel
scope, route, middleware, handler, container, request, response, transport,
listener, process, worker, log, incident, signal, or termination state.

It must not emit output, choose a response, remove or change headers, flush,
buffer, roll back, register a shutdown function, install an exception/error
handler, call `exit`/`die`, terminate a process, or invoke a second delivery.
All response work remains inside the existing one-delivery path.

---

## 8. Failure and shutdown semantics

Lock this exact matrix:

| Entry result | Shutdown result | Observable result |
| --- | --- | --- |
| success | success | return `void` |
| failure `P` | success | throw exact `P` |
| success | failure `S` | throw exact `S` |
| failure `P` | failure `S` | throw `CgiEntryShutdownException(P, S)` |

The shutdown callback represents only the caller's explicit application
shutdown. It is not a general cleanup registry or extension surface. It must be
supplied after successful application boot and runtime construction. The
consumer composition root remains responsible for any failure before that
point, including partial construction cleanup.

Failure handling must preserve these rules:

- successful or failed entry always gets one shutdown attempt once the front
  controller has been entered;
- there is no cleanup retry, second response, fallback response, rollback, or
  recovery claim;
- a committed or uncertain output failure remains uncertain and the runtime or
  process must not be reused by inference;
- an escaped shutdown failure means lifecycle state is uncertain and the
  process must be retired by the external host policy;
- the front controller does not swallow, log, stringify, redact, serialize, or
  publish either throwable;
- `display_errors=0` remains a mandatory prerequisite so an escaped failure is
  not appended to the response body by PHP; and
- host logging, process termination/replacement, monitoring, and incident
  handling remain operator responsibilities outside Kongono.

The entry and shutdown throwable objects may contain developer-sensitive
detail. Their explicit accessors are inspectability surfaces, not Safe
diagnostic fields. No throwable message, trace, argument, file, line, code, or
stringification may be copied into the package diagnostic message or context.

---

## 9. Actionable simultaneous-failure diagnostic

### 9.1 Diagnostic enum addition

Add exactly one case to `PhpCgiDiagnostic`:

```php
case EntryAndShutdownFailed = 'KGN-PHP-CGI-1019';
```

Exact metadata:

```text
operation:          Run PHP CGI front controller
violated invariant: The primary entry failure and application shutdown failure must both remain inspectable
title:              PHP CGI entry and shutdown both failed
remediation:        Retire the process and correct both the entry failure and application shutdown failure before serving again
violation token:    entry_and_shutdown_failed
```

The context schema remains exactly one Safe Text field named `violation` with
label `Violation`. No optional or additional field is added. The package enum
becomes exactly nineteen contiguous unique cases `KGN-PHP-CGI-1001` through
`KGN-PHP-CGI-1019`; the repository-wide runtime diagnostic inventory becomes
exactly 54 unique identifiers.

### 9.2 Exception contract

Create:

```text
Kongono\PhpCgi\Exception\CgiEntryShutdownException
```

It must:

- use strict types;
- be final and Experimental;
- extend `RuntimeException`;
- implement only the PHP-CGI package `ExceptionInterface` beyond inherited
  interfaces;
- declare exactly four private readonly instance properties named
  `diagnostic`, `diagnosticContext`, `primaryFailure`, and `shutdownFailure`;
- have exact constructor parameters `(Throwable $primaryFailure, Throwable
  $shutdownFailure)`;
- eagerly create the exact 1019 context with
  `entry_and_shutdown_failed`;
- use the diagnostic title as its message, code `0`, and the primary failure as
  native `previous`;
- expose exact `primaryFailure(): Throwable` and
  `shutdownFailure(): Throwable` accessors returning the original objects;
- implement the four exact final diagnostic accessors required by the package
  marker; and
- inherit stringification rather than overriding it.

Do not add a mutable property, array of failures, arbitrary label, dynamic
message, serialization contract, logging side effect, or recovery behavior.

---

## 10. Production tests

### 10.1 `CgiFrontControllerTest`

Create one focused test class under the exact new test path. It must prove at
minimum:

- final/Experimental/private-constructor/zero-property/exact-method shape;
- exact `WebRuntimeAdapter`, `Closure`, and `void` signature;
- a successful existing entry followed by one successful shutdown returns
  `void`, invokes the closure once after entry output, and reaches the paired
  application's Kernel shutdown once;
- a primary entry failure followed by successful shutdown rethrows the primary
  category and invokes shutdown once;
- successful entry followed by a caller-created shutdown throwable rethrows
  that exact object;
- simultaneous entry and shutdown failure throws the new composite exception;
- composite `primaryFailure()`, native `previous`, and
  `shutdownFailure()` preserve exact roles and identities available to the
  test;
- no second response or additional output is emitted after a primary failure;
- no retry occurs in any matrix cell;
- two independent application/runtime/shutdown compositions execute
  sequentially without retained front-controller state; and
- a shutdown callback is never invoked more than once.

Use real package composition. Anonymous test-only implementations of existing
public ports are permitted where needed. Do not add fixture files, mock or
subclass final production classes, mutate private state, use serialization,
use `eval`, or weaken the production types.

Native-output-sensitive tests must use the existing separate-process PHPUnit
mechanism. They must not change permanent INI configuration or depend on a
first-party listener.

### 10.2 Diagnostic and exception tests

Update the existing focused tests to prove:

- exact nineteen-case PHP-CGI catalog and exact 1019 prose/schema/token;
- repository-wide 54-ID uniqueness and domain grammar;
- exact exception parent, marker membership, status, constructor, properties,
  methods, message, code, context, previous, and accessors;
- explicit throwable accessors preserve identity while Safe metadata contains
  no raw failure sentinel; and
- every other PHP-CGI diagnostic and exception contract remains unchanged.

### 10.3 Architecture enforcement

Update `ApiStatusConventionTest` to require:

- exactly 126 production PHP declarations across fourteen packages;
- new type statuses exactly as specified;
- `CgiFrontController` exact stateless public surface;
- no new package or manifest edge;
- no direct PHP-CGI dependency/import of Web Application or lower composition
  packages;
- source confinement for the new lifecycle behavior;
- no forbidden global/ambient/listener/process/configuration/output/recovery
  surface; and
- all existing statuses and package-direction checks unchanged.

Do not add a test skip, incomplete test, suppression, ignore, baseline, broad
source exemption, or assertion that merely repeats a fixture-provided expected
value without exercising behavior.

---

## 11. ADR and documentation reconciliation

### 11.1 ADR 0057

Create ADR 0057 with:

```text
Status: Accepted
Date: 2026-08-21
```

It must record:

1. the precise Phase 3F starting gap;
2. the new front-controller role and why it belongs in PHP-CGI;
3. the exact closure signature and why it avoids a direct Web Application edge;
4. consumer ownership of correct runtime/shutdown pairing and of failures
   before a fully booted application/runtime exists;
5. the four-way failure matrix;
6. exact one-attempt entry and shutdown behavior;
7. diagnostic 1019 and the two preserved throwable roles;
8. Safe metadata versus explicit developer-inspection surfaces;
9. no second output, rollback, retry, logging, or automatic recovery;
10. the fixed selected-profile operational prerequisites;
11. Kongono, application, and external-host ownership boundaries;
12. persistent-runtime/process-retirement consequences;
13. exact API statuses and unchanged dependency graph;
14. rejected direct Web Application dependency, destructor, shutdown hook,
    generic cleanup registry, `callable`, service location, second response,
    listener, deployment generator, and exception swallowing alternatives;
15. Phase 3G limitations and non-guarantees; and
16. Phase 3H external acceptance and checkpoint as the only next phase.

### 11.2 Deployment guide

Create `docs/engineering/php-cgi-deployment.md` as the exact supported
operational contract, not as a runnable server configuration. It must include:

- supported profile: PHP `cgi-fcgi`, CGI/1.1, FastCGI `RESPONDER` only;
- explicit literal profile selection before bootstrap;
- the exact consumer composition order from Section 1;
- a copyable PHP-shaped example using an already-composed application/runtime
  and a first-class shutdown closure, with application-specific composition
  clearly marked rather than invented;
- the four-way failure matrix and process-retirement rule;
- the exact required PHP directive values accepted by the current validator:

| Directive | Accepted value |
| --- | --- |
| `enable_post_data_reading` | `0` or the empty disabled representation |
| `output_buffering` | exactly `0` |
| `zlib.output_compression` | `0` or empty |
| `display_errors` | `0` or empty |
| `display_startup_errors` | `0` or empty |
| `html_errors` | `0` or empty |
| `expose_php` | `0` or empty |
| `auto_prepend_file` | exactly empty |
| `auto_append_file` | exactly empty |
| `cgi.rfc2616_headers` | `0` or empty |

- exact request/response bounds and unsupported input classes by linking to,
  and accurately summarizing, the authoritative profile rather than creating a
  second conflicting schema;
- `Production` trusted diagnostics denied and development trust requiring the
  separate literal bootstrap permission plus exact direct canonical loopback
  evidence with no forwarded marker;
- `display_errors=0` as required protection against exception text entering a
  response;
- one request delivery at a time per interpreter invocation and no claim of
  arbitrary concurrent runtime reuse;
- Kongono ownership: bounded capture, conversion, trusted/generic selection,
  staged emission, delivery close, and the front-controller shutdown attempt;
- application ownership: Kernel/routes/middleware/handlers, construction, boot,
  partial-construction cleanup, and the supplied explicit shutdown closure;
- external host/operator ownership: socket listening, FastCGI framing, TLS,
  proxy policy, filesystem permissions, PHP process lifecycle, timeouts,
  resource ceilings outside Kongono bounds, logs, monitoring, restart/retire
  policy, deployment, secrets, and incident response;
- unsupported environments and explicit absence of Apache/Nginx/Caddy/IIS
  snippets, Docker/service files, `.env`, generated entry scripts, health
  checks, or production certification; and
- a Phase 3H note stating that documentation is not external acceptance proof.

Do not include credentials, filesystem-specific deployment paths, speculative
configuration keys, or a server command that has not been accepted and tested.

### 11.3 Current-document reconciliation

Every Section 5.3 Markdown path must be updated truthfully so current material
states:

- Phases 3A through 3G are implemented;
- `CgiFrontController` owns one existing entry attempt plus one explicit
  supplied shutdown attempt;
- all four failure outcomes are deterministic and the simultaneous case uses
  1019;
- no direct PHP-CGI-to-Web-Application edge exists;
- operational requirements are documented but no listener, server config,
  deployment artifact, or external production proof exists;
- application-specific construction and partial-boot cleanup remain caller
  responsibilities;
- `Production` trusted diagnostics remain denied;
- uncertain emission or shutdown means no reuse claim and requires external
  process retirement policy;
- Phase 3H alone is next; and
- Phase 3 remains smaller than a full framework, Stable API, 1.0, or
  production-hardening certification.

Keep the fixed Phase 3 A-H roadmap table byte-identical:

```text
bytes:   1329
SHA-256: 653BE9BE8A64487C275FCEC935C724F1201D2BCCC398B6CAC4DFCCE0F3ED9B04
```

Update only the roadmap's status/current prose around that fixed table. Do not
rewrite historical directives, reports, or accepted decision history.

---

## 12. Explicitly prohibited scope

Do not add or change:

- a socket listener, FastCGI parser, server, process manager, worker supervisor,
  event loop, coroutine runtime, or concurrency abstraction;
- Apache, Nginx, Caddy, IIS, container, service-manager, platform-hosting, or
  cloud deployment configuration;
- `public/index.php`, generated application/bootstrap files, templates,
  skeletons, installers, CLI commands, or deployment automation;
- environment/configuration repositories, `.env`, secrets loading, dynamic
  profile strings, reload/watch behavior, or client-controlled operator policy;
- Kernel, Web Application, Web Runtime, delivery, converter, emitter,
  bootstrap, permission, routing, middleware, handler, HTTP message, DI, or
  Application Graph production contracts;
- direct PHP-CGI dependency on Web Application or any reverse dependency;
- error middleware, semantic 4xx mapping, problem details, redirects, alternate
  fallback documents, output rollback, second response, or response retry;
- `register_shutdown_function`, destructor cleanup, automatic exception/error
  handlers, output buffering, header removal, flush, `exit`, or process kill;
- general cleanup callbacks, arbitrary lifecycle registries, hooks, events,
  extension points, service location, or ambient current application/runtime;
- logging, telemetry, tracing, metrics, incident storage, health checks,
  restart orchestration, or automatic recovery;
- real-host/subprocess CGI/FastCGI acceptance, served deployment proof, or a
  production-readiness claim; Phase 3H owns external acceptance;
- Composer requirements, package manifests, `composer.lock`, plugins, policy,
  platform emulation, autoload mappings, tooling configuration, or quality
  scripts;
- PHP constraints or the future rolling PHP baseline doctrine/ADR;
- database, ORM, migrations, authentication, authorization, sessions, CSRF,
  templates, frontend, cache, queue, events, mail, scheduler, realtime, agents,
  extensions, MCP, A2A, or framework CLI; or
- staging, committing, amending, resetting, restoring, cleaning, branching,
  tagging, ref mutation, or history rewriting.

Do not make unrelated cleanup changes even if they appear beneficial.

---

## 13. Validation gates

### Gate A - authentication and baseline

Require Sections 2 through 4 to pass before mutation.

### Gate B - exact implementation boundary

After implementation prove:

- exactly the five Section 5.1 paths were created;
- exactly the 32 Section 5.3 paths were modified;
- no other path changed;
- the two active caller records remain untracked and unstaged;
- the 208-record historical manifest remains exact; and
- the 570-record protected manifest remains exact.

### Gate C - dependency, API, and autoload

Require:

- byte-identical root and package Composer manifests plus lockfile;
- fourteen packages, unchanged dependency direction, and zero new edge;
- no PHP-CGI Web Application import;
- exact new statuses and production declaration count;
- strict optimized PSR-4 autoload pass; and
- no PHP constraint, policy, plugin, tooling, or platform change.

### Gate D - focused PHP checks

Run syntax checks for every new or modified PHP file. Then run exactly, at
minimum:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi tests/Architecture
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
composer dump-autoload --optimize --strict-psr
```

Require zero PHPStan errors, zero style findings, complete test success, and
strict PSR-4 success on the first non-network attempt. Do not use suppression,
baseline, ignore, skip, incomplete test, altered process limits, or a weakened
assertion.

### Gate E - semantic and security proofs

Prove directly:

- one entry attempt precedes one shutdown attempt;
- shutdown occurs exactly once for every entered matrix path;
- entry-only and shutdown-only failures preserve exact identity;
- simultaneous failure preserves exact primary/previous and shutdown roles;
- 1019 metadata is fixed, unique, bounded, and contains no raw failure value;
- throwable accessors are not mislabeled as Safe context;
- no second response/output operation occurs after failure;
- no retry, rollback, recovery, handler registration, destructor, or process
  operation exists;
- no listener/server/config/environment/service-locator/ambient state was
  introduced;
- PHP-CGI still has no direct Web Application dependency;
- existing delivery, Web Runtime, Web Application, and Kernel ownership is
  unchanged; and
- sequential independent front-controller compositions retain no operation
  state.

### Gate F - documentation and file safety

Require:

- ADR 0057 exact status/date and all required decisions;
- deployment guide contains every required constraint and no runnable server
  artifact or unsupported claim;
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
untracked paths:               258
total status paths:            473
staged paths:                    0
physical Git-visible files:    609
tracked index paths:           536
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
docs/dev_plan/phase_3 files:     12
docs/task_reports/phase_3 files: 12
```

Also require unchanged branch, refs, `HEAD`, tree, parent, commit count,
semantic index, Composer/lock identities, normal 128M PHP, absent
`PHP_INI_SCAN_DIR`, exact caller identities, exact historical/protected
manifests, modes, and streams.

Only then populate `docs/task_reports/phase_3/Phase_3G.md`. After population,
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
## 8. FRONT-CONTROLLER CONTRACT
## 9. FAILURE AND SHUTDOWN SEMANTICS
## 10. ACTIONABLE DIAGNOSTIC
## 11. OPERATIONAL AND DEPLOYMENT CONTRACT
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
Await architect review before Phase 3H.
```

After completion return the populated report's byte count and SHA-256 outside
the report body. Do not place self-referential identity values inside it.

---

## 15. Strict acceptance criteria

All must pass:

1. Independent directive authentication matched.
2. Every required authority was read completely.
3. Repository identity, caller records, exact counts, semantic index, lockfile,
   602-file baseline, 208-record archive, 32-record authorized-start manifest,
   and 570-record protected manifest matched.
4. Pre-change canonical quality passed.
5. Exactly five paths were created and 32 existing paths modified; no other
   path changed.
6. Package count, manifests, lockfile, dependencies, PHP constraints, Composer
   policy, and tooling remain byte-identical.
7. Strict optimized PSR-4 autoload passed.
8. Exactly two production and one test PHP files were added.
9. `CgiFrontController` has the exact final/Experimental/stateless API.
10. Its closure parameter is exact and PHP-CGI gains no direct Web Application
    dependency.
11. One entry attempt always precedes one shutdown attempt.
12. Shutdown is attempted exactly once on entry success and failure.
13. Success/success returns `void` with no extra behavior.
14. Primary-only failure rethrows the exact primary object.
15. Shutdown-only failure rethrows the exact shutdown object.
16. Simultaneous failure produces the exact composite with preserved roles,
    native previous, and accessors.
17. No retry, second response, rollback, logging, recovery, automatic handler,
    destructor, listener, or process behavior was added.
18. Diagnostic 1019 has exact ID, prose, schema, token, uniqueness, and no raw
    disclosure.
19. `CgiEntryShutdownException` has the exact status, parent, marker,
    constructor, property, accessor, diagnostic, and previous contract.
20. Existing CgiWebEntry, delivery, Web Runtime, Web Application, Kernel,
    converter, emitter, bootstrap, permission, and lower-package production
    bytes remain unchanged.
21. Focused behavioral tests cover all matrix paths, ordering, once-only
    shutdown, no second output, and sequential isolation.
22. Architecture tests enforce exact counts, statuses, dependency direction,
    source confinement, and prohibited surfaces.
23. ADR 0057 is Accepted, exact, complete, and consistent.
24. The deployment guide records the exact selected profile and ownership split
    without adding a deployable server artifact or certification claim.
25. Every authorized current document is reconciled; Phase 3H alone is next and
    the fixed A-H table is byte-identical.
26. Production trusted diagnostics remain denied; development trust remains
    two-gate, direct-loopback, non-forwarded, and fail-closed.
27. Existing request, response, emission, cleanup, and generic-failure semantics
    remain unchanged.
28. Focused syntax, PHPStan, style, package tests, architecture tests, and
    strict autoload pass.
29. Canonical quality passes completely with exact results recorded.
30. Current links, encoding, LF, final LF, whitespace, NUL, ordinary-file,
    stream, reparse, mode, nested-repository, and `git diff --check` proofs pass.
31. Normal PHP remains 128M and `PHP_INI_SCAN_DIR` remains absent.
32. Final state is exactly `30 modified / 185 deleted / 258 untracked / 473
    total / 0 staged`, 609 physical files, 536 tracked index paths, 126
    production PHP files, 140 test PHP files, and 266 style-covered PHP files.
33. Historical records, protected files, branch, refs, history, semantic index,
    Composer files, and lockfile remain exact.
34. Nothing was staged or committed.
35. Only the Phase 3G report was populated after every non-report gate passed.
36. Report structure, truthful evidence, exact next step, and external report
    identity handoff pass.

Any failure means Phase 3G is not complete. Preserve the exact reached state,
leave the report zero bytes unless all non-report gates passed, and request the
smallest authenticated correction. Do not begin Phase 3H.

---

## 16. Final instruction

Implement Phase 3G only.

Use the modern narrow lifecycle seam specified here: an explicit typed closure,
one entry attempt, one application-shutdown attempt, exact throwable identity,
and no ambient state or service location. Preserve the current acyclic package
graph and all earlier ownership boundaries.

Document the selected profile precisely without pretending documentation is a
deployed or externally accepted system. Stop rather than guessing if any
authenticated identity, count, path, contract, or required proof differs.
