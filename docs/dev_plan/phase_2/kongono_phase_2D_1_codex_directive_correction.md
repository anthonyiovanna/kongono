# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2D.1

## Native Routing Request Exception and Required Proof Completion

**Directive type:** narrow corrective implementation and verification
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-16
**Authority:** architect-issued correction to the authenticated Phase 2D directive
**Executor role:** executor, not architect

---

## 1. Mission

Correct the accepted Phase 2D worktree without widening its architecture.

The independent review found one production contract defect and several missing
mandatory proofs:

1. `InvalidRouteRequestException` extends native `InvalidArgumentException`,
   while Phase 2D Section 9.1 requires native `RuntimeException` exactly.
2. Package and root architecture tests do not enforce the two distinct native
   exception parents.
3. Several accepted upper bounds required by Phase 2D Sections 12.2, 12.4, and
   12.5 are not exercised.
4. `UriInterface::getPath()` Throwable identity propagation is not exercised.
5. Route-table duplicate/conflict failure behavior is not proved under
   equivalent reversed declaration order.
6. The raw-value disclosure test exercises only one rejected route name even
   though the completion report claims broader route and request coverage.

Make only the exact correction and proof additions authorized below. Preserve
all Phase 2D routing semantics, API statuses, diagnostics, package boundaries,
documentation, dependency state, and prohibited scope.

This correction does not reopen the route grammar, matching algorithm,
specificity, conflict definition, diagnostic catalog, public API, package
graph, or Phase 2E decisions.

---

## 2. Governing authority and caller authentication

### 2.1 Read completely before mutation

Read these files completely, in this order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `SECURITY.md`
4. `docs/doctrine.md`
5. `docs/documentation-standard.md`
6. `docs/adr/0001-kongono-identity.md`
7. `docs/adr/0013-documentation-doctrine.md`
8. `docs/adr/0014-php-engineering-toolchain.md`
9. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
10. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
11. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
12. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`
13. `docs/adr/0044-http-message-and-interoperability-architecture.md`
14. `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
15. `docs/adr/0046-deterministic-route-declaration-and-matching.md`
16. `docs/architecture/routing-model.md`
17. `docs/engineering/error-handling.md`
18. `docs/engineering/api-status.md`
19. `docs/dev_plan/kongono_phase_2D_codex_directive.md`
20. `docs/task reports/Phase_2D.md`
21. `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md`

The filenames above are exact current repository paths. Do not substitute an
ADR merely because it has the same number as a differently titled path.

### 2.2 Independent correction-directive identity

Before validation or mutation, compare this correction directive with the
architect-supplied byte count and SHA-256 delivered outside this file.

The local file is not self-authenticating. Do not calculate its identity and
adopt that value as authority. If either independently supplied value is absent
or differs, stop and report the observed values without changing anything.

### 2.3 Caller-managed report records

Before execution, the caller must provide:

```text
docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md
docs/task reports/Phase_2D_1_correction.md
```

The correction report must begin as an ordinary, untracked, unstaged, zero-byte
file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create, truncate, replace, stage, commit, or populate either caller
record except that the zero-byte correction report may be populated only after
every non-report acceptance gate passes.

The populated Phase 2D report is immutable historical evidence:

```text
path: docs/task reports/Phase_2D.md
bytes: 26060
SHA-256: 834582F8A0A18510F72F89F5800BA2CA9DDE460A5DAF3D780B4F81AAD0367EAD
```

The Phase 2D directive is also immutable:

```text
path: docs/dev_plan/kongono_phase_2D_codex_directive.md
bytes: 54316
SHA-256: 7A0F803802704EA8E95B7DFA8E4A49809264493CC941686648621079EBEA58FF
```

---

## 3. Locked starting state

After the caller copies this directive and creates the zero-byte report, verify
the following before running broad quality checks:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
commit count: 9
tracked modifications: 18
untracked paths: 87
total status paths: 105
staged paths: 0
package directories: 10
package manifests: 10
production PHP files: 80
test PHP files: 104
PHP files covered by PHP CS Fixer: 184
```

Count untracked paths with full expansion, equivalent to
`git status --porcelain=v1 -uall`; do not rely on collapsed directory output.

The repository must have exactly one local branch, no remotes, no tags, and no
unexpected refs. Codex-managed `refs/codex/turn-diffs/**` are environmental and
exempt from that ref invariant; enumerate them separately and do not alter them.

The following accepted starting files must match exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/routing/src/Exception/InvalidRouteRequestException.php` | 1453 | `5F1BB182F94D2C3F48A746365AFF742ED33EBED87B5B4D983802009838BC1EE0` |
| `packages/routing/tests/Unit/Exception/RoutingExceptionTest.php` | 4290 | `0D21FCE6ABE26480F3190BB56A0740CBB31EC3D59FAB4EFA3E784A18EF45152E` |
| `tests/Architecture/ApiStatusConventionTest.php` | 49653 | `4131383DD1BA2BA38E99A880A1A897DCCA5542D3FDD0786F32FC36791E07BD71` |
| `packages/routing/tests/Unit/Definition/RouteDefinitionTest.php` | 6587 | `DC23EF0FF941CE70D7FA4F197777F0D12EF3F172FDDF22B34EC9508AB21AC8EB` |
| `packages/routing/tests/Unit/Definition/RouteTableTest.php` | 4554 | `7309E087ED01A04BB562E534606B3641CFF1E29D09C9F066171F90BB3A667482` |
| `packages/routing/tests/Unit/Match/RouteMatchTest.php` | 2766 | `D67CF328E4561E4B59CB2CAB79841C4874568E69AE75299A8A4476495470B942` |
| `packages/routing/tests/Unit/Matcher/RouteMatcherTest.php` | 9600 | `682B09E9E701BD9DEF691186A513D4E577A9972CF20595CC5FE56C0406DA3E3D` |

If an identity, inventory, mode, ordinary-file property, stream state, or Git
state differs, stop. Do not repair, restore, clean, normalize, stage, or infer a
new baseline.

---

## 4. Exact authorized file boundary

Modify only these seven existing files:

```text
packages/routing/src/Exception/InvalidRouteRequestException.php
packages/routing/tests/Unit/Exception/RoutingExceptionTest.php
packages/routing/tests/Unit/Definition/RouteDefinitionTest.php
packages/routing/tests/Unit/Definition/RouteTableTest.php
packages/routing/tests/Unit/Match/RouteMatchTest.php
packages/routing/tests/Unit/Matcher/RouteMatcherTest.php
tests/Architecture/ApiStatusConventionTest.php
```

After all non-report gates pass, populate only:

```text
docs/task reports/Phase_2D_1_correction.md
```

No other file may change. In particular, do not modify:

- any Composer manifest, `composer.lock`, autoload mapping, or tooling config;
- ADR 0046, the routing model, or any other documentation;
- any other production or test file;
- the Phase 2D directive or Phase 2D completion report;
- Git index, history, refs, branches, remotes, or tags.

Create no production declaration, fixture, test file, compatibility shim,
baseline, suppression, dependency, package, or generated artifact.

---

## 5. Exact production correction

In
`packages/routing/src/Exception/InvalidRouteRequestException.php`, replace the
native parent category only:

```php
use RuntimeException;

final class InvalidRouteRequestException extends RuntimeException implements ExceptionInterface
```

Remove the obsolete `InvalidArgumentException` import. Preserve all other
bytes and behavior in that class except formatting mechanically required by the
import replacement.

The resulting native categories are exact:

| Routing failure | Required native parent |
|---|---|
| Invalid route declaration/table/fabricated match | `InvalidArgumentException` |
| Invalid request method/path structure | `RuntimeException` |

`InvalidRouteRequestException` remains final and Experimental, implements only
the routing package marker in addition to native inheritance, has the same exact
constructor and two private readonly properties, preserves previous Throwable
identity, uses the diagnostic title as its message, and has no custom
`__toString()`.

Do not modify any throw site or diagnostic. The existing request-validation
throw sites must naturally construct the corrected native category.

---

## 6. Required focused proof additions

Keep tests deterministic, inspectable, and based on public behavior except for
the existing root architecture reflection contract. Do not weaken assertions,
add suppressions, or change production limits to fit tests.

### 6.1 Exact native exception parents

Update `RoutingExceptionTest.php` so it no longer treats both exceptions as
`InvalidArgumentException`.

Prove separately:

- `InvalidRouteDefinitionException` is an `InvalidArgumentException`;
- `InvalidRouteRequestException` is a `RuntimeException`;
- both implement the routing `ExceptionInterface`;
- the existing diagnostic, message, code, context, previous-chain, finality,
  property, constructor, API-status, and no-custom-stringification proofs still
  pass.

Update `ApiStatusConventionTest.php` to assert the exact immediate native parent
class for each routing exception. A shared loop that checks only finality,
marker, fields, and constructor is insufficient.

### 6.2 Accepted route-definition upper bounds

Update `RouteDefinitionTest.php` to prove these valid values are accepted and
preserved exactly:

- one canonical method whose byte length is exactly 32;
- a non-root template containing exactly 32 segments; and
- a literal template segment whose encoded byte length is exactly 128.

The existing accepted 128-byte name, 16-entry method list, 2048-byte template,
64-byte parameter name, root, pchar, percent-triplet, and complex-template
proofs must remain.

Do not combine limits in a way that makes one selected upper bound invalid
because another aggregate bound is exceeded. The 32-segment and 128-byte
segment cases may use separate route definitions.

### 6.3 Accepted route-match upper bound

Update `RouteMatchTest.php` to prove a captured parameter value whose encoded
byte length is exactly 1024 is accepted, detached, and returned exactly.

Retain rejection of a 1025-byte value and all existing key-set, string,
nonempty, percent, byte, and slash proofs.

### 6.4 Accepted request upper bounds

Update `RouteMatcherTest.php` to prove these request inputs are structurally
valid and do not raise `InvalidRouteRequestException`:

- a canonical request method whose byte length is exactly 32;
- an encoded request path whose total byte length is exactly 8192;
- an encoded request path containing exactly 64 segments; and
- an encoded request path containing one segment whose byte length is exactly
  1024.

An ordinary unmatched result of `null` is sufficient when the input is valid.
Use independent cases where needed so aggregate path limits remain satisfied.
Retain the existing just-over-limit and grammar failure proofs.

### 6.5 PSR getter Throwable propagation

Extend the existing getter-order/identity test with a third independent case:

1. `getMethod()` returns a valid method;
2. `getUri()` returns the expected `UriInterface` object;
3. `getPath()` throws a caller-owned Throwable;
4. `RouteMatcher::match()` propagates that exact same object by identity;
5. no later request or URI accessor is consulted.

Do not catch, wrap, map, or translate the Throwable in production.

### 6.6 Input-order-independent route-table failures

Update `RouteTableTest.php` to build equivalent two-route invalid tables in
forward and reversed declaration order for both:

- duplicate case-sensitive route names; and
- shared-method structural route-pattern conflicts.

For each order, prove the same exact diagnostic ID and the same closed context
shape. With two entries, the earlier/later input positions must remain `[0, 1]`
in either construction. Do not require raw route identity, name, template, or
parameter spelling in diagnostic output.

Retain the existing multi-entry position tests, disjoint method/literal cases,
multi-method conflict behavior, canonical result order, and boundaries.

### 6.7 Representative raw-value disclosure proof

Strengthen `RoutingExceptionTest.php` with actual unique sentinel values that
exercise package-owned failures for at least:

- rejected route name;
- rejected route method;
- rejected route template or template segment;
- rejected fabricated match parameter value;
- rejected request method; and
- rejected request path.

For each caught routing exception, prove that the corresponding raw sentinel is
absent from all package-owned surfaces:

```text
native message
diagnostic ID
diagnostic title
diagnostic remediation
diagnostic operation
diagnostic violated invariant
every diagnostic-context field value
```

Do not inspect native trace arguments or native exception stringification; Phase
2D expressly excludes those environment-owned surfaces. Do not change the
diagnostic catalog or context schema.

---

## 7. Prohibited scope

Do not implement or alter:

- middleware, PSR-15, handler invocation, dispatch, terminal mapping, or error
  middleware;
- bootstrap, composition root, DI/container bindings, Application Graph nodes,
  Kernel lifecycle integration, or current-route storage;
- SAPI/server adapters, request conversion, proxy trust, response emission,
  transport I/O, cleanup, cancellation, or runtime retirement;
- authentication, authorization, sessions, CSRF, cookies, controllers,
  frontend, templates, telemetry, ORM, agents, extensions, or CLI;
- route grammar, match precedence, conflict semantics, canonical order,
  percent behavior, bounds, public method shapes, API status, diagnostic IDs,
  diagnostic text, disclosure classifications, or unmatched `null` behavior;
- Composer requirements, package names, namespaces, PHP version policy,
  quality scripts, suppressions, baselines, or third-party dependencies.

No cleanup of adjacent work is authorized. Stop rather than guess.

---

## 8. Required validation sequence

### 8.1 Before editing

After authentication and baseline proof, run:

```powershell
composer quality
```

The accepted pre-correction baseline is expected to pass despite encoding the
wrong parent assertion:

```text
PHP CS Fixer: 0 of 184 files fixable
PHPStan: zero errors
PHPUnit: 382 tests, 6689 assertions
security advisories: zero
```

If Composer advisory access alone is blocked by the managed environment, retry
the identical command with approved network/cache access. Do not skip or weaken
`audit:locked`.

### 8.2 After editing

Run, in order:

```powershell
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
```

No warning, notice, risky test, skipped test, incomplete test, unexpected output,
PHPStan error, style finding, security advisory, PSR-4 warning, or test failure is
accepted. Existing Composer license/local-development constraint warnings are
accepted categories only.

The exact final PHPUnit test and assertion totals are observational evidence,
not architect-supplied constants. Report them exactly; do not write tests merely
to reach a predicted count.

### 8.3 Direct contract and preservation audit

Also verify:

- the immediate native parents are exactly distinct as Section 5 requires;
- all ten routing diagnostics and their schemas remain byte-for-byte unchanged;
- all routing public method shapes and API statuses remain unchanged;
- `RouteMatcher` retains no request or match state;
- all accepted and just-over-bound tests listed in Section 6 exist and pass;
- no prohibited source import, dependency, package, declaration, file, or
  behavior was introduced;
- all files outside the seven authorized paths and final correction report
  retain their pre-change bytes;
- all 400 pre-Phase-2D protected Git-visible files remain exact except the
  already-authorized Phase 2D modifications and the single architecture test
  change authorized here;
- UTF-8, LF-only, one final LF, no trailing whitespace, no NUL, ordinary-file,
  mode, NTFS alternate-stream, reparse-point, and local-link checks pass where
  applicable;
- `git diff --check` passes;
- no index, commit, ref, branch, remote, or tag changed.

Before report population, final status must remain:

```text
tracked modifications: 18
untracked paths: 87
total status paths: 105
staged paths: 0
```

The same counts must remain after populating the already-existing untracked
correction report.

---

## 9. Mandatory stop rules

Stop immediately, preserve the current state, and report the exact mismatch if:

- any independent directive or locked caller identity fails;
- the zero-byte correction report prerequisite fails;
- Git/inventory/file-safety baseline differs;
- any unauthorized path would need modification;
- satisfying a test would require changing a locked production behavior other
  than the one native parent correction;
- any diagnostic, route grammar, matching, conflict, bound, or API decision is
  ambiguous;
- a production defect outside the exact authorized correction is exposed;
- a non-report quality or preservation gate fails after bounded test correction;
  or
- final status, history, ref, mode, stream, encoding, or protected-byte proof
  differs.

Do not stage, commit, reset, restore, clean, stash, amend, merge, rebase, fetch,
push, switch branches, create/delete refs, or normalize historical records.

Do not populate the correction report after a failed non-report gate.

---

## 10. Strict acceptance criteria

Phase 2D.1 is complete only when all are true:

1. every authority was read completely;
2. independent directive bytes and SHA-256 matched;
3. the correction report began as the exact zero-byte caller placeholder;
4. the locked Phase 2D report/directive and seven starting file identities
   matched;
5. Git identity, status, inventories, refs, modes, streams, and ordinary-file
   properties matched;
6. pre-change `composer quality` passed at 382 tests / 6689 assertions;
7. only the seven authorized files changed before report population;
8. `InvalidRouteRequestException` extends native `RuntimeException` exactly;
9. `InvalidRouteDefinitionException` continues to extend native
   `InvalidArgumentException` exactly;
10. package and root architecture tests enforce both exact immediate parents;
11. all accepted upper-bound proofs in Sections 6.2-6.4 pass;
12. just-over-limit rejection proofs remain;
13. all three PSR getter Throwable identity cases pass in exact access order;
14. reversed duplicate/conflict route-table failures are deterministic;
15. representative raw route/request sentinels are absent from every
    package-owned diagnostic surface;
16. the ten-row routing diagnostic catalog is unchanged;
17. route grammar, precedence, conflict, encoded matching, unmatched behavior,
    API shapes, API statuses, and package graph are unchanged;
18. focused routing tests pass with no notices, risks, skips, incompletes, or
    output;
19. root architecture test, focused/full PHPStan, style, strict PSR autoload,
    advisory audit, and canonical quality all pass;
20. package/source/test/style/diagnostic inventories remain 10/80/104/184/106;
21. no prohibited capability or dependency was introduced;
22. all protected and historical records remain exact;
23. `git diff --check`, encoding, file-safety, stream, mode, and local-link
    checks pass;
24. Git history/index/refs remain unchanged and zero paths are staged;
25. final status is exactly 18 modified, 87 untracked, 105 total, 0 staged;
26. only after criteria 1-25 pass, the correction report is populated;
27. the report has the exact structure below and states no unsupported claim;
28. the populated report's final byte count and SHA-256 are returned outside
    the report body.

---

## 11. Standard executor completion report

Populate `docs/task reports/Phase_2D_1_correction.md` with exactly these
top-level headings, in this order:

```markdown
# PHASE 2D.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. PRODUCTION CORRECTION

## 8. TEST PROOF ADDED

## 9. ARCHITECTURAL DECISIONS

## 10. DEVIATIONS FROM DIRECTIVE

## 11. DEPENDENCIES ADDED/REMOVED

## 12. ENVIRONMENT

## 13. COMMANDS RUN

## 14. VALIDATION RESULTS

## 15. DOCUMENTATION REVIEW

## 16. SECURITY AND FILE-SAFETY REVIEW

## 17. WARNINGS / KNOWN ISSUES

## 18. QUESTIONS / BLOCKERS

## 19. GIT DIFF SUMMARY

## 20. ACCEPTANCE CRITERIA

## 21. NEXT STEP
```

The report must state:

- the exact independent correction-directive identity used;
- all locked identities and baseline/final status counts;
- every modified file and that no file was created by the executor;
- the exact native-parent correction;
- each missing proof added;
- exact command outcomes and final test/assertion totals;
- unchanged dependencies, diagnostics, APIs, docs, package graph, Git history,
  index, and refs;
- every warning and approved-access retry;
- whether every acceptance criterion passed; and
- `Await architect review before Phase 2E.` as the exact final sentence.

After writing and validating the report, calculate its byte count and SHA-256
and return those two values to the caller outside the report body.

---

## 12. Required final outcome

At completion, Phase 2D remains the same narrow deterministic routing boundary,
with one corrected native failure category and complete evidence for its locked
contracts. No Phase 2E capability exists, and no routing architecture decision
is reopened.
