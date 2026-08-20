# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.3
## Gate B Evidence Acceptance and Separate Cleanup-Predicate Resume

**Directive type:** evidence acceptance, wrapper-expression correction, implementation resume, checkpoint reconciliation, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.3

**Depends on:** authenticated Phase 2H.2 Gate B with complete canonical success and independently passing cleanup predicates

---

## 1. Mission

Accept the already completed Phase 2H Gate B evidence and resume directly at
the original Phase 2H implementation gate without rerunning pre-change
canonical quality.

The Phase 2H.2 approved-access run established all substantive Gate B facts:

```text
locked advisories       0
platform requirements   pass
PHP CS Fixer            0 of 218 files fixable
PHPStan                 zero errors
PHPUnit                  464 tests, 8337 assertions
temporary INI           removed and proven absent
temporary directory     removed and proven absent
memory after cleanup    128M
main INI                 C:\php\php.ini
scanned INI state       typed boolean false
PHP_INI_SCAN_DIR         absent
```

After those requirements passed, a redundant wrapper expression failed:

```powershell
Test-Path Env:PHP_INI_SCAN_DIR -or Test-Path -LiteralPath $ini -or Test-Path -LiteralPath $tempDir
```

PowerShell parsed it as one command invocation rather than three boolean
expressions. This parsing failure occurred after the variable, file, directory,
PHP API state, memory, and quality results had already been independently
proved. It exposed no repository or environment failure.

The architect therefore accepts Gate B as **PASS** and authorizes resume at
Phase 2H Gate C. Do not run Gate B again.

For the remaining final pre-staging and post-commit overlays, evaluate the three
existence predicates separately as specified in Section 6. Preserve all earlier
reports as zero-byte historical records and populate only the Phase 2H.3 report
after the complete implementation and checkpoint pass.

---

## 2. Architect decision and narrow supersession

The architect selects the executor's recommended Option 1:

> Accept the recorded Gate B canonical quality and cleanup evidence and resume
> at implementation without repeating an already successful canonical run.

This correction supersedes prior Phase 2H-family directives only where they:

- require a successful exit from the redundant combined `Test-Path` expression
  after independently successful required predicates;
- prohibit resuming after the accepted Phase 2H.2 Gate B evidence;
- identify an earlier report as active; or
- use checkpoint counts that exclude Phase 2H.3 caller records.

It does not waive or weaken any quality, security, cleanup, implementation,
black-box, documentation, staging, commit, or post-commit requirement. Gate B
is accepted because its required evidence exists, not because a failure is
ignored.

The original fixture architecture, twelve new PHP files, fourteen created
paths, twenty-two modified paths, thirteen-package boundary, no-dependency
change, final pre-staging quality, post-commit quality, exact checkpoint
subject/parent, and prohibited scope remain locked.

---

## 3. Independent correction authentication

Before repository mutation, another temporary overlay, staging, or Git writes,
compare this correction with the byte count and SHA-256 supplied independently
by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md
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
docs/task reports/Phase_2H_2_correction.md
docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md
```

Verify exact directive identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2H_codex_directive.md` | 57027 | `29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC` |
| `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md` | 20701 | `5302D1A8EE111F0E03EB5BAAA6EF0FF199A1D449A6F55A696032E14D9203F048` |
| `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md` | 21134 | `A6A53FF03C7D52D80381C5C6C71C154A6D475A997BF915A47E24D639C5BE80B1` |

All three earlier report placeholders must remain ordinary, untracked,
unstaged, absent from `HEAD`, zero bytes, and have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Their paths are:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
docs/task reports/Phase_2H_2_correction.md
```

Do not populate, truncate, replace, rename, or delete them. After all
pre-commit gates pass, stage and commit them as intentional zero-byte historical
records.

The caller must additionally provide:

```text
docs/task reports/Phase_2H_3_correction.md
```

At entry it must be ordinary, untracked, unstaged, absent from `HEAD`, zero
bytes, and have the empty-file digest above. Keep it zero-byte through all
non-report gates and never stage it.

All prior Phase 2 authorities and caller identities in the original directive
remain immutable.

---

## 5. Accepted stopped state and Gate B evidence

### 5.1 State before Phase 2H.3 caller records

Require:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        153
total status paths     180
staged paths           0
Git-visible paths      511
normal PHP memory      128M
PHP_INI_SCAN_DIR       absent
three earlier reports  zero bytes each
```

The stopped execution also proved:

- exact Phase 2H.2 authentication;
- exact reconstruction of the original 505-path manifest;
- corrected and prevalidated temporary directory creation;
- exact active `512M` overlay, main INI, and sole scanned temporary file;
- the literal approved-access `composer quality` ran to successful completion;
- locked audit, platform, style, PHPStan, and PHPUnit all passed with the exact
  totals in Section 1;
- exact process-variable removal occurred before path cleanup;
- exact temporary INI and directory were absent before the redundant
  expression;
- post-cleanup PHP APIs returned `128M`, main INI, and typed boolean false;
- the controlling process scan variable was absent; and
- repository, index, history, and caller records were unchanged.

These facts are now architect-accepted Gate B evidence. Do not rerun Gate B.

### 5.2 Correction entry after caller setup

After the caller adds only this correction directive and its zero-byte report,
require:

```text
tracked modifications  27
untracked paths        155
total status paths     182
staged paths           0
Git-visible paths      513
tracked files          358
```

The only additions are:

```text
docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md
docs/task reports/Phase_2H_3_correction.md
```

Reconstruct the original accepted snapshot by excluding exactly the eight
Phase 2H-family caller paths. Require:

```text
records          505
manifest bytes   62292
SHA-256          A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
```

Stop if anything differs. Do not repair or infer a new baseline.

---

## 6. Correct remaining overlay existence proof

For final pre-staging and post-commit canonical quality, retain every Phase
2H.1 and Phase 2H.2 overlay rule, including:

- prevalidated GUID child of the normalized temp root;
- exact `New-Item -ItemType Directory -Path` creation;
- exact one-line temporary INI;
- active PHP state of `512M`, main `C:\php\php.ini`, and exactly one scanned
  temporary path string;
- literal `composer quality`;
- approved-access retry only for an initial managed network/cache denial;
- exact scan-variable removal first in `finally`;
- exact non-recursive file/directory removal;
- typed post-cleanup `scanned === false`; and
- fresh normal PHP at `128M`.

Do not use the invalid combined command:

```powershell
Test-Path Env:PHP_INI_SCAN_DIR -or Test-Path -LiteralPath $ini -or Test-Path -LiteralPath $tempDir
```

Instead, evaluate three separate commands and store three booleans:

```powershell
$phase2h3ScanVariablePresent = Test-Path Env:PHP_INI_SCAN_DIR
$phase2h3IniPresent = Test-Path -LiteralPath $ini
$phase2h3DirectoryPresent = Test-Path -LiteralPath $tempDir
```

Then evaluate only the already-materialized booleans:

```powershell
if (
    $phase2h3ScanVariablePresent
    -or $phase2h3IniPresent
    -or $phase2h3DirectoryPresent
) {
    throw 'Phase 2H temporary overlay cleanup proof failed.'
}
```

Require all three variables to be typed booleans and exactly `$false`. Do not
place `-or` inside a `Test-Path` command invocation, repeat a parameter in one
command, rely on truthiness of path text, or combine parser-sensitive inline
expressions.

This wrapper correction is validation-only. It changes no quality requirement,
repository file, permanent environment, or fixture behavior.

---

## 7. Resume at original Gate C

After Phase 2H.3 authentication and accepted-state proof, resume directly at
original Phase 2H Gate C.

Do not run pre-change canonical quality again. Report the accepted Phase 2H.2
Gate B results as the pre-change evidence.

Then execute exactly:

1. the twelve test-owned black-box PHP files;
2. ADR 0050 and the runnable-fixture architecture model;
3. the twenty-two authorized documentation/PHPStan reconciliations;
4. focused syntax, PHPStan, style, black-box PHPUnit, and direct runner proof;
5. documentation, link, inventory, encoding, stream, mode, and frozen-path
   proof;
6. final pre-staging canonical quality using Section 6;
7. strict dev/no-dev/dev optimized autoload proof;
8. the corrected Phase 2 checkpoint in Section 9;
9. post-commit focused and canonical proof using Section 6; and
10. report population only after all non-report gates pass.

All original modes, scenarios, routes, handlers, middleware order, bounded
envelopes, trust rules, failure models, cleanup counts, isolation tests,
documentation meaning, package boundary, and prohibited capabilities remain
unchanged.

---

## 8. Corrected final inventories before staging

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
untracked paths        169
total status paths     196
staged paths           0
Git-visible paths      527
tracked files          358
```

The increase beyond the authenticated 505-path snapshot is exactly:

- eight Phase 2H-family caller records; and
- fourteen original Phase 2H created paths.

Replace all earlier pre-stage totals (`27/163/190/0`, `27/165/192/0`, or
`27/167/194/0`; 521, 523, or 525 visible) with `27/169/196/0` and 527 visible.

---

## 9. Corrected checkpoint boundary

### 9.1 Exact staging set

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. `docs/dev_plan/kongono_phase_2H_codex_directive.md`;
3. zero-byte `docs/task reports/Phase_2H.md`;
4. `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md`;
5. zero-byte `docs/task reports/Phase_2H_1_correction.md`;
6. `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md`;
7. zero-byte `docs/task reports/Phase_2H_2_correction.md`;
8. `docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md`; and
9. the fourteen original Phase 2H created paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_3_correction.md
```

Materialize the exact 195 staging paths as a literal ordinal path array and
retain all original pathspec-safe/no-wildcard rules.

After staging require:

```text
staged paths                  195
staged modified paths          27
staged added paths            168
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 526
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.3 report. All three earlier
reports are intentional staged empty blobs.

### 9.2 Tree and commit

Require staged blob/worktree equality, exact modes, no conflict stage, and
clean `git diff --cached --check`. `git write-tree` must contain exactly 526
paths, all `100644`.

Use the unchanged exact subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      526
tree modes      all 100644
delta paths     195
modified        27
added           168
deleted         0
```

Every original author, hook, signing, branch, ref, remote, tag, history, and
prohibited Git-operation rule remains.

Replace all earlier staging/tree values with 195 staged, 27 modified, 168
added, zero deleted, and 526 index/tree paths.

### 9.3 Post-commit state

Before report population require:

```text
tracked files          526
tracked modifications 0
staged paths           0
untracked paths        1
total status paths     1
```

The sole path is the zero-byte Phase 2H.3 report. All three earlier reports must
exist in `HEAD` as exact empty blobs.

Run every original post-commit focused, direct runner, canonical, inventory,
documentation, link, safety, mode, and history proof. Only after all pass may
the active report be populated.

---

## 10. Corrected acceptance substitutions

Every original Phase 2H implementation criterion remains, with these exact
substitutions:

- active report is `Phase_2H_3_correction.md`;
- the three earlier reports remain zero-byte and are committed;
- pre-stage state is `27/169/196/0`, 527 visible, 358 tracked;
- staged state is 195 paths: 27 modified, 168 added, zero deleted;
- index and checkpoint tree contain 526 `100644` paths;
- commit delta is 195 paths: 27 modified, 168 added, zero deleted;
- post-commit tracked file count is 526; and
- Phase 2H.3 report is the sole final status path.

Add these Phase 2H.3 criteria:

1. Phase 2H.3 directive authenticated independently;
2. exact stopped state and all three earlier zero-byte reports matched;
3. Phase 2H.2 Gate B quality evidence was accepted without rerun;
4. locked audit, platform, 0/218 style, zero PHPStan errors, and 464/8337 tests
   were recorded as passed pre-change evidence;
5. variable, INI, directory, typed-false, and `128M` cleanup evidence was
   recorded as passed;
6. the redundant parser failure was not treated as a product or cleanup failure;
7. later overlays use three separately evaluated `Test-Path` predicates;
8. all three materialized booleans are exactly false after cleanup;
9. no fourth Gate B quality run occurred;
10. every original implementation/checkpoint/post-commit gate passed; and
11. only the Phase 2H.3 report was populated after all others.

Any failure means status cannot be Complete.

---

## 11. Prohibited scope

The complete Phase 2H, 2H.1, and 2H.2 prohibited scope remains. Additionally,
do not:

- rerun Gate B;
- classify the accepted redundant expression error as a repository defect;
- use the invalid combined `Test-Path ... -or Test-Path ...` command;
- place a boolean operator inside a `Test-Path` parameter list;
- waive final pre-staging or post-commit canonical quality;
- populate any earlier report;
- stage the Phase 2H.3 report;
- alter the original fixture architecture/file boundary;
- change permanent configuration or dependency policy; or
- begin Phase 3.

---

## 12. Stop rules

Stop and preserve exact state if:

- correction identity, caller records, accepted evidence, or manifest differs;
- an earlier report is non-empty or changed;
- a later overlay fails active or post-cleanup proof;
- any separate existence boolean is not exactly false after cleanup;
- final pre-staging or post-commit canonical quality fails;
- an original implementation, black-box, documentation, autoload, staging,
  tree, commit, or history gate fails;
- corrected counts differ; or
- a prohibited operation would be required.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Preserve an exact staged index after any post-staging failure and never amend or
reset a created commit.

---

## 13. Phase 2H.3 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_3_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.3 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. GATE B EVIDENCE ACCEPTANCE
## 6. WRAPPER CORRECTIONS
## 7. FILES CREATED
## 8. FILES MODIFIED
## 9. RUNNABLE FIXTURE BOUNDARY
## 10. DELIVERY AND OUTPUT CONTRACT
## 11. BLACK-BOX ACCEPTANCE
## 12. SECURITY AND TRUST PROOF
## 13. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 14. ARCHITECTURAL DECISIONS
## 15. DEVIATIONS FROM DIRECTIVE
## 16. DEPENDENCIES ADDED/REMOVED
## 17. ENVIRONMENT
## 18. COMMANDS RUN
## 19. PRE-COMMIT VALIDATION RESULTS
## 20. STAGING EVIDENCE
## 21. COMMIT EVIDENCE
## 22. POST-COMMIT VALIDATION RESULTS
## 23. DOCUMENTATION REVIEW
## 24. SECURITY AND FILE-SAFETY REVIEW
## 25. WARNINGS / KNOWN ISSUES
## 26. QUESTIONS / BLOCKERS
## 27. FINAL GIT STATUS
## 28. ACCEPTANCE CRITERIA
## 29. NEXT STEP
```

Include every original Phase 2H implementation/checkpoint report requirement
plus:

- independent Phase 2H.3 identity;
- all Phase 2H-family directive/report identities;
- exact accepted Gate B quality and cleanup evidence;
- explicit statement that Gate B was not rerun;
- exact separate-boolean cleanup proof for both later overlays;
- all three earlier zero-byte reports staged and committed;
- exact `27/169/196/0`, 527-visible pre-stage evidence;
- exact 195-path stage and 526-path tree/commit;
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

## 14. Required final outcome

Phase 2H resumes without repeating an already successful pre-change canonical
run. The original fixture architecture is implemented and proved, later
temporary overlays use unambiguous separate cleanup predicates, and the full
accepted Phase 2 work is checkpointed once.

All three earlier Phase 2H reports remain zero-byte historical records in
`HEAD`. Only the populated Phase 2H.3 report remains outside the commit. Then
await architect review before Phase 3 planning.
