# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.6

## Executable Typed-List Narrowing at Chie Runtime Boundaries

## 1. Purpose and authority

This is the architect-authenticated correction for the twelve findings produced by the complete Phase 4D.5 sequential PHPStan analysis.

Phase 4D.5 correctly made the raw runtime array boundaries honest, but `array_all()` is a boolean predicate and does not give PHPStan an executable element-by-element narrowing proof for later property assignment, iteration, callback typing, or return types. The correct bounded solution is to retain list-shape checks, validate each raw element, and append each validated element to a newly constructed typed list.

This correction authorizes changes only in:

- `packages/chie/src/ChieClaim.php`;
- `packages/chie/src/ChieResponse.php`.

It also authorizes exactly one new sequential focused PHPStan execution. It does not authorize suppressions, configuration changes, weakened runtime validation, public API changes, or any other source/test change.

All Phase 4D.2 substantive rules, as corrected by Phase 4D.3 through Phase 4D.5, remain controlling except where this directive explicitly replaces the failed raw-array narrowing implementation.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_6_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_6_correction.md`

The architect will supply this directive's byte count and SHA-256 outside this file. Do not self-authenticate from local values. Stop before further validation or mutation if the independent identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Locked caller records

Authenticate all earlier Phase 4D caller records:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md` | 21229 | `EBAA4F85E60F205DC943B6A78186E1F3B6B49044392C56FB49136036066844E0` |
| `docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md` | 10606 | `3E893F2F556A69DC2612871AEE7E90A494BC5D13AE59648E5C540387BDC8D67B` |
| `docs/dev_plan/phase_4/kongono_phase_4D_4_codex_directive_correction.md` | 12371 | `BC1F2A3FCE2A2BB6BBDD9982C2D68C8E87CDF2D5B039B88E405092371D2A27F5` |
| `docs/dev_plan/phase_4/kongono_phase_4D_5_codex_directive_correction.md` | 13267 | `0A32FA43EB0F191DB09BD69A423296D1C89B26888C6934C9FF074D3FD758EAC8` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |
| `docs/task_reports/phase_4/Phase_4D_2_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_3_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_4_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_5_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier directives and reports are immutable. Populate only the Phase 4D.6 report, and only after every non-report gate succeeds.

## 4. Exact entry state

After the caller places the Phase 4D.6 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 97 untracked paths;
- 123 total status paths;
- zero staged paths;
- 726 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 PHP files in canonical style scope;
- 64 ADR files;
- 13 files in `docs/dev_plan/phase_4`;
- 13 files in `docs/task_reports/phase_4`.

No dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized.

## 5. Exact accepted implementation identities

Authenticate all eight Phase 4D implementation files at their post-Phase-4D.5 identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/ChieCitation.php` | 2442 | `88E5C82ECDB3CA160255CCC14E2621E00E3FB0F01F2199F069776DA0CE96A01B` |
| `packages/chie/src/ChieClaim.php` | 2413 | `28B4B31B4D1F12819835E907DF64B6BF16A6FF495CF558DCAE7044786DA12322` |
| `packages/chie/src/ChieResponse.php` | 5007 | `017AA27C72D82DC402FB1A2B7B547D491390D555808EDC903393F3097144AECF` |
| `packages/chie/src/Diagnostic/ChieDiagnostic.php` | 7026 | `832A3B389161E2EC180C48208946491E6D34F3125221BB35CA9247FA1983F913` |
| `packages/chie/src/Exception/ChieException.php` | 2449 | `A13A2D2A7AFFD2BCEF5496FFE69C679730386C0AB761CC0CB445F3D94021AC6C` |
| `packages/chie/src/Internal/CanonicalJson.php` | 1830 | `8E78D74199451CF855214EDFEC9E52F802A1BEDDA17A023EB4DFB4FE86B2B22F` |
| `packages/chie/tests/Unit/ChieResponseTest.php` | 13096 | `F25232C913F7DED0ADB78C4DCEC474D6EB0EDB4E8B3B2CD43E35E98D263C4570` |
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 9323 | `5468B79F2AEF7E344F8C5B68E1A9A6F139D959D1BCA218075EBC9FDAC88DC121` |

Stop before mutation if any differs.

## 6. Exact partial-state manifests

Exclude the fourteen Phase 4D caller paths—seven directives and seven reports—from reconstruction. Use ordinal case-sensitive ordering, forward-slash paths, UTF-8 without BOM, LF only between records, no terminal LF, and path/tab/decimal-byte-count/tab/uppercase-SHA-256 records.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete partial implementation | 712 | 89259 | `1703B6C3FB27364E3E608D04D64D3537B1E6FC2FEB0663C9C15F62B11A664F00` | 7701217 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding fourteen callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

The complete manifest's first path is `.editorconfig`; its last path is `tests/Tooling/EnvironmentTest.php`. Stop on any mismatch.

## 7. Accepted Phase 4D.5 finding evidence

Accept the consumed Phase 4D.5 sequential analysis as complete failed evidence and do not rerun it. It reported exactly:

- three findings in `ChieClaim.php`: `assign.propertyType`, `return.type`, and `argument.type`;
- nine findings in `ChieResponse.php`: mixed iteration/object-access plus property, return, and callback typing;
- the prior redundant assertion finding in `ChieResponseTest.php` was resolved;
- no internal crash or incomplete-result warning.

The cause is that PHPStan does not use `array_all()` alone to narrow a raw `array<array-key, mixed>` into a `list<ChieCitation>`, `list<ChieClaim>`, or `list<string>` for later operations.

## 8. Exact authorized production correction

Modify exactly `ChieClaim.php` and `ChieResponse.php`. No test or other file may change.

### 8.1 `ChieClaim.php`

Preserve the Phase 4D.5 constructor annotation:

```php
/** @param array<array-key, mixed> $citations */
```

Replace the boolean-only citation validation with executable narrowing:

1. If `array_is_list($citations)` is false, throw exactly `new InvalidArgumentException('Invalid Chie claim.')`.
2. Initialize a fresh empty local validated-citation list.
3. Iterate the raw citations.
4. For each element, if it is not a `ChieCitation`, throw the same exact fixed exception.
5. Append only the narrowed `ChieCitation` to the fresh list using `[]` append.
6. Use the validated list—not the raw array—for citation counts, empty-list matrix checks, final detachment, and assignment to `$this->citations`.

Retain the full text validation and exact class/text/citation matrix. Do not use `array_filter`, unchecked casts, PHPDoc `@var` assertions over unvalidated input, or `array_values` as a substitute for validation.

The resulting local list must be inferred from executable control flow as `list<ChieCitation>`.

### 8.2 `ChieResponse.php`

Preserve the Phase 4D.5 private-constructor annotations:

```php
* @param array<array-key, mixed> $claims
* @param array<array-key, mixed> $uncertainties
```

Replace boolean-only raw-array validation with executable narrowing:

1. If either raw input is not a list, throw exactly `new InvalidArgumentException('Invalid Chie response.')`.
2. Initialize a fresh empty validated-claim list.
3. Iterate raw claims; reject any non-`ChieClaim` with the same fixed exception; append only narrowed `ChieClaim` values.
4. Initialize a fresh empty validated-uncertainty list.
5. Iterate raw uncertainties; reject any non-string with the same fixed exception; append only narrowed strings.
6. Use validated claims and uncertainties for every status-matrix count/comparison.
7. Use validated claims for citation/source-set iteration.
8. Detach and assign only the validated lists to the typed properties.

Preserve digest validation, exact status/operation/uncertainty mapping, source-set equality checking, public factories and their annotations, serialized shape/order, accessors, and exception messages.

The local lists must be inferred from executable control flow as `list<ChieClaim>` and `list<string>`.

## 9. Behavioral preservation

The correction must not change any accepted result:

- valid list inputs construct the identical value objects and canonical JSON;
- associative arrays, wrong element types, invalid counts, invalid text, invalid digests, invalid mappings, and cross-source-set citations still fail closed;
- every rejected value remains absent from messages and exception strings;
- returned arrays remain detached;
- no public PHP signature, enum, class, status, operation, response key, or diagnostic changes.

Do not add a suppression, ignore, baseline, cast, helper file, configuration entry, dependency, or new test. Do not delete a runtime check.

## 10. Validation and one authorized sequential run

After the exact two-file correction:

1. Confirm only those two files changed from Section 5.
2. Run `php -l` on both files.
3. Run exactly once:

```powershell
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/chie
```

Acceptance requires complete sequential analysis, exit code 0, zero findings, and no internal error/crash/incomplete result. On any nonzero result, stop without retry or further edit.

After a pass, resume with the focused PHP CS Fixer dry run and all later Phase 4D.2/4D.3 gates under Phase 4D.4's continuation rules. The final literal `composer quality` is still mandatory. Only its already authorized Composer audit network/cache retry remains available.

## 11. Final invariants

Full success still requires:

- exactly eight total implementation files differing from original Phase 4D.2 identities;
- exactly two files differing from Phase 4D.6 entry identities;
- focused and complete test counts of exactly 36 and 633;
- all focused tests, adversarial probes, valid CLI regressions, style, sequential PHPStan, canonical quality, artifact, disclosure, manifest, and preservation gates passing;
- Knowledge artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
- exactly 31 Knowledge sources;
- protected and historical manifests unchanged;
- no documentation, dependency, Composer, configuration, caller-record, staging, commit, ref, or history mutation;
- Phase 4E not begun.

## 12. Completion report

Populate only `docs/task_reports/phase_4/Phase_4D_6_correction.md` after every non-report gate passes. Use the standard 16 sections and include:

- independent directive identity and all caller identities;
- entry status, all eight file identities, and all three manifests;
- accepted Phase 4D.5 findings;
- exact two-file narrowing correction and final identities;
- syntax and single sequential PHPStan evidence;
- every resumed focused, adversarial, CLI, style, artifact, canonical, manifest, and preservation result;
- confirmation that no test or unauthorized file changed in Phase 4D.6;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 13. Acceptance criteria

Phase 4D.6 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, eight identities, and manifests match.
3. Exactly two files receive executable typed-list narrowing.
4. Runtime validation and valid output remain behaviorally exact.
5. Both syntax checks pass.
6. The sequential PHPStan command runs once and reports zero errors.
7. Every remaining Phase 4D gate passes.
8. Focused and complete counts remain 36 and 633.
9. Artifact identities and source count remain exact.
10. No unauthorized mutation, retry, staging, or commit occurs.
11. Only the Phase 4D.6 report is populated after non-report success.
12. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
