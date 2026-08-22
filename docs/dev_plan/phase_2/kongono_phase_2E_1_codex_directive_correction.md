# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2E.1

## Route-Handler Key Failure Input-Order Proof

**Directive type:** narrow test-only correction and verification
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-16
**Authority:** architect-issued correction to the authenticated Phase 2E directive
**Executor role:** executor, not architect

---

## 1. Mission

Complete one missing Phase 2E regression proof without changing the accepted
implementation or architecture.

Independent review established all of the following:

- the Phase 2E production implementation has the required behavior;
- equivalent route-handler key failures already produce the same diagnostic
  and context when the caller reverses map insertion order;
- focused HTTP Server tests, the root API architecture test, full PHPStan with
  a 512 MiB memory limit, and canonical `composer quality` pass;
- the Phase 2E test suite proves caller-order independence for invalid handler
  **values**; but
- it does not construct equivalent invalid handler **key sets** in forward and
  reversed insertion order and compare the resulting failures, even though
  Phase 2E Section 12.4 requires key and value failures to be input-order
  independent.

Add only that missing durable test proof. Do not alter production code,
diagnostics, documentation, dependencies, package boundaries, or any other
Phase 2E behavior.

The populated Phase 2E report remains immutable historical evidence. This
correction report must candidly state that Phase 2E.1 supplies a proof that the
original report claimed more broadly than the original test suite demonstrated.
Do not rewrite or truncate the Phase 2E report.

---

## 2. Governing authority and caller authentication

### 2.1 Read completely before mutation

Read these exact files completely, in this order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `SECURITY.md`
4. `docs/doctrine.md`
5. `docs/documentation-standard.md`
6. `docs/adr/0001-kongono-identity.md`
7. `docs/adr/0003-monorepo-initial-development.md`
8. `docs/adr/0013-documentation-doctrine.md`
9. `docs/adr/0014-php-engineering-toolchain.md`
10. `docs/adr/0016-strict-types-and-per-cs-style.md`
11. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`
12. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`
13. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`
14. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`
15. `docs/adr/0044-http-message-and-interoperability-architecture.md`
16. `docs/adr/0045-first-party-http-message-and-factory-implementation.md`
17. `docs/adr/0046-deterministic-route-declaration-and-matching.md`
18. `docs/adr/0047-explicit-psr-15-middleware-and-terminal-routing.md`
19. `docs/architecture/http-message-model.md`
20. `docs/architecture/routing-model.md`
21. `docs/architecture/http-server-model.md`
22. `docs/architecture/runtime-model.md`
23. `docs/architecture/phase-2-roadmap.md`
24. `docs/engineering/api-status.md`
25. `docs/engineering/error-handling.md`
26. `docs/engineering/package-layout.md`
27. `docs/dev_plan/kongono_phase_2D_codex_directive.md`
28. `docs/task reports/Phase_2D.md`
29. `docs/dev_plan/kongono_phase_2D_1_codex_directive_correction.md`
30. `docs/task reports/Phase_2D_1_correction.md`
31. `docs/dev_plan/kongono_phase_2E_codex_directive.md`
32. `docs/task reports/Phase_2E.md`
33. `docs/dev_plan/kongono_phase_2E_1_codex_directive_correction.md`

The filenames above are exact. Do not substitute an ADR based only on its
number. Historical directives and reports are evidence, not authorization to
repeat or widen their mutations.

### 2.2 Independent correction-directive identity

Before quality checks, edits, or any other mutation, compare this correction
directive with the architect-supplied byte count and SHA-256 delivered outside
this file.

The local file is not self-authenticating. Do not calculate its identity and
adopt the result as authority. If either independently supplied value is absent
or differs, stop and report the observed identity without changing anything.

### 2.3 Caller-managed records

Before execution, the caller must provide:

```text
docs/dev_plan/kongono_phase_2E_1_codex_directive_correction.md
docs/task reports/Phase_2E_1_correction.md
```

The correction report must begin as an ordinary, untracked, unstaged,
zero-byte file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create, truncate, replace, stage, commit, or populate either caller
record. The zero-byte correction report may be populated only after every
permitted non-report gate passes.

The following Phase 2E records are immutable:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2E_codex_directive.md` | 41768 | `53CD9AA94E6F5A2518E94C4FB1FAA9E3461C5E87A13C02E923BF4F0F38CF270A` |
| `docs/task reports/Phase_2E.md` | 25112 | `44FB493A64FD50C4707E8ABDBBD1C75E923F038C6C48070F56B4F63F756260B5` |

All earlier directive and report records are also immutable. Record their
pre-change identities and prove they remain exact. Do not normalize historical
bytes, including line endings.

---

## 3. Locked starting state

After the caller copies this correction directive and creates the zero-byte
correction report, verify this exact state before broad quality checks:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent: 8fe793b124384744ac43887037360154c149241a
commit count: 9
tracked modifications: 18
untracked paths: 107
total status paths: 125
staged paths: 0
package directories: 11
package manifests: 11
production PHP files: 86
test PHP files: 110
PHP files covered by PHP CS Fixer: 196
```

Count untracked paths with full expansion equivalent to
`git status --porcelain=v1 -uall`; do not use collapsed directory output.

The repository must have exactly one local branch, no remotes, no tags, and no
unexpected refs. Codex-managed `refs/codex/turn-diffs/**` are environmental and
exempt from that invariant; enumerate them separately and do not alter them.

These accepted starting files must match exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/http-server/tests/Unit/RoutingRequestHandlerTest.php` | 16752 | `7FC590A883FFCAF4AAE6E66936C1812029651F79CBD3705687CE2855ED3317BB` |
| `packages/http-server/composer.json` | 559 | `BA9DD2B9BD364526DD6893498EF9CEEA1CB882206683A11E5A91C96678CC1406` |
| `composer.lock` | 193651 | `521CFB7BACF023C463B95C7146D6077CC34BD0871E2DFEFB3807DA89CD4552DC` |

Also prove that the exact 12 PHP files in `packages/http-server`, ADR 0047,
the HTTP Server model, all Phase 2E documentation changes, and the complete
diagnostic catalog match their accepted Phase 2E bytes.

If an identity, inventory, ordinary-file property, mode, stream, reparse-point,
Git state, or caller prerequisite differs, stop. Do not repair, restore, clean,
normalize, stage, or infer a new baseline.

---

## 4. Exact authorized file boundary

Modify exactly one existing file:

```text
packages/http-server/tests/Unit/RoutingRequestHandlerTest.php
```

After all non-report gates pass, populate only:

```text
docs/task reports/Phase_2E_1_correction.md
```

No other file may change. In particular, do not modify:

- anything under `packages/http-server/src/`;
- any other test or fixture;
- any Composer manifest, `composer.lock`, autoload mapping, or tooling config;
- ADR 0047, the HTTP Server model, or any documentation;
- any earlier directive or completion report;
- Git index, history, refs, branches, remotes, or tags.

Create no production file, test file, fixture, dependency, package, shim,
baseline, suppression, or generated artifact.

---

## 5. Exact required regression proof

In `RoutingRequestHandlerTest.php`, add one focused public PHPUnit test method or
narrowly extend `testKeyFailuresPrecedeValuesAndUseOnlyCounts()` so it proves
route-handler key failure is independent of caller map insertion order.

### 5.1 Test arrangement

Use the existing two-route table with canonical names `alpha` and `beta`.
Construct one valid `RequestHandlerInterface` object for use as every map value
so the proof isolates key-set validation and cannot accidentally exercise the
invalid-value diagnostic.

Construct these two semantically equivalent maps:

```php
[
    'alpha' => $validHandler,
    'extra' => $validHandler,
]

[
    'extra' => $validHandler,
    'alpha' => $validHandler,
]
```

The maps have the same key set but opposite caller insertion order. Relative to
the route table, `beta` is missing and `extra` is unexpected.

For each map, construct `RoutingRequestHandler` and capture the expected
`InvalidHttpServerDefinitionException`. Do not use invalid string values in
this particular proof.

### 5.2 Required assertions

For both exceptions, prove:

- the exact diagnostic ID is
  `HttpServerDiagnostic::InvalidRouteHandlerKeys->value`;
- the diagnostic ID, native message, title, remediation, operation, and
  violated invariant are identical between the two insertion orders;
- the context contains exactly these fields in this order:

```text
route_count
handler_count
missing_count
unexpected_count
```

- the four context values are exactly:

```text
2
2
1
1
```

- each emitted field's key, label, kind, disclosure classification,
  `textWasTruncated()` result, and value are identical between the two
  exceptions;
- `HttpServerDiagnostic::InvalidRouteHandlerKeys->contextFields()` still
  declares the same four fields in the same order and every schema field's
  `isRequired()` value is `true`; and
- the failure does not disclose the raw keys `beta` or `extra` through the
  native message or any package-owned diagnostic prose/context value.

Use inspectable PHPUnit assertions. Do not merely iterate the two maps and
assert the same diagnostic ID independently; retain both caught exceptions and
compare their complete package-owned diagnostic surfaces.

### 5.3 Preservation

Retain all existing key-set cases, key-before-value checks, invalid-value
canonical-position proof, successful routing behavior, Throwable propagation,
reflection/API-status proof, disclosure checks, and other Phase 2E tests.

Do not change production code to make the test pass. The accepted implementation
already satisfies the required behavior.

---

## 6. Prohibited scope

Do not alter or implement:

- middleware pipeline construction or execution;
- route matching, route-handler selection, unmatched delegation, or reserved
  request-attribute behavior;
- any diagnostic ID, prose, schema, field order, kind, disclosure, required
  flag, or value;
- exception classes, native parents, messages, constructors, or throw sites;
- public API status, method shape, constant, namespace, package graph, or
  dependency constraint;
- composition root, Kernel lifecycle, Application Graph integration, DI
  bindings, current-request state, bootstrap, SAPI conversion, response
  emission, transport I/O, or error presentation;
- controllers, authentication, authorization, sessions, cookies, CSRF,
  frontend, templates, telemetry, ORM, agents, extensions, or CLI;
- PHP version, tooling policy, quality scripts, suppressions, baselines, or
  third-party dependencies.

Do not clean up adjacent work. Stop rather than guess.

---

## 7. Required validation

### 7.1 Before editing

After authentication and complete baseline proof, run:

```powershell
composer quality
```

Accepted Phase 2E evidence is:

```text
security advisories: zero
PHP CS Fixer: 0 of 196 files fixable
PHPStan: zero errors
PHPUnit: 410 tests, 7275 assertions
```

If advisory access alone is blocked by the managed environment, retry the
identical command with approved network/cache access. Do not skip or weaken
`audit:locked`.

### 7.2 After editing

Run, in order:

```powershell
php -l packages/http-server/tests/Unit/RoutingRequestHandlerTest.php
composer dump-autoload --optimize --strict-psr
php vendor/bin/phpunit --configuration phpunit.xml.dist --display-phpunit-notices packages/http-server/tests
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress packages/http-server/src packages/http-server/tests tests/Architecture/ApiStatusConventionTest.php
composer cs
composer analyse -- --memory-limit=512M
composer quality
```

The focused PHPStan command may encounter the already-observed managed
environment's 128 MiB parallel-worker ceiling. If and only if that exact
environmental memory failure occurs, rerun the same focused scope with an
explicit 512 MiB memory limit, record both outcomes, and still require the full
512 MiB analysis and canonical quality commands to pass. Do not add a baseline,
suppression, config change, or weaker scope.

No test failure, PHPUnit notice, risky/skipped/incomplete test, unexpected test
output, PHPStan error, style finding, security advisory, PSR-4 warning, or
unaccepted warning is allowed. Existing Composer license and authorized local
development-constraint warnings remain accepted categories only.

Exact final test and assertion totals are observational evidence, not target
constants. Report them exactly; do not write assertions merely to reach a
predicted total.

### 7.3 Direct preservation audit

Also verify:

- both opposite-order maps fail with identical complete package-owned
  diagnostic surfaces and exact context values `[2, 2, 1, 1]`;
- all 12 HTTP Server PHP files remain present and no new package file exists;
- every production file remains byte-for-byte exact;
- the three HTTP Server diagnostic cases and their schemas remain exact;
- Composer manifests, `composer.lock`, dependencies, package inventories,
  docs, ADRs, API statuses, and public contracts remain exact;
- every file outside the one authorized test and final correction report
  retains its pre-change bytes;
- UTF-8, LF-only, one final LF, no trailing whitespace, no NUL, ordinary-file,
  100644 mode, NTFS stream, reparse-point, and local-link checks pass where
  applicable;
- `git diff --check` passes; and
- no index, commit, ref, branch, remote, or tag changed.

Before and after populating the already-existing untracked correction report,
final status must remain:

```text
tracked modifications: 18
untracked paths: 107
total status paths: 125
staged paths: 0
```

---

## 8. Mandatory stop rules

Stop immediately, preserve the current state, and report the exact mismatch if:

- independent directive authentication or any locked caller identity fails;
- the zero-byte correction report prerequisite fails;
- Git, inventory, mode, stream, reparse-point, ordinary-file, or hash baseline
  differs;
- any unauthorized path would need modification;
- the required proof exposes a production defect;
- satisfying the test would require changing production, diagnostics,
  dependencies, documentation, or another test;
- the required diagnostic comparison is ambiguous;
- any non-report quality or preservation gate fails after a bounded correction;
  or
- final status, history, refs, encoding, mode, stream, or protected-byte proof
  differs.

Do not stage, commit, reset, restore, clean, stash, amend, merge, rebase, fetch,
push, switch branches, create/delete refs, or normalize historical records.

Do not populate the correction report after a failed non-report gate.

---

## 9. Strict acceptance criteria

Phase 2E.1 is complete only when all are true:

1. every authority was read completely;
2. the independent correction-directive byte count and SHA-256 matched;
3. the correction report began as the exact zero-byte caller placeholder;
4. the Phase 2E directive/report and one authorized test identity matched;
5. Git identity, status, inventories, refs, modes, streams, and ordinary-file
   properties matched;
6. pre-change canonical quality passed at 410 tests / 7275 assertions;
7. only `RoutingRequestHandlerTest.php` changed before report population;
8. the proof uses valid request handlers and equivalent forward/reversed key
   maps;
9. both maps fail with `KGN-HTTP-SERVER-1002` through the exact enum value;
10. both failures expose the exact context key order and values `[2, 2, 1, 1]`;
11. the complete package-owned diagnostic surfaces are identical between
    insertion orders;
12. raw missing/unexpected keys are absent from package-owned failure surfaces;
13. all prior Phase 2E tests and assertions remain substantively intact;
14. focused HTTP Server tests pass with no notices, risks, skips, incompletes,
    or unexpected output;
15. root architecture tests, focused/full PHPStan, style, strict PSR autoload,
    advisory audit, and canonical quality pass;
16. package/source/test/style inventories remain 11/86/110/196;
17. production, diagnostics, APIs, documentation, dependencies, manifests, and
    lockfile remain exact;
18. no prohibited capability, file, dependency, or suppression was introduced;
19. historical/caller records and all protected paths remain exact;
20. `git diff --check`, encoding, file-safety, stream, mode, and local-link
    checks pass;
21. Git history, index, and refs remain unchanged and zero paths are staged;
22. final status is exactly 18 modified, 107 untracked, 125 total, 0 staged;
23. only after criteria 1-22 pass, the correction report is populated;
24. the report has the exact structure below and makes no unsupported claim;
25. the populated report's final byte count and SHA-256 are returned outside
    the report body.

---

## 10. Standard executor completion report

Populate `docs/task reports/Phase_2E_1_correction.md` with exactly these
top-level headings, in this order:

```markdown
# PHASE 2E.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. TEST PROOF ADDED

## 8. PRODUCTION PRESERVATION

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
- that no file was created by the executor and exactly one test was modified;
- the two opposite-order maps and complete diagnostic comparison added;
- that the test closes a proof gap while production behavior was preserved;
- exact command outcomes and final test/assertion totals;
- unchanged production, diagnostics, APIs, docs, dependencies, manifests,
  lockfile, package graph, Git history, index, and refs;
- every warning, environmental memory event, and approved-access retry;
- whether every acceptance criterion passed; and
- `Await architect review before Phase 2F.` as the exact final sentence.

After writing and validating the report, calculate its byte count and SHA-256
and return those two values to the caller outside the report body.

---

## 11. Required final outcome

Phase 2E remains the same bounded PSR-15 middleware and terminal-routing
implementation. Phase 2E.1 adds only the missing persistent proof that
route-handler key-set diagnostics are independent of caller map insertion
order. No Phase 2F capability is introduced.
