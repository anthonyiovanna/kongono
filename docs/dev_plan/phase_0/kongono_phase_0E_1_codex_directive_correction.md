# CODEX CORRECTIVE DIRECTIVE — KONGONO PHASE 0E.1

## Canonical Directive Filename and Baseline Inventory Correction

## Disposition

Phase 0E stopped correctly before mutation because its supplied filename conflicted with the canonical path written inside the directive.

The blocker was caused by the directive, not by the Kongono repository or executor.

Do not begin Phase 0F.

Resume Phase 0E only under this corrective overlay.

The original Phase 0E directive remains authoritative except where this correction explicitly supersedes it.

---

# 1. LOCKED RESOLUTION

The established Kongono development-plan filename convention is retained.

The canonical Phase 0E directive path is:

```text
docs/dev_plan/kongono_phase_0E_codex_directive.md
```

The path required by Section 6 of the original directive is withdrawn:

```text
docs/dev_plan/kongono_phase_0E_codex_implementation_directive.md
```

Do not create the withdrawn path.

Do not rename, copy, delete, or rewrite the existing canonical file.

The existing canonical file has already been verified as the supplied directive:

```text
Path:
docs/dev_plan/kongono_phase_0E_codex_directive.md

Expected size:
30,742 bytes

Expected SHA-256:
1FE223AFC4FE9AC9DEA35EA019381A291DDD7F9D1638DEB29CE84461145AAD48
```

Recompute the SHA-256 before acting. If the size or hash differs, stop and report the mismatch. Do not restore or overwrite it.

Its currently staged form is an empty historical placeholder. That stale staged blob is not authoritative. The non-empty working-tree file is authoritative and must replace the empty index blob during the original directive's index-normalization step.

---

# 2. CORRECTION DIRECTIVE RECORD

Add this corrective directive byte-for-byte at:

```text
docs/dev_plan/kongono_phase_0E_1_codex_directive_correction.md
```

If the caller has already supplied it at that path, verify it and do not rewrite it.

If absent, create only that file from the supplied corrective directive.

Do not create any other development-plan file.

The original Phase 0E rule stating that its directive was the only authorized content creation is superseded only to authorize this correction record.

---

# 3. BLOCKED PHASE 0E REPORT — LOCKED HISTORICAL RECORD

The following file now contains the genuine blocked Phase 0E task-completion report:

```text
docs/task reports/Phase_0E.md
```

It is an authorized historical audit record and must be included in the initial commit.

Requirements:

1. Preserve it byte-for-byte.
2. Do not edit its `Blocked` status.
3. Do not replace it with the eventual Phase 0E.1 report.
4. Do not reinterpret it as a successful Phase 0E result.
5. Include it in secret, machine-local, UTF-8, link, newline, and staged-tree audits.

The original Phase 0E instruction to exclude a concurrent Phase 0E placeholder is superseded for this now-populated 7,331-byte historical report.

Do not create:

```text
docs/task reports/Phase_0E_1_correction.md
```

Return the Phase 0E.1 report to the caller. If the caller's environment creates that report path concurrently after the commit, preserve it untracked and apply the original directive's concurrent-report exception.

---

# 4. CORRECTED INVENTORY — LOCKED

The observed current union before adding this corrective directive is exactly:

```text
101 non-ignored repository files
```

That consists of:

```text
99 accepted Phase 0A–0D baseline files
1 canonical Phase 0E directive
1 blocked Phase 0E task report
```

After adding this correction directive, the intended initial commit contains exactly:

```text
102 files
```

The original directive's expected counts of 99 before its directive and 100 after it are superseded by the current historical state and this correction.

Every original count-based requirement for the staged and committed tree is replaced as follows:

```text
Intended path count: 102
Staged path count:   102
Committed path count: 102
```

Do not use the count alone as proof. Rebuild and verify the exact sorted inventory.

The 102-file tree must include:

```text
docs/dev_plan/kongono_phase_0E_codex_directive.md
docs/dev_plan/kongono_phase_0E_1_codex_directive_correction.md
docs/task reports/Phase_0E.md
```

It must not include:

```text
docs/dev_plan/kongono_phase_0E_codex_implementation_directive.md
docs/task reports/Phase_0E_1_correction.md
```

unless the latter is created concurrently after the commit by the caller's environment, in which case leave it untracked and report it.

---

# 5. STALE INDEX INVENTORY — CORRECTED

The pre-normalization index contains four historical entries, not three:

```text
docs/dev_plan/kongono_phase_0B_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0E_codex_directive.md
docs/task reports/Phase_0B_1_Correction.md
docs/task reports/Phase_0C_1_correction.md
```

The Phase 0E directive's staged blob is empty while its accepted worktree form is non-empty.

The original Phase 0E instructions referring to three stale/historical staged entries are superseded to require inspection and normalization of all four paths.

Do not modify the working-tree contents of any of these files. The original authorized index-normalization operation must stage their current accepted working-tree bytes.

---

# 6. REQUIRED EXECUTION SEQUENCE

Restart the Phase 0E procedure from its beginning. Do not resume after the prior stop point.

Execute all original Phase 0E requirements, with the corrections in Sections 1–5 above, including:

1. reread all required authority and accepted artifacts;
2. confirm HEAD remains unborn, branch remains `master`, commit count is zero, remotes are absent, and Git identity is available;
3. verify the canonical Phase 0E directive's size and SHA-256;
4. record this correction directive at its exact canonical path;
5. classify the populated blocked Phase 0E report as an authorized historical record;
6. build and verify the exact 102-file inventory;
7. complete all file-safety, secret, machine-local, generated-content, dependency, symlink, mode, size, encoding, newline, conflict-marker, and historical-record audits;
8. run the complete pre-stage Composer and quality gate;
9. create the 102-file pre-stage SHA-256 preservation manifest outside the repository;
10. rename the unborn branch `master` to `main`;
11. normalize the index to the exact accepted 102-file working tree;
12. verify every staged mode is `100644` and every entry is Added;
13. verify all four stale index paths now match their working-tree forms;
14. independently export and validate the staged tree;
15. create exactly one zero-parent commit using the original exact commit message;
16. audit the committed tree and preservation manifest;
17. rerun all post-commit quality checks;
18. return the Phase 0E.1 correction report.

Do not skip a check merely because the first Phase 0E attempt stopped before it.

---

# 7. COMMIT CONTRACT — UNCHANGED

The original commit contract remains exact:

```text
Branch: main
Commit count: 1
Parent count: 0
Commit message: Establish Kongono framework baseline through Phase 0D
Committed files: 102
Remote count: 0
Tag count: 0
Push: prohibited
```

Use existing Git identity and normal hook/signing policy.

Do not alter Git configuration, bypass hooks or signing, add a remote, create a tag, push, amend, or create another commit.

---

# 8. ADDITIONAL VALIDATION SPECIFIC TO THE CORRECTION

Before staging, verify:

```text
Canonical original directive exists at the retained short path.
Canonical original directive size is 30,742 bytes.
Canonical original directive SHA-256 matches the locked hash.
Withdrawn long-form directive path is absent.
Correction directive exists at its exact path.
Blocked Phase 0E report exists, is non-empty, and still reports Blocked.
Current non-ignored union is exactly 102 files after the correction directive is added.
```

After staging, verify through Git object/index reads:

```text
Original Phase 0E directive staged blob is non-empty.
Original Phase 0E directive staged blob hashes to the normalized Git form of the working-tree file.
Correction directive is staged.
Blocked Phase 0E report is staged unchanged.
All four historical stale index paths match their working-tree content.
Withdrawn long-form directive path is absent.
Phase 0E.1 task-report path is absent unless concurrently created and explicitly excluded.
```

After commit, verify through commit-tree reads:

```text
All three Phase 0E historical records required by Section 4 are present.
The withdrawn path is absent.
The committed original directive is non-empty.
The committed blocked report remains Blocked.
The committed tree contains exactly 102 ordinary 100644 files.
```

---

# 9. PROHIBITED ACTIONS

All original Phase 0E prohibitions remain in force.

Additionally, do not:

- rename the canonical short-form Phase 0E directive;
- create the withdrawn long-form path;
- delete or rewrite the blocked Phase 0E report;
- conceal the initial blocked execution;
- reuse the blocked report path for the correction report;
- change the commit subject or file count without another explicit architect correction;
- treat the filename correction as authorization for any framework, documentation, dependency, configuration, or Git-policy change.

---

# 10. CORRECTED ACCEPTANCE CRITERIA

Phase 0E.1 is complete only if every original Phase 0E criterion passes after applying these replacements:

- [ ] Canonical Phase 0E directive is the existing short-form path.
- [ ] Its size and SHA-256 match the locked values.
- [ ] The long-form path is absent.
- [ ] This Phase 0E.1 correction directive is present byte-for-byte.
- [ ] The blocked Phase 0E report is preserved unchanged and included.
- [ ] Current intended inventory is exactly 102 files.
- [ ] All four stale staged paths are normalized to accepted working-tree content.
- [ ] Staged tree contains exactly 102 Added entries, all mode `100644`.
- [ ] Initial commit contains exactly 102 ordinary files.
- [ ] Required three Phase 0E historical records are committed.
- [ ] No Phase 0E.1 report is committed unless separately authorized.
- [ ] Every other original Phase 0E acceptance criterion passes unchanged.

If any item fails, status is Partial or Blocked—not Complete.

---

# 11. REQUIRED COMPLETION REPORT

Return exactly:

```text
PHASE 0E.1 CORRECTION REPORT

1. STATUS
Complete / Partial / Blocked

2. CORRECTION APPLIED
- canonical original directive path
- size and SHA-256
- withdrawn path absence
- correction directive path
- blocked report preservation

3. INITIAL REPOSITORY STATE
- HEAD/unborn state
- initial branch
- commit/remotes/tags
- staged/unstaged/untracked state
- four stale index paths

4. AUTHORITY AND INVENTORY AUDIT
- files read
- exact intended path count
- directory counts
- ADR/directive/report sequence
- exact required and prohibited Phase 0E paths

5. SECRET, MACHINE-LOCAL, AND FILE-SAFETY AUDIT
- methods
- results without secret values
- historical exceptions
- binary/size/path/mode/symlink/nested-repository results

6. GENERATED, DEPENDENCY, AND SECURITY AUDIT
- ignored paths
- package counts
- lockfile
- external versions
- plugin/security policy

7. PRE-STAGE QUALITY RESULTS
- every original Phase 0E command and result
- tests/assertions/outcomes
- verbose quality order

8. WORKTREE PRESERVATION MANIFEST
- 102-file count
- manifest SHA-256
- pre-stage/post-stage/post-commit comparisons
- temporary-file disposition

9. BRANCH AND INDEX NORMALIZATION
- master-to-main result
- staged path/status/mode counts
- four stale-path verification
- required/prohibited Phase 0E paths
- ignored/concurrent exclusions
- cached diff result

10. STAGED-TREE INDEPENDENT VALIDATION
- temporary export disposition
- manifest/JSON/PHP/Markdown/UTF-8/newline/conflict results

11. INITIAL COMMIT
- commit hash
- tree hash
- subject
- author/committer source
- parent count
- file/mode count
- hook/signing result

12. COMMITTED-TREE AUDIT
- exact 102-path match
- required Phase 0E records
- prohibited paths absent
- blocked report status preserved
- accepted package/ADR/test/history presence

13. POST-COMMIT QUALITY RESULTS
- every required result

14. FINAL GIT STATE
- branch
- commit count
- status
- remotes
- tags
- push confirmation
- concurrent correction-report placeholder, if any

15. DEVIATIONS
None, or exact deviation and approval status.

16. QUESTIONS / BLOCKERS
None, or exact action required.

17. ACCEPTANCE CHECKLIST
Pass/Fail for every original Phase 0E criterion as corrected by Section 10.

18. NEXT STEP
Await architect review before Phase 0F.
```

For item 18, state only:

> Await architect review before Phase 0F.

Do not begin Phase 0F.

---

# 12. FINAL INSTRUCTION

The filename conflict is resolved by retaining Kongono's established short-form directive convention.

Preserve the failed attempt as history. Add this correction as history. Then perform the original baseline audit and create the one authorized initial commit.

Do not implement anything else.
