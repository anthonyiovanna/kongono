# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.11

## Three Exact Architecture-Test Expectation Corrections

## 1. Purpose and authority

This is the architect-authenticated correction for the three focused architecture-test failures reported by Phase 4D.10.

Phase 4D.10 established that:

- the Phase 4D.8 exception implementation remains exact;
- the accepted 512 MB sequential PHPStan result is complete with zero errors;
- focused style passes with 0 of 32 files fixable;
- all 36 Chie tests pass;
- all six former raw-value disclosure failures pass;
- three architecture assertions alone are stale.

The stale assertions are mechanical consequences of the authorized Phase 4D.8 design:

1. one new production class increases the production declaration count from 167 to 168;
2. the same file increases the production file count from 167 to 168;
3. PHP 8.5 reflection resolves a method declared with return type `self` to the defining class name when converted to string.

This correction changes only those three architecture-test expectations. Production declarations remain unchanged. All Phase 4D.2 rules as corrected through Phase 4D.10 remain controlling.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_11_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_11_correction.md`

The architect will supply this directive's byte count and SHA-256 independently outside the file. Do not self-authenticate from local values. Stop before validation or mutation if the supplied identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Caller records

Reauthenticate every caller record required by Phase 4D.10. Additionally authenticate:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_10_codex_directive_correction.md` | 12551 | `E16B1379E031B53AD984D4F9E19FCA0418190B117D1F6CEACC03AA08126DDF07` |
| `docs/task_reports/phase_4/Phase_4D_10_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier Phase 4D directives and reports remain immutable. Populate only the Phase 4D.11 report after every remaining non-report gate passes.

## 4. Exact entry state

After the caller places the Phase 4D.11 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 108 untracked paths;
- 134 total status paths;
- zero staged paths;
- 737 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 168 production PHP files;
- 163 test PHP files;
- 333 files in canonical style scope;
- 64 ADR files;
- 18 files in `docs/dev_plan/phase_4`;
- 18 files in `docs/task_reports/phase_4`.

No production, package test, dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized.

## 5. Exact starting identities

Authenticate the complete ten-file Phase 4D.8 implementation exactly as listed in Phase 4D.10 Section 5. In particular, the only mutable non-report file in this correction begins as:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `tests/Architecture/ApiStatusConventionTest.php` | 105025 | `3A2A02A321675D229A57EC6F8B5B9A4BE3020E3DD28C31CB22391D1CB0A6BB38` |

The other nine implementation files must remain byte-identical throughout Phase 4D.11. Stop before mutation if any identity differs.

## 6. Exact entry manifests

Exclude the twenty-four Phase 4D caller paths—twelve directives and twelve reports. Use the locked ordinal/no-terminal-LF construction.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete current implementation | 713 | 89388 | `0C17F2671282D27BB31DE783DC3936830C1C06F76135F7B7B8D943210572E010` | 7710114 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding twenty-four callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. Ignored PHPStan cache state remains outside every manifest and is governed by Phase 4D.10's corrected ephemeral-cache policy.

## 7. Accepted prior evidence

Accept without rerun solely for this correction:

- Phase 4D.9 sequential PHPStan at process-local 512 MB: complete, exit 0, zero errors;
- unchanged normal 128 MB PHP configuration before and after that analysis;
- Phase 4D.10 ignored-cache classification and preservation proof;
- Phase 4D.10 focused style: 0 of 32 fixable;
- Phase 4D.10 focused Chie tests: 36 passed;
- six former disclosure failures: passed;
- architecture test file: eight tests executed, three exact failures listed in Section 8, no other failure.

Do not rerun the earlier focused PHPStan command in Phase 4D.11. Final canonical PHPStan remains mandatory through `composer quality`.

## 8. Exact authorized replacements

Modify only `tests/Architecture/ApiStatusConventionTest.php` with exactly these three replacements.

### 8.1 Production declaration count

Replace:

```php
self::assertCount(167, $types);
```

with:

```php
self::assertCount(168, $types);
```

### 8.2 Reflected named-factory return type

Inside the existing loop that reflects the six `InvalidChieValueException` factories, replace:

```php
self::assertSame('self', (string) $method->getReturnType());
```

with:

```php
self::assertSame(InvalidChieValueException::class, (string) $method->getReturnType());
```

Do not modify the production factories. They remain declared with `self`. This assertion records PHP 8.5 reflection's resolved string representation; it does not change the source-level return declaration.

### 8.3 Production file count

Replace:

```php
self::assertCount(167, $productionFiles);
```

with:

```php
self::assertCount(168, $productionFiles);
```

No other line, whitespace, import, assertion, helper, test method, or expectation may change except a mechanical line-ending effect caused by applying these exact replacements.

## 9. Validation sequence

After the exact replacements:

1. Confirm only `ApiStatusConventionTest.php` changed from its Section 5 identity and all ten production/package-test identities remain exact.
2. Run `php -l tests/Architecture/ApiStatusConventionTest.php`.
3. Run the cache-disabled focused style command from Phase 4D.10 exactly once. Expected: exit code 0 and 0 of 32 fixable.
4. Run focused PHPUnit exactly once:

```powershell
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/chie/tests tests/Architecture/ApiStatusConventionTest.php
```

Expected:

- Chie tests: 36 passed;
- architecture tests: 8 passed;
- total: 44 tests;
- assertions: 4183;
- zero failures and errors.

5. After the focused pass, resume the remaining Phase 4D.10 sequence at adversarial construction/disclosure proofs, then valid CLI regressions, artifact proof, final literal `composer quality`, manifests, preservation, and report population.

Final literal `composer quality` must report zero advisories, 0 of 333 style findings, zero PHPStan errors, and exactly 633 tests. Report its assertion count.

Only the existing audit-only approved-access retry remains permitted. Stop without retry on any other failure.

## 10. Prohibited scope

Do not:

- change a production factory from `self` to the concrete class;
- edit any Chie source/package test;
- change any architecture assertion other than the three exact lines;
- alter test methods, counts, helpers, imports, or API-status inventory;
- weaken disclosure, inheritance, constructor, factory, or stringification proofs;
- change PHPStan, PHPUnit, PHP CS Fixer, Composer, PHP, environment, cache, or repository configuration;
- manually change ignored cache state;
- modify dependencies, artifacts, documentation, ADRs, caller records, index, refs, or history;
- stage, commit, clean, restore, reset, or stash;
- populate an earlier report;
- begin Phase 4E.

Only `docs/task_reports/phase_4/Phase_4D_11_correction.md` may be populated after every non-report gate passes.

## 11. Final invariants

Full success requires:

- the one-created/nine-modified Phase 4D.8 implementation remains exact;
- only the architecture test differs from Phase 4D.11 entry, through three literal expectation changes;
- all 36 Chie and eight architecture tests pass;
- all six former disclosure failures remain passing;
- adversarial and valid CLI proofs pass;
- Knowledge artifact SHA-256 remains `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- source-set SHA-256 remains `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320` with 31 sources;
- canonical quality passes with 333 style files and 633 tests;
- protected/historical manifests remain exact and complete delta remains closed;
- ignored cache paths remain ignored, untracked, and unstaged;
- no staging, commit, dependency, documentation, configuration, caller, ref, or history mutation occurs;
- Phase 4E is not begun.

## 12. Completion report

Populate only `docs/task_reports/phase_4/Phase_4D_11_correction.md` after full non-report success. Use the standard 16 sections and include:

- independent directive and all earlier caller identities;
- entry state, implementation identities, and manifests;
- accepted PHPStan/cache/style/failed-test evidence;
- the three exact replacements and final architecture-test identity;
- syntax, focused style, focused tests, adversarial, CLI, artifact, canonical, final manifest, and preservation results;
- separate Chie, architecture, focused-total, and canonical test/assertion counts;
- confirmation that production factories still declare `self`;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 13. Acceptance criteria

Phase 4D.11 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, identities, and manifests match.
3. Exactly three architecture-test expectations change.
4. Production declarations and package tests remain exact.
5. Syntax and focused style pass.
6. Focused PHPUnit passes with 44 tests and 4183 assertions.
7. All remaining adversarial, CLI, artifact, canonical, manifest, and preservation gates pass.
8. Canonical quality reports exactly 633 tests with zero findings.
9. Ignored cache policy remains satisfied.
10. No unauthorized mutation, staging, or commit occurs.
11. Only the Phase 4D.11 report is populated after success.
12. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
