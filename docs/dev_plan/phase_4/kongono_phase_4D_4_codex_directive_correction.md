# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.4

## Sequential Focused PHPStan Resume After Parallel Worker Corruption

## 1. Purpose and authority

This is the architect-authenticated resume correction for Phase 4D. Phase 4D.3 correctly applied changes to exactly the eight authorized Chie files and passed syntax checks, but its focused PHPStan process failed inside a parallel worker with Windows process exit code `-1073740791` and `zend_mm_heap corrupted`. PHPStan explicitly reported that the result was incomplete. No source-level finding was reported.

This correction:

1. authenticates the exact accepted partial implementation state;
2. accepts the already completed Phase 4D.3 pre-change quality and syntax evidence;
3. authorizes exactly one sequential focused PHPStan execution using PHPStan's supported `--debug` option, which disables parallel processing and the result cache;
4. authorizes continuation at the next Phase 4D.2/4D.3 gate only if that run completes with exit code 0 and zero analysis errors.

It does not waive PHPStan, alter its rules, change memory limits, permit a repository configuration change, or treat an incomplete analysis as passing.

All substantive production rules, eight-file boundary, tests, later validation, artifact preservation, prohibited scope, stop rules, and completion requirements of Phase 4D.2 as corrected by Phase 4D.3 remain controlling.

The executor is an executor, not an architect. Do not guess or expand scope. Do not begin Phase 4E.

## 2. Independent authentication and caller records

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_4_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_4_correction.md`

The architect will supply this directive's byte count and SHA-256 outside this file. Do not self-authenticate from the local file. If the independent identity is absent or differs, stop before further validation.

The active Phase 4D.4 report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

Authenticate all earlier Phase 4D caller records:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_codex_directive.md` | 53533 | `504EF58E8999FB0BFF61A630BA0FB2594363972C13C04C2BC3D169B807A2BAC9` |
| `docs/dev_plan/phase_4/kongono_phase_4D_1_codex_directive_correction.md` | 13286 | `BCAC386F0468CBD592F686ADD979B44E5996DB58CC970FA0006A314C83D7BB9C` |
| `docs/dev_plan/phase_4/kongono_phase_4D_2_codex_directive_correction.md` | 21229 | `EBAA4F85E60F205DC943B6A78186E1F3B6B49044392C56FB49136036066844E0` |
| `docs/dev_plan/phase_4/kongono_phase_4D_3_codex_directive_correction.md` | 10606 | `3E893F2F556A69DC2612871AEE7E90A494BC5D13AE59648E5C540387BDC8D67B` |
| `docs/task_reports/phase_4/Phase_4D.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_1_correction.md` | 30739 | `497FF940A9204B4BF68E3A68664994177430A469756994475B068AC84E8B9FD8` |
| `docs/task_reports/phase_4/Phase_4D_2_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_4/Phase_4D_3_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier directives and reports are immutable. Only the Phase 4D.4 report may be populated, and only after every remaining non-report gate passes.

## 3. Corrected entry state

After the caller places the Phase 4D.4 directive and zero-byte report, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 93 untracked paths;
- 119 total status paths;
- zero staged paths;
- 722 Git-visible paths;
- 629 tracked files;
- 16 package directories and 16 package manifests;
- 167 production PHP files;
- 163 test PHP files;
- 332 PHP files in canonical style scope;
- 64 ADR files;
- 11 files in `docs/dev_plan/phase_4`;
- 11 files in `docs/task_reports/phase_4`.

Require the same branch, commit, index, refs, history, Git configuration, Composer files, lockfile, Knowledge artifact, and protected/historical content accepted by Phase 4D.3. No staging or commit is authorized.

## 4. Exact accepted partial implementation identities

Exactly these eight Phase 4D.2-authorized files differ from their original authenticated identities. Their accepted Phase 4D.4 entry identities are:

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

Stop if any differs. Do not edit, format, restore, or otherwise mutate a file before this authentication passes.

## 5. Partial-state manifest

For Phase 4D.4 manifest reconstruction, exclude exactly the ten Phase 4D caller paths: the five directive paths from Phase 4D through Phase 4D.4 and the five corresponding report paths.

Use ordinal case-sensitive path ordering, UTF-8 without BOM, LF between records, no terminal LF, and records of forward-slash path, tab, decimal byte count, tab, uppercase SHA-256.

The accepted partial implementation complete manifest is:

- records: `712`;
- manifest bytes: `89259`;
- SHA-256: `54D39A0F5E87D40501CEF9D035746901DB4FAAD301ED886DE45A227B86B46533`;
- aggregate file bytes: `7701235`;
- first path: `.editorconfig`;
- last path: `tests/Tooling/EnvironmentTest.php`.

The protected and historical manifests remain exactly:

| Set | Records | Manifest bytes | SHA-256 | Aggregate file bytes |
|---|---:|---:|---|---:|
| Protected content | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical caller archive excluding ten callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Stop before the authorized analysis if any manifest scalar differs.

## 6. Accepted completed evidence

Do not rerun the following solely because of this correction:

- Phase 4D.3 directive/caller/authority authentication;
- pre-change canonical `composer quality`, which passed with zero advisories, zero PHPStan errors, 0 of 332 style findings, and 633 tests with 14690 assertions;
- the Phase 4D.2 eight-file implementation;
- syntax checks for all eight authorized files, which passed.

The parallel-worker crash is neither a source pass nor a source failure. It is replaced only by the exact sequential proof below.

## 7. One authorized sequential PHPStan command

After Sections 2 through 6 pass, run exactly once:

```powershell
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/chie
```

For the installed PHPStan command, `--debug` is the supported command-line mechanism that disables parallel processing and the result cache. It does not lower the rule level, ignore errors, apply a baseline, suppress findings, change memory limits, or modify repository configuration.

Acceptance requires:

- exit code `0`;
- complete analysis of the focused Chie path;
- zero PHPStan errors;
- no internal error, heap-corruption message, crash, or incomplete-result warning;
- no repository-file, configuration, dependency, index, or history mutation.

The command is authorized exactly once. If it crashes, reports an internal error, reports any source finding, or exits nonzero, stop immediately. Do not rerun it, switch flags, alter memory, clear caches, edit configuration, or continue.

## 8. Resume after a passing sequential proof

Only after Section 7 passes:

1. Resume at the focused PHP CS Fixer gate that followed focused PHPStan in Phase 4D.3.
2. Continue the focused PHPUnit, adversarial-construction, CLI-regression, artifact-identity, final canonical `composer quality`, manifest, preservation, and report gates exactly as required by Phase 4D.2 and corrected by Phase 4D.3.
3. Preserve the focused count of exactly 36 tests and complete count of exactly 633 tests. Report the increased assertion counts.
4. Preserve the Knowledge artifact SHA-256 `bb713577f7f8a9d2f2317e44f1e36dae1cb14fbb27fb10039d72995b3e55447c`, source-set SHA-256 `08bbb97e2030a22caea1c6f7c52809a063c6664dbe86df1e32de934daa45a320`, and 31-source count.
5. The final literal `composer quality` remains mandatory. Its PHPStan stage is not waived or replaced by the focused sequential proof.
6. The existing Phase 4D retry rule remains unchanged: only one identical approved-access retry when the locked Composer audit is blocked solely by managed network/cache access.

If any later gate fails, follow the existing immediate stop rule. This directive does not authorize a second non-network retry.

## 9. Mutation boundary

No new production, test, documentation, configuration, dependency, or artifact mutation is authorized by Phase 4D.4. The eight authenticated files in Section 4 are the complete accepted implementation delta. They may not be changed further unless a later gate exposes a genuine source defect and new architect authority is issued.

The only file that may subsequently change is:

`docs/task_reports/phase_4/Phase_4D_4_correction.md`

and only after every non-report gate passes.

Do not stage, commit, tag, create/delete refs, modify history, change Git configuration, clean, restore, reset, stash, or begin Phase 4E.

## 10. Completion report

Populate only the Phase 4D.4 report after full success. Use the standard 16 sections required by Phase 4D.2. Include:

- this directive's independently supplied identity;
- all earlier caller identities;
- corrected 26/93/119/0 and 722/629 entry state;
- the eight partial implementation identities;
- all three partial-state manifest results;
- accepted pre-change quality and syntax evidence;
- the exact sequential PHPStan command, exit code, complete zero-error result, and confirmation that parallel processing/result cache were disabled by `--debug`;
- every subsequently resumed focused, adversarial, CLI, artifact, canonical, manifest, and preservation result;
- explicit confirmation that no source file changed during Phase 4D.4;
- report byte count and uppercase SHA-256 after its contents are final.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 11. Acceptance criteria

Phase 4D.4 completes only when:

1. Independent directive authentication passes.
2. Active report and all earlier caller records authenticate.
3. Corrected entry state matches exactly.
4. All eight partial implementation files match Section 4.
5. Complete, protected, and historical manifests match Section 5.
6. Accepted prior quality and syntax evidence is preserved.
7. The exact sequential PHPStan command runs once and completes with zero errors.
8. No incomplete result or internal runtime error occurs.
9. Every remaining Phase 4D.2/4D.3 gate passes.
10. Focused and complete test counts remain 36 and 633 respectively.
11. The Knowledge artifact identities and source count remain exact.
12. No implementation, documentation, configuration, dependency, Git, or historical caller mutation occurs during Phase 4D.4.
13. Only the Phase 4D.4 report is populated after full non-report success.
14. Nothing is staged or committed.
15. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
