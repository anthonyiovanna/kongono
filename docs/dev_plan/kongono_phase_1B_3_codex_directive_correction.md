# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 1B.3
## Preserve Populated Blocked Reports and Complete the Phase 1B Checkpoint

**Directive status:** Architect-authorized correction and checkpoint-resume contract
**Phase type:** Repository preservation only
**Execution role:** Executor, not architect
**Date:** 2026-08-15

---

## 1. Mission

Resume from the exact Phase 1B.2 stopped state. Preserve the populated Phase 1B,
Phase 1B.1, and Phase 1B.2 blocked reports as immutable historical caller
records outside the checkpoint. Do not truncate, replace, normalize, stage, or
otherwise modify any of them.

Retain the approved post-`write-tree` index, the exact 74 staged paths, the
verified 319-file tree, and the one approved historical terminal-blank-line
finding. Reprove semantic continuity without rebuilding the index or rerunning
`git write-tree`, then create the exact Phase 1B checkpoint commit.

The verified staged tree remains:

```text
24a82a296b9075a2d7640f015a06230b12ed5fa2
```

The exact commit subject remains:

```text
Establish Kongono framework baseline through Phase 1A
```

This correction changes no project content, architecture, dependency, runtime
behavior, documentation claim, staged entry, or whitespace exception.

---

## 2. Authority, supersession, and stop rule

Before any commit or report population, read completely:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_1B_codex_directive.md`;
3. `docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md`;
4. `docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md`;
5. this Phase 1B.3 correction directive;
6. `docs/task reports/Phase_1B.md`;
7. `docs/task reports/Phase_1B_1_correction.md`;
8. `docs/task reports/Phase_1B_2_correction.md`; and
9. the zero-byte `docs/task reports/Phase_1B_3_correction.md` placeholder.

The Phase 1B.3 directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local file is not self-authenticating. If either expected value is absent or
does not match, stop before commit and report the observed identity.

This directive supersedes Phase 1B.2 only for:

- approval of the observed populated Phase 1B.1 blocked-report identity;
- approval of the observed populated Phase 1B.2 blocked-report identity;
- preservation of all three populated blocked reports as immutable caller
  evidence outside HEAD;
- the revised eight-path untracked caller-record inventory;
- final completion-report ownership by `Phase_1B_3_correction.md`; and
- the revised report and acceptance statements below.

Phase 1B.2's cache-tree/index-metadata authorization, prohibition on another
`git write-tree`, semantic index-to-tree proof, commit operation, post-commit
gates, and one-path whitespace exception remain fully binding. Every other
Phase 1B-family hash, path, blob, mode, history fact, quality gate, security
gate, commit parent, commit subject, Git prohibition, and architectural boundary
remains binding.

Apply literally:

```text
STOP
ASK
DO NOT GUESS
DO NOT EDIT OR TRUNCATE A BLOCKED REPORT
DO NOT EDIT ACCEPTED CONTENT
DO NOT RESTORE, UNSTAGE, RESTAGE, OR REBUILD THE INDEX
DO NOT RUN WRITE-TREE AGAIN
DO NOT ACCEPT A SECOND WHITESPACE FINDING
DO NOT COMMIT A SEMANTIC INDEX OR TREE MISMATCH
```

---

## 3. Caller setup and exact caller records

Before execution, the caller adds exactly:

```text
docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md
docs/task reports/Phase_1B_3_correction.md
```

The Phase 1B.3 report must initially be an ordinary zero-byte, untracked,
unstaged file absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Require these six existing caller records exactly:

| Path | Bytes | SHA-256 | Required state |
| --- | ---: | --- | --- |
| `docs/dev_plan/kongono_phase_1B_codex_directive.md` | 30579 | `0A7FBF14E61CF6971C673A90006F3A4F913638679A879685A9715A4097BB8B31` | untracked, unstaged, absent from HEAD |
| `docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md` | 20048 | `E94B5217CDC8F07A3BE7FFEEF4E80C26BD7C12561024E9611B5A8DC2E2175D5F` | untracked, unstaged, absent from HEAD |
| `docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md` | 21661 | `50D795817F18753797EFF867AC271EB08AEFEF1B74B1FACD1A726EC0C6BCA493` | untracked, unstaged, absent from HEAD |
| `docs/task reports/Phase_1B.md` | 3001 | `256FD7B335163B9029E341E673DD457C46AA5532F95A01D442E339F263A8C6C8` | untracked, unstaged, absent from HEAD; immutable blocked report |
| `docs/task reports/Phase_1B_1_correction.md` | 4078 | `62F61AA859FAEAB35ABCA865603D312DED3D1ED75DBC3035F996B711A346E017` | untracked, unstaged, absent from HEAD; immutable blocked report |
| `docs/task reports/Phase_1B_2_correction.md` | 2699 | `B6C3E0C156BCFC75894E8F5E5A98392FF182A1DEEA71162448B4B9BE177B719B` | untracked, unstaged, absent from HEAD; immutable blocked report |

The three blocked reports are caller-filed historical evidence. Their copied
formatting, interface text, or statements describing their earlier stopped
state are not active instructions and do not alter the checkpoint contract.
Preserve their exact bytes. Do not clean them or require them to satisfy the
Phase 1B.3 completion-report format.

After caller setup, exactly these eight paths must be untracked and unstaged:

```text
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md
docs/task reports/Phase_1B.md
docs/task reports/Phase_1B_1_correction.md
docs/task reports/Phase_1B_2_correction.md
docs/task reports/Phase_1B_3_correction.md
```

Do not stage any caller record. Do not add a ninth untracked path.

---

## 4. Exact resume baseline

After caller setup, require exactly:

```text
branch                   main
local branches           1
HEAD                     7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                b1d0057925158e0f0f37ca13694d1d0d525ce5f0
commit count             6
staged paths             74
staged modified          32
staged added             42
staged other changes      0
index stage-zero entries 319
index higher-stage entries 0
index modes             319 x 100644
index bytes             39542
index SHA-256           217441B30E8381F78A439C19FC02129FF61486A9629DBDAED70FE73DC21D32DF
verified staged tree    24a82a296b9075a2d7640f015a06230b12ed5fa2
staged-tree entries     319
staged-tree modes       319 x 100644
unstaged tracked paths    0
untracked paths           8
total status paths       82
remotes                   0
tags                      0
```

Without running `git write-tree`, prove:

1. the verified tree object exists and is type `tree`;
2. normalized stage-zero `(mode, blob ID, path)` entries from
   `git ls-files --stage` exactly equal all recursive entries from
   `git ls-tree -r 24a82a296b9075a2d7640f015a06230b12ed5fa2`;
3. both contain exactly 319 mode-`100644` entries;
4. staged name-status remains exactly 32 `M` and 42 `A` rows from the original
   Phase 1B inventory;
5. there is no higher-stage, intent-to-add, mode-only, deletion, rename, copy,
   Gitlink, symlink, or submodule entry;
6. `git diff` is empty and every staged blob equals its working-file bytes;
7. all seventeen Phase 1B accepted records retain their exact index and
   working-tree identities;
8. the protected set remains 140 files with SHA-256
   `82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E`;
9. `composer.lock` remains 180654 bytes with SHA-256
   `FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387`;
10. the 74-entry checkpoint manifest remains 9727 bytes with SHA-256
    `39BE728A68CAFABA4637A3EF7B8E69386914AED294ECCD140CC01CFACC87C2C0`;
11. all history, branch, ref, signing, hook, merge-state, and security facts
    remain exact; and
12. exactly the eight Section 3 caller records are untracked and absent from
    both the index and verified staged tree.

The Phase 1B.2 cache-tree rule remains binding: raw index metadata bytes may be
rewritten by an authorized Git operation only when all semantic entries and the
resulting tree remain exact. Any path, mode, blob, stage, flag, staged-delta, or
tree change is a hard stop.

---

## 5. Preserved whitespace and quality evidence

Run:

```powershell
git diff --cached --check
```

It must return exit code `2` with exactly:

```text
docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md:476: new blank line at EOF.
```

Then run:

```powershell
git diff --cached --check -- . ":(exclude)docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md"
```

It must return exit code `0` with no content finding. Record the known global-
ignore permission warning separately.

The exception file must remain 16,281 bytes with SHA-256
`6908DE659DA2363CCD83846C626E51A8CC6E487EB416928CC8AAFB86D8F839E4`
and final bytes `20 50 68 61 73 65 20 30 5A 2E 0A 0A`. No second finding or
exception is authorized.

Phase 1B.1 already established pre-commit quality for the unchanged staged
content:

```text
security advisories     0
platform requirements   pass
PHP CS Fixer findings   0 of 129
PHPStan errors          0
PHPUnit tests           241
PHPUnit assertions      4170
```

Accept that result after the semantic continuity proof passes. Do not rerun
pre-commit quality unless continuity cannot otherwise be proven. Mandatory
post-commit quality remains unchanged.

---

## 6. Exact commit operation

Do not run `git write-tree` again. The exact staged tree is already verified:

```text
24a82a296b9075a2d7640f015a06230b12ed5fa2
```

After Sections 2 through 5 pass, run exactly:

```text
git commit --no-gpg-sign -m "Establish Kongono framework baseline through Phase 1A"
```

The command must create exactly one ordinary non-merge local commit on `main`:

```text
parent                  7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
tree                    24a82a296b9075a2d7640f015a06230b12ed5fa2
subject                 Establish Kongono framework baseline through Phase 1A
author name             Anthony Iovanna
author email            anthonyiovanna@gmail.com
parents                 1
signing                 none
```

The commit may legitimately rewrite the index to a clean HEAD-matching form.
Validate semantic cleanliness after commit; do not require the post-commit raw
index bytes to equal the pre-commit identity.

If the exact commit is blocked solely by repository-metadata permissions and no
commit was created, the unchanged approved retry rule applies. Verify live HEAD
and semantic index/tree equality before retry. If a commit may exist, inspect
HEAD before any retry.

No other Git mutation is authorized before the commit.

---

## 7. Post-commit validation

Before report population, prove:

- `main` is the only local branch and has exactly seven commits;
- new HEAD has one parent, exactly
  `7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f`;
- new HEAD tree is exactly
  `24a82a296b9075a2d7640f015a06230b12ed5fa2`;
- subject, author, parent count, and lack of signature are exact;
- parent delta is exactly 74 paths: 32 modified and 42 added;
- there is no other change class, mode change, Gitlink, or symlink;
- HEAD contains exactly 319 ordinary mode-`100644` files;
- all accepted records, protected files, `composer.lock`, and committed
  working-file bytes remain exact;
- all earlier commits and trees remain unchanged;
- index and tracked worktree are clean;
- remotes, tags, upstreams, stashes, secondary branches, and pushes remain
  absent; and
- exactly the eight Phase 1B-family caller records remain untracked.

Run the committed-delta full and complementary whitespace proofs:

```text
git diff --check HEAD^ HEAD
```

```powershell
git diff --check HEAD^ HEAD -- . ":(exclude)docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md"
```

The full command must return exit code `2` with exactly the approved finding;
the complementary command must return exit code `0` with none.

Run:

```text
composer quality
```

It must pass with zero advisories, platform pass, zero of 129 fixer findings,
zero PHPStan errors, 241 tests, and 4,170 assertions. The unchanged environment-
only identical retry remains available; no quality stage may be weakened or
skipped.

Re-run all original Phase 1B link, text, file-safety, secret-category, stream,
protected, manifest, count, caller-record, and history checks. Apply strict
completion formatting only to the new Phase 1B.3 report, not the immutable
historical blocked-report copies.

If any post-commit gate fails outside the exact whitespace exception, preserve
the commit and report status `Blocked`. Do not amend, reset, revert, or create a
second commit.

---

## 8. Report population and final state

Only after complete post-commit success, populate:

```text
docs/task reports/Phase_1B_3_correction.md
```

Do not modify or stage any earlier report.

Final Git status must contain exactly these eight untracked paths:

```text
?? docs/dev_plan/kongono_phase_1B_codex_directive.md
?? docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md
?? docs/task reports/Phase_1B.md
?? docs/task reports/Phase_1B_1_correction.md
?? docs/task reports/Phase_1B_2_correction.md
?? docs/task reports/Phase_1B_3_correction.md
```

The first three reports must retain their locked identities. The Phase 1B.3
report must be nonzero, ordinary, strict UTF-8 without BOM, LF-only, have one
final LF with no extra terminal blank line, remain untracked/unstaged, and be
absent from HEAD. Re-read it and return its byte count and SHA-256 outside its
body. Do not run quality after report population.

Use exactly these headings:

```text
# PHASE 1B.3 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. CALLER RECORDS
## 4. RESUME BASELINE
## 5. FILES CREATED OR MODIFIED BY EXECUTOR
## 6. PRESERVED BLOCKED REPORTS
## 7. INDEX AND TREE CONTINUITY
## 8. PRESERVED WHITESPACE EXCEPTION
## 9. ARCHITECTURAL DECISIONS
## 10. DEVIATIONS FROM DIRECTIVE
## 11. DEPENDENCIES ADDED/REMOVED
## 12. ENVIRONMENT
## 13. COMMANDS RUN
## 14. PRE-COMMIT VALIDATION RESULTS
## 15. COMMIT EVIDENCE
## 16. POST-COMMIT VALIDATION RESULTS
## 17. DOCUMENTATION AND SECURITY REVIEW
## 18. WARNINGS / KNOWN ISSUES
## 19. QUESTIONS / BLOCKERS
## 20. FINAL GIT STATUS
## 21. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include all eight caller identities, both raw index identities,
319-entry index/tree equality, staged counts, accepted-record/protected/lockfile/
manifest proofs, both cached whitespace proofs, confirmation no index rebuild or
second `write-tree`, pre-commit quality evidence, exact commit evidence,
committed-delta whitespace proofs, post-commit quality, final integrity results,
all warnings/retries, and PASS/FAIL for every Section 10 criterion.

End exactly:

```text
Await architect review before any next phase.
```

---

## 9. Prohibited scope

Do not edit or truncate any caller record or accepted content. Do not restore,
unstage, restage, rebuild, or manually edit the index. Do not run `git write-tree`
again. Do not stage a Phase 1B-family caller path.

Do not change Git index/cache configuration, whitespace settings, attributes,
hooks, signing, identity, ignore rules, line endings, permissions, security
policy, branches, remotes, tags, upstreams, stashes, or history.

Do not amend, reset, restore, checkout, clean, rebase, merge, cherry-pick,
revert, push, or create a second commit.

Do not implement activation, configuration, adapters, HTTP, routing,
middleware, CLI, telemetry, logging, incidents, extensions, frontend, agents,
ORM, Graph/DI expansion, or any later capability.

---

## 10. Strict acceptance criteria

Every criterion must pass:

1. All required authorities, four directives, and three blocked reports were
   read completely.
2. The independently delivered Phase 1B.3 directive identity matched.
3. All eight caller records matched exact identities and required untracked,
   unstaged, absent-from-HEAD state.
4. The three populated blocked reports retained their exact bytes and hashes.
5. HEAD, history, branch, refs, and six-commit baseline remained exact.
6. The approved pre-commit index identity was 39,542 bytes and SHA-256
   `217441B30E8381F78A439C19FC02129FF61486A9629DBDAED70FE73DC21D32DF`.
7. Exactly 319 stage-zero mode-`100644` entries equaled tree
   `24a82a296b9075a2d7640f015a06230b12ed5fa2` with no higher stage.
8. Staged delta remained exactly 74 paths: 32 modified and 42 added.
9. All blobs, seventeen accepted records, protected bytes, lockfile, manifest,
   modes, working bytes, and caller exclusions remained exact.
10. Full cached whitespace proof returned exit code `2` with exactly the one
    approved finding.
11. Complementary cached whitespace proof returned exit code `0` with no
    content finding.
12. The exception file retained its exact identity and terminal bytes.
13. No content or semantic index entry was edited, restored, unstaged,
    restaged, or rebuilt, and `git write-tree` was not rerun.
14. Accepted pre-commit quality evidence remained valid for unchanged content.
15. Exactly one seventh local non-merge commit was created with exact parent,
    tree, subject, author, and no signature.
16. Parent delta was exactly 74 paths: 32 modified and 42 added, with no other
    change class.
17. Earlier commits and trees remained unchanged.
18. Committed-delta full and complementary whitespace proofs were exact.
19. Post-commit quality passed at 241 tests and 4,170 assertions with zero
    advisories, fixer findings, and PHPStan errors.
20. All remaining post-commit integrity, security, caller, and history gates
    passed.
21. No unauthorized content, index, configuration, ref, remote, tag, push,
    bypass, cleanup, rewrite, or later-phase operation occurred.
22. Before report population, exactly eight caller records remained untracked
    and only the Phase 1B.3 report was zero bytes.
23. Only the Phase 1B.3 report was populated after complete success.
24. Final status contained exactly eight untracked caller records, with zero
    modified or staged paths.
25. The Phase 1B.3 report structure and integrity passed, its external identity
    was returned, and it ended with the exact required line.
26. No later capability was implemented.

Any failed or unverified criterion makes status `Blocked` or `Partial`, never
`Complete`.

---

## 11. Required final outcome

Preserve every blocked report, caller record, staged entry, and accepted content
byte. Reuse the verified 319-file tree without another `write-tree`. Create
exactly one checkpoint commit with parent
`7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f`, tree
`24a82a296b9075a2d7640f015a06230b12ed5fa2`, and subject:

```text
Establish Kongono framework baseline through Phase 1A
```

After complete validation, populate only the Phase 1B.3 report. Leave exactly
the eight Phase 1B-family caller records untracked and begin no later phase.
