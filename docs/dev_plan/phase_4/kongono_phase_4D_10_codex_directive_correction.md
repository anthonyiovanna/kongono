# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4D.10

## Accept Ephemeral Ignored PHPStan Cache and Resume Without Reanalysis

## 1. Purpose and correction

This is the architect-authenticated correction for Phase 4D.9's cache-preservation contradiction.

Phase 4D.9's exact sequential PHPStan command completed successfully with:

- process-local memory limit 512 MB;
- sequential `--debug` execution;
- exit code 0;
- complete analysis;
- zero PHPStan errors;
- no internal error, crash, or incomplete result;
- normal PHP restored and independently observed at 128 MB afterward;
- no Git-visible file, source, test, configuration, dependency, index, ref, or history mutation.

The command also updated `.cache/phpstan`, which is explicitly ignored by repository `.gitignore`. Phase 4D.9 incorrectly prohibited any cache mutation even though PHPStan may update ephemeral internal cache/container metadata during an otherwise successful analysis.

This correction accepts the observed Phase 4D.9 cache transition as non-authoritative ephemeral tooling state, preserves the successful zero-error PHPStan result, and authorizes resumption at the focused style gate without rerunning PHPStan.

This does not weaken source analysis, adopt cache contents as framework evidence, authorize cache deletion/restoration, or allow a Git-visible mutation. All substantive Phase 4D.2 rules as corrected through Phase 4D.9 remain controlling except the impossible zero-cache-mutation requirement.

The executor is an executor, not an architect. Stop, report expected and observed values, preserve the worktree, index, history, and caller records, and ask rather than guessing or expanding scope. Do not begin Phase 4E.

## 2. Independent authentication

Directive path:

`docs/dev_plan/phase_4/kongono_phase_4D_10_codex_directive_correction.md`

Active report path:

`docs/task_reports/phase_4/Phase_4D_10_correction.md`

The architect will supply this directive's byte count and SHA-256 independently outside this file. Do not self-authenticate from local values. Stop before further validation if the supplied identity is absent or differs.

The active report must initially be an ordinary untracked and unstaged zero-byte file, absent from `HEAD`, with SHA-256:

`E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`

## 3. Caller records

Reauthenticate every caller record listed in Phase 4D.9 Section 3 exactly. Additionally authenticate:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4D_9_codex_directive_correction.md` | 13837 | `2D2C248B8B2D75956EE87C2C8D01A6AA8B73D09E33A4269D29973EB9392993E9` |
| `docs/task_reports/phase_4/Phase_4D_9_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All earlier Phase 4D directives and reports are immutable. Populate only the Phase 4D.10 report after every remaining non-report gate passes.

## 4. Exact entry state

After the caller places the Phase 4D.10 directive and report placeholder, require exactly:

- branch `main`;
- `HEAD` `6aacafc92f07b567818173d660e2e182dfa83d23`;
- 26 tracked modifications;
- 106 untracked paths;
- 132 total status paths;
- zero staged paths;
- 735 Git-visible paths;
- 629 tracked files;
- 16 package directories and manifests;
- 168 production PHP files;
- 163 test PHP files;
- 333 files in canonical style scope;
- 64 ADR files;
- 17 files in `docs/dev_plan/phase_4`;
- 17 files in `docs/task_reports/phase_4`.

No source, test, dependency, Composer, lockfile, artifact, documentation, configuration, index, ref, branch, tag, remote, or history mutation is authorized during Phase 4D.10.

## 5. Exact ten-file implementation state

Authenticate the ten files from Phase 4D.9 Section 5 exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/src/Exception/InvalidChieValueException.php` | 1278 | `1FEF75BC83EEBFC33B7DB13849AB4D8482169903681F52452E7A15E641DA754C` |
| `packages/chie/src/ChieCitation.php` | 2449 | `2A80C50DCBF8CEBCF00C6A489C6E60AB31DBEC2BDD438D622A46BDE517C4141E` |
| `packages/chie/src/ChieClaim.php` | 2642 | `0596443D4E73A4DF3A481AAD4FEC69CC74BBB521577190C46CE2C9F613A335F6` |
| `packages/chie/src/ChieResponse.php` | 5428 | `488D4D0031B99EE770B861465AF3758B862ED44D01731A0F3A8C06F971A66FF7` |
| `packages/chie/src/Diagnostic/ChieDiagnostic.php` | 7032 | `C4B84AA09EF4601789A3F9222E0EDF3D6133E1933826E356A8B8875DF2D7A3DD` |
| `packages/chie/src/Exception/ChieException.php` | 2401 | `B9714B2E86BD245A70F8E923AEBE26D1F8FF28CCF61B7BAC045B7F8397429B05` |
| `packages/chie/src/Internal/CanonicalJson.php` | 1858 | `A0867209E894FB12BE4C7C92E70D59589D5055A14FDC7273D3E41ADC102EF287` |
| `packages/chie/tests/Unit/ChieResponseTest.php` | 14673 | `970F300715EA152FF93BAAC443D758A7CD60EC2E64F9343D8CC09BC9DAD32C2C` |
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 11860 | `630E039FAE414D0B5976DC2D086FC2DC32EC46344DE33BD4DF3C4E3F03087931` |
| `tests/Architecture/ApiStatusConventionTest.php` | 105025 | `3A2A02A321675D229A57EC6F8B5B9A4BE3020E3DD28C31CB22391D1CB0A6BB38` |

Stop if any differs. No edit or formatting is authorized.

## 6. Exact Git-visible manifests

Exclude the twenty-two Phase 4D caller paths—eleven directives and eleven reports. Use the locked ordinal/no-terminal-LF construction.

| Set | Records | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Complete partial implementation | 713 | 89388 | `0C17F2671282D27BB31DE783DC3936830C1C06F76135F7B7B8D943210572E010` | 7710114 |
| Protected | 652 | 82872 | `EA51BB81FE600563534B20209D29D2A9B5DD7BA1701736678268FE824DAB2F56` | 6865953 |
| Historical excluding twenty-two callers | 230 | 28846 | `CB26B97B64D72E59A081869BA6E92FCADBD930565A81E088AEA68989AB2E63DB` | 4768984 |

Complete endpoints remain `.editorconfig` and `tests/Tooling/EnvironmentTest.php`. `.cache/phpstan` is ignored and excluded from every Git-visible manifest. Stop on any Git-visible mismatch.

## 7. Accepted cache evidence and corrected policy

Accept the Phase 4D.9 cache transition as historical execution evidence:

| Cache state | Files | Manifest bytes | SHA-256 | Aggregate bytes |
|---|---:|---:|---|---:|
| Before Phase 4D.9 PHPStan | 1594 | 242740 | `B27589BB76EC599CD42F9DA61611BE753F4D0FC041CC102AC03B22E8B0EE1A36` | 73771617 |
| After Phase 4D.9 PHPStan | 1594 | 242740 | `5212ADB9DD144B62DFEE38494D13E213D69D50C56BD53866224A85B0CAE4283E` | 73798070 |

The aggregate increase was 26453 bytes. Observed changed cache paths included:

- `.cache/phpstan/resultCache.php`;
- `.cache/phpstan/cache/nette.configurator/container.journal`.

Both are ignored by `.gitignore` through the existing `*.cache` rule. Their observed current individual identities include:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `.cache/phpstan/resultCache.php` | 317409 | `CB0D4875BA77FAD835DE61B467D2373A4246A6DCA35F4EFD42F32BBE70819EDE` |
| `.cache/phpstan/cache/nette.configurator/container.journal` | 2688 | `DA3B72B565D53013BC8D5E0101A285B0A18C83933C8334EA0E21C41C6D0A34CE` |

These values authenticate the observed transition only. They are not a framework artifact, caller record, reproducible build output, or continuing byte-lock. Do not stop merely because a later authorized PHPStan invocation updates ignored `.cache/phpstan` state.

Corrected rule:

- ignored PHPStan cache mutation caused by an authorized PHPStan or canonical quality process is permitted ephemeral tooling state;
- cache paths remain excluded from Git-visible manifests and completion artifacts;
- the executor must not manually edit, delete, restore, copy, stage, commit, inspect content for semantic authority, or intentionally prewarm the cache;
- any cache path becoming Git-visible, staged, tracked, or non-ignored is a mandatory stop;
- source, test, configuration, lockfile, dependency, caller, index, ref, and history preservation remains strict.

This corrected rule supersedes only Phase 4D.9's zero-cache-mutation wording.

## 8. Accepted Phase 4D.9 PHPStan proof

Accept without rerun:

```text
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug --memory-limit=512M packages/chie tests/Architecture/ApiStatusConventionTest.php
exit code: 0
result: [OK] No errors
```

Also accept:

- pre-run normal PHP: 128M, `C:\php\php.ini`, no scanned INI, `PHP_INI_SCAN_DIR` absent;
- post-run normal PHP: identical;
- `C:\php\php.ini`: 72032 bytes, SHA-256 `9311CD40CC8A466A1F8D6BAEF9F0F6336A9A19731835169F1987D4A48BB78841`;
- no PHP or repository configuration changed.

Do not rerun focused PHPStan in Phase 4D.10.

## 9. Exact resume point

After Sections 2 through 8 pass, resume at the Phase 4D.8 cache-disabled focused style gate:

```powershell
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no packages/chie/src packages/chie/tests packages/chie/bin/kongono-chie tests/Architecture/ApiStatusConventionTest.php
```

Then continue, in order:

1. focused PHPUnit specified by Phase 4D.8;
2. adversarial construction and disclosure proofs;
3. all valid CLI regressions;
4. Knowledge artifact/source-set/31-source proof;
5. final literal `composer quality`;
6. final complete/protected/historical manifests;
7. exact file boundary, API status, autoload, disclosure, Git, index, history, refs, configuration, dependency, documentation, encoding, stream, and ordinary-file preservation;
8. report population.

Focused Chie count remains 36 and complete count remains 633. Canonical style scope remains 333 files. Final canonical quality must still report zero PHPStan errors and zero advisories.

Only one identical approved-access retry remains permitted if the locked Composer audit is blocked solely by managed network/cache access. Stop without retry on any other failure.

## 10. Mutation boundary and prohibitions

Phase 4D.10 authorizes no Git-visible non-report mutation. Do not edit or format source/test files, configuration, Composer files, documentation, artifacts, dependencies, or caller records.

Do not manually mutate `.cache/phpstan`; merely tolerate incidental changes caused by later authorized tooling. Do not clean or restore it.

Do not stage, commit, tag, create/delete refs, alter history/configuration, clean, reset, restore, stash, populate an earlier report, or begin Phase 4E.

Only `docs/task_reports/phase_4/Phase_4D_10_correction.md` may change, after every non-report gate passes.

## 11. Completion report

Populate the Phase 4D.10 report only after full success. Use the standard 16 sections and include:

- independent directive and every earlier caller identity;
- corrected entry state, ten file identities, and Git-visible manifests;
- the Phase 4D.9 cache transition and corrected ephemeral-cache classification;
- accepted 512 MB sequential zero-error PHPStan and unchanged normal PHP proof;
- every resumed focused style/test, adversarial, CLI, artifact, canonical, final manifest, and preservation result;
- confirmation that cache state was never treated as semantic authority or manually changed;
- confirmation that all cache paths remained ignored and non-Git-visible;
- final report byte count and uppercase SHA-256.

The final line before the report identity appendix remains exactly:

`Phase 4D is accepted only after architect review of this Phase 4D.2 correction report; Phase 4E was not begun.`

## 12. Acceptance criteria

Phase 4D.10 completes only when:

1. Independent directive authentication passes.
2. Caller records, entry state, ten identities, and Git-visible manifests match.
3. Phase 4D.9's complete zero-error PHPStan result is preserved without rerun.
4. Ignored cache transition is accepted only as ephemeral tooling state.
5. Cache paths remain ignored, untracked, unstaged, and outside manifests.
6. Focused style and focused tests pass.
7. All six former disclosure failures pass without weakening.
8. Adversarial and valid CLI regressions pass.
9. Artifact/source-set identities and 31-source count remain exact.
10. Final canonical quality passes with zero advisories, zero PHPStan errors, 0 of 333 style findings, and 633 tests.
11. Protected/historical manifests remain exact and complete delta remains closed.
12. No unauthorized Git-visible mutation, staging, commit, ref, history, dependency, or configuration change occurs.
13. Only the Phase 4D.10 report is populated after non-report success.
14. Phase 4E is not begun.

STOP

REPORT EXPECTED AND OBSERVED VALUES

PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS

DO NOT GUESS OR EXPAND SCOPE
