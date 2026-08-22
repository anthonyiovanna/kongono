# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0I.1

## Phase 0I Caller-Record Path Correction and Checkpoint Retry

## Mission

Correct the caller-record setup that blocked Phase 0I, preserve the blocked report as historical evidence, and complete the originally authorized Phase 0H repository checkpoint.

Authorize only:

1. exact rename of the untracked Phase 0I directive to add its missing `.md` extension;
2. preservation and inclusion of the populated blocked Phase 0I report;
3. complete read-only reverification of the accepted Phase 0H state;
4. exact staging of the accepted checkpoint and both Phase 0I directives;
5. one normal third local commit on `main`;
6. post-commit integrity and quality verification;
7. the required correction completion report.

No framework content edit or Phase 0J work is authorized.

---

# 1. AUTHORITY, SUPERSESSION, AND STOP RULE

You are the checkpoint correction executor, not the architect.

Read completely:

1. `AGENTS.md` and all repository governance;
2. the original Phase 0I directive;
3. the blocked `docs/task reports/Phase_0I.md` report;
4. accepted authority and implementation through Phase 0H;
5. this Phase 0I.1 correction directive.

This directive supersedes the original Phase 0I directive only as follows:

- the original directive currently exists at an unclassified path without `.md`;
- this correction authorizes its exact path-only rename;
- the populated `Phase_0I.md` is now an accepted historical blocked report and will enter the checkpoint;
- `Phase_0I_1_correction.md`, not `Phase_0I.md`, is the new zero-byte caller-managed report placeholder excluded from the commit;
- path, status, staging, tracked-file, and commit-delta counts are replaced by this directive.

Every other original Phase 0I rule remains in force.

Stop before mutation if any byte, path, hash, Git fact, or inventory differs from this directive. Do not repair any difference.

If blocked:

```text
STOP
DO NOT FIX
DO NOT STAGE
DO NOT COMMIT
REPORT THE EXACT BLOCKER
```

---

# 2. ACCEPTED BLOCKED BASELINE

Before the caller adds Phase 0I.1 records, require:

```text
Branch: main
Only local branch: main
HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f
HEAD parent: 136822352a0caac199506cbddb4c9a539001c592
Commit count: 2
HEAD subject: Establish Kongono framework baseline through Phase 0F
Tracked files: 178
Tracked modes: 178 x 100644
Tracked modifications: 11
Untracked paths: 6
Total status paths: 17
Staged paths: 0
Remotes: 0
Tags: 0
```

The 11 tracked modifications remain exactly those listed in original Phase 0I Section 3, with the same `323 insertions, 22 deletions` diff.

Require these exact six untracked paths:

```text
docs/adr/0024-exact-direct-relation-selection.md
    4,446 bytes
    SHA-256 9E13C2B0D7E08F21A1BA727E7A4799A07E30C3E839C8B79823B6D42172590C66

docs/dev_plan/kongono_phase_0H_codex_directive.md
    32,191 bytes
    SHA-256 9070F0E7C72D98578275DFA8C20900CB4B50140B334E8F649D2092B8E515F25A

docs/dev_plan/kongono_phase_0I_codex_directive
    24,650 bytes
    SHA-256 4DEA066908DA06E8B88F4DA2FE9AA7C87217F52AE2F39267FE4AE43D6BC111E3

docs/task reports/Phase_0G.md
    22,011 bytes
    SHA-256 46D045825169CB35BDC4080704B8D05896B601473AD18CAFFE512D997D43DD06

docs/task reports/Phase_0H.md
    21,110 bytes
    SHA-256 361ED188B7857D355A5EF15EC05F1E294D60327B1C4C88F41C7A907A6EA0215C

docs/task reports/Phase_0I.md
    15,105 bytes
    SHA-256 658094924AE55F1AE6D8ECF67A9078F256DA9B964E8CE8943D8AA599AB136F96
```

The populated Phase 0I report is accepted evidence of a correct mandatory stop. Preserve it byte-for-byte. Its `Blocked` status is historical and does not prevent this explicitly authorized retry.

---

# 3. PHASE 0I.1 CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0I_1_codex_directive_correction.md
docs/task reports/Phase_0I_1_correction.md
```

The correction directive must be a non-empty exact caller-supplied copy of this artifact. Record its size and SHA-256 and do not edit it.

The correction report must be exactly zero bytes. Do not populate, stage, commit, rename, or delete it. Return the completed report in the response.

After caller setup and before the authorized rename, require:

```text
Tracked modifications: 11
Untracked paths: 8
Total status paths: 19
Staged paths: 0
Physical worktree paths: 186
```

No other path is authorized.

---

# 4. EXACT AUTHORIZED PATH CORRECTION

After the complete preflight in Section 5 passes, rename exactly:

```text
FROM: docs/dev_plan/kongono_phase_0I_codex_directive
TO:   docs/dev_plan/kongono_phase_0I_codex_directive.md
```

Use one native filesystem rename within the repository. Do not use Git staging as the rename mechanism. Do not copy-and-delete, edit, normalize, or rewrite the file.

Immediately prove:

- the source path is absent;
- the target path exists;
- target size remains 24,650 bytes;
- target SHA-256 remains `4DEA066908DA06E8B88F4DA2FE9AA7C87217F52AE2F39267FE4AE43D6BC111E3`;
- no other path or byte changed;
- status still contains exactly 11 tracked modifications and eight untracked paths.

Any mismatch is a blocker. Do not reverse, retry with another name, or continue.

---

# 5. REQUIRED PREFLIGHT AND CONTENT REVERIFICATION

Before the rename, run all baseline, environment, Git, author/signing/hook, record-hash, inventory, secret, file-safety, link, Phase 0H architecture, focused-test, and full-quality checks required by original Phase 0I Sections 6 and 7.

Require the accepted quality baseline:

```text
PHPUnit: 119 tests, 513 assertions
PHPStan: 0 errors
PHP CS Fixer: 0 of 89 files require a fix
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Composer warnings remain limited to the missing license and existing unreleased local `@dev` or `*` constraints.

Use authorized access only when the restricted environment blocks the unchanged real advisory lookup or ignored tool caches. Never weaken or skip a check.

Record a path/mode/size/SHA-256 inventory outside the repository before the rename. After the rename, recompute it and prove that the only difference is the authorized path name. File bytes must be identical.

After the rename, the worktree remains exactly 186 physical paths. Exactly 185 are intended for commit. The sole excluded path is:

```text
docs/task reports/Phase_0I_1_correction.md
```

---

# 6. CONTENT IMMUTABILITY AND SECURITY

Apart from the exact path correction in Section 4, the executor may change only the Git index and commit metadata.

Do not edit, format, generate, normalize, create, remove, or rename any other repository content. Ignored read-only tool caches may update but must remain absent from status.

Reverify every intended committed file for secrets, credentials, environment files, generated artifacts, logs, caches, editor artifacts, binaries, executable modes, symlinks, junctions, reparse points, Gitlinks, submodules, nested repositories, archives, unexpected machine-local paths, invalid UTF-8, line-ending defects, missing final newlines, trailing whitespace, NUL, replacement characters, mojibake, conflict markers, or unexpected zero-byte content.

Historical directives and reports may truthfully contain developer-local paths. They may not contain secrets.

All 185 intended entries must be ordinary `100644` files.

---

# 7. EXACT STAGING CONTRACT

Only after the rename and every pre-stage check pass:

```text
git add -u -- .
```

Then stage exactly:

```text
docs/adr/0024-exact-direct-relation-selection.md
docs/dev_plan/kongono_phase_0H_codex_directive.md
docs/dev_plan/kongono_phase_0I_codex_directive.md
docs/dev_plan/kongono_phase_0I_1_codex_directive_correction.md
docs/task reports/Phase_0G.md
docs/task reports/Phase_0H.md
docs/task reports/Phase_0I.md
```

Use explicit shell-appropriate quoting. Do not use `git add -A`, `git add .`, `git add --all`, an unrestricted wildcard, or a directory-wide add.

Never stage `docs/task reports/Phase_0I_1_correction.md`.

Require:

```text
Staged paths: 18
Staged modifications: 11
Staged additions: 7
Staged deletions: 0
Staged renames: 0
Unstaged tracked paths: 0
Untracked paths: 1
Untracked path: docs/task reports/Phase_0I_1_correction.md
Index entries: 185
Index modes: 185 x 100644
```

Run and inspect `git status`, ordinary and cached diffs, `git diff --cached --check`, exact name/status, stat, numstat, index entries, and `git write-tree`. Compare every index path, mode, and blob with the approved 185-file inventory.

---

# 8. FINAL PRE-COMMIT GATE

With the exact index staged:

1. rerun `composer quality`;
2. rerun focused `ApplicationGraphTest.php` and `ApiStatusConventionTest.php`;
3. recompute all 185 intended hashes;
4. rerun secret/file-safety and staged-tree comparisons;
5. prove the only untracked path is the zero-byte correction report;
6. prove branch, HEAD, remotes, tags, author, signing, and hooks remain acceptable;
7. prove the staged tree remains identical to the approved recorded tree.

Any difference is a blocker.

---

# 9. AUTHORIZED COMMIT

Create exactly one normal local commit with the original exact subject:

```text
Establish Kongono framework baseline through Phase 0H
```

Do not add a body or trailer. Do not use amend, bypass, alternate-author/date, allow-empty, or signing-disable flags.

The commit must:

- be commit three on sole branch `main`;
- have only parent `29235c6a943dd9f3e96bae7b85ff4bea6a26535c`;
- use the recorded staged tree;
- contain exactly 185 ordinary `100644` files;
- have exactly 18 parent-delta paths: 11 modified and seven added;
- exclude only the Phase 0I.1 report from the 186-path worktree;
- create no branch, tag, remote, or push.

If the commit fails, stop. Do not amend, bypass, reset, or rewrite.

---

# 10. POST-COMMIT VERIFICATION

Run every post-commit command required by original Phase 0I Section 11, adjusted to the counts in this directive.

Require:

```text
Branch: main, and only main
Commit count: 3
HEAD parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD parent count: 1
HEAD subject: Establish Kongono framework baseline through Phase 0H
HEAD tree: recorded pre-commit tree
HEAD files: 185
HEAD modes: 185 x 100644
Parent delta: 18 paths; 11 modified; 7 added; 0 deleted; 0 renamed
Unstaged tracked changes: 0
Staged changes: 0
Untracked: only docs/task reports/Phase_0I_1_correction.md
Correction report placeholder: 0 bytes
Remotes: 0
Tags: 0
```

Verify both previous commits remain unchanged. Verify all committed bytes against the approved inventory. Rerun full quality, focused tests, locked audit, and Git diff checks after commit.

---

# 11. PROHIBITED SCOPE

Do not:

- modify any file's contents;
- move any path except the exact Section 4 source to target;
- remove or overwrite the blocked Phase 0I report;
- change source, tests, documentation, ADRs, manifests, lockfile, tooling, policy, version, or license;
- add any framework behavior or Phase 0J capability;
- stage the correction report;
- create another branch, tag, remote, commit, or push;
- clean, reset, restore, checkout, stash, rebase, merge, cherry-pick, amend, or rewrite;
- change Git configuration, signing, hooks, credentials, or ignores.

---

# 12. STRICT ACCEPTANCE CRITERIA

Phase 0I.1 is Complete only if:

- [ ] The exact blocked baseline and all hashes matched.
- [ ] Exactly two correction caller records existed and the report was zero bytes.
- [ ] All original Phase 0I preflight, Phase 0H reverification, quality, security, and file-safety checks passed.
- [ ] The no-extension directive was renamed exactly once to the `.md` path.
- [ ] Its size and SHA-256 remained identical.
- [ ] No other path or content changed.
- [ ] The blocked Phase 0I report remained byte-identical and was included as historical evidence.
- [ ] Exactly 18 paths were staged: 11 modified and seven added.
- [ ] The index contained exactly 185 ordinary `100644` entries.
- [ ] Only the zero-byte Phase 0I.1 report remained untracked.
- [ ] The staged tree exactly matched the approved inventory.
- [ ] Final pre-commit quality and integrity gates passed.
- [ ] Exactly one third commit was created with the exact subject and parent.
- [ ] Its tree contains 185 files and its delta is exactly 11 modified plus seven added.
- [ ] No hook/signing policy was bypassed and no history/configuration expansion occurred.
- [ ] Both earlier commits remained immutable.
- [ ] Post-commit quality, security, focused tests, and integrity checks passed.
- [ ] No Phase 0J or other prohibited work occurred.
- [ ] The correction report follows Section 13 exactly.

Any failed item requires `Blocked`, not `Complete`.

---

# 13. REQUIRED CORRECTION COMPLETION REPORT

Return one fenced `text` block with exactly these 22 numbered top-level sections:

```text
PHASE 0I.1 CORRECTION TASK COMPLETION REPORT

1. STATUS
2. SUMMARY
3. BLOCKED BASELINE OBSERVED
4. ORIGINAL AND CORRECTION CALLER RECORDS
5. AUTHORIZED PATH CORRECTION
6. CONTENT AND INVENTORY AUDIT
7. PHASE 0H ACCEPTANCE REVERIFICATION
8. SECRET AND FILE-SAFETY AUDIT
9. PRE-STAGE TEST AND QUALITY RESULTS
10. STAGING COMMANDS
11. STAGED STATE
12. FINAL PRE-COMMIT GATE
13. COMMIT RESULT
14. POST-COMMIT REPOSITORY STATE
15. POST-COMMIT CONTENT INTEGRITY
16. POST-COMMIT TEST AND QUALITY RESULTS
17. FILES CREATED / MODIFIED / MOVED / REMOVED BY EXECUTOR
18. COMMANDS RUN
19. ARCHITECTURAL DECISIONS, DEVIATIONS, QUESTIONS, OR BLOCKERS
20. ACCEPTANCE CHECKLIST
21. GIT STATUS SUMMARY AND REPORT STRUCTURE SELF-CHECK
22. NEXT STEP
Await architect review before Phase 0J.
```

Each section must contain exact evidence applicable to its title. Section 20 must reproduce every Section 12 item with PASS/FAIL evidence. Section 21 must confirm exactly 22 sections in order, one outer fence, and no content after Section 22.

For Section 22, state only:

> Await architect review before Phase 0J.

Do not write to the zero-byte correction report placeholder. Return the report for the caller to copy.

---

# 14. FINAL INSTRUCTION

Repair only the exact missing-extension caller path, preserve the blocked report, and complete the accepted Phase 0H checkpoint as the third local commit.

Do not modify content. Do not begin Phase 0J.
