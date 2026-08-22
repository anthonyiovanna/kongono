# PHASE 2D.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

Complete.

The authenticated Phase 2D.1 correction was implemented within its exact seven-file boundary. Every non-report acceptance gate passed before this caller-created report was populated. No path was staged or committed, and Phase 2E was not started.

## 2. SUMMARY

Corrected `InvalidRouteRequestException` to extend native `RuntimeException` exactly while preserving its constructor, routing marker, diagnostic behavior, two private readonly properties, previous-Throwable identity, Experimental API status, native message, and absence of custom stringification.

Completed the missing Phase 2D proof by adding exact native-parent assertions, accepted upper-bound cases, a third PSR getter-Throwable case for `UriInterface::getPath()`, reversed-order duplicate/conflict failure proof, and six representative raw-value disclosure sentinels. Routing grammar, precedence, conflict semantics, canonical order, encoded matching, diagnostics, public APIs, dependencies, package graph, documentation, unmatched `null` behavior, and prohibited Phase 2E+ scope remain unchanged.

## 3. BASELINE VERIFIED

- Branch: `main`.
- HEAD: `d7846f137a1d53f5306f48d19196b916fde44ac2`.
- HEAD tree: `bfa1908c619069b699bc9b08668db17ea2555eb3`.
- HEAD parent: `8fe793b124384744ac43887037360154c149241a`.
- Commit count: 9.
- Starting status: 18 tracked modifications, 87 untracked paths, 105 total status paths, 0 staged.
- Starting inventories: 10 package directories, 10 package manifests, 80 production PHP files, 104 test PHP files, 184 PHP CS Fixer files, and 106 unique framework diagnostic IDs.
- Local refs: exactly `refs/heads/main`; no remotes or tags; 39 observed Codex-managed exempt refs under `refs/codex/turn-diffs/**` were enumerated and left untouched.
- All tracked modes were `100644`. Required ordinary-file, reparse-point, and NTFS alternate-stream checks passed.
- The caller-created correction report was absent from HEAD, ordinary, untracked, unstaged, 0 bytes, and SHA-256 `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`.
- Pre-change `composer quality`: PASS at 382 tests / 6,689 assertions; PHPStan clean; PHP CS Fixer 0/184; platform requirements satisfied; no locked security advisories.

## 4. CALLER RECORDS

The independently supplied correction authority matched before validation or mutation:

- `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md`: 23,051 bytes; SHA-256 `1C745CDC6F4F920EE67355FD2C2525A738631A7FB8A11ABB58587AC56DD03A92`.

Immutable Phase 2D records matched exactly and remained unchanged:

- `docs/dev_plan/kongono_phase_2D_codex_directive.md`: 54,316 bytes; SHA-256 `7A0F803802704EA8E95B7DFA8E4A49809264493CC941686648621079EBEA58FF`.
- `docs/task reports/Phase_2D.md`: 26,060 bytes; SHA-256 `834582F8A0A18510F72F89F5800BA2CA9DDE460A5DAF3D780B4F81AAD0367EAD`.

All seven locked starting files matched before editing:

- `packages/routing/src/Exception/InvalidRouteRequestException.php`: 1,453 bytes; SHA-256 `5F1BB182F94D2C3F48A746365AFF742ED33EBED87B5B4D983802009838BC1EE0`.
- `packages/routing/tests/Unit/Exception/RoutingExceptionTest.php`: 4,290 bytes; SHA-256 `0D21FCE6ABE26480F3190BB56A0740CBB31EC3D59FAB4EFA3E784A18EF45152E`.
- `tests/Architecture/ApiStatusConventionTest.php`: 49,653 bytes; SHA-256 `4131383DD1BA2BA38E99A880A1A897DCCA5542D3FDD0786F32FC36791E07BD71`.
- `packages/routing/tests/Unit/Definition/RouteDefinitionTest.php`: 6,587 bytes; SHA-256 `DC23EF0FF941CE70D7FA4F197777F0D12EF3F172FDDF22B34EC9508AB21AC8EB`.
- `packages/routing/tests/Unit/Definition/RouteTableTest.php`: 4,554 bytes; SHA-256 `7309E087ED01A04BB562E534606B3641CFF1E29D09C9F066171F90BB3A667482`.
- `packages/routing/tests/Unit/Match/RouteMatchTest.php`: 2,766 bytes; SHA-256 `D67CF328E4561E4B59CB2CAB79841C4874568E69AE75299A8A4476495470B942`.
- `packages/routing/tests/Unit/Matcher/RouteMatcherTest.php`: 9,600 bytes; SHA-256 `682B09E9E701BD9DEF691186A513D4E577A9972CF20595CC5FE56C0406DA3E3D`.

## 5. FILES CREATED

None. The correction directive and zero-byte correction report were caller-created records present before execution. The executor created no production declaration, test file, fixture, documentation file, dependency, generated artifact, baseline, suppression, or compatibility shim.

## 6. FILES MODIFIED

Exactly the seven authorized existing files were modified before report population:

- `packages/routing/src/Exception/InvalidRouteRequestException.php` - replaced the obsolete native `InvalidArgumentException` import and parent with `RuntimeException` only.
- `packages/routing/tests/Unit/Exception/RoutingExceptionTest.php` - separated native-parent proof and added six route/request raw-value disclosure sentinels across every package-owned surface.
- `packages/routing/tests/Unit/Definition/RouteDefinitionTest.php` - added accepted 32-byte method, 32-segment template, and 128-byte literal-segment proof.
- `packages/routing/tests/Unit/Definition/RouteTableTest.php` - added forward/reversed duplicate-name and structural-conflict failure equivalence proof, including exact closed context shape and positions.
- `packages/routing/tests/Unit/Match/RouteMatchTest.php` - added accepted, detached, exact 1,024-byte captured-parameter proof.
- `packages/routing/tests/Unit/Matcher/RouteMatcherTest.php` - added accepted request-method/path upper bounds and exact `getPath()` Throwable identity/access-order proof.
- `tests/Architecture/ApiStatusConventionTest.php` - enforced the exact immediate native parent for each routing exception.

After every non-report gate passed, only the caller-created `docs/task reports/Phase_2D_1_correction.md` was populated with this report.

## 7. PRODUCTION CORRECTION

The production delta is exactly:

```php
use RuntimeException;

final class InvalidRouteRequestException extends RuntimeException implements ExceptionInterface
```

`InvalidRouteDefinitionException` remains an immediate child of native `InvalidArgumentException`. `InvalidRouteRequestException` is now an immediate child of native `RuntimeException`. No throw site, diagnostic, context schema, title, remediation, operation, invariant, field, API status, constructor, property, marker, method, message, previous chain, or other production behavior changed.

## 8. TEST PROOF ADDED

- Native categories are proved separately in the package test and enforced as exact immediate parents by the root architecture test.
- Route declarations accept and preserve one exactly 32-byte canonical method.
- Route declarations accept and preserve a non-root template with exactly 32 segments.
- Route declarations accept and preserve one exactly 128-byte encoded literal segment.
- Route matches accept, detach, and return exactly one 1,024-byte captured parameter value; the existing 1,025-byte rejection remains.
- Request matching accepts an exactly 32-byte method, exactly 8,192-byte encoded path, exactly 64-segment encoded path, and one exactly 1,024-byte path segment as structurally valid unmatched input.
- Existing just-over-limit method/path/segment/count rejections remain and pass.
- `getMethod()`, `getUri()`, and now `getPath()` caller-owned Throwables are each propagated unchanged by object identity with exact accessor order and no later accessor use.
- Equivalent forward/reversed two-route duplicate-name tables produce the same diagnostic ID, operation, invariant, field metadata, and `[0, 1]` positions.
- Equivalent forward/reversed shared-method structural conflicts produce the same closed failure proof and `[0, 1]` positions.
- Unique rejected route-name, route-method, route-template, fabricated-match-value, request-method, and request-path sentinels are absent from the native message, diagnostic ID/title/remediation, operation, violated invariant, and every diagnostic-context value.
- Existing grammar, precedence, conflict, canonical order, encoded matching, ordinary unmatched, detachment, state-isolation, just-over-bound, and prohibited-access tests remain passing.

## 9. ARCHITECTURAL DECISIONS

None. This was an exact corrective implementation under authenticated authority. No routing or Phase 2E decision was reopened or inferred.

## 10. DEVIATIONS FROM DIRECTIVE

None.

The managed environment initially blocked Composer network/cache access and emitted a cache warning. The exact commands were retried with approved access, as permitted, without changing policy or code. An initial focused PHPStan parallel worker terminated with Windows exit code `-1073741819`; the unchanged retry exposed eight test-only reflection-narrowing findings. Those assertions were corrected without suppression, baseline, cast, type widening, or configuration change, and every mandated command was rerun successfully.

## 11. DEPENDENCIES ADDED/REMOVED

None. No Composer manifest, lockfile, package requirement, third-party version, path-package reference, plugin authorization, platform rule, script, autoload mapping, security policy, baseline, suppression, ignore, or quality-gate exemption changed.

## 12. ENVIRONMENT

- OS: Microsoft Windows NT 10.0.26200.0.
- PowerShell: 7.6.4.
- PHP: 8.5.9 ZTS, Visual C++ 2022 x64.
- Composer: 2.10.2.
- Git: 2.45.1.windows.1.
- PHPUnit: 13.3.0.
- PHPStan: 2.2.8.
- PHP CS Fixer: 3.95.18.

## 13. COMMANDS RUN

Directive-required commands, including environmental and bounded corrective reruns, were:

```text
composer quality
php -l packages/routing/src/Exception/InvalidRouteRequestException.php
php -l packages/routing/tests/Unit/Exception/RoutingExceptionTest.php
php -l packages/routing/tests/Unit/Definition/RouteDefinitionTest.php
php -l packages/routing/tests/Unit/Definition/RouteTableTest.php
php -l packages/routing/tests/Unit/Match/RouteMatchTest.php
php -l packages/routing/tests/Unit/Matcher/RouteMatcherTest.php
php -l tests/Architecture/ApiStatusConventionTest.php
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist packages/routing/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/routing/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/routing/src packages/routing/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
git -c safe.directory='C:/Users/antho/Dev/kongono' status --porcelain=v1 --untracked-files=all
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --cached --name-only
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse HEAD
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse 'HEAD^{tree}'
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-parse 'HEAD^'
git -c safe.directory='C:/Users/antho/Dev/kongono' rev-list --count HEAD
git -c safe.directory='C:/Users/antho/Dev/kongono' for-each-ref --format='%(refname)'
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files -s
```

Read-only PowerShell and `rg` inspections also authenticated caller records, enumerated full status/refs/inventories, computed the 437-file correction protected manifest, compared all 400 pre-Phase-2D protected files individually, checked locked hashes, source/dependency/prohibited tokens, UTF-8/LF/whitespace/NUL state, NTFS streams, reparse attributes, ordinary-file properties, modes, and exact parent source declarations. `apply_patch` performed only the authorized seven-file edits and final report population.

No stage, commit, reset, restore, clean, stash, amend, merge, rebase, fetch, push, switch, branch, tag, ref, Composer update, formatter mutation, or generated-file command was run.

## 14. VALIDATION RESULTS

- Correction directive authentication: PASS at 23,051 bytes and the independently supplied SHA-256.
- Locked caller records and seven starting file identities: PASS.
- Starting Git/status/inventory/ref/mode/stream/ordinary-file baseline: PASS at 18/87/105/0 and 10/10/80/104/184.
- Pre-change `composer quality`: PASS at 382 tests / 6,689 assertions; no advisories; platform, style, and PHPStan clean.
- PHP lint: PASS for all seven authorized PHP files.
- Strict optimized PSR autoload: PASS, 3,167 classes, no PSR-4 warning.
- Focused routing PHPUnit: PASS, 58 tests / 389 assertions.
- Focused routing PHPUnit with notices displayed: PASS, 58 tests / 389 assertions; no notice, risky, skipped, incomplete, or output result.
- Root architecture PHPUnit: PASS, 6 tests / 1,448 assertions.
- Focused PHPStan: PASS, no errors.
- PHP CS Fixer dry run: PASS, 0 of 184 files fixable.
- Full configured PHPStan with 512 MiB command-line memory allowance: PASS, no errors.
- Final `composer quality`: PASS; no advisories; platform satisfied; style clean; PHPStan clean; 387 tests / 6,721 assertions.
- Direct contract/prohibited-scope audit: PASS.
- Correction protected manifest: PASS, 437 files, SHA-256 `4E59256741F9A9E9693F6ED12962B990DABC42F215FF3EE070F9BEDD05C692CE`, identical before and after authorized edits.
- Pre-Phase-2D protected snapshot: PASS, all 400 files retain exact byte counts and SHA-256 identities.
- Final non-report status: PASS at 18 modified / 87 untracked / 105 total / 0 staged.
- HEAD/tree/parent/commit count/branch/remotes/tags/non-exempt refs/index: PASS and unchanged.
- Encoding, one final LF, no trailing whitespace, no NUL, ordinary-file, mode, NTFS stream, reparse, and `git diff --check` gates: PASS.

## 15. DOCUMENTATION REVIEW

No ADR, architecture guide, engineering guide, README, security policy, doctrine, glossary, roadmap, directive, or historical report was modified. ADR 0046 and the routing model remain exact protected Phase 2D records. Current-versus-planned language, Experimental/Public/Internal statuses, Phase 2E deferral, and Phase 2H runnable milestone remain unchanged and accurate. No new documentation link or stale capability claim was introduced.

## 16. SECURITY AND FILE-SAFETY REVIEW

- All ten routing diagnostics, their schemas, fixed prose, disclosures, and closed vocabularies remain byte-for-byte unchanged.
- Raw route/request inputs remain excluded from package-owned diagnostic surfaces; the new six-sentinel proof covers route and request failure families directly.
- Captured parameters remain untrusted and no match establishes authentication or authorization.
- `RouteMatcher` retains only its route table and immutable compiled route data; no request, URI, method, path, parameter, match, principal, tenant, permission, exception, or operation state was added.
- No PSR-15, middleware, handler, bootstrap, adapter, emitter, SAPI, Kernel, Graph, DI, HTTP concrete, framework/router substrate, ambient state, or I/O capability was introduced.
- Composer dependencies, diagnostics, APIs, public shapes, API statuses, package graph, documentation, and Phase 2D historical records remain exact.
- Every file outside the seven authorized paths and final report retained its pre-change byte identity. The prior 400-file protected set also remained exact.
- Required UTF-8, LF-only, one-final-LF, trailing-whitespace, NUL, mode, stream, reparse-point, ordinary-file, and diff-whitespace checks passed.

## 17. WARNINGS / KNOWN ISSUES

Accepted existing warnings:

- Composer reports the repository's existing missing-license warning.
- Composer reports existing unbounded local `@dev` and `*` development constraints.
- Managed Git inspections can report the inaccessible global-ignore warning for `C:\Users\antho\.config\git\ignore`; exact machine-readable probes suppressed only that environmental stderr after status identities had been verified.

Environmental retries and bounded corrections:

- Initial sandboxed baseline and final `composer quality` runs could not access Packagist or the user Composer cache. Identical approved-access retries passed with zero advisories.
- The first sandboxed full-analysis run passed analysis but emitted a non-writable Composer-cache warning; the identical approved-access rerun passed cleanly.
- One focused PHPStan parallel worker terminated with Windows exit code `-1073741819`. The unchanged retry produced eight exact `method.nonObject` / `nullsafe.neverNull` test findings. Explicit `assertNotFalse()` narrowing fixed the test assertions, after which focused and full analysis passed without suppression or policy change.

Known implementation issues within the authorized Phase 2D.1 boundary: none. Kongono remains pre-alpha and intentionally has no Phase 2E+ runtime capability.

## 18. QUESTIONS / BLOCKERS

None.

## 19. GIT DIFF SUMMARY

- Production correction: one native import and immediate parent replacement in one existing routing exception.
- Test proof: six existing package test files and one existing root architecture test modified within the exact authorization boundary.
- Files created by executor: 0.
- Composer/dependency/documentation changes: 0.
- Report: caller-created zero-byte `docs/task reports/Phase_2D_1_correction.md` populated only after all non-report gates passed.
- Final status after report population is required to remain 18 modified / 87 untracked / 105 total / 0 staged.
- Git history, index, refs, branch, remotes, and tags remain unchanged; no staging or commit occurred.

## 20. ACCEPTANCE CRITERIA

1. PASS - every authority was read completely.
2. PASS - independent correction-directive bytes and SHA-256 matched.
3. PASS - the correction report began as the exact zero-byte caller placeholder.
4. PASS - the locked Phase 2D report/directive and seven starting file identities matched.
5. PASS - Git identity, status, inventories, refs, modes, streams, and ordinary-file properties matched.
6. PASS - pre-change `composer quality` passed at 382 tests / 6,689 assertions.
7. PASS - only the seven authorized files changed before report population.
8. PASS - `InvalidRouteRequestException` extends native `RuntimeException` exactly.
9. PASS - `InvalidRouteDefinitionException` continues to extend native `InvalidArgumentException` exactly.
10. PASS - package and root architecture tests enforce both exact immediate parents.
11. PASS - all accepted upper-bound proofs in Sections 6.2-6.4 pass.
12. PASS - just-over-limit rejection proofs remain.
13. PASS - all three PSR getter Throwable identity cases pass in exact access order.
14. PASS - reversed duplicate/conflict route-table failures are deterministic.
15. PASS - representative raw route/request sentinels are absent from every package-owned diagnostic surface.
16. PASS - the ten-row routing diagnostic catalog is unchanged.
17. PASS - route grammar, precedence, conflict, encoded matching, unmatched behavior, API shapes, API statuses, and package graph are unchanged.
18. PASS - focused routing tests pass with no notices, risks, skips, incompletes, or output.
19. PASS - root architecture, focused/full PHPStan, style, strict PSR autoload, advisory audit, and canonical quality pass.
20. PASS - package/source/test/style/diagnostic inventories remain 10/80/104/184/106.
21. PASS - no prohibited capability or dependency was introduced.
22. PASS - all protected and historical records remain exact.
23. PASS - `git diff --check`, encoding, file-safety, stream, mode, and applicable link-preservation checks pass.
24. PASS - Git history/index/refs remain unchanged and zero paths are staged.
25. PASS - final status is exactly 18 modified, 87 untracked, 105 total, 0 staged.
26. PASS - this correction report was populated only after criteria 1-25 passed.
27. PASS - this report has the exact required structure and states no unsupported claim.
28. PASS - the populated report's measured byte count and SHA-256 are returned outside this report body.

## 21. NEXT STEP

Await architect review before Phase 2E.
