# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0I.2

## Caller-Record Recovery and Phase 0H Checkpoint Completion

## Mission

Recover from the two correct caller-setup stops in Phase 0I and Phase 0I.1, preserve both blocked reports, repair the original directive filename, and complete the accepted Phase 0H checkpoint as the third local commit.

No framework content change and no Phase 0J work is authorized.

---

# 1. AUTHORITY AND SUPERSESSION

Read completely:

1. `AGENTS.md` and all repository governance;
2. accepted authority and implementation through Phase 0H;
3. the original Phase 0I directive;
4. the Phase 0I.1 correction directive;
5. both populated blocked reports;
6. this Phase 0I.2 directive.

This directive supersedes Phase 0I and Phase 0I.1 only for caller-record classification, the exact path correction, status/staging/file counts, the report placeholder, and the completion-report contract. All other checkpoint, validation, immutability, security, Git, and prohibited-scope rules remain in force.

The two blocked reports are accepted historical evidence. Preserve and commit them byte-for-byte.

## Mandatory stop rule

Before any rename or staging, stop if any expected path, byte, hash, status, Git fact, or caller record differs.

```text
STOP
DO NOT FIX
DO NOT STAGE
DO NOT COMMIT
REPORT THE EXACT BLOCKER
```

Do not treat an attachment as satisfying a required repository path.

---

# 2. EXACT PRE-CALLER BASELINE

Before the caller adds the Phase 0I.1 and Phase 0I.2 directive records and the Phase 0I.2 report placeholder, require:

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
Untracked paths: 7
Total status paths: 18
Staged paths: 0
Remotes: 0
Tags: 0
```

The 11 tracked modifications are exactly the accepted Phase 0H paths and remain `323 insertions, 22 deletions`.

Require these exact seven untracked paths:

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

docs/task reports/Phase_0I_1_correction.md
    12,688 bytes
    SHA-256 D8B0A4201CAFB74D2E8B1A29627F3656A04B26ADD4A6CFBCDA3D4746EC0C614F
```

No Phase 0I mutation, staging, or commit has occurred.

---

# 3. REQUIRED CALLER RECORDS

The caller must add all three exact paths before execution:

```text
docs/dev_plan/kongono_phase_0I_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0I_2_codex_directive_correction.md
docs/task reports/Phase_0I_2_correction.md
```

The Phase 0I.1 directive repository copy must be the architect artifact:

```text
14,263 bytes
SHA-256 B173AED8E382A4C382153EC6CB339D80C7B31BC0390D7CE5C927583BD56580A5
```

The Phase 0I.2 directive must be a non-empty exact copy of this artifact. Record its size and SHA-256 and do not edit it.

The Phase 0I.2 report must be exactly zero bytes. Do not populate, stage, commit, rename, or delete it. Return the completion report in the response.

After caller setup and before the authorized rename, require:

```text
Tracked modifications: 11
Untracked paths: 10
Total status paths: 21
Staged paths: 0
Physical worktree paths: 188
```

No other path is authorized. If either directive exists only as an attachment, stop.

---

# 4. REQUIRED PREFLIGHT

Before any mutation, execute every environment, Git, author/signing/hook, inventory, record-hash, Phase 0H acceptance, secret, file-safety, local-link, focused-test, and full-quality check required by the original Phase 0I directive Sections 6 and 7.

Require:

```text
PHPUnit: 119 tests, 513 assertions
PHPStan: 0 errors
PHP CS Fixer: 0 of 89 files require a fix
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Composer warnings remain limited to the missing license and existing local `@dev` or `*` constraints.

Use authorized access only for an unchanged advisory lookup or ignored tool-cache operation blocked by the restricted environment. Never weaken or omit a validation.

Create a task-specific path/mode/size/SHA-256 inventory outside the repository. Verify all 188 worktree files, with exactly 187 intended for commit and only `docs/task reports/Phase_0I_2_correction.md` excluded.

---

# 5. EXACT AUTHORIZED PATH CORRECTION

Only after Section 4 passes, rename exactly:

```text
FROM: docs/dev_plan/kongono_phase_0I_codex_directive
TO:   docs/dev_plan/kongono_phase_0I_codex_directive.md
```

Use one native filesystem rename. Do not copy-and-delete, use Git staging as the move mechanism, or change file bytes.

Immediately require:

- source absent and target present;
- target size exactly 24,650 bytes;
- target SHA-256 exactly `4DEA066908DA06E8B88F4DA2FE9AA7C87217F52AE2F39267FE4AE43D6BC111E3`;
- no other path or byte changed;
- status remains exactly 11 tracked modifications and ten untracked paths.

Recompute the complete inventory. The only pre/post difference may be the authorized path name.

---

# 6. CONTENT AND SECURITY IMMUTABILITY

Apart from Section 5's exact path rename, the executor may change only Git index and commit metadata.

Do not edit, format, normalize, create, remove, or move any other content. Preserve all three directives and both blocked reports byte-for-byte.

Audit all 187 intended files for secrets, credentials, environment files, caches, generated artifacts, logs, editor files, binaries, modes, links, reparse points, submodules, nested repositories, archives, unexpected machine paths, invalid UTF-8, line-ending defects, missing final newlines, trailing whitespace, NUL, replacement characters, mojibake, and conflict markers.

Historical records may contain truthful developer-local paths but never secrets. All intended entries must be ordinary `100644` files.

---

# 7. EXACT STAGING CONTRACT

After every check and the exact rename pass:

```text
git add -u -- .
```

Then explicitly stage only:

```text
docs/adr/0024-exact-direct-relation-selection.md
docs/dev_plan/kongono_phase_0H_codex_directive.md
docs/dev_plan/kongono_phase_0I_codex_directive.md
docs/dev_plan/kongono_phase_0I_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0I_2_codex_directive_correction.md
docs/task reports/Phase_0G.md
docs/task reports/Phase_0H.md
docs/task reports/Phase_0I.md
docs/task reports/Phase_0I_1_correction.md
```

Use exact shell-appropriate quoting. Do not use an unrestricted or directory-wide add.

Never stage `docs/task reports/Phase_0I_2_correction.md`.

Require:

```text
Staged paths: 20
Staged modifications: 11
Staged additions: 9
Staged deletions: 0
Staged renames: 0
Unstaged tracked paths: 0
Untracked paths: 1
Untracked path: docs/task reports/Phase_0I_2_correction.md
Index entries: 187
Index modes: 187 x 100644
```

Inspect exact status, ordinary/cached diffs, diff checks, name/status, stat, numstat, index entries, and `git write-tree`. Prove every path, mode, and blob against the approved inventory.

---

# 8. FINAL PRE-COMMIT GATE

With the exact index staged:

1. rerun full quality and the focused Graph/API-status tests;
2. rerun all hashes and secret/file-safety checks;
3. prove the tracked worktree is clean relative to the index;
4. prove only the zero-byte Phase 0I.2 report is untracked;
5. prove branch, HEAD, remotes, tags, author, signing, and hooks remain acceptable;
6. prove `git write-tree` remains the approved tree.

Any difference is a blocker.

---

# 9. AUTHORIZED COMMIT

Create exactly one normal local commit with:

```text
Establish Kongono framework baseline through Phase 0H
```

No body, trailer, bypass, amend, alternate author/date, allow-empty, or signing-disable flag is allowed.

The commit must:

- be commit three on sole branch `main`;
- have only parent `29235c6a943dd9f3e96bae7b85ff4bea6a26535c`;
- use the approved staged tree;
- contain exactly 187 ordinary `100644` files;
- have exactly 20 parent-delta paths: 11 modified and nine added;
- exclude only the Phase 0I.2 report from the 188-file worktree;
- create no branch, tag, remote, or push.

If commit fails, stop without amend, bypass, reset, or rewrite.

---

# 10. POST-COMMIT VERIFICATION

Run every original Phase 0I post-commit proof, adjusted to these exact requirements:

```text
Branch: main, and only main
Commit count: 3
HEAD parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD parent count: 1
HEAD subject: Establish Kongono framework baseline through Phase 0H
HEAD tree: approved pre-commit tree
HEAD files: 187
HEAD modes: 187 x 100644
Parent delta: 20 paths; 11 modified; 9 added; 0 deleted; 0 renamed
Unstaged tracked changes: 0
Staged changes: 0
Untracked: only docs/task reports/Phase_0I_2_correction.md
Report placeholder: 0 bytes
Remotes: 0
Tags: 0
```

Verify both previous commits remain immutable. Verify all committed bytes. Rerun full quality, focused tests, the real locked audit, and Git diff checks.

---

# 11. PROHIBITED SCOPE

Do not:

- modify any file contents;
- move any path except Section 5's exact source/target;
- omit, erase, overwrite, or reinterpret either blocked report;
- change source, tests, docs, ADRs, manifests, lockfile, tooling, policy, version, or license;
- implement any Graph, DI, Kernel, runtime, HTTP, ORM, security, event, workflow, agent, extension, CLI, frontend, telemetry, release, or Phase 0J capability;
- stage the Phase 0I.2 report;
- create another branch, tag, remote, commit, or push;
- clean, restore, reset, checkout, stash, rebase, merge, cherry-pick, amend, or rewrite;
- change Git configuration, hooks, signing, credentials, or ignores.

---

# 12. STRICT ACCEPTANCE CRITERIA

Phase 0I.2 is Complete only if:

- [ ] The exact seven-path blocked baseline and all hashes matched.
- [ ] Both missing directive repository copies and the zero-byte Phase 0I.2 report existed before execution.
- [ ] Pre-mutation status was exactly 11 modified plus ten untracked paths.
- [ ] Every original preflight, Phase 0H, quality, security, link, and file-safety check passed.
- [ ] The original directive was renamed exactly once to its `.md` path without byte change.
- [ ] No other content or path changed.
- [ ] Both blocked reports and all directives remained byte-identical.
- [ ] Exactly 20 paths were staged: 11 modified and nine added.
- [ ] The index contained exactly 187 ordinary `100644` entries.
- [ ] Only the zero-byte Phase 0I.2 report remained untracked.
- [ ] The staged tree matched the approved inventory.
- [ ] Final pre-commit quality and integrity gates passed.
- [ ] Exactly one third commit was created with the exact parent and subject.
- [ ] Its tree contains 187 files and its delta is exactly 11 modified plus nine added.
- [ ] No hook/signing policy was bypassed and no history/configuration expansion occurred.
- [ ] Both previous commits remained immutable.
- [ ] Post-commit quality, security, focused tests, and integrity checks passed.
- [ ] No Phase 0J or prohibited work occurred.
- [ ] The completion report follows Section 13 exactly.

Any failed item requires `Blocked`, not `Complete`.

---

# 13. REQUIRED COMPLETION REPORT

Return one fenced `text` block containing exactly these 22 numbered top-level sections:

```text
PHASE 0I.2 CORRECTION TASK COMPLETION REPORT

1. STATUS
2. SUMMARY
3. BLOCKED BASELINE OBSERVED
4. ORIGINAL AND CORRECTION CALLER RECORDS
5. PREFLIGHT AND PHASE 0H REVERIFICATION
6. AUTHORIZED PATH CORRECTION
7. CONTENT, INVENTORY, SECRET, AND FILE-SAFETY AUDIT
8. PRE-STAGE TEST AND QUALITY RESULTS
9. STAGING COMMANDS
10. STAGED STATE
11. FINAL PRE-COMMIT GATE
12. COMMIT RESULT
13. POST-COMMIT REPOSITORY STATE
14. POST-COMMIT CONTENT INTEGRITY
15. POST-COMMIT TEST AND QUALITY RESULTS
16. FILES CREATED / MODIFIED / MOVED / REMOVED BY EXECUTOR
17. COMMANDS RUN
18. ARCHITECTURAL DECISIONS AND DEVIATIONS
19. QUESTIONS OR BLOCKERS
20. ACCEPTANCE CHECKLIST
21. GIT STATUS SUMMARY AND REPORT STRUCTURE SELF-CHECK
22. NEXT STEP
Await architect review before Phase 0J.
```

Every section must contain exact applicable evidence. Section 20 must reproduce every Section 12 criterion with PASS/FAIL evidence. Section 21 must confirm exactly 22 sections, correct order, one outer fence, and no content after Section 22.

For Section 22, state only:

> Await architect review before Phase 0J.

Do not write to the zero-byte Phase 0I.2 report placeholder. Return the report for the caller to archive.

---

# 14. FINAL INSTRUCTION

Preserve all evidence, restore the two missing directive records, repair only the original directive's filename, and complete the accepted Phase 0H checkpoint as the third local commit.

Do not change framework content. Do not begin Phase 0J.
