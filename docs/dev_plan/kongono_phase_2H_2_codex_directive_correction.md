# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.2
## No-Scanned-INI Boolean Predicate and Approved-Access Gate B Resume

**Directive type:** exact validation-predicate correction, approved-access retry, checkpoint reconciliation, and completion resume

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.2

**Depends on:** authenticated Phase 2H.1 stopped without repository mutation after a network-blocked Gate B run and successful cleanup

---

## 1. Mission

Resume Phase 2H after correcting one executor-side PHP restoration predicate.

PHP's API contract is:

```php
php_ini_scanned_files(): string|false
```

When no additional INI file was scanned, the correct result is boolean
`false`. The Phase 2H.1 wrapper incorrectly treated that value as a scanned-file
entry even though all independent restoration evidence passed:

```json
{"memory_limit":"128M","loaded":"C:\\php\\php.ini","scanned":false}
```

Phase 2H.2 therefore authorizes exactly these corrections:

1. after overlay cleanup, treat `php_ini_scanned_files() === false` as the exact
   required representation of zero scanned INI files;
2. do not cast, split, enumerate, or stringify that boolean as a path;
3. retain the stricter exact-one-string-path predicate while the temporary
   overlay is active;
4. authorize one fresh Gate B overlay-backed `composer quality` attempt with
   approved network/cache access from the outset; and
5. resume the original Phase 2H implementation and corrected checkpoint after
   Gate B passes.

The previous run failed its locked advisory step only because Packagist was
unreachable (`curl error 7`, exit `100`). Its temporary overlay was completely
removed, fresh PHP returned to `128M`, the scan environment variable is absent,
and no repository, index, history, caller record, or permanent environment
state changed.

Preserve both earlier Phase 2H reports as zero-byte historical records. Populate
only the Phase 2H.2 report after all implementation, validation, checkpoint, and
post-commit gates pass.

---

## 2. Narrow supersession

This correction supersedes Phase 2H and Phase 2H.1 only where they:

- failed to define boolean `false` as the exact post-cleanup no-scanned-file
  state;
- prevent the newly authorized approved-access Gate B attempt;
- identify an earlier Phase 2H report as the active completion report; or
- use checkpoint counts that do not include the Phase 2H.2 caller records.

Everything else remains locked, including:

- the original Phase 2H fixture architecture and twelve-PHP-file inventory;
- the fourteen created and twenty-two modified implementation/documentation
  boundaries;
- the corrected `New-Item -ItemType Directory -Path` operation;
- prevalidated GUID temp-path safety;
- exact temporary INI bytes and `512M` limit;
- literal unchanged `composer quality`;
- exact process-variable removal before file/directory cleanup;
- normal `128M` restoration;
- all black-box, trust, persistent-runtime, documentation, and prohibited-scope
  requirements;
- the exact checkpoint subject and parent; and
- stop-on-mismatch behavior.

This correction does not waive the advisory audit, allow offline audit results,
or accept a partial Composer quality run.

---

## 3. Independent correction authentication

Before approved-access quality, temporary state, repository mutation, staging,
or Git writes, compare this correction with the byte count and SHA-256 supplied
independently by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

---

## 4. Required authorities and caller records

Read completely:

```text
AGENTS.md
docs/dev_plan/kongono_phase_2H_codex_directive.md
docs/task reports/Phase_2H.md
docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md
docs/task reports/Phase_2H_1_correction.md
docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md
```

The original Phase 2H directive must remain:

```text
bytes      57027
SHA-256    29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC
```

The Phase 2H.1 correction must remain:

```text
bytes      20701
SHA-256    5302D1A8EE111F0E03EB5BAAA6EF0FF199A1D449A6F55A696032E14D9203F048
```

Both earlier report placeholders must remain ordinary, untracked, unstaged,
absent from `HEAD`, zero bytes, and have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The exact paths are:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
```

They are immutable historical records. Do not populate, truncate, replace,
rename, or delete them. After all pre-commit gates pass, stage and commit both
as intentional zero-byte files.

The caller must additionally provide:

```text
docs/task reports/Phase_2H_2_correction.md
```

At entry it must be ordinary, untracked, unstaged, absent from `HEAD`, zero
bytes, and have the empty-file digest above. Keep it zero-byte through every
non-report gate and never stage it.

All earlier Phase 2 authorities and records retain the identities required by
the original directive. Verify them before Gate B.

---

## 5. Accepted stopped state

Before the two Phase 2H.2 caller paths are placed, require:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        151
total status paths     178
staged paths           0
Git-visible paths      509
normal PHP memory      128M
PHP_INI_SCAN_DIR       absent
Phase 2H report        zero bytes
Phase 2H.1 report      zero bytes
```

The accepted Phase 2H.1 stopped execution established:

- exact Phase 2H.1 authentication;
- exact `27/151/178/0`, 509-visible, 358-tracked entry state;
- exact reconstruction of the original 505-path manifest;
- successful corrected directory creation and path proof;
- successful active-overlay PHP API proof at `512M`;
- execution of literal `composer quality`;
- audit failure only from managed Packagist network denial;
- complete environment and temporary-path cleanup;
- fresh normal PHP at `128M`, main `C:\php\php.ini`, no scanned files;
- absent `PHP_INI_SCAN_DIR`; and
- zero repository, index, history, or caller-record mutations.

After the caller adds only the Phase 2H.2 directive and zero-byte report,
require correction entry state:

```text
tracked modifications  27
untracked paths        153
total status paths     180
staged paths           0
Git-visible paths      511
tracked files          358
```

The only additions are:

```text
docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md
docs/task reports/Phase_2H_2_correction.md
```

Reconstruct the original authenticated snapshot by excluding exactly the six
Phase 2H-family caller paths. Require:

```text
records          505
manifest bytes   62292
SHA-256          A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
```

Stop if any accepted fact differs. Do not repair or infer a new baseline.

---

## 6. Exact PHP scanned-file predicates

### 6.1 Machine-readable state

For overlay and restoration proof, obtain one JSON object from PHP using the
three native APIs:

```php
[
    'memory_limit' => ini_get('memory_limit'),
    'loaded' => php_ini_loaded_file(),
    'scanned' => php_ini_scanned_files(),
]
```

Encode with `JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR`. Decode in PowerShell
without converting property kinds. Validate each property's runtime type before
its value.

Do not validate the human-formatted `php --ini` display. Do not use truthiness,
string interpolation, array wrapping, `@(...)`, implicit enumeration, or a
generic path splitter before distinguishing `string` from boolean `false`.

### 6.2 Active overlay predicate

While `PHP_INI_SCAN_DIR` points to the exact temporary directory, require:

```text
memory_limit  string exactly 512M
loaded        string path normalizing exactly to C:\php\php.ini
scanned       non-empty string
```

Split the `scanned` string on commas and line endings, trim empty entries,
normalize remaining paths, and require exactly one path equal to the exact
temporary INI. Boolean `false` is invalid while the overlay is expected active.

### 6.3 Post-cleanup predicate

After the exact `finally` cleanup and in a fresh ordinary PHP process, require:

```text
memory_limit  string exactly 128M
loaded        string path normalizing exactly to C:\php\php.ini
scanned       boolean exactly false
```

The acceptance predicate for `scanned` is logically equivalent to:

```powershell
($state.scanned -is [bool]) -and ($state.scanned -eq $false)
```

If it is boolean `false`, it means **zero scanned INI files** and passes. Do not
cast it to a string, split it, count it as one array item, compare it with path
text, or reject it merely because it is a JSON property.

Any string, array, object, number, null, or boolean `true` in the post-cleanup
`scanned` property fails. Also independently require:

```powershell
Test-Path Env:PHP_INI_SCAN_DIR
```

to be exactly `$false`, and require both exact temporary paths absent.

---

## 7. Fresh approved-access Gate B attempt

The architect authorizes one fresh Gate B attempt with the managed environment's
approved network/cache access from the outset.

Use the Phase 2H.1 corrected creation line only after exact path prevalidation:

```powershell
New-Item -ItemType Directory -Path $tempDir -ErrorAction Stop
```

Then perform the unchanged exact overlay, active-state proof, and literal:

```powershell
composer quality
```

This approved-access execution must not change the command, Composer policy,
audit mode, repositories, cache configuration, memory limit, PHPStan workers,
tests, or repository files.

In `finally`, retain the exact order:

1. `Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue`;
2. prove the variable absent;
3. delete the exact temporary INI with literal-path removal;
4. delete the exact now-empty GUID directory non-recursively with literal-path
   removal;
5. prove both paths absent; and
6. apply the exact boolean-false post-cleanup predicate in Section 6.3.

The prior network-blocked run is accepted historical evidence and is not
repeated. The new approved-access attempt is the sole fresh Gate B execution
authorized by this correction.

If approved-access quality fails for any reason, clean up and stop. Do not run a
second Gate B attempt.

After Gate B passes, the original Phase 2H authorization for final pre-staging
and post-commit canonical quality remains. Each of those runs uses a fresh
overlay, the same corrected boolean predicate, and at most its original one
approved-access retry only for an initial managed network/cache denial.

---

## 8. Resume original Phase 2H

After corrected Gate B passes, execute the original Phase 2H implementation
without architectural change:

- exactly twelve new PHP files under `tests/BlackBox`;
- exactly ADR 0050 and the runnable-fixture model;
- exactly twenty-two authorized existing-file reconciliations;
- no production package/source/test, dependency, manifest, lock, or frozen
  tooling change;
- exact fixture modes, scenarios, routes, middleware order, delivery result,
  trusted/generic behavior, limits, exit codes, and sequential-isolation proof;
- focused and canonical validation;
- strict dev/no-dev/dev autoload proof;
- exact Phase 2 checkpoint; and
- no Phase 3 work.

Apply only the report-path and count substitutions in Sections 9-11 below.

---

## 9. Corrected final inventories before staging

Framework inventories remain:

```text
package directories       13
package manifests         13
production PHP files      100
test PHP files            130
style-covered PHP files   230
production diagnostic IDs 113 unique
locked production         17
locked development        66
```

After implementation and before staging require:

```text
tracked modifications  27
untracked paths        167
total status paths     194
staged paths           0
Git-visible paths      525
tracked files          358
```

The increase beyond the authenticated 505-path snapshot is exactly:

- six Phase 2H-family caller records; and
- fourteen original Phase 2H created paths.

Where an earlier directive requires `27/163/190/0`, `27/165/192/0`, 521 or 523
Git-visible paths, replace it with `27/167/194/0` and 525 Git-visible paths.

---

## 10. Corrected checkpoint boundary

### 10.1 Staging set

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. `docs/dev_plan/kongono_phase_2H_codex_directive.md`;
3. zero-byte `docs/task reports/Phase_2H.md`;
4. `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md`;
5. zero-byte `docs/task reports/Phase_2H_1_correction.md`;
6. `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md`; and
7. the fourteen original Phase 2H created paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_2_correction.md
```

Materialize the exact 193 paths as a literal ordinal path array and preserve
every original pathspec-safe, no-wildcard staging rule.

After staging require:

```text
staged paths                  193
staged modified paths          27
staged added paths            166
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 524
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.2 report. Both earlier report
files are intentional staged empty blobs.

### 10.2 Tree and commit

Require staged blob/worktree equality, no conflicts, exact modes, and clean
`git diff --cached --check`. `git write-tree` must contain exactly 524 paths,
all `100644`.

Use the unchanged exact subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      524
tree modes      all 100644
delta paths     193
modified        27
added           166
deleted         0
```

All original authorship, hook, signing, branch, ref, remote, tag, history, and
prohibited Git-operation rules remain exact.

Where an earlier directive says 189 or 191 staged paths, 162 or 164 additions,
520 or 522 index/tree paths, replace those values with 193 staged, 166 added,
and 524 index/tree paths.

### 10.3 Post-commit state

Before report population require:

```text
tracked files          524
tracked modifications 0
staged paths           0
untracked paths        1
total status paths     1
```

The sole path must be the zero-byte Phase 2H.2 report. Both earlier Phase 2H
reports must be present in `HEAD` as exact empty blobs.

Run all original focused, direct fixture, post-commit canonical overlay,
inventory, documentation, link, file-safety, mode, and history proofs with the
corrected scanned-file predicate.

Only then populate the Phase 2H.2 report; it remains the sole untracked path.

---

## 11. Corrected acceptance substitutions

Every original Phase 2H and Phase 2H.1 implementation criterion remains unless
replaced exactly here:

- active report is `Phase_2H_2_correction.md`;
- Phase 2H and Phase 2H.1 reports remain zero-byte and are committed;
- pre-stage state is `27/167/194/0`, 525 visible, 358 tracked;
- staged state is 193 paths: 27 modified, 166 added, zero deleted;
- index and checkpoint tree contain 524 `100644` paths;
- commit delta is 193 paths: 27 modified, 166 added, zero deleted;
- post-commit tracked file count is 524; and
- Phase 2H.2 report is the sole final status path.

Add these criteria:

1. Phase 2H.2 correction authenticated independently;
2. Phase 2H.1 stopped state and both zero-byte reports matched;
3. active overlay proof required a non-empty scanned-files string containing
   exactly the temporary INI;
4. post-cleanup proof required `scanned` to be typed boolean `false`;
5. boolean `false` was accepted as zero scanned INI files and never enumerated
   or treated as a path;
6. exact scan-variable and temporary-path absence independently passed;
7. one fresh approved-access Gate B execution ran literal `composer quality`;
8. the locked advisory audit completed successfully rather than being waived;
9. every later overlay used the same corrected predicates and exact cleanup;
10. both earlier reports remained zero-byte through checkpoint; and
11. only the Phase 2H.2 report was populated after every other gate.

Any failure means status cannot be Complete.

---

## 12. Prohibited scope

The complete prohibited scope from Phase 2H and Phase 2H.1 remains. Also do not:

- treat boolean `false` as a filename, non-empty array, or restoration failure;
- accept null, empty string, boolean true, array, or another value as equivalent
  to false;
- weaken the active-overlay one-string-path requirement;
- parse the presentation-oriented `php --ini` output;
- skip or waive the network-backed locked advisory audit;
- change Composer repositories, cache paths, policy, or command;
- run more than the one newly authorized approved-access Gate B attempt;
- populate either earlier report;
- stage the Phase 2H.2 report;
- change the original fixture architecture or file boundary;
- alter permanent PHP/environment configuration; or
- begin Phase 3.

---

## 13. Stop rules

Stop and preserve exact state if:

- correction identity, caller records, stopped state, or original manifest
  differs;
- active overlay `scanned` is not exactly the one temporary path string;
- post-cleanup `scanned` is not typed boolean `false`;
- process scan variable or temporary path remains;
- fresh normal PHP is not exactly `128M` with main `C:\php\php.ini`;
- approved-access Gate B quality fails;
- any original implementation, black-box, documentation, quality, autoload,
  staging, tree, commit, or post-commit gate fails;
- corrected counts differ; or
- a prohibited operation would be needed.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Retain exact staged-index preservation after any post-staging failure and never
amend/reset a created commit.

---

## 14. Phase 2H.2 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_2_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.2 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. WRAPPER CORRECTIONS
## 6. FILES CREATED
## 7. FILES MODIFIED
## 8. RUNNABLE FIXTURE BOUNDARY
## 9. DELIVERY AND OUTPUT CONTRACT
## 10. BLACK-BOX ACCEPTANCE
## 11. SECURITY AND TRUST PROOF
## 12. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 13. ARCHITECTURAL DECISIONS
## 14. DEVIATIONS FROM DIRECTIVE
## 15. DEPENDENCIES ADDED/REMOVED
## 16. ENVIRONMENT
## 17. COMMANDS RUN
## 18. PRE-COMMIT VALIDATION RESULTS
## 19. STAGING EVIDENCE
## 20. COMMIT EVIDENCE
## 21. POST-COMMIT VALIDATION RESULTS
## 22. DOCUMENTATION REVIEW
## 23. SECURITY AND FILE-SAFETY REVIEW
## 24. WARNINGS / KNOWN ISSUES
## 25. QUESTIONS / BLOCKERS
## 26. FINAL GIT STATUS
## 27. ACCEPTANCE CRITERIA
## 28. NEXT STEP
```

Include every original implementation/checkpoint report requirement plus:

- independent Phase 2H.2 identity;
- exact Phase 2H and 2H.1 directive/report identities;
- accepted network-blocked run and complete restoration evidence;
- exact active-string versus restored-boolean predicate proof;
- exact approved-access Gate B execution and audit result;
- all later overlay/cleanup results;
- both historical zero-byte reports staged and committed;
- exact `27/167/194/0`, 525-visible pre-stage evidence;
- exact 193-path stage and 524-path tree/commit;
- final sole-report status;
- whether Phase 2 is fully closed; and
- final report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The report must be strict UTF-8 without BOM, LF-only, exactly one final LF, no
trailing whitespace/NUL, ordinary, untracked, unstaged, absent from `HEAD`, and
the sole final status path.

---

## 15. Required final outcome

Phase 2H completes without changing its framework or fixture architecture. The
wrapper correctly distinguishes the active scanned-file string from PHP's
post-cleanup boolean-false no-file state, and canonical quality completes with
the locked advisory audit under approved access.

The full deterministic runnable fixture and black-box proof are implemented,
the complete accepted Phase 2 work is checkpointed once, both earlier Phase 2H
reports remain zero-byte historical records in `HEAD`, and only the populated
Phase 2H.2 report remains outside the commit. Then await architect review before
Phase 3 planning.
