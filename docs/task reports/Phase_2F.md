# PHASE 2F TASK COMPLETION REPORT

## 1. STATUS

Complete.

Phase 2F was implemented within the exact authorized boundary. All 38 strict
acceptance criteria passed. No path was staged or committed.

## 2. SUMMARY

Implemented `kongono/web-application`, the explicit transport-free PSR-15
application composition boundary for Kongono. The package validates Routing and
HTTP Server composition before booting Kernel, opens and closes one exact Kernel
operation scope around each accepted in-memory application call, preserves
primary and cleanup failure identities, and retires fail-closed after uncertain
lifecycle cleanup.

Added ADR 0048 and the Web Application architecture model. Reconciled only the
authorized current-state documentation and tooling surfaces. Phase 2G remains
next, and Phase 2H remains the first runnable black-box milestone.

No outer adapter, request conversion, response emission, generic production
failure response, trusted diagnostic delivery, runnable fixture, DI behavior,
Application Graph behavior, scoped service, or transport cleanup was added.

## 3. BASELINE VERIFIED

The independently supplied directive identity was authenticated before quality
checks or mutation:

- path: `docs/dev_plan/kongono_phase_2F_codex_directive.md`
- bytes: `53418`
- SHA-256: `5D25D4FA8A34F410548836B673AD3F77845F509DD45DB0039C1858572DF58EB4`

The caller-managed report began as an ordinary, untracked, unstaged, zero-byte
file absent from `HEAD`, with SHA-256
`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`.
It remained zero bytes until every permitted non-report gate passed.

Exact starting state:

- branch: `main`
- `HEAD`: `d7846f137a1d53f5306f48d19196b916fde44ac2`
- `HEAD` tree: `bfa1908c619069b699bc9b08668db17ea2555eb3`
- `HEAD` parent: `8fe793b124384744ac43887037360154c149241a`
- commit count: `9`
- tracked files: `358`
- tracked modes other than `100644`: `0`
- tracked modifications: `18`
- untracked paths: `109`
- total status paths: `127`
- staged paths: `0`
- Git-visible paths: `467`
- package directories/manifests: `11/11`
- production/test PHP files: `86/110`
- PHP files covered by PHP CS Fixer: `196`
- Composer locked production/development packages: `15/66`
- local branches/remotes/tags: `1/0/0`

The pre-change index was `44541` bytes with SHA-256
`768B60DC6F0E2C2A9C4040C2A48EB78035A37713AF482649B16C72592BD0AC2B`.
The 467-path path/byte/SHA-256 snapshot, modes, ordinary-file state, streams,
reparse state, encoding properties, and local Markdown links passed before
mutation.

Pre-change `composer quality` passed on the unchanged tree after one approved
network/cache retry: zero advisories, platform requirements passed, PHP CS Fixer
reported 0 of 196 files fixable, PHPStan reported zero errors, and PHPUnit
reported 411 tests with 7298 assertions.

## 4. CALLER RECORDS

The immediate immutable caller records remained exact:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2E_codex_directive.md` | 41768 | `53CD9AA94E6F5A2518E94C4FB1FAA9E3461C5E87A13C02E923BF4F0F38CF270A` |
| `docs/task reports/Phase_2E.md` | 25112 | `44FB493A64FD50C4707E8ABDBBD1C75E923F038C6C48070F56B4F63F756260B5` |
| `docs/dev_plan/kongono_phase_2E_1_codex_directive_correction.md` | 19554 | `C659F596208376B82BE5C0C280950CC6DAB8EE2AD9AC3094A1EE371D5387B928` |
| `docs/task reports/Phase_2E_1_correction.md` | 18561 | `0AE1128FB3735CF298C856C07D8FD1503129B9BD0815EB486A044148558B0C58` |

All 159 directive/report records present before report population, including the
authenticated Phase 2F directive and all earlier historical records, matched
their pre-change snapshot blobs exactly. Historical line endings and final bytes
were not normalized.

## 5. FILES CREATED

Exactly 13 paths were created:

1. `packages/web-application/composer.json`
2. `packages/web-application/README.md`
3. `packages/web-application/src/Diagnostic/WebApplicationDiagnostic.php`
4. `packages/web-application/src/Exception/ExceptionInterface.php`
5. `packages/web-application/src/Exception/WebApplicationException.php`
6. `packages/web-application/src/Exception/OperationCleanupException.php`
7. `packages/web-application/src/Exception/RetiredWebApplicationException.php`
8. `packages/web-application/src/WebApplication.php`
9. `packages/web-application/tests/Unit/Diagnostic/WebApplicationDiagnosticTest.php`
10. `packages/web-application/tests/Unit/Exception/WebApplicationExceptionTest.php`
11. `packages/web-application/tests/Unit/WebApplicationTest.php`
12. `docs/adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md`
13. `docs/architecture/web-application-model.md`

## 6. FILES MODIFIED

Exactly 27 existing paths were modified:

1. `.php-cs-fixer.dist.php` — added the package source/test paths to style coverage.
2. `ARCHITECTURE.md` — reconciled the package graph and implemented milestone state.
3. `README.md` — reconciled the implemented Web Application boundary and next phase.
4. `SECURITY.md` — documented the retirement and disclosure boundary.
5. `composer.json` — registered the exact local package dependency and repository.
6. `composer.lock` — Composer-generated local package entry and content hash.
7. `docs/adr/README.md` — indexed accepted ADR 0048.
8. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md` — reconciled milestone ownership.
9. `docs/adr/0044-http-message-and-interoperability-architecture.md` — reconciled composition ownership.
10. `docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md` — reconciled application orchestration.
11. `docs/architecture/http-message-model.md` — clarified the unchanged lower boundary.
12. `docs/architecture/http-server-model.md` — clarified terminal-handler composition.
13. `docs/architecture/microkernel.md` — documented application-owned scope orchestration.
14. `docs/architecture/overview.md` — added the implemented package and phase state.
15. `docs/architecture/phase-2-roadmap.md` — marked Phase 2F implemented and Phase 2G next.
16. `docs/architecture/routing-model.md` — clarified validation before boot.
17. `docs/architecture/runtime-model.md` — documented persistent-runtime retirement behavior.
18. `docs/doctrine.md` — reconciled exact scope ownership and open cleanup work.
19. `docs/engineering/api-status.md` — registered the six exact declaration statuses.
20. `docs/engineering/error-handling.md` — documented simultaneous failure preservation.
21. `docs/engineering/package-layout.md` — added the twelfth package layout.
22. `docs/glossary.md` — added the Web Application and retirement terms.
23. `packages/http-server/README.md` — documented the unchanged lower-layer role.
24. `packages/kernel/README.md` — documented application-owned operation closure.
25. `phpstan.neon.dist` — added the package source/test analysis paths.
26. `phpunit.xml.dist` — added the package test suite.
27. `tests/Architecture/ApiStatusConventionTest.php` — added exact package, API, diagnostic, exception, and tooling assertions.

After all non-report gates passed, only the existing caller-managed
`docs/task reports/Phase_2F.md` placeholder was populated.

## 7. PACKAGE BOUNDARY

The sole new package is `kongono/web-application`, type `library`, with production
namespace `Kongono\Web\Application\` mapped to `src/` and test namespace
`Kongono\Web\Application\Tests\` mapped to `tests/`.

Exact production requirements:

- `php: ^8.5`
- `kongono/api-metadata: *`
- `kongono/diagnostics: *`
- `kongono/http-server: *`
- `kongono/kernel: *`
- `kongono/routing: *`
- `psr/http-message: ^2.0`
- `psr/http-server-handler: ^1.0`
- `psr/http-server-middleware: ^1.0`

No dependency was added on DI, Application Graph, Activation, Diagnostic
Presentation, Handler, a concrete HTTP Message implementation, or an adapter.
The resulting 12-package dependency graph remains acyclic.

## 8. PRODUCTION IMPLEMENTATION

Exactly six production declarations were added:

| Declaration | Shape | API status |
|---|---|---|
| `WebApplicationDiagnostic` | string-backed enum | Internal |
| `ExceptionInterface` | exception marker extending `Throwable` | Public |
| `WebApplicationException` | abstract `RuntimeException` base | Internal |
| `OperationCleanupException` | final simultaneous-failure exception | Experimental |
| `RetiredWebApplicationException` | final retired-state exception | Experimental |
| `WebApplication` | final PSR-15 request handler | Experimental |

The diagnostic catalog contains exactly:

- `KGN-WEB-APPLICATION-1001` for application retirement;
- `KGN-WEB-APPLICATION-1002` for simultaneous operation and cleanup failure.

Both diagnostics have fixed safe prose and empty `DiagnosticContext` schemas.
No raw failure, request, response, or scope data is exposed through package
diagnostic surfaces.

`WebApplicationException` stores only its exact diagnostic and empty diagnostic
context, uses the diagnostic title as its message, code `0`, and preserves the
exact optional previous throwable. Its four diagnostic accessors are final.

`OperationCleanupException` stores the exact primary and cleanup throwables,
returns them through typed accessors, and uses the primary throwable as the
native previous exception. `RetiredWebApplicationException` has no previous
exception and stores no request or failure data.

`WebApplication` is final, non-readonly, implements only
`Psr\Http\Server\RequestHandlerInterface`, has readonly Kernel and pipeline
properties plus one mutable boolean retirement flag, and has a private
constructor and private clone method. Its only public methods are `boot`,
`handle`, `shutdown`, and `isRetired`. It has no destructor.

## 9. BOOT AND COMPOSITION BEHAVIOR

`WebApplication::boot` performs this exact sequence:

1. validate the supplied terminal handler as `RoutingRequestHandler`;
2. construct and thereby validate the `MiddlewarePipeline`;
3. invoke Kernel boot exactly once;
4. return the composed application.

Invalid Routing or HTTP Server composition fails before Kernel boot. Valid boot
does not begin an operation scope, invoke middleware, invoke a route handler, or
perform application work.

The existing Kernel, Routing, HTTP Server, HTTP Message, Diagnostics, DI,
Application Graph, Activation, Presentation, and Handler production APIs and
behavior remain unchanged.

## 10. OPERATION OWNERSHIP AND CLEANUP

Each non-retired `handle` call:

1. asks Kernel to begin one operation scope;
2. sends the exact request through the retained middleware pipeline;
3. ends that exact scope once;
4. returns the exact response or applies the failure matrix below.

The scope end operation is called exactly once and is never retried. Phase 2F
does not depend on a destructor, process teardown, ambient current scope,
service location, resolver traversal, or static/global state. Sequential and
reentrant probes passed with exact request/scope isolation and no retained
operation-specific data.

Only Kernel operation-scope cleanup is owned here. Body streams, uploaded
resources, middleware-owned resources, handler-owned resources, adapter and
transport cleanup, cancellation, incident recording, logging, telemetry, and
broader resource cleanup remain open.

## 11. FAILURE AND RETIREMENT BEHAVIOR

Exact handle failure matrix:

| Work result | Scope end result | Observable result | Retired |
|---|---|---|---|
| response | success | exact response | no |
| primary `Throwable` | success | exact primary `Throwable` | no |
| response | cleanup `Throwable` | exact cleanup `Throwable` | yes |
| primary `Throwable` | cleanup `Throwable` | `OperationCleanupException` with both identities | yes |

For simultaneous failure, the composite preserves the exact primary and cleanup
objects and the primary is the exact native previous exception.

A known `LifecycleException` from begin or shutdown is propagated without
retiring because it is a deterministic lifecycle rejection. An unknown begin or
shutdown throwable retires the application before propagation. Once retired,
both handling and shutdown fail with `RetiredWebApplicationException` before
accessing Kernel or pipeline dependencies.

Shutdown is explicit. Successful shutdown delegates exactly once to Kernel;
there is no automatic shutdown, recovery, process termination, response mapping,
logging, incident recording, or cleanup retry.

## 12. TEST COVERAGE

Added three focused test files covering:

- exact diagnostic IDs, fixed prose, empty contexts, and API status;
- marker distinctness, base exception construction/accessors, native previous
  identity, and both concrete exception shapes;
- boot validation order and no work during boot;
- successful handling and every failure-matrix row;
- exact begin/pipeline/end order and exact-once cleanup;
- response, request, scope, primary, cleanup, and previous identities;
- known and unknown begin/shutdown failure semantics;
- retirement before dependency access;
- sequential and reentrant isolation;
- explicit shutdown and absence of a destructor.

Focused Web Application PHPUnit passed: 26 tests, 192 assertions, with no
notices, risky, skipped, or incomplete tests. The architecture test passed:
6 tests, 2160 assertions. The canonical final suite passed: 437 tests, 7853
assertions, with no notices, risky, skipped, incomplete, or unexpected output.

## 13. ARCHITECTURAL DECISIONS

No new architectural decisions were made by the executor.

ADR 0048 records the directive-authorized amendment: the Phase 2F application
boundary owns exact Kernel operation-scope opening/closure around one in-memory
application invocation, while Phase 2G owns the outer adapter and transport
responsibilities. ADR 0048 is Accepted. Its rejected alternatives include
placing web behavior in Kernel, third-party framework/dispatcher foundations,
ambient discovery or service location, boot before validation, teardown-based
cleanup, ambient scopes, failure replacement, retrying cleanup, continuing after
uncertain cleanup, raw diagnostic disclosure, premature adapter/presentation
selection, and broader scoped-resource behavior.

## 14. DEVIATIONS FROM DIRECTIVE

None.

## 15. DEPENDENCIES ADDED/REMOVED

Added one local root development dependency: `kongono/web-application: *`.

Composer generated one production lock entry for `kongono/web-application` at
`dev-main`, dist reference
`a6038259a925c49268ead9e161a3572f3d8c7b34`, plus the corresponding stability
flag and root content-hash update. Locked totals changed from 15/66 to 16/66
production/development packages.

No third-party dependency was added, updated, or removed. All 81 pre-existing
production/development lock entries remained structurally exact; the sole added
entry is `kongono/web-application`.

## 16. ENVIRONMENT

- OS/shell: Windows, PowerShell
- PHP: `8.5.9`
- Composer: `2.10.2` (`2026-07-01 11:24:45`)
- Git: `2.45.1.windows.1`
- PHPStan memory limit used for focused and full analysis: `512M`

## 17. COMMANDS RUN

Authentication, baseline, preservation, and direct-probe commands included
`Get-Item`, `Get-FileHash -Algorithm SHA256`, `Get-Content`, `git status
--porcelain=v1 -uall`, `git ls-files`, `git ls-tree`, `git hash-object`, `git
for-each-ref`, `git rev-parse`, `git rev-list --count`, `rg`, strict .NET UTF-8
decoding, NTFS stream/reparse inspection, local Markdown-link resolution, and
`git diff --check`.

The required quality/dependency commands were run in this sequence:

```text
composer quality
composer update kongono/web-application --with-dependencies --no-interaction
php -l packages/web-application/src/Diagnostic/WebApplicationDiagnostic.php
php -l packages/web-application/src/Exception/ExceptionInterface.php
php -l packages/web-application/src/Exception/WebApplicationException.php
php -l packages/web-application/src/Exception/OperationCleanupException.php
php -l packages/web-application/src/Exception/RetiredWebApplicationException.php
php -l packages/web-application/src/WebApplication.php
php -l packages/web-application/tests/Unit/Diagnostic/WebApplicationDiagnosticTest.php
php -l packages/web-application/tests/Unit/Exception/WebApplicationExceptionTest.php
php -l packages/web-application/tests/Unit/WebApplicationTest.php
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/web-application/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --memory-limit=512M packages/web-application/src packages/web-application/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
git diff --check
```

The unchanged pre-change `composer quality`, exact Composer update, and unchanged
final `composer quality` were each retried with approved network/cache access
after their first sandboxed attempt could not access Packagist/cache. No command
was weakened, no audit was skipped, and the lock policy remained active.

During focused validation, initial test assertions exposed test-only expectation
errors, focused PHPStan exposed test-only typing errors, and PHP CS Fixer exposed
one test-only anonymous-class spacing issue. Only the three authorized new Web
Application test files were corrected. No production behavior, existing package,
suppression, baseline, or policy was changed to clear those findings. All focused
commands and the full suite then passed.

## 18. VALIDATION RESULTS

- directive identity: PASS — exact independently supplied bytes and SHA-256.
- pre-change canonical quality: PASS — 411 tests, 7298 assertions.
- PHP syntax: PASS — all nine new source/test files.
- optimized strict PSR-4 autoload: PASS — 3190 classes, no warning.
- focused Web Application PHPUnit: PASS — 26 tests, 192 assertions.
- architecture PHPUnit: PASS — 6 tests, 2160 assertions.
- focused PHPStan: PASS — zero errors.
- PHP CS Fixer: PASS — 0 of 205 files fixable.
- full PHPStan: PASS — zero errors.
- final `composer quality`: PASS — zero advisories, platform requirements pass,
  style clean, PHPStan clean, 437 tests, 7853 assertions.
- package/manifests/production PHP/test PHP/style PHP: PASS —
  `12/12/92/113/205`.
- diagnostics: PASS — 111 unique IDs with exact split: Kernel 6, Graph 11,
  DI 25, Presentation 15, Diagnostics 11, HTTP 28, HTTP Server 3, Routing 10,
  Web Application 2.
- lock totals: PASS — 16 production and 66 development packages.
- lock preservation: PASS — 81 existing entries exact, one authorized local
  entry added.
- Markdown links: PASS — 520 local link targets checked, zero broken.
- changed-path text/file safety: PASS — strict UTF-8, LF-only, one final LF, no
  trailing whitespace, no NUL, ordinary files, no reparse points or alternate
  streams, and authorized modes.
- protected snapshot: PASS — 13 exact creations, 27 exact modifications, zero
  deletions, zero unauthorized differences.
- `git diff --check`: PASS.

Accepted environmental warnings were limited to Composer's existing missing
license metadata and authorized local unbounded `dev-main` package constraints.
Repeated Git reads also reported that the sandbox could not read the user-level
`C:\Users\antho\.config\git\ignore`; repository status was still deterministically
enumerated with the repository's own exclusions and full `-uall` expansion.

## 19. DOCUMENTATION REVIEW

PASS.

ADR 0048, the Web Application model, package README, and all authorized
current-state documents distinguish implemented behavior from plans. They state
that Phase 2F and ADR 0048 are implemented, Phase 2G is next, Phase 2H is the
first runnable black-box milestone, and exactly 12 packages exist. They preserve
Kernel and HTTP Server as unchanged lower layers, identify Web Application as
the explicit composition and application-call scope owner, and leave broader
resource/transport cleanup open.

Current documentation does not claim a runnable application, adapter, inbound
conversion, evidence evaluator, emitter, generic production failure response,
trusted diagnostic delivery, DI/Graph integration, concrete HTTP Message
dependency, or completed broader cleanup. Historical records were preserved.

## 20. SECURITY AND FILE-SAFETY REVIEW

PASS.

The package is transport-free and performs no filesystem, network, process,
environment, clock, random-source, superglobal, global/static mutable-state,
service-location, resolver, Graph, concrete HTTP Message, diagnostic
presentation, or emission operation. It retains no request-specific, scope,
response, or failure data after a call. Retirement refuses new work before
dependency access and exposes only fixed package diagnostics with empty context.

All 480 Git-visible paths were ordinary files with authorized modes, no reparse
points, no non-default NTFS streams, and no NUL bytes. The 40 changed/created
non-report paths passed strict UTF-8, LF-only, exactly one final LF, and no
trailing-whitespace checks. Accepted historical byte peculiarities remained
unchanged exactly rather than being normalized.

## 21. WARNINGS / KNOWN ISSUES

- Phase 2F intentionally provides no outer adapter, I/O, request conversion,
  response emission, generic production failure response, trusted diagnostic
  delivery, or runnable fixture.
- Broader service/resource/transport cleanup, cancellation, logging, telemetry,
  and incident recording remain open and unselected.
- The Composer audit/update/final audit required approved network/cache retries
  after sandboxed Packagist/cache access failures; the identical commands passed.
- Existing missing-license metadata and local unbounded development constraints
  remain the accepted Composer warning categories.
- The user-level Git ignore file was unavailable to sandboxed Git reads; full
  repository status counts and protected-path comparison still matched exactly.

## 22. QUESTIONS / BLOCKERS

None.

## 23. GIT DIFF SUMMARY

Relative to the authenticated 467-path pre-change snapshot:

- created: exactly 13 authorized implementation/documentation paths;
- modified: exactly 27 authorized existing paths;
- deleted: 0;
- unauthorized path differences: 0.

Final repository state before and after this report population:

- tracked modifications: `20`
- untracked paths: `122`
- total status paths: `142`
- staged paths: `0`
- Git-visible paths: `480`
- branch: `main`
- local branches/remotes/tags: `1/0/0`
- `HEAD`, tree, parent, and commit count: unchanged
- index: unchanged at `44541` bytes and SHA-256
  `768B60DC6F0E2C2A9C4040C2A48EB78035A37713AF482649B16C72592BD0AC2B`
- refs: unchanged apart from enumerated, exempt Codex-managed environmental refs;
  no executor-created ref
- staging/commit: none

## 24. ACCEPTANCE CRITERIA

1. PASS — every required authority was read completely.
2. PASS — directive bytes and SHA-256 matched independent caller values.
3. PASS — report began as the exact zero-byte caller placeholder.
4. PASS — immediate and historical caller records remained exact.
5. PASS — Git identity, status, inventory, modes, refs, streams, file safety,
   and protected snapshot matched.
6. PASS — pre-change quality passed at 411 tests / 7298 assertions.
7. PASS — exactly 13 authorized paths created and 27 existing paths modified.
8. PASS — the exact sole new `kongono/web-application` package was added.
9. PASS — exactly six production declarations have exact API statuses.
10. PASS — two exact diagnostics with empty schemas are implemented.
11. PASS — marker and concrete failure contracts are exact.
12. PASS — simultaneous failure preserves both failures and native previous.
13. PASS — diagnostic surfaces contain no raw failure/request data.
14. PASS — `WebApplication` has the exact class and public-method shape.
15. PASS — Routing/HTTP Server inputs validate before Kernel boot.
16. PASS — valid boot calls Kernel boot once and performs no application work.
17. PASS — every accepted handle call begins one scope and ends it once.
18. PASS — success returns the exact response.
19. PASS — primary failure with successful cleanup propagates unchanged.
20. PASS — cleanup-only failure propagates unchanged and retires.
21. PASS — simultaneous failure throws the exact composite and retires.
22. PASS — known/unknown begin and shutdown lifecycle rules are exact.
23. PASS — retired handling/shutdown refuses before dependency access.
24. PASS — sequential and reentrant operations have no ambient/retained data.
25. PASS — shutdown is explicit; no destructor or cleanup retry exists.
26. PASS — no adapter, I/O, failure mapping, trusted presentation, DI, Graph,
   scoped service, configuration, or prohibited capability exists.
27. PASS — ADR 0048 is Accepted with exact decision and alternatives.
28. PASS — model and authorized current-state documentation are reconciled.
29. PASS — Composer added only the local package and preserved existing entries.
30. PASS — all strict autoload, test, analysis, style, audit, platform, and
   canonical quality gates passed.
31. PASS — inventories are exactly `12/12/92/113/205`.
32. PASS — diagnostics total 111 and lock totals are `16/66`.
33. PASS — links, encoding, modes, streams, reparse, NUL, whitespace, and diff
   checks passed.
34. PASS — Git history/index/refs remained unchanged; zero paths staged.
35. PASS — final status is exactly 20 modified, 122 untracked, 142 total,
   0 staged.
36. PASS — report was populated only after criteria 1-35 passed.
37. PASS — report has the exact required structure and supported claims.
38. PASS — populated report byte count and SHA-256 are returned outside it.

## 25. NEXT STEP

Await architect review before Phase 2G.
