# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.1
## PowerShell Temporary-Directory Creation Correction and Exact Phase 2H Resume

**Directive type:** exact validation-wrapper correction, implementation resume, checkpoint reconciliation, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.1

**Depends on:** authenticated Phase 2H stopped without mutation after Gate A

---

## 1. Mission

Resume Phase 2H from its preserved post-Gate-A state after correcting exactly
one PowerShell wrapper defect:

```powershell
New-Item -ItemType Directory -LiteralPath $tempDir -ErrorAction Stop
```

is unsupported because `New-Item` does not expose a `LiteralPath` parameter in
the executor's PowerShell environment.

For Phase 2H temporary directory creation only, replace that operation with:

```powershell
New-Item -ItemType Directory -Path $tempDir -ErrorAction Stop
```

The path remains a newly generated, prevalidated absolute GUID-named child of
the normalized process temporary directory. This exact change does not weaken
path validation and does not authorize interpolation, wildcard use, recursive
deletion, repository mutation, or a permanent PHP/environment change.

Authorize one fresh pre-change overlay attempt because the stopped attempt:

- passed authentication and Gate A;
- failed before creating a directory or file;
- never set `PHP_INI_SCAN_DIR`;
- never ran `composer quality`;
- left PHP at `128M` with the scan variable absent; and
- changed no repository, index, history, caller record, or permanent state.

After the corrected Gate B passes, execute the original Phase 2H implementation,
black-box validation, documentation reconciliation, and checkpoint exactly as
amended here. Preserve the original Phase 2H report as a zero-byte historical
record. Populate only the Phase 2H.1 correction report after every non-report
gate and the corrected checkpoint pass.

---

## 2. Narrow supersession

This correction supersedes the Phase 2H directive only in these matters:

1. directory creation for each validation-only temporary INI uses `New-Item
   -ItemType Directory -Path`, not unsupported `-LiteralPath`;
2. the stopped wrapper invocation does not consume an authorized quality run;
3. one fresh corrected Gate B attempt is authorized;
4. `docs/task reports/Phase_2H.md` becomes an immutable zero-byte historical
   caller record that is included in the checkpoint;
5. `docs/task reports/Phase_2H_1_correction.md` becomes the active report and is
   the sole path excluded from the checkpoint; and
6. exact entry, pre-stage, staging, tree, and final counts are increased for the
   two Phase 2H.1 caller records as stated in this correction.

All architectural, implementation, fixture, test, documentation, security,
dependency, tooling, quality, overlay validation, cleanup, Git, stop-rule, and
prohibited-scope requirements in Phase 2H remain in force unless an exact count
or report path is replaced here.

In particular, do not change the temporary-file creation, PHP API validation,
literal `composer quality`, cleanup order, exact environment-variable removal,
or normal `128M` restoration requirements.

---

## 3. Independent correction authentication

Before quality, temporary state, repository mutation, staging, or Git writes,
compare this correction with the byte count and SHA-256 supplied independently
by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either independent value
is absent or differs.

---

## 4. Required authorities and caller records

Read completely:

```text
AGENTS.md
docs/dev_plan/kongono_phase_2H_codex_directive.md
docs/task reports/Phase_2H.md
docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md
```

Also retain the Phase 2H directive's full authority-reading requirement. Gate A
already passed, but verify the previously read authorities have not changed
before relying on that completed reading.

The original Phase 2H directive must remain:

```text
bytes      57027
SHA-256    29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC
```

`docs/task reports/Phase_2H.md` must remain:

```text
bytes      0
SHA-256    E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
state      ordinary, untracked, unstaged, absent from HEAD at correction entry
```

It is now the immutable report for the stopped original execution. Do not
populate, truncate, replace, rename, delete, or otherwise change it. It will be
staged and committed as an intentional zero-byte historical record only after
all original pre-commit gates pass.

The caller must additionally provide:

```text
docs/task reports/Phase_2H_1_correction.md
```

At correction entry it must be ordinary, untracked, unstaged, absent from
`HEAD`, zero bytes, and have the empty-file SHA-256 above. It remains zero-byte
through implementation, validation, staging, commit, and all post-commit
non-report gates. Never stage it.

All Phase 2F, Phase 2G, Phase 2G.1, and Phase 2G.2 identities in the original
directive remain exact and immutable. Verify them again before Gate B.

---

## 5. Accepted stopped state

Before the two Phase 2H.1 caller records are placed, the exact preserved state
is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        149
total status paths     176
staged paths           0
Git-visible paths      507
normal PHP memory      128M
PHP_INI_SCAN_DIR       absent
Phase 2H report        zero bytes
```

The original Phase 2H Gate A established:

- exact external authentication of the 57,027-byte directive;
- exact reconstruction of the 505-path accepted manifest;
- exact `27/149/176/0`, 507-visible, 358-tracked caller setup;
- exact caller records and framework inventories; and
- no file-safety, link, mode, stream, ref, branch, remote, tag, or Git identity
  mismatch.

No temporary directory, temporary INI, scan-variable value, quality result,
implementation delta, index entry, object, ref, or commit was created by the
stopped attempt.

After the caller adds only this authenticated correction directive and its
zero-byte report, require correction entry state:

```text
tracked modifications  27
untracked paths        151
total status paths     178
staged paths           0
Git-visible paths      509
tracked files          358
```

The only additions are:

```text
docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md
docs/task reports/Phase_2H_1_correction.md
```

Reconstruct the original authenticated 505-path manifest by excluding exactly
the four Phase 2H/2H.1 caller paths. Require the original manifest identity:

```text
records          505
manifest bytes   62292
SHA-256          A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
```

Stop if any accepted fact differs. Do not repair or infer a new baseline.

---

## 6. Exact corrected overlay operation

### 6.1 Prevalidation remains mandatory

For every Phase 2H-authorized quality overlay, retain all Section 17 safety
requirements from the original directive. Before directory creation:

- create the candidate name from a new GUID with no wildcard characters;
- combine it directly under `[System.IO.Path]::GetTempPath()`;
- normalize the absolute temp root and candidate;
- require the normalized candidate's parent to equal the normalized temp root;
- require the candidate not to exist;
- require it not to equal or contain the repository, workspace, user home, PHP
  installation, Composer cache, or another existing directory; and
- record the exact normalized path for later non-recursive cleanup.

### 6.2 Authorized creation line

After those predicates pass, create it exactly once with:

```powershell
New-Item -ItemType Directory -Path $tempDir -ErrorAction Stop
```

Immediately resolve the created item with a read-only literal-path lookup and
prove its full normalized path equals the prevalidated candidate, its parent is
still the exact temp root, it is an ordinary directory, and it contains no
entry.

`-Path` is authorized only because `$tempDir` is a fully resolved, newly
generated, prevalidated literal string with no wildcard characters. Do not pass
user input, an unresolved environment variable, relative path, glob, array, or
multiple path.

Do not use `mkdir`, `md`, `cmd`, another shell, `New-Item -LiteralPath`, a
recursive helper, or a different .NET filesystem mechanism.

### 6.3 Remaining overlay contract unchanged

After successful directory creation:

- create the exact `99-kongono-phase-2h-memory.ini` bytes;
- set only the current process scan variable;
- validate `512M`, main `C:\php\php.ini`, and exactly one scanned temporary INI
  through PHP APIs;
- run literal `composer quality`;
- permit one identical approved-access retry only for managed network/cache
  denial and only after full cleanup;
- in `finally`, first execute exact
  `Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue`;
- prove the variable absent;
- delete only the exact temporary INI with `Remove-Item -LiteralPath`;
- delete only the exact now-empty directory non-recursively with
  `Remove-Item -LiteralPath`;
- prove both paths absent; and
- prove fresh ordinary PHP returns to `128M`.

The unsupported `New-Item -LiteralPath` failure does not count against the
original allowance of pre-change, final pre-staging, and post-commit canonical
quality runs. Each corrected run still uses a fresh directory.

No non-network retry after a quality execution is authorized.

---

## 7. Resume point and original implementation authority

Resume at original Phase 2H Gate B. Do not repeat Gate A mutation or create a
new architecture. It is permitted to repeat read-only Gate A proofs when needed
to establish preservation.

After corrected pre-change canonical quality passes:

1. implement exactly the original twelve black-box PHP files;
2. create exactly ADR 0050 and the runnable-fixture model;
3. modify exactly the original twenty-two existing paths;
4. run the original focused, direct fixture, documentation, final canonical,
   strict autoload, staging, checkpoint, and post-commit gates; and
5. apply only the count/report/checkpoint substitutions in Sections 8-10 of
   this correction.

All original fixture modes, scenarios, routes, handlers, middleware order,
bounded delivery snapshot, request/send/close counts, trusted permission truth
table, generic response, trusted response, sequential isolation, CLI result
schema, security rules, and prohibited capabilities remain exact.

---

## 8. Corrected final inventories before staging

The production and test inventories remain those required by Phase 2H:

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

After implementation and before staging, replace the original status totals
with:

```text
tracked modifications  27
untracked paths        165
total status paths     192
staged paths           0
Git-visible paths      523
tracked files          358
```

The increase from the original 505-path accepted snapshot is exactly:

- original Phase 2H directive;
- original zero-byte Phase 2H report;
- Phase 2H.1 correction directive;
- active zero-byte Phase 2H.1 report; and
- the fourteen original Phase 2H created implementation/documentation paths.

No existing authorized modified path adds a new status path.

Where the original directive says `27/163/190/0`, 521 Git-visible, or 190 total
status paths, replace it with `27/165/192/0`, 523 Git-visible, and 192 total.

---

## 9. Corrected checkpoint boundary

### 9.1 Exact staging set

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. `docs/dev_plan/kongono_phase_2H_codex_directive.md`;
3. the original zero-byte `docs/task reports/Phase_2H.md` historical record;
4. `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md`; and
5. the fourteen Phase 2H created paths in original Section 15.1.

Exclude exactly:

```text
docs/task reports/Phase_2H_1_correction.md
```

Materialize the exact 191 paths as a literal ordinal path array. Preserve all
original no-wildcard/no-directory/no-`git add .` staging rules.

After staging require:

```text
staged paths                  191
staged modified paths          27
staged added paths            164
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 522
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.1 correction report.

The original zero-byte Phase 2H report is an intentional staged `100644` blob
with the empty-file SHA-256. It is not a whitespace failure and must not be
populated.

### 9.2 Tree and commit

Run the unchanged staged whitespace and semantic blob proofs. `git write-tree`
must contain exactly 522 paths, all `100644`.

Create the unchanged exact commit subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

The commit must have:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      522
tree modes      all 100644
delta paths     191
modified        27
added           164
deleted         0
```

All original commit-safety, author, hook, signing, ref, branch, history,
no-amend/no-reset/no-clean/no-push rules remain unchanged.

Where the original directive requires 189 staged, 162 added, 520 index/tree
paths, or a 189-path commit delta, replace those values with 191 staged, 164
added, 522 index/tree paths, and a 191-path delta.

### 9.3 Post-commit state

Before correction-report population require:

```text
tracked files          522
tracked modifications 0
staged paths           0
untracked paths        1
total status paths     1
```

The sole path must be the zero-byte Phase 2H.1 correction report. Original
`Phase_2H.md` must be present in `HEAD` as the exact empty blob.

Run every original post-commit focused, direct fixture, canonical overlay,
inventory, documentation, link, encoding, stream, mode, and history proof.

Only after all pass may the active correction report be populated; status
remains one untracked path.

---

## 10. Corrected acceptance substitutions

Apply every original Phase 2H acceptance criterion except make these exact
substitutions:

- active report means `Phase_2H_1_correction.md`;
- original `Phase_2H.md` must remain zero-byte and be committed as historical
  evidence;
- pre-stage state is `27/165/192/0`, 523 visible, 358 tracked;
- staged state is 191 paths: 27 modified, 164 added, zero deleted;
- index and checkpoint tree contain 522 `100644` paths;
- commit delta is 191 paths: 27 modified, 164 added, zero deleted;
- post-commit tracked file count is 522;
- the correction report is the sole untracked/unstaged status path; and
- final handoff is recorded in the Phase 2H.1 report.

Add these correction-specific criteria:

1. Phase 2H.1 directive authentication passed independently;
2. the stopped Phase 2H attempt was accepted only as a no-mutation pre-quality
   wrapper failure;
3. new directory creation used exact `New-Item -ItemType Directory -Path` only
   after all path predicates;
4. created-directory literal-path proof matched the prevalidated candidate;
5. no unsupported `New-Item -LiteralPath` call was retried;
6. every overlay cleanup and normal-memory restoration passed;
7. the original Phase 2H report stayed zero-byte through checkpoint; and
8. only the Phase 2H.1 correction report was populated after all other gates.

If any original or corrected criterion fails, completion status must not be
Complete.

---

## 11. Prohibited scope

The complete original Phase 2H prohibited scope remains in force. Additionally,
do not:

- change any Phase 2H fixture/API/documentation decision because of this
  wrapper correction;
- use `New-Item -LiteralPath` for directory creation;
- broaden `-Path` to caller input, wildcard, multiple paths, or an unresolved
  location;
- create a directory before path validation;
- use a different directory-creation mechanism;
- retry the failed unsupported command;
- count the stopped invocation as canonical quality;
- populate or exclude the original Phase 2H report from the checkpoint;
- stage or commit the Phase 2H.1 report;
- restore, clean, reset, unstage, rebuild, amend, or otherwise alter accepted
  caller-managed state; or
- begin Phase 3.

---

## 12. Stop rules

Stop and preserve exact state if:

- correction authentication, caller identity, or accepted state differs;
- the original 505-path manifest cannot be reconstructed exactly;
- `$tempDir` is not a single absolute, new, wildcard-free direct child of the
  normalized temp root;
- `New-Item -Path` creates or resolves anything other than that exact path;
- PHP API overlay validation differs;
- canonical quality executes and fails for a non-network/cache reason;
- exact environment/path cleanup or return to `128M` fails;
- any original Phase 2H implementation/validation/architecture gate fails;
- corrected inventories, staging, tree, commit, or post-commit state differs;
- either report is changed at the wrong time; or
- a prohibited operation would be needed.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Retain the original instruction to preserve an exact staged index on any
post-staging failure and never amend/reset a created commit.

---

## 13. Phase 2H.1 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_1_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.1 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. WRAPPER CORRECTION
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

In addition to every original Phase 2H report requirement, state explicitly:

- exact independent Phase 2H.1 identity;
- exact original Phase 2H directive and zero-byte report identities;
- accepted stopped-at-Gate-B wrapper facts;
- exact corrected `New-Item -Path` use and post-creation path proof;
- all three fresh overlay runs and any allowed network-only retry;
- exact cleanup and return to `128M` after each;
- original report's zero-byte staged blob and presence in the commit;
- corrected `27/165/192/0`, 523-visible pre-stage state;
- exact 191-path index and 522-path tree/commit evidence;
- post-commit sole active-report status;
- whether Phase 2 is fully closed; and
- report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The report must be strict UTF-8 without BOM, LF-only, one final LF, no trailing
whitespace/NUL, ordinary, untracked, unstaged, absent from `HEAD`, and the sole
final status path.

---

## 14. Required final outcome

Phase 2H completes exactly as originally designed after the PowerShell-only
directory-creation correction. The real thirteen-package framework path is
proved through the bounded runnable fixture, canonical quality passes under
fully cleaned temporary overlays, and the complete accepted Phase 2 work is
committed once with the corrected historical caller-record boundary.

The original Phase 2H report remains a zero-byte record inside the checkpoint.
Only the populated Phase 2H.1 correction report remains outside `HEAD`. Then
return that report and await architect review before Phase 3 planning.
