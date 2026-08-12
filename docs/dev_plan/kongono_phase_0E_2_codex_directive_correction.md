# CODEX CORRECTIVE DIRECTIVE — KONGONO PHASE 0E.2

## Directive and Task-Report Filing Convention Correction

## Disposition

Phase 0E.1 stopped correctly before mutation because its directive classified a normal caller-managed task-report path as prohibited.

That classification was incorrect.

Kongono maintains two distinct historical record directories:

```text
docs/dev_plan/     Phase directives supplied by the architect to the executor
docs/task reports/ Copies of task-completion reports returned by the executor
```

Files in either directory are legitimate repository history when they represent a completed or blocked prior execution.

Do not begin Phase 0F.

Restart Phase 0E under this corrective overlay.

The original Phase 0E directive and the Phase 0E.1 corrective directive remain authoritative except where this directive explicitly supersedes them.

---

# 1. LOCKED FILING CONVENTION

Apply the following convention without inference:

1. Every architect-issued Phase directive or corrective directive is filed under `docs/dev_plan/`.
2. Every executor task-completion report copied by the caller is filed under `docs/task reports/`.
3. A report from a completed, partial, or blocked prior execution is an authorized historical record.
4. A placeholder for the current execution's not-yet-returned report is not historical content yet.
5. Never classify a prior report as unauthorized merely because its status is `Blocked`.
6. Never overwrite a prior report with a later report.
7. Never invent, rename, delete, or merge directive or report records.

The exact capitalization, spaces, and filenames already present remain authoritative.

This convention applies to all existing Phase 0A through Phase 0E.1 records and to later phases unless an architect directive explicitly changes it.

---

# 2. PHASE 0E.1 REPORT — AUTHORIZED PRIOR RECORD

The following path is authorized and required:

```text
docs/task reports/Phase_0E_1_correction.md
```

It is the caller-managed copy of the Phase 0E.1 executor report.

Before execution continues, it must be populated with the complete Phase 0E.1 report supplied by the caller. A zero-byte working-tree file is not an acceptable completed historical report.

Verify, without rewriting it, that the populated file:

- is non-empty;
- begins with `PHASE 0E.1 CORRECTION REPORT`;
- records `STATUS` as `Blocked`;
- identifies the 103-versus-102 inventory stop;
- identifies its own previously staged zero-byte placeholder as the blocker;
- states that Phase 0E.1 made no repository-content or Git-state changes.

Record its byte size and SHA-256 in the completion report.

Its currently staged empty blob is a stale placeholder, not authoritative content. During index normalization, replace that empty staged blob with the populated working-tree bytes.

If the working-tree file is still zero bytes or does not contain the supplied Phase 0E.1 report, stop. Do not synthesize the missing report and do not continue using the attachment text from memory.

The Phase 0E.1 instructions that prohibited this path are withdrawn.

---

# 3. PHASE 0E.2 DIRECTIVE RECORD

This corrective directive must be filed byte-for-byte at:

```text
docs/dev_plan/kongono_phase_0E_2_codex_directive_correction.md
```

If the caller has already supplied it at that path, verify it and do not rewrite it.

If it is absent, create only that file from the supplied corrective directive.

Do not create any other development-plan file.

If the index contains an empty placeholder for this directive while the working-tree file is populated, treat the working-tree bytes as authoritative and normalize the index during the authorized staging step. That known editor/index condition is not a blocker.

---

# 4. CURRENT EXECUTION REPORT BOUNDARY

The Phase 0E.2 executor must return its completion report to the caller after execution.

The canonical eventual archive path is:

```text
docs/task reports/Phase_0E_2_correction.md
```

Preferred caller workflow:

1. do not create that path before Phase 0E.2 executes;
2. let the executor complete the initial commit;
3. receive the Phase 0E.2 report;
4. copy the report into that path after the execution.

If the exact path already exists before execution as a zero-byte placeholder:

- preserve the worktree file;
- do not populate, delete, rename, or commit it;
- if staged, remove only that exact path from the index during index normalization;
- leave it as an untracked zero-byte concurrent placeholder after the commit;
- report its presence and disposition.

This exact path-specific index removal is authorized. It is not authorization to unstage or delete any other caller-managed report.

If the path exists before execution and is non-empty, stop and report its size and SHA-256. A non-empty report purporting to describe an execution that has not occurred requires architect review.

---

# 5. CANONICAL PHASE 0E RECORDS

The following existing files are authorized historical records and must be preserved:

```text
docs/dev_plan/kongono_phase_0E_codex_directive.md
docs/dev_plan/kongono_phase_0E_1_codex_directive_correction.md
docs/task reports/Phase_0E.md
docs/task reports/Phase_0E_1_correction.md
```

This correction adds:

```text
docs/dev_plan/kongono_phase_0E_2_codex_directive_correction.md
```

The withdrawn long-form directive remains prohibited and absent:

```text
docs/dev_plan/kongono_phase_0E_codex_implementation_directive.md
```

The canonical original Phase 0E directive remains locked as:

```text
Path: docs/dev_plan/kongono_phase_0E_codex_directive.md
Size: 30,742 bytes
SHA-256: 1FE223AFC4FE9AC9DEA35EA019381A291DDD7F9D1638DEB29CE84461145AAD48
```

The Phase 0E.1 correction directive remains locked as:

```text
Path: docs/dev_plan/kongono_phase_0E_1_codex_directive_correction.md
Size: 13,015 bytes
SHA-256: 9DEEE9806FDF2BE86D8CDBFCD7EFC5695B74F60E19ED73B731538D582A176E9A
```

Recompute and verify both hashes before mutation. Stop on any mismatch.

---

# 6. CORRECTED INVENTORY — LOCKED

The Phase 0E.1 executor observed 103 non-ignored paths before this Phase 0E.2 directive was added.

Those 103 paths consist of:

```text
99 accepted Phase 0A–0D baseline files
1 canonical Phase 0E directive
1 blocked Phase 0E report
1 Phase 0E.1 correction directive
1 Phase 0E.1 blocked report path
```

After the Phase 0E.1 report is populated in place and this Phase 0E.2 directive is added, the exact intended commit inventory is:

```text
104 files
```

Populating the existing Phase 0E.1 report changes bytes, not the path count.

Every earlier Phase 0E or Phase 0E.1 count of 100 or 102 is superseded.

Use these exact commit-tree counts:

```text
Intended commit paths: 104
Staged paths:          104
Committed paths:       104
```

If no current-execution report placeholder exists, the non-ignored worktree union must be 104.

If and only if the exact zero-byte current-execution placeholder from Section 4 exists, the pre-normalization worktree union may be 105 while the accepted staging and commit inventory remains exactly 104.

Do not use count alone as proof. Build and compare exact sorted path inventories.

No other count variance is authorized.

---

# 7. KNOWN INDEX STATE AND NORMALIZATION AUTHORITY

The Phase 0E.1 report observed six staged entries:

```text
docs/dev_plan/kongono_phase_0B_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0E_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0E_codex_directive.md
docs/task reports/Phase_0B_1_Correction.md
docs/task reports/Phase_0C_1_correction.md
docs/task reports/Phase_0E_1_correction.md
```

The three Phase 0E/0E.1 entries were observed as empty staged blobs while their accepted working-tree roles differ:

- the Phase 0E directive working-tree file is populated and locked;
- the Phase 0E.1 correction directive working-tree file is populated and locked;
- the Phase 0E.1 report must be populated by the caller before this execution.

The executor is explicitly authorized to normalize the entire index to the exact accepted 104-file working-tree inventory after all pre-stage audits and the preservation manifest pass.

This includes:

- replacing all stale staged blobs with accepted working-tree bytes;
- staging all accepted untracked paths;
- staging this Phase 0E.2 directive;
- removing the exact current Phase 0E.2 report placeholder from the index if Section 4 applies;
- producing exactly 104 Added entries, all mode `100644`.

Do not alter any accepted working-tree file during index normalization.

---

# 8. REQUIRED EXECUTION SEQUENCE

Restart Phase 0E from the beginning. Do not resume from either prior stop point.

Perform the original Phase 0E procedure with the Phase 0E.1 and Phase 0E.2 corrections applied in latest-directive order:

1. read `AGENTS.md`, the complete original Phase 0E directive, the complete Phase 0E.1 correction, and this complete Phase 0E.2 correction;
2. reread every authority and accepted Phase 0A–0D artifact required by the original directive;
3. verify both locked directive hashes from Section 5;
4. verify the populated Phase 0E.1 report under Section 2;
5. classify all prior directives and reports according to Section 1;
6. confirm HEAD remains unborn, branch is `master`, commit count is zero, remotes and tags are absent, and existing Git identity is available;
7. build the exact accepted 104-path inventory and separately classify any Section 4 placeholder;
8. complete every original file-safety, secret, machine-local, generated-content, dependency, security, symlink, nested-repository, mode, size, encoding, newline, conflict-marker, historical-record, ADR, directive-sequence, and report-sequence audit;
9. run the complete original pre-stage Composer and PHP quality gate;
10. create an external preservation manifest for the exact 104 accepted files;
11. rename the unborn branch from `master` to `main`;
12. normalize the index under Section 7;
13. verify exactly 104 Added entries, every mode `100644`, with exact accepted bytes;
14. independently export and validate the staged tree;
15. create exactly one zero-parent commit with the unchanged commit message;
16. independently audit the committed tree and compare the preservation manifest;
17. rerun every original post-commit quality check;
18. return the Phase 0E.2 correction report to the caller.

Do not skip work because earlier attempts performed preliminary reads.

---

# 9. COMMIT CONTRACT — CORRECTED COUNT ONLY

The commit contract is:

```text
Branch: main
Commit count: 1
Parent count: 0
Commit message: Establish Kongono framework baseline through Phase 0D
Committed files: 104
Remote count: 0
Tag count: 0
Push: prohibited
```

Use the existing Git identity and normal hook/signing policy.

Do not alter Git configuration, bypass hooks or signing, add a remote, create a tag, push, amend, or create another commit.

---

# 10. PROHIBITED SCOPE

All original Phase 0E prohibited scope remains in force.

Do not:

- implement or modify framework code;
- modify tests, Composer metadata, lockfile content, tooling configuration, doctrine, architecture, ADRs, or other documentation;
- implement DI/container, HTTP/router, Application Graph schema/IDs/serialization beyond the already accepted baseline, ORM, agents, extensions, CLI, frontend, telemetry, or any later capability;
- delete, rewrite, combine, or conceal either blocked report;
- rename either historical directory;
- create the withdrawn long-form Phase 0E directive;
- populate the Phase 0E.2 report path during execution;
- infer permission for a path not explicitly classified here;
- change the initial commit subject;
- create more than one commit;
- create remotes or tags;
- push.

The only repository-content creation authorized by this correction is its own exact directive record if absent.

The only Git-state mutations authorized are the original audited branch rename, exact index normalization, exact zero-parent commit, and the Section 4 path-specific index exclusion when applicable.

---

# 11. ACCEPTANCE CRITERIA

Phase 0E.2 is Complete only if every item passes:

- [ ] Both earlier Phase 0E directives match their locked size and SHA-256.
- [ ] The withdrawn long-form directive is absent.
- [ ] This Phase 0E.2 directive is present at the exact canonical path.
- [ ] `docs/task reports/Phase_0E.md` remains the populated blocked Phase 0E report.
- [ ] `docs/task reports/Phase_0E_1_correction.md` is populated with the caller-supplied blocked Phase 0E.1 report.
- [ ] All prior directive and report records are classified under the locked two-directory convention.
- [ ] The accepted commit inventory is exactly 104 paths.
- [ ] Any exact Phase 0E.2 report placeholder is preserved in the worktree, excluded from the index, and reported.
- [ ] No unclassified path-count variance exists.
- [ ] Every original authority, inventory, file-safety, secret, machine-local, dependency, generated-content, and security audit passes.
- [ ] Every required pre-stage quality command passes.
- [ ] The 104-file preservation manifest matches the staged and committed trees.
- [ ] The branch is renamed from unborn `master` to `main` without creating another branch.
- [ ] The normalized index contains exactly 104 Added entries, all `100644`.
- [ ] Independent staged-tree validation passes.
- [ ] Exactly one zero-parent commit exists with the exact required subject.
- [ ] The commit contains exactly 104 ordinary files.
- [ ] No remote or tag exists and nothing was pushed.
- [ ] Every post-commit quality command passes.
- [ ] Every other original Phase 0E criterion not explicitly superseded passes.

If any criterion fails, report `Partial` or `Blocked`, not `Complete`.

---

# 12. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly:

```text
PHASE 0E.2 CORRECTION REPORT

1. STATUS
Complete / Partial / Blocked

2. FILING CONVENTION APPLIED
- dev_plan role
- task reports role
- prior-report classifications
- current-report placeholder classification, if any

3. CORRECTION RECORDS
- original Phase 0E directive path, size, and SHA-256
- Phase 0E.1 directive path, size, and SHA-256
- Phase 0E.2 directive path, size, and SHA-256
- withdrawn path absence
- both blocked reports, sizes, SHA-256 values, and statuses

4. INITIAL REPOSITORY STATE
- HEAD/unborn state
- initial branch
- commit/remotes/tags
- staged/unstaged/untracked state
- six known staged paths and any later exact placeholders

5. AUTHORITY AND INVENTORY AUDIT
- files read
- exact accepted path count
- pre-normalization union count and any authorized exclusion
- directory counts
- ADR/directive/report sequences
- required, prohibited, and excluded Phase 0E paths

6. SECRET, MACHINE-LOCAL, AND FILE-SAFETY AUDIT
- methods
- results without secret values
- historical exceptions
- binary/size/path/mode/symlink/nested-repository results

7. GENERATED, DEPENDENCY, AND SECURITY AUDIT
- ignored paths
- package counts
- lockfile
- external versions
- plugin/security policy

8. PRE-STAGE QUALITY RESULTS
- every original Phase 0E command and result
- tests/assertions/outcomes
- verbose quality order

9. WORKTREE PRESERVATION MANIFEST
- 104-file count
- manifest SHA-256
- pre-stage/post-stage/post-commit comparisons
- temporary-file disposition

10. BRANCH AND INDEX NORMALIZATION
- master-to-main result
- staged path/status/mode counts
- stale-path normalization
- current-report placeholder index exclusion, if any
- cached diff result

11. STAGED-TREE INDEPENDENT VALIDATION
- temporary export disposition
- manifest/JSON/PHP/Markdown/UTF-8/newline/conflict results

12. INITIAL COMMIT
- commit hash
- tree hash
- exact subject
- author/committer source
- parent count
- file/mode count
- hook/signing result

13. COMMITTED-TREE AUDIT
- exact 104-path match
- required Phase 0E/0E.1 directive and report records
- prohibited and excluded paths absent
- blocked statuses preserved
- accepted package/ADR/test/history presence

14. POST-COMMIT QUALITY RESULTS
- every required result

15. FINAL GIT STATE
- branch
- commit count
- status
- remotes
- tags
- push confirmation
- concurrent Phase 0E.2 report placeholder, if any

16. DEVIATIONS
None, or exact deviation and approval status.

17. QUESTIONS / BLOCKERS
None, or exact action required.

18. ACCEPTANCE CHECKLIST
Pass/Fail for every original Phase 0E criterion as corrected through Phase 0E.2.

19. NEXT STEP
Await architect review before Phase 0F.
```

For item 19, state only:

> Await architect review before Phase 0F.

Do not begin Phase 0F.

---

# 13. FINAL INSTRUCTION

The two-folder convention is now explicit: directives are project records under `docs/dev_plan/`, and copies of returned executor reports are project records under `docs/task reports/`.

Preserve both blocked attempts as truthful history. Populate the Phase 0E.1 report before execution, add this correction directive, then perform the full original baseline audit and create the one authorized initial commit.

Return the Phase 0E.2 report to the caller for filing only after execution.

Do not implement anything else.
