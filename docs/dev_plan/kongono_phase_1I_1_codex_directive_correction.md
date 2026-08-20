# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 1I.1

## Ordinal Manifest Hash Correction and Checkpoint Resume

**Directive type:** narrow checkpoint correction and resume

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Correct one architect-authored expected manifest SHA-256 in the Phase 1I directive and resume the unchanged checkpoint through Phase 1H.

The Phase 1I executor correctly stopped at Gate A. The repository, index, caller records, file identities, entry count, and manifest byte length all matched. The sole mismatch was:

```text
Phase 1I directive expected manifest SHA-256:
2E9CE853DFEB80D5F2658ABD80D8AF7CD9A92AFB34D5838D71900FD1C0DCDDCB

Correct ordinal manifest SHA-256:
DB1F8EBC5554574331CEF7C6B30A356A593C3C868412E1CAC6E1CA640BCBDF3E
```

Independent reconstruction proved that the original expected value was produced by Windows culture-sensitive ordering. That value contradicts the original directive's explicit requirement to sort repository-relative paths using ordinal bytewise order. The observed value `DB1F...DF3E` is reproduced by explicit `StringComparer.Ordinal` ordering and by the already ordinal-ordered literal checkpoint array.

No checkpoint path is defective. No accepted byte may be edited. The original Phase 1I directive remains an immutable caller record and must not be rewritten to replace its historical incorrect value.

The exact commit subject remains:

```text
Establish Kongono trusted diagnostic handling through Phase 1H
```

### 1.1 Required outcome

After successful completion:

- exactly the original 36 Phase 1I checkpoint paths are staged and committed;
- the commit contains 22 modified and 14 added paths;
- the commit delta remains 6,167 insertions and 104 deletions;
- `main` has exactly nine commits and 358 tracked `100644` files;
- canonical quality passes with 254 tests and 4,279 assertions;
- no accepted content byte changes during Phase 1I.1;
- the original Phase 1I directive and zero-byte Phase 1I report remain outside the commit;
- this correction directive and its populated correction report remain outside the commit; and
- Phase 1J is not started.

---

## 2. Authority and supersession

### 2.1 Required reading

Before validation or mutation, read completely:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. `docs/dev_plan/kongono_phase_1I_codex_directive.md`
6. the executor's Phase 1I blocked report supplied by the caller
7. this Phase 1I.1 correction directive
8. the zero-byte `docs/task reports/Phase_1I_1_correction.md` placeholder

The complete Accepted ADR and Phase 1F-1H authority reads already performed during the immediately preceding Phase 1I Gate A attempt need not be repeated. Their file identities must still be verified as unchanged under the original Phase 1I rules. If the execution is not a direct resume of that stopped attempt, read the complete original Phase 1I authority set before continuing.

### 2.2 Exact supersession

This correction supersedes only these original Phase 1I statements:

1. Section 5's required manifest SHA-256;
2. Gate A item 13 only insofar as it references that hash;
3. Gate B's manifest recheck only insofar as it references that hash;
4. Gate D's staged manifest proof only insofar as it references that hash;
5. Gate F's committed manifest proof only insofar as it references that hash; and
6. Acceptance criterion 9 only insofar as it references that hash.

For all of those locations, the authoritative result is now:

```text
entries: 36
manifest bytes: 4012
manifest SHA-256: DB1F8EBC5554574331CEF7C6B30A356A593C3C868412E1CAC6E1CA640BCBDF3E
ordering: ordinal bytewise
```

The original entry count and byte length were correct. Every other original Phase 1I requirement remains authoritative except where this correction necessarily adjusts caller-record counts, final untracked paths, and report ownership.

### 2.3 Correction directive identity

The architect will supply this correction directive's byte count and uppercase SHA-256 outside this file. Compare those independently supplied values with:

```text
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
```

Do not derive the expected identity from the local file.

The correction report placeholder must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_1I_1_correction.md
```

Its initial SHA-256 must be:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.4 Stop rather than assume

At the first mismatch, ambiguity, changed checkpoint byte, unexpected path, failed gate, unauthorized warning, or prohibited side effect:

1. stop immediately;
2. preserve the repository and index in the state required by the failed gate;
3. do not repair, rewrite, restage, unstage, reset, amend, retry, or widen scope unless expressly authorized;
4. leave the correction report zero bytes unless Section 9 authorizes population; and
5. report exact expected and observed values, viable options, and a recommendation.

The original Phase 1I environment-only retry rule remains authoritative.

---

## 3. Locked correction baseline

Before the caller adds the two Phase 1I.1 records, the stopped Phase 1I state is exactly:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `8fe793b124384744ac43887037360154c149241a` |
| `HEAD^{tree}` | `9de9388e85e463639f8d7fbbc90ae085f947a1b0` |
| `HEAD^` | `837863d11feda282e4959ef7ec98a810c184531e` |
| Commit count | `8` |
| Remotes | `0` |
| Tags | `0` |
| Tracked/index entries | `344` |
| Higher-stage entries | `0` |
| Modified tracked paths | `22` |
| Untracked paths with `-uall` | `16` |
| Total status paths with `-uall` | `38` |
| Staged paths | `0` |
| Package directories | `8` |
| Production PHP files | `53` |
| Test PHP files | `85` |

The original Phase 1I caller records must be:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1I_codex_directive.md` | 30470 | `85E3293A9FF52D35FBFF775E45E77C5BEDDD2D6EF125A31DA469EAADDCF6013B` |
| `docs/task reports/Phase_1I.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

The original Phase 1I report remains zero bytes throughout Phase 1I.1. It records the stopped attempt by its preserved empty placeholder and the caller-supplied blocked response; it is not populated, staged, deleted, or replaced.

The original Phase 1I checkpoint identities remain:

```text
checkpoint paths: 36
modified tracked: 22
pre-existing untracked accepted additions: 14
composer.lock bytes: 182894
composer.lock SHA-256: 804B276750164A634D25ED97A613D9B51FC1E59F9D170A9D345C733299B3498B
```

Every Section 4.3 and 4.4 identity from the original Phase 1I directive remains exact.

### 3.1 Expected state after correction caller setup

After the caller adds this correction directive and creates its zero-byte report placeholder, but before any staging, the state must be:

| Property | Required value |
|---|---:|
| Modified tracked paths | `22` |
| Untracked paths with `-uall` | `18` |
| Total status paths with `-uall` | `40` |
| Staged paths | `0` |
| Unstaged tracked paths | `22` |

The four Phase 1I-family caller records outside the checkpoint are exactly:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/task reports/Phase_1I_1_correction.md
```

No other starting-state difference is authorized.

---

## 4. Exact manifest correction proof

Reconstruct the manifest over exactly the original Phase 1I Section 9 `$checkpointPaths` array.

The array already lists the 36 paths in ordinal order. Independently prove that order with an explicitly typed string array and ordinal comparer. Do not use `Sort-Object`, current culture, invariant culture, case-insensitive culture ordering, Explorer ordering, locale-dependent collation, or an untyped nested PowerShell array.

A conforming ordering check is:

```powershell
[string[]] $ordinalPaths = $checkpointPaths.Clone()
[Array]::Sort($ordinalPaths, [StringComparer]::Ordinal)

if ([string]::Join("`n", $checkpointPaths) -cne [string]::Join("`n", $ordinalPaths)) {
    throw 'The locked checkpoint array is not already in ordinal order.'
}
```

Construct the manifest with the original Phase 1I record grammar:

```text
<path><NUL><decimal byte length><NUL><uppercase SHA-256><LF>
```

Required result:

```text
entries: 36
manifest bytes: 4012
manifest SHA-256: DB1F8EBC5554574331CEF7C6B30A356A593C3C868412E1CAC6E1CA640BCBDF3E
```

For diagnostic clarity, the rejected culture-sensitive result is:

```text
2E9CE853DFEB80D5F2658ABD80D8AF7CD9A92AFB34D5838D71900FD1C0DCDDCB
```

Seeing that rejected value confirms use of the wrong ordering rule; it is not an accepted alternative.

Do not create a repository manifest file. If the ordinal reconstruction does not produce the corrected result, stop. Do not adopt a third value.

---

## 5. Prohibited scope

Phase 1I.1 must not:

- edit the original Phase 1I directive to replace its historical hash;
- populate, stage, delete, or truncate the original Phase 1I report;
- edit any of the 36 checkpoint paths;
- stage either Phase 1I-family directive or report;
- change the original 36-path checkpoint inventory;
- use a culture-sensitive manifest order;
- run `git add .`, `git add -A`, a wildcard, directory-wide staging, or a status-derived path list;
- run a second staging command after the original literal staging command succeeds;
- regenerate the lockfile or change a dependency;
- unstage, reset, restore, clean, stash, amend, rebase, merge, cherry-pick, or rewrite history;
- treat raw `.git/index` bytes or cache-tree metadata as semantic acceptance criteria;
- add a branch, tag, remote, signing rule, hook, or Git configuration;
- implement an adapter, configuration loader, evidence evaluator, HTTP, router, middleware, request, response, transport, CLI, ORM, agent, extension, frontend, telemetry, or other later capability; or
- begin Phase 1J.

This correction authorizes only:

1. correction-directive and caller-record validation;
2. the corrected ordinal manifest proof;
3. the remaining original Phase 1I validation gates;
4. exact staging of the original 36 paths;
5. semantic index and tree proof;
6. the one original Phase 1I checkpoint commit;
7. post-commit validation; and
8. population of the Phase 1I.1 correction report after complete success.

---

## 6. Resume Gate A and pre-stage validation

Before broad quality or mutation, prove:

1. this correction directive matches the independently supplied identity;
2. its report is the required zero-byte placeholder;
3. both original Phase 1I caller records match Section 3;
4. the repository identity, history, index, modes, streams, ordinary-file state, lockfile, and exact inventories still match the stopped baseline plus the two correction caller records;
5. all original Phase 1I Section 4.3 and 4.4 identities still match;
6. no original checkpoint path changed after the stopped attempt;
7. the original 36-entry checkpoint array remains exact and unique;
8. the corrected ordinal manifest matches Section 4; and
9. there are zero staged paths.

Then run the original Phase 1I Gate B exactly as written. Required canonical result remains:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Re-prove the corrected manifest and all caller, lockfile, history, inventory, file-safety, encoding, link, disclosure, and secret checks after quality. The original accepted Composer warnings and unchanged environment-only retry rule remain in force.

---

## 7. Exact staging and semantic index proof

Use exactly the original Phase 1I Section 9 `$checkpointPaths` literal array and its one exact `git add` command. Do not reconstruct or infer a replacement array.

After staging, the required semantic state is:

| Property | Required value |
|---|---:|
| Staged paths | `36` |
| Staged modified paths | `22` |
| Staged added paths | `14` |
| Staged deletions/renames/copies | `0` |
| Staged insertions | `6167` |
| Staged deletions | `104` |
| Unstaged tracked paths | `0` |
| Untracked paths with `-uall` | `4` |
| Stage-zero index entries | `358` |
| Higher-stage index entries | `0` |
| Index modes | all `100644` |

The four untracked paths must be exactly the Phase 1I-family caller records in Section 3.1.

Every staged blob must equal its worktree primary bytes and reproduce the corrected ordinal manifest. Run:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --cached --check
git -c safe.directory='C:/Users/antho/Dev/kongono' write-tree
```

Record the tree ID. `git write-tree` may change cache-tree metadata. Raw index bytes remain deliberately outside the contract. Re-prove all semantic index properties after `write-tree`.

---

## 8. Commit and post-commit proof

Only after every prior gate passes, create exactly one unsigned commit:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' commit --no-gpg-sign -m "Establish Kongono trusted diagnostic handling through Phase 1H"
```

All original Phase 1I commit restrictions remain. Do not amend or create a repair commit.

Before correction-report population, prove:

```text
branch: main
local branches: 1
HEAD parent: 8fe793b124384744ac43887037360154c149241a
HEAD parents: 1
commit count: 9
commit subject: Establish Kongono trusted diagnostic handling through Phase 1H
commit signature: none
commit tree: exact write-tree result
changed paths: 36
modified paths: 22
added paths: 14
deleted/renamed/copied paths: 0
insertions: 6167
deletions: 104
tracked files: 358
file modes: all 100644
remotes: 0
tags: 0
```

The commit path/status set must exactly equal the original Phase 1I Section 4 inventory. All committed bytes must reproduce the corrected ordinal manifest. No Phase 1I-family caller record may appear in the commit.

Before report population, status must be exactly:

```text
modified tracked paths: 0
staged paths: 0
untracked paths with -uall: 4
total status paths with -uall: 4
```

The original Phase 1I report and Phase 1I.1 correction report must both still be zero bytes at this point.

Run post-commit `composer quality` and every original Phase 1I post-commit documentation, link, encoding, stream, mode, security, dependency-topology, API-status, and count proof. Required quality remains 254 tests, 4,279 assertions, zero PHPStan errors, 0 of 138 fixer findings, platform pass, and zero advisories.

If any post-commit requirement fails, do not amend, reset, or create another commit. Keep the correction report zero bytes and report the exact blocker outside it.

---

## 9. Correction report and final state

Populate only:

```text
docs/task reports/Phase_1I_1_correction.md
```

Populate it only after all non-report gates pass. Do not populate `docs/task reports/Phase_1I.md`.

The correction report must remain untracked, unstaged, absent from `HEAD`, strict UTF-8 without BOM, LF-only, free of NUL and trailing whitespace, and terminated by exactly one final LF.

After report population, final status must remain exactly four untracked paths and no tracked or staged change:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/task reports/Phase_1I_1_correction.md
```

Return the correction report's final byte count and uppercase SHA-256 outside the report body.

---

## 10. Acceptance criteria

Phase 1I.1 is accepted only if every item passes:

1. required authorities were read completely;
2. the externally supplied correction-directive identity matched;
3. both report placeholders began as zero-byte ordinary files;
4. the original Phase 1I directive retained its locked identity;
5. the stopped Git baseline plus two correction caller records matched exactly;
6. all original caller, implementation, lockfile, mode, stream, and ordinary-file identities matched;
7. no checkpoint byte was edited;
8. explicit ordinal reconstruction produced exactly 36 entries, 4,012 bytes, and `DB1F...DF3E`;
9. the rejected culture-sensitive hash was not accepted;
10. pre-stage quality passed with 254 tests and 4,279 assertions;
11. exactly the original 36 paths were staged once from the locked literal array;
12. staged status was exactly 22 modified and 14 added with 6,167 insertions and 104 deletions;
13. only four Phase 1I-family caller records remained untracked;
14. every staged blob matched worktree bytes and the corrected manifest;
15. all 358 index entries were stage zero and mode `100644`;
16. `git diff --cached --check` passed;
17. `git write-tree` succeeded and only semantic index state was treated as authoritative;
18. exactly one unsigned commit was created with the locked subject and parent;
19. commit tree and delta matched the staged proof exactly;
20. repository history then contained nine commits and 358 tracked files;
21. post-commit quality passed with 254 tests and 4,279 assertions;
22. all original post-commit architecture, documentation, dependency, security, encoding, stream, mode, link, and count gates passed;
23. no prohibited mutation, history operation, dependency change, or later capability occurred;
24. the original Phase 1I report remained zero bytes and outside `HEAD`;
25. the correction report was populated only after every non-report gate passed;
26. all four Phase 1I-family caller records remained untracked and unstaged; and
27. final status contained no tracked or staged change.

No partial result is a pass.

---

## 11. Standard executor completion report

Use exactly these headings, in this order:

```markdown
# PHASE 1I.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. ROOT CAUSE AND CORRECTION

## 4. BASELINE VERIFIED

## 5. CALLER RECORDS

## 6. MANIFEST PROOF

## 7. CHECKPOINT INVENTORY

## 8. FILES CREATED OR MODIFIED BY EXECUTOR

## 9. ARCHITECTURAL DECISIONS

## 10. DEVIATIONS FROM DIRECTIVE

## 11. DEPENDENCIES ADDED OR REMOVED

## 12. ENVIRONMENT

## 13. COMMANDS RUN

## 14. PRE-STAGE VALIDATION RESULTS

## 15. STAGING EVIDENCE

## 16. COMMIT EVIDENCE

## 17. POST-COMMIT VALIDATION RESULTS

## 18. DOCUMENTATION, SECURITY, AND FILE-SAFETY REVIEW

## 19. WARNINGS, BLOCKERS, AND FINAL GIT STATUS

## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- `Complete` only if all 27 criteria pass;
- the correction directive's independently verified identity;
- the original Phase 1I directive and zero-byte report identities;
- the culture-ordering root cause and corrected ordinal proof;
- confirmation that no accepted content byte changed;
- pre-stage and post-commit quality results;
- exact staged semantic index and `write-tree` evidence;
- new commit ID, parent, tree, subject, redacted author summary, and exact delta;
- confirmation that raw index byte identity was not a gate;
- exact environment warnings and authorized retries;
- final four-path untracked inventory; and
- explicit pass or fail for every acceptance criterion.

Do not include a full private email address. Do not claim success without observed evidence.

The report's final nonblank line must be exactly:

```text
Await architect review before Phase 1J.
```

Return the report byte count and uppercase SHA-256 separately after completion.

---

## 12. Required final architectural outcome

Phase 1I.1 changes no Kongono architecture or accepted content. It corrects only an architect-authored validation constant and preserves the Phase 1H implementation as an immutable local checkpoint.

The eight-package framework boundary, fail-closed permissions, transport-neutral diagnostic handling, direct-call caveats, persistent-runtime doctrine, and all prohibited future capabilities remain exactly as accepted.

Do not begin Phase 1J.
