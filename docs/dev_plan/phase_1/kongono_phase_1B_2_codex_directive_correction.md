# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 1B.2
## Accept Cache-Tree Index Metadata and Complete the Phase 1B Checkpoint

**Directive status:** Architect-authorized correction and checkpoint-resume contract
**Phase type:** Repository preservation only
**Execution role:** Executor, not architect
**Date:** 2026-08-15

---

## 1. Mission

Resume from the exact Phase 1B.1 post-`write-tree` state. Accept the current
Git index container identity and its one cache-tree extension only because its
319 semantic stage-zero entries exactly equal the already-created staged tree:

```text
24a82a296b9075a2d7640f015a06230b12ed5fa2
```

Do not restore, reconstruct, unstage, restage, or rebuild the index. Do not run
`git write-tree` again. Reprove the staged path, mode, blob, tree, whitespace,
caller-record, and Git-history facts without changing content. Then create the
single Phase 1B checkpoint commit using the existing verified staged tree.

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
4. this Phase 1B.2 correction directive;
5. `docs/task reports/Phase_1B.md`;
6. the zero-byte `docs/task reports/Phase_1B_1_correction.md`; and
7. the zero-byte `docs/task reports/Phase_1B_2_correction.md`.

The Phase 1B.2 directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local file is not self-authenticating. If either expected value is absent or
does not match, stop before any commit and report the observed identity.

This directive supersedes the Phase 1B and Phase 1B.1 contracts only for:

- architect approval of the exact post-`write-tree` index identity in Section 4;
- recognition that Git's cache-tree and stat-cache/index-container bytes are
  not project-content invariants once semantic entry and tree equality pass;
- removal of the Phase 1B.1 requirement that `git write-tree` leave the raw
  index byte identity unchanged;
- prohibition on a second `git write-tree` call;
- direct use of the already-created and independently verified tree object;
- the revised six-path untracked caller-record inventory;
- preservation of the now-populated historical `Phase_1B.md` caller record;
- preservation of the zero-byte `Phase_1B_1_correction.md` caller record;
- final completion-report ownership by `Phase_1B_2_correction.md`; and
- the revised report and acceptance statements in Sections 8 and 10.

Phase 1B.1's exact one-path terminal-blank-line exception remains fully binding.
Every other Phase 1B/1B.1 path, blob, mode, history fact, quality gate, security
gate, commit parent, commit subject, Git prohibition, and architectural boundary
remains binding.

Apply literally:

```text
STOP
ASK
DO NOT GUESS
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
docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
docs/task reports/Phase_1B_2_correction.md
```

The Phase 1B.2 report must initially be an ordinary zero-byte, untracked,
unstaged file absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Require these existing caller records exactly:

| Path | Bytes | SHA-256 | Required state |
| --- | ---: | --- | --- |
| `docs/dev_plan/kongono_phase_1B_codex_directive.md` | 30579 | `0A7FBF14E61CF6971C673A90006F3A4F913638679A879685A9715A4097BB8B31` | untracked, unstaged, absent from HEAD |
| `docs/task reports/Phase_1B.md` | 3001 | `256FD7B335163B9029E341E673DD457C46AA5532F95A01D442E339F263A8C6C8` | untracked, unstaged, absent from HEAD; preserve as historical blocked caller record |
| `docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md` | 20048 | `E94B5217CDC8F07A3BE7FFEEF4E80C26BD7C12561024E9611B5A8DC2E2175D5F` | untracked, unstaged, absent from HEAD |
| `docs/task reports/Phase_1B_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` | untracked, unstaged, absent from HEAD; remain zero bytes |

`Phase_1B.md` was populated by the caller after the original blocked execution.
Its current 3,001-byte form includes copied chat/interface text and is not a
strict completion report. It is immutable historical caller evidence outside
the checkpoint, not accepted project content. Do not clean, normalize, replace,
stage, or reinterpret it. Its formatting does not create an exception for any
committed path or for the Phase 1B.2 completion report.

After caller setup, exactly these six paths must be untracked and unstaged:

```text
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
docs/task reports/Phase_1B.md
docs/task reports/Phase_1B_1_correction.md
docs/task reports/Phase_1B_2_correction.md
```

Do not stage any of them. Do not add a seventh untracked path.

---

## 4. Exact post-write-tree resume baseline

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
staged deleted            0
staged renamed            0
staged copied             0
staged mode-only          0
index stage-zero entries 319
index higher-stage entries 0
index modes             319 x 100644
index bytes             39542
index SHA-256           217441B30E8381F78A439C19FC02129FF61486A9629DBDAED70FE73DC21D32DF
index TREE markers        1
verified staged tree    24a82a296b9075a2d7640f015a06230b12ed5fa2
staged-tree entries     319
staged-tree modes       319 x 100644
unstaged tracked paths    0
untracked paths           6
total status paths       80
remotes                   0
tags                      0
```

The architect approves the transition from the earlier 39,022-byte index to the
current 39,542-byte index solely as Git-generated cache-tree metadata produced
by the already-authorized `git write-tree`. The current index must contain the
same 319 `(mode, blob ID, stage, path)` entries as before, with no higher-stage
entry, intent-to-add entry, path change, blob change, or mode change.

Raw index bytes are not a durable project-content identity. Git may rewrite
cache-tree or stat-cache metadata during an authorized Git operation. Such a
metadata-only rewrite is not a failure if and only if all semantic index entries,
the staged name-status inventory, staged blobs, and the resulting tree remain
exact. A path, mode, blob, stage, flag, staged-delta, or tree change remains a
hard stop.

Before commit, verify without running `git write-tree`:

1. `git cat-file -t 24a82a296b9075a2d7640f015a06230b12ed5fa2`
   returns exactly `tree`;
2. normalized stage-zero output from `git ls-files --stage` exactly equals the
   full recursive `(mode, blob ID, path)` output from
   `git ls-tree -r 24a82a296b9075a2d7640f015a06230b12ed5fa2`;
3. both inventories contain exactly 319 entries, all mode `100644`;
4. `git diff --cached --name-status` contains exactly the Phase 1B 32 `M` and
   42 `A` rows;
5. `git diff` is empty and every staged blob equals its working-file bytes;
6. the seventeen Phase 1B Section 3.4 record identities remain exact in the
   index and working tree;
7. the protected set remains 140 files with aggregate SHA-256
   `82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E`;
8. `composer.lock` remains 180654 bytes with SHA-256
   `FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387`;
9. the 74-entry checkpoint manifest remains 9727 bytes with SHA-256
   `39BE728A68CAFABA4637A3EF7B8E69386914AED294ECCD140CC01CFACC87C2C0`;
10. all history, branch, ref, signing, hook, merge-state, and caller-record facts
    remain exact; and
11. the six untracked paths are exactly Section 3 and are absent from both the
    index and the verified staged tree.

If semantic index-to-tree equality fails, stop. Do not restore or repair the
index. If only raw index bytes change during these read-only inspections, first
prove whether the change is metadata-only using the same semantic comparison.
Continue only when the 319 entries and verified tree remain exact; record both
raw identities in the report.

---

## 5. Preserved one-path whitespace exception

Run the full cached check:

```powershell
git diff --cached --check
```

It must return exit code `2` with exactly this one Git content finding:

```text
docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md:476: new blank line at EOF.
```

Run the complementary check:

```powershell
git diff --cached --check -- . ":(exclude)docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md"
```

It must return exit code `0` with no Git content finding. The known inability to
read `C:\Users\antho\.config\git\ignore` may appear on stderr and must be
recorded separately as an environment warning.

The exception file must remain exactly 16,281 bytes with SHA-256
`6908DE659DA2363CCD83846C626E51A8CC6E487EB416928CC8AAFB86D8F839E4`
and final bytes `20 50 68 61 73 65 20 30 5A 2E 0A 0A`.

No second content finding is authorized. Do not edit the file, alter whitespace
configuration, add another exclusion, or broaden the exception category.

---

## 6. Pre-commit continuity and exact commit

Phase 1B.1 already completed the pre-commit canonical quality gate against the
unchanged 74 staged paths:

```text
security advisories     0
platform requirements   pass
PHP CS Fixer findings   0 of 129
PHPStan errors          0
PHPUnit tests           241
PHPUnit assertions      4170
```

This Phase 1B.2 correction accepts that result as pre-commit evidence after
Sections 3 through 5 prove semantic continuity. Do not rerun pre-commit quality
unless continuity cannot otherwise be proven. This does not waive the mandatory
post-commit quality run.

Do not run `git write-tree` again. The staged tree is already recorded and must
remain exactly:

```text
24a82a296b9075a2d7640f015a06230b12ed5fa2
```

After every pre-commit proof passes, run exactly:

```text
git commit --no-gpg-sign -m "Establish Kongono framework baseline through Phase 1A"
```

The command must create exactly one ordinary non-merge local commit on `main`
with:

```text
parent                  7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
tree                    24a82a296b9075a2d7640f015a06230b12ed5fa2
subject                 Establish Kongono framework baseline through Phase 1A
author name             Anthony Iovanna
author email            anthonyiovanna@gmail.com
parents                 1
signing                 none
```

The commit may legitimately rewrite the index from its staged form to a clean
HEAD-matching form. Do not require the post-commit raw index size or SHA-256 to
equal the pre-commit identity. Instead, require zero staged entries, zero
unstaged tracked paths, exact HEAD/worktree equality, and the exact commit tree.

If the exact commit fails solely because repository metadata is sandbox-blocked
and no commit was created, the unchanged Phase 1B metadata-permission retry rule
applies. Before retry, verify live HEAD and all semantic index/tree facts. If a
commit may have been created, inspect HEAD before any retry.

Do not stage a caller record, add or remove an index entry, run `write-tree`, or
perform any other Git mutation before the exact commit.

---

## 7. Post-commit validation

Before report population, prove:

- `main` is the only local branch and has exactly seven commits;
- new HEAD has exactly one parent:
  `7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f`;
- new HEAD tree is exactly
  `24a82a296b9075a2d7640f015a06230b12ed5fa2`;
- subject, author, parent count, and lack of signature are exact;
- parent-to-HEAD delta contains exactly 74 paths: 32 modified and 42 added;
- no deletion, rename, copy, type change, mode change, Gitlink, symlink, or
  submodule exists;
- HEAD contains exactly 319 ordinary mode-`100644` files;
- all seventeen accepted records, protected files, `composer.lock`, and
  committed working-file bytes remain exact;
- all six earlier commits, trees, subjects, and parents remain unchanged;
- the index and tracked worktree are clean;
- there is no remote, tag, upstream, stash, secondary branch, push, or other
  unexpected Git operation; and
- only the six Phase 1B-family caller records remain untracked.

Run:

```text
git diff --check HEAD^ HEAD
```

It must return exit code `2` with exactly the same approved line-476 finding.
Then run:

```powershell
git diff --check HEAD^ HEAD -- . ":(exclude)docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md"
```

It must return exit code `0` with no content finding.

Run the unchanged post-commit:

```text
composer quality
```

It must pass with zero advisories, platform pass, zero of 129 fixer findings,
zero PHPStan errors, 241 tests, and 4,170 assertions. The original environment-
only identical Composer retry rule remains available. Do not weaken or skip any
quality stage.

Re-run the Phase 1B link, text, file-safety, secret-category, stream, protected
aggregate, manifest, implementation-count, caller-record, and Git-history
checks. Apply strict formatting checks to committed content and the Phase 1B.2
report. Preserve the locked 3,001-byte historical `Phase_1B.md` caller record
without treating its copied interface text as committed-content formatting
authority.

If any post-commit check fails outside the exact one-path whitespace exception,
preserve the commit and report status `Blocked`. Do not amend, reset, revert,
restore, or create a second commit.

---

## 8. Report population and final state

Only after complete post-commit success, populate:

```text
docs/task reports/Phase_1B_2_correction.md
```

Do not modify or populate either earlier report. Do not stage any report.

Final Git status must contain exactly these six untracked paths and nothing
else:

```text
?? docs/dev_plan/kongono_phase_1B_codex_directive.md
?? docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md
?? docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
?? docs/task reports/Phase_1B.md
?? docs/task reports/Phase_1B_1_correction.md
?? docs/task reports/Phase_1B_2_correction.md
```

`Phase_1B.md` must retain its locked 3,001-byte identity.
`Phase_1B_1_correction.md` must remain zero bytes. The Phase 1B.2 report must be
nonzero, ordinary, strict UTF-8 without BOM, LF-only, final-newline terminated,
have no extra terminal blank line, remain untracked/unstaged, and be absent from
HEAD. Re-read it from disk and return its byte count and SHA-256 outside its
body. Do not run quality after report population.

Use exactly these headings:

```text
# PHASE 1B.2 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. CALLER RECORDS
## 4. POST-WRITE-TREE RESUME BASELINE
## 5. FILES CREATED OR MODIFIED BY EXECUTOR
## 6. APPROVED INDEX-METADATA TRANSITION
## 7. PRESERVED WHITESPACE EXCEPTION
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. PRE-COMMIT CONTINUITY RESULTS
## 14. TREE AND COMMIT EVIDENCE
## 15. POST-COMMIT VALIDATION RESULTS
## 16. DOCUMENTATION AND SECURITY REVIEW
## 17. WARNINGS / KNOWN ISSUES
## 18. QUESTIONS / BLOCKERS
## 19. FINAL GIT STATUS
## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- all six caller-record identities and states;
- both the pre- and post-`write-tree` raw index identities;
- proof the raw transition contained no semantic entry change;
- normalized 319-entry index-to-tree equality evidence;
- the exact verified tree hash and tree modes;
- 74-path, 32-modified, 42-added staged continuity;
- all seventeen accepted record, protected, lockfile, and manifest proofs;
- full and complementary cached whitespace outputs and exit codes;
- confirmation no index restoration, unstage, restage, rebuild, or second
  `write-tree` occurred;
- the accepted pre-commit quality evidence and mandatory post-commit quality;
- exact commit, tree, parent, subject, author, mode, and parent-delta evidence;
- equivalent full and complementary committed-delta whitespace proof;
- final links, text, security, stream, file-safety, history, and caller-state
  results;
- every environment warning, approved retry, and safe resolution;
- PASS or FAIL for all Section 10 criteria; and
- the exact final sentence below.

End exactly:

```text
Await architect review before any next phase.
```

---

## 9. Prohibited scope

Do not edit accepted content or any existing caller record. Do not restore,
unstage, restage, rebuild, or manually modify the index. Do not run
`git write-tree` again. Do not stage any Phase 1B-family caller path.

Do not change Git cache-tree configuration, index version, whitespace settings,
attributes, hooks, signing, identity, ignore rules, line endings, permissions,
security policy, branches, remotes, tags, upstreams, stashes, or history.

Do not amend, reset, restore, checkout, clean, rebase, merge, cherry-pick,
revert, push, or create a second commit.

Do not implement activation, configuration, environment loading, adapters,
HTTP, routing, middleware, CLI, telemetry, logging, incidents, extensions,
frontend, agents, ORM, Graph/DI expansion, or any Phase 1C or later capability.

---

## 10. Strict acceptance criteria

Every item must pass:

1. All required authorities and three directives were read completely.
2. The independently delivered Phase 1B.2 directive identity matched.
3. All six caller records matched exact path, bytes, hash, tracked/index, and
   required report state.
4. HEAD, branch, history, refs, and six-commit baseline remained exact.
5. The current index began at 39,542 bytes and SHA-256
   `217441B30E8381F78A439C19FC02129FF61486A9629DBDAED70FE73DC21D32DF`.
6. The index contained exactly 319 stage-zero entries, all mode `100644`, with
   no higher-stage or intent-to-add entry.
7. Normalized index entries exactly equaled all 319 entries of tree
   `24a82a296b9075a2d7640f015a06230b12ed5fa2`.
8. The staged delta remained exactly 74 paths: 32 modified and 42 added.
9. All staged blobs, seventeen accepted records, protected bytes, lockfile,
   manifest, modes, working bytes, and caller exclusions remained exact.
10. Full cached whitespace proof returned exit code `2` with exactly the one
    approved finding.
11. Complementary cached whitespace proof returned exit code `0` with no
    content finding.
12. The exception file retained its exact size, hash, and terminal bytes.
13. No accepted content or semantic index entry was changed, restored, unstaged,
    restaged, or rebuilt, and `git write-tree` was not rerun.
14. Phase 1B.1 pre-commit quality evidence was valid for the semantically
    unchanged staged content.
15. Exactly one seventh local non-merge commit was created with the exact
    parent, tree, subject, author, and no signature.
16. Parent-to-HEAD delta was exactly 74 paths: 32 modified and 42 added, with no
    other change class.
17. Earlier commits and trees remained unchanged.
18. Equivalent full and complementary committed-delta whitespace proofs were
    exact.
19. Post-commit quality passed at 241 tests and 4,170 assertions with zero
    advisories, fixer findings, and PHPStan errors.
20. All other post-commit link, text, security, stream, file-safety, manifest,
    protected, count, caller-record, and history gates passed.
21. No unauthorized content, index, configuration, ref, remote, tag, push,
    bypass, cleanup, rewrite, or later-phase operation occurred.
22. Before report population, exactly six caller records remained untracked;
    `Phase_1B_1_correction.md` and `Phase_1B_2_correction.md` were zero bytes.
23. Only `Phase_1B_2_correction.md` was populated after complete success.
24. Final status contained exactly the six untracked caller records, with zero
    modified or staged paths.
25. The report structure and integrity passed, its external bytes and SHA-256
    were returned, and it ended with the exact required line.
26. No Phase 1C implementation or later capability occurred.

Any failed or unverified criterion makes status `Blocked` or `Partial`, never
`Complete`.

---

## 11. Required final outcome

Accept the exact Git-generated cache-tree index form only after proving that its
319 semantic entries equal the already-created verified tree. Preserve the one
historical terminal-blank-line exception, all 74 staged paths, and all accepted
content bytes. Do not run `write-tree` again.

Create exactly one commit with parent
`7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f`, tree
`24a82a296b9075a2d7640f015a06230b12ed5fa2`, and subject:

```text
Establish Kongono framework baseline through Phase 1A
```

After complete validation, populate only the Phase 1B.2 report. Leave exactly
the six Phase 1B-family caller records untracked and nothing else. Do not begin
the next phase.
