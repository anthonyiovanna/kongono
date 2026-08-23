# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.5

## Honest Boundary PHPDoc and Redundant Test Assertion Correction

## 1. Purpose and authority

This is the architect-authenticated correction for the seven complete source findings reported by the Phase 4D.4 sequential PHPStan gate.

The findings are caused by a mismatch between runtime boundary validation and PHPDoc that already promises the validated types. PHPStan therefore correctly identifies the defensive checks as redundant. The correction preserves every runtime validation and public PHP signature while making the accepted raw array input honest in PHPDoc. It also removes one test assertion that PHPStan proves is redundant.

This correction authorizes exactly three small edits in three existing Phase 4D files and exactly one new sequential focused PHPStan execution. It does not suppress findings, add ignores, weaken types, remove runtime validation, change behavior, or reopen architecture.

All Phase 4D.2 substantive rules, as corrected by Phase 4D.3 and resumed by Phase 4D.4, remain controlling except where this directive explicitly replaces the Phase 4D.4 no-further-source-mutation rule.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_5_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_5_correction.md`

The architect will independently supply this directive's byte count and SHA-256 outside the file. Do not self-authenticate from the local artifact. Stop before further validation or mutation if the independent identity is absent or differs.

The Phase 4D.5 report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Locked Phase 4D caller records

Authenticate these earlier caller records exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md` | 21229 | `EBAA4F85E60F205DC943B6A78186E1F3B6B49044392C56FB49136036066844E0` |
| `docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md` | 10606 | `3E893F2F556A69DC2612871AEE7E90A494BC5D13AE59648E5C540387BDC8D67B` |
| `docs/dev_plan/phase_4/kongono_phase_4D_4_codex_directive_correction.md` | 12371 | `BC1F2A3FCE2A2BB6BBDD9982C2D68C8E87CDF2D5B039B88E405092371D2A27F5` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |
| `docs/task_reports/phase_4/Phase_4D_2_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_3_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_4_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

Earlier directives and reports are immutable. Only the Phase 4D.5 report may be populated after full non-report success.

## 4. Exact entry state

After the caller places the Phase 4D.5 directive and zero-byte report, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 95 untracked paths;
- 121 total status paths;
- zero staged paths;
- 724 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 PHP files in canonical style scope;
- 64 ADR files;
- 12 files in `docs/dev_plan/phase_4`;
- 12 files in `docs/task_reports/phase_4`.

No index, history, ref, branch, tag, remote, Git configuration, dependency, Composer, lockfile, Knowledge artifact, or documentation mutation is authorized.

## 5. Accepted partial-state identities and manifests

Before mutation, authenticate all eight Phase 4D implementation files exactly as locked by Phase 4D.4:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/ChieCitation.php` | 2442 | `88E5C82ECDB3CA160255CCC14E2621E00E3FB0F01F2199F069776DA0CE96A01B` |
| `packages/chie/src/ChieClaim.php` | 2408 | `DFD567F8FC72A113D5C105F4014942C88570E1590FB818C0E7CF05F39C8D1DF5` |
| `packages/chie/src/ChieResponse.php` | 4991 | `4C0E2EEB9F9AF412870FC056380C40753C46318836DF9C04E5C44377C60D6AC5` |
| `packages/chie/src/Diagnostic/ChieDiagnostic.php` | 7026 | `832A3B389161E2EC180C48208946491E6D34F3125221BB35CA9247FA1983F913` |
| `packages/chie/src/Exception/ChieException.php` | 2449 | `A13A2D2A7AFFD2BCEF5496FFE69C679730386C0AB761CC0CB445F3D94021AC6C` |
| `packages/chie/src/Internal/CanonicalJson.php` | 1830 | `8E78D74199451CF855214EDFEC9E52F802A1BEDDA17A023EB4DFB4FE86B2B22F` |
| `packages/chie/tests/Unit/ChieResponseTest.php` | 13135 | `0D1C06600C34CB3AD94E818DC6F1AE782B9DD65DDC894098E1137992EB0CD307` |
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 9323 | `5468B79F2AEF7E344F8C5B68E1A9A6F139D959D1BCA218075EBC9FDAC88DC121` |

Exclude the twelve Phase 4D caller paths—six directives and six reports—from manifests. Use the exact ordinal/no-terminal-LF construction already locked.

Accepted entry manifests:

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete partial implementation | 712 | 89259 | `54D39A0F5E87D40501CEF9D035746901DB4FAAD301ED886DE45A227B86B46533` | 7701235 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding twelve callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Stop before edits on any mismatch.

## 6. Accepted Phase 4D.4 analysis evidence

Accept and do not repeat the exact Phase 4D.4 sequential command. It completed without a runtime crash and reported seven findings:

- `ChieClaim.php`: two redundant-type findings;
- `ChieResponse.php`: four redundant-type findings;
- `ChieResponseTest.php`: one redundant assertion;
- finding identifiers limited to `function.alreadyNarrowedType`, `instanceof.alwaysTrue`, and `staticMethod.alreadyNarrowedType`.

No other file or finding is authorized by this correction.

## 7. Exact authorized edits

Modify exactly these three files and only as follows.

### 7.1 `packages/chie/src/ChieClaim.php`

Replace the constructor parameter annotation:

```php
/** @param list<ChieCitation> $citations */
```

with:

```php
/** @param array<array-key, mixed> $citations */
```

Do not alter the PHP `array` signature, property type, return type, constructor checks, matrix, serialization, exception message, or any other byte beyond the mechanical line-ending effect of the authorized replacement.

Reason: a public PHP `array` boundary can receive arbitrary keys and values at runtime. The constructor intentionally validates and detaches that raw input. The broadened input annotation makes the runtime boundary honest while the stored property and accessor remain `list<ChieCitation>`.

### 7.2 `packages/chie/src/ChieResponse.php`

In the private constructor docblock only, replace:

```php
* @param list<ChieClaim> $claims
* @param list<string>    $uncertainties
```

with:

```php
* @param array<array-key, mixed> $claims
* @param array<array-key, mixed> $uncertainties
```

Preserve the public `answered(...)` `list<ChieClaim>` annotation. Do not change any PHP signature, property/accessor type, validation expression, state matrix, source-set check, factory, serialization, or exception message.

Reason: the private constructor is the runtime validation boundary for arrays passed by the public factories. The raw-input annotation makes its defensive validation meaningful to PHPStan while successful storage remains narrowed to the declared list properties.

### 7.3 `packages/chie/tests/Unit/ChieResponseTest.php`

Delete exactly this redundant assertion:

```php
self::assertIsArray($decoded);
```

Do not add a replacement assertion and do not alter any other test byte except the mechanical line-ending effect of deleting that line. The immediately following exact key-order assertion remains the executable structural proof.

## 8. Prohibited correction techniques

Do not:

- remove or weaken `array_is_list`, `array_all`, `instanceof`, `is_string`, digest, claim, uncertainty, state, or provenance validation;
- add a PHPStan ignore, baseline, suppression, error-identifier exclusion, stub, cast, unreachable branch, or configuration change;
- change a public PHP signature, enum, response shape, diagnostic, exception, or successful/failed runtime behavior;
- modify any fourth source/test file;
- add files other than the caller-provided directive/report records;
- run an automatic formatter before the explicit focused dry-run gate;
- stage, commit, clean, restore, reset, stash, or modify history/refs/configuration.

## 9. Validation and single authorized analysis

After the three exact edits:

1. Confirm only the three authorized files changed relative to Section 5 and the other five implementation files remain exact.
2. Run `php -l` on the two modified production files and the modified test file.
3. Run exactly once:

```powershell
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/chie
```

Acceptance requires complete sequential analysis, exit code 0, and zero errors. If any source finding, internal error, crash, incomplete-result warning, or nonzero exit occurs, stop without retry.

After a pass, resume with the focused PHP CS Fixer dry run and every subsequent Phase 4D.2/4D.3 gate exactly as authorized by Phase 4D.4. The final literal `composer quality` remains mandatory and is not replaced by the sequential focused proof.

No separate pre-change `composer quality` rerun is required; preserve its accepted evidence of 633 tests and 14690 assertions.

## 10. Final invariants

Full success still requires:

- exactly eight total Phase 4D implementation files changed from their original Phase 4D.2 identities;
- exactly three of those eight changed from their Phase 4D.5 entry identities;
- 36 focused Chie tests and 633 complete tests;
- all adversarial construction/disclosure and valid CLI regressions passing;
- zero focused and canonical PHPStan errors;
- zero style findings;
- zero security advisories;
- Knowledge artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`;
- Knowledge source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`;
- 31 Knowledge sources;
- protected and historical manifests unchanged;
- final complete-manifest change limited to the same eight implementation files;
- no staging, commit, dependency, configuration, documentation, caller-record, or history mutation;
- Phase 4E not begun.

## 11. Completion report

Populate only `docs/task_reports/phase_4/Phase_4D_5_correction.md` after every non-report gate passes. Use the standard 16 report sections required by Phase 4D.2 and include:

- independent directive identity;
- all earlier caller identities;
- entry state and manifests;
- all eight entry identities;
- the exact three replacements and their final identities;
- Phase 4D.4 finding evidence;
- syntax and one sequential PHPStan result;
- every resumed focused, adversarial, CLI, artifact, canonical, and preservation result;
- final status/manifests;
- confirmation that no unauthorized file changed;
- report bytes and uppercase SHA-256 after finalization.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 12. Acceptance criteria

Phase 4D.5 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, eight files, and three manifests authenticate.
3. Exactly the three authorized edits are applied.
4. All runtime validation remains present and behaviorally unchanged.
5. Three syntax checks pass.
6. The exact sequential PHPStan command runs once and reports zero errors.
7. Every later Phase 4D gate passes without unauthorized retry.
8. Focused/complete tests remain exactly 36/633.
9. Artifact and source-set identities remain exact.
10. Only the same eight total implementation files differ from original Phase 4D.2 state.
11. Protected/historical content and all caller records remain immutable.
12. Nothing is staged or committed.
13. Only the Phase 4D.5 report is populated after non-report success.
14. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
