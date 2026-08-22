# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0N.1

## Remove Two Windows Zone Metadata Streams and Complete the Phase 0N Checkpoint

## Mission

Correct the sole verified Phase 0N blocker by removing exactly two Windows `Zone.Identifier` alternate data streams from two historical tracked Markdown files. Preserve each primary file stream byte-for-byte. Then restart the Phase 0N verification sequence, stage the accepted repository plus the Phase 0N.1 caller record, create the fifth immutable local checkpoint, and leave only the Phase 0N.1 completion report untracked.

This correction does not weaken the Phase 0N file-safety rule. It removes the two prohibited metadata streams so the repository satisfies that rule.

The exact commit subject remains:

```text
Establish Kongono framework baseline through Phase 0M
```

No production, test, documentation, ADR, manifest, dependency, tool, or Git-tracked content edit is authorized.

---

# 1. AUTHORITY, SUPERSESSION, AND STOP RULE

You remain the executor, not the architect.

Read completely:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0N_codex_directive.md`;
3. `docs/task reports/Phase_0N.md`;
4. this Phase 0N.1 correction directive;
5. all authorities, accepted paths, directives, and reports required by the original Phase 0N directive.

This directive supersedes the original Phase 0N directive only for:

- authorization to remove the two exact alternate data streams in Section 4;
- acceptance of the populated blocked Phase 0N report as a historical record;
- the added Phase 0N.1 directive and report caller records;
- revised untracked, staged, index, committed-file, and commit-delta counts;
- the exact list of records staged into the checkpoint; and
- final report ownership by `Phase_0N_1_correction.md`.

Every other Phase 0N rule, validation gate, doctrine, prohibited scope, historical Git fact, content hash, quality expectation, and security requirement remains binding.

```text
STOP
REPORT
DO NOT GUESS
DO NOT FIX ANY OTHER PATH
DO NOT COMMIT A MISMATCH
```

Stop if:

- the baseline differs from Section 3;
- either authorized primary file differs in size or SHA-256;
- either expected `Zone.Identifier` stream is absent before correction, has a size other than 342 bytes, or any additional non-default stream exists;
- any other repository path has an alternate data stream;
- removing a stream changes a primary stream, tracked diff, status inventory, or any locked record;
- either removal command fails or needs a broader permission/configuration change;
- any validation or original Phase 0N gate fails;
- any unexpected path or byte appears; or
- exact staging or commit proof cannot be obtained.

Do not remove any additional stream. Do not retry with a recursive or wildcard deletion. Do not change the primary stream, copy a replacement file over it, recreate the file, normalize line endings, change NTFS permissions, or weaken the file-safety gate.

---

# 2. LOCKED FINAL OUTCOME

The exact corrected checkpoint outcome is:

```text
Branch: main
Only local branch: main
Commit count: 5
New commit parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit subject: Establish Kongono framework baseline through Phase 0M
Tracked files: 208
Tracked modes: 208 x 100644
Commit delta paths: 51
Commit delta: 35 modified, 16 added, 0 deleted
Final untracked path: docs/task reports/Phase_0N_1_correction.md
Remotes: 0
Tags: 0
Push: prohibited
```

The difference from the original Phase 0N counts is exactly two additional committed caller records:

```text
docs/task reports/Phase_0N.md
docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md
```

The first is the truthful blocked historical report. The second is this corrective authority. The new Phase 0N.1 completion report remains untracked.

The resulting commit and tree hashes are outputs. Do not invent them.

---

# 3. REQUIRED PRE-CORRECTION BASELINE

Before removing either stream, require exactly:

```text
Branch: main
Only local branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
Commit count: 4
HEAD subject: Establish Kongono framework baseline through Phase 0J
Tracked files in HEAD: 192
Tracked modes: 192 x 100644
Tracked modifications: 35
Untracked paths: 17
Total status paths: 52
Staged paths: 0
Tracked diff: 35 files, 1265 insertions, 214 deletions
Remotes: 0
Tags: 0
```

The 35 tracked modifications and the original 13 accepted untracked paths must match Sections 3 and 4 of the Phase 0N directive exactly, including all sizes and SHA-256 values.

The additional four untracked caller records are:

```text
docs/dev_plan/kongono_phase_0N_codex_directive.md
  30,853 bytes
  SHA-256 809D2539AC7B73CEAE4B7FC9D0898CB9B106D00862781DCAE5C97C9B32A44F3B

docs/task reports/Phase_0N.md
  3,871 bytes
  SHA-256 3E65139E86EBC5939A2305378685156B32BD68B89A2E84F7F0AFCE58DA7C8C70

docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md
  non-empty exact caller-supplied copy of this artifact
  record observed byte count and SHA-256; do not edit

docs/task reports/Phase_0N_1_correction.md
  exactly 0 bytes
  untracked, unstaged, ordinary file
```

`Phase_0N.md` is now an immutable historical record of the blocked run. Its statement that the report remained zero bytes describes the executor-time state of that run. Its later caller archival population does not retroactively alter that evidence.

No other path is authorized.

Verify the complete four-commit chain and trees exactly as locked by Phase 0N. Verify the effective Git author configuration without changing it. Redact the email in the report.

---

# 4. EXACT ALTERNATE-DATA-STREAM CORRECTION

Before mutation, inspect streams without printing stream contents.

Exactly these two tracked ordinary files must each have exactly two streams:

```text
docs/dev_plan/kongono_phase_0A_codex_directive.md
  primary :$DATA
  primary size 24,156 bytes
  primary SHA-256 4B8F25D85AD43A18F8E9B41D44261B2ADE076A28BEA9EE729E01F61E7EDAFD46
  Zone.Identifier size 342 bytes

docs/dev_plan/kongono_phase_0B_codex_directive.md
  primary :$DATA
  primary size 25,399 bytes
  primary SHA-256 6E68D7ECD8EC1029A5103B9D8C1766F01269434A4C94E85424BC7EDA22729D57
  Zone.Identifier size 342 bytes
```

A repository-wide stream inventory must find no other non-default stream.

Resolve and verify both absolute paths remain beneath:

```text
C:\Users\antho\Dev\kongono\docs\dev_plan
```

Only after all preconditions match, run exactly these two scoped operations:

```powershell
Unblock-File -LiteralPath "C:\Users\antho\Dev\kongono\docs\dev_plan\kongono_phase_0A_codex_directive.md"
Unblock-File -LiteralPath "C:\Users\antho\Dev\kongono\docs\dev_plan\kongono_phase_0B_codex_directive.md"
```

These operations are authorized only to remove each file's `Zone.Identifier` stream. They do not authorize a primary-file write.

Immediately afterward prove:

- both primary sizes and SHA-256 values remain exact;
- both paths expose only the default `:$DATA` stream;
- a repository-wide inventory finds zero non-default streams;
- `git diff --name-status` still lists the exact original 35 paths;
- `git diff --stat` remains exactly 35 files, 1265 insertions, 214 deletions;
- `git status` still contains exactly 52 paths before staging;
- the index remains empty;
- every locked caller record remains byte-identical; and
- no timestamp or metadata observation is misreported as a Git content change.

If any primary byte changes, stop. Do not restore, copy, reset, or rewrite the file. Preserve evidence and return `Blocked`.

The alternate-stream removal is the only pre-commit filesystem mutation authorized by this correction.

---

# 5. COMPLETE RESTART OF PHASE 0N VALIDATION

After the exact stream correction, restart the original Phase 0N preflight from its beginning. Do not reuse the blocked run's unexecuted or partial results as a substitute.

Run every applicable Phase 0N gate with only these count/path substitutions:

```text
Pre-stage status paths: 52
Pre-stage untracked paths: 17
Intended committed files: 208
Intended commit delta paths: 51
Staged modified paths: 35
Staged added paths: 16
Final report: docs/task reports/Phase_0N_1_correction.md
```

Required accepted implementation results remain:

- 93 first-party PHP/configuration syntax files pass;
- Kernel: 17 tests, 84 assertions;
- Application Graph: 48 tests, 273 assertions;
- Dependency Injection: 62 tests, 473 assertions;
- API-status architecture: 6 tests, 267 assertions;
- PHPStan: zero errors;
- PHP CS Fixer: 92 files, zero fixable files;
- canonical quality: at least 134 tests and 1099 assertions;
- locked audit: zero advisories;
- platform requirements: pass;
- warnings: only missing license and authorized local `@dev` or `@dev/*` categories;
- local Markdown links: pass;
- all textual candidates: strict UTF-8, no BOM, CRLF, NUL, replacement character, mojibake, conflict marker, trailing whitespace, or missing final newline;
- 42 diagnostic IDs reconcile exactly as 6 Kernel, 11 Graph, and 25 DI;
- no sensitive material, unsafe file type, non-default stream, unexpected mode, or unexpected path.

If a fail-closed dependency audit encounters only a sandbox network/cache denial, rerun the identical command with authorized access. Do not alter security policy.

---

# 6. REVISED EXACT STAGING CONTRACT

Do not use `git add .`, `git add -A`, wildcards, or interactive staging.

Stage the exact 35 tracked modifications:

```powershell
git add -u -- .
```

Verify this stages only the original Phase 0N Section 3 list. Then add exactly these 16 untracked files using literal paths:

```powershell
git add -- "docs/adr/0026-actionable-diagnostics-and-error-boundaries.md"
git add -- "docs/adr/0027-package-local-runtime-diagnostic-metadata.md"
git add -- "docs/dev_plan/kongono_phase_0L_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md"
git add -- "docs/dev_plan/kongono_phase_0N_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md"
git add -- "docs/engineering/error-handling.md"
git add -- "docs/task reports/Phase_0K.md"
git add -- "docs/task reports/Phase_0L.md"
git add -- "docs/task reports/Phase_0M.md"
git add -- "docs/task reports/Phase_0M_1_correction.md"
git add -- "docs/task reports/Phase_0N.md"
git add -- "packages/application-graph/src/Diagnostic/GraphDiagnostic.php"
git add -- "packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php"
git add -- "packages/kernel/src/Diagnostic/LifecycleDiagnostic.php"
```

Never stage, populate, modify, remove, rename, or move:

```text
docs/task reports/Phase_0N_1_correction.md
```

After staging, require:

```text
51 staged paths
  35 modified
  16 added
   0 deleted, renamed, copied, type-changed, or mode-changed

208 index entries
208 ordinary 100644 files
0 non-default alternate data streams

0 unstaged tracked paths
1 untracked path
  docs/task reports/Phase_0N_1_correction.md
```

Run the complete staged-state, blob-identity, tree, secret, file-safety, encoding, link, syntax, focused-test, analysis, and canonical quality checks required by Phase 0N. Record `git write-tree`. The report placeholder must remain zero bytes.

---

# 7. EXACT COMMIT OPERATION

Create one ordinary commit on `main` with exactly:

```text
Establish Kongono framework baseline through Phase 0M
```

The commit must:

```text
be the fifth commit reachable from main
have exactly one parent
have parent 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
contain exactly 208 ordinary 100644 files
equal the verified staged tree
change exactly 51 paths from its parent
record exactly 35 modifications and 16 additions
record no deletion, rename, copy, type change, Gitlink, or mode change
contain the populated blocked Phase 0N report
contain this Phase 0N.1 correction directive
exclude the zero-byte Phase 0N.1 completion report
use the exact subject above with no body or trailers
```

Retain all original Phase 0N commit prohibitions. Do not amend, bypass hooks, alter signing, change identity/configuration, push, tag, branch, merge, rebase, reset, or rewrite history.

The commit and tree hashes are outputs to record exactly.

---

# 8. POST-COMMIT VERIFICATION

Before populating the correction report, rerun every original Phase 0N post-commit check with the revised counts and paths.

Prove:

- `main` is the only branch and has exactly five commits;
- the new `HEAD` has the exact parent and subject;
- its tree equals the pre-commit staged tree;
- it contains exactly 208 ordinary `100644` files;
- its parent delta is exactly 51 paths: 35 modified and 16 added;
- all four earlier commits and trees remain unchanged;
- no non-default alternate data stream exists anywhere in the repository worktree;
- both corrected primary files retain their exact Section 4 size and SHA-256;
- index and tracked worktree are clean;
- remotes and tags remain zero;
- all quality, syntax, tests, analysis, style, audit, platform, links, text-integrity, diagnostic-catalog, secret, and file-safety checks pass from committed `HEAD`; and
- the only status path is the zero-byte untracked `docs/task reports/Phase_0N_1_correction.md`.

If any post-commit check fails, do not reset, amend, revert, or create a second commit. Preserve evidence and write a truthful blocked report.

Only after all gates pass may you populate `Phase_0N_1_correction.md`. Its final form must be nonzero, strict UTF-8 without BOM, LF-only, final-newline terminated, untracked, unstaged, and absent from `HEAD`. Record its final byte count and SHA-256 in the accompanying response after its final write.

---

# 9. PROHIBITED SCOPE

Apart from the exact two `Unblock-File` operations, all Phase 0N content-immutability and prohibited-scope rules remain in force.

Do not:

- remove an unexpected stream;
- edit either primary Markdown stream;
- edit any accepted content or historical report;
- alter manifests, lockfiles, tools, dependencies, policies, warnings, suppressions, baselines, or exclusions;
- build diagnostic context, redaction, renderers, error pages, HTTP/CLI mapping, logging, telemetry, incident handling, retry, cleanup, or later capabilities;
- expand Kernel, DI, Graph, ORM, agents, extensions, frontend, deployment, or other framework scope; or
- make any unauthorized Git-history or configuration change.

---

# 10. CORRECTION COMPLETION REPORT

Populate `docs/task reports/Phase_0N_1_correction.md` only after all post-commit validation passes.

Use exactly one outer `text` fence, no text outside it, and these 22 numbered top-level sections:

```text
1. STATUS
2. SUMMARY
3. BASELINE VERIFIED
4. CALLER RECORDS
5. FILES CREATED
6. FILES MODIFIED
7. AUTHORIZED STREAM CORRECTION
8. ARCHITECTURAL DECISIONS
9. DEVIATIONS FROM DIRECTIVE
10. DEPENDENCIES ADDED/REMOVED
11. ENVIRONMENT
12. COMMANDS RUN
13. PRE-STAGE VALIDATION RESULTS
14. STAGING EVIDENCE
15. COMMIT EVIDENCE
16. POST-COMMIT VALIDATION RESULTS
17. DOCUMENTATION REVIEW
18. SECURITY AND FILE-SAFETY REVIEW
19. WARNINGS / KNOWN ISSUES
20. QUESTIONS / BLOCKERS
21. ACCEPTANCE CHECKLIST AND FINAL GIT STATUS
22. NEXT STEP
```

Mandatory evidence:

- exact before/after stream inventories without stream contents;
- both primary file sizes and SHA-256 before and after;
- proof the stream correction changed no primary byte, Git diff, or locked caller record;
- exact baseline and historical Git facts;
- exact sizes and SHA-256 for all caller records, including the archived blocked report and this directive;
- every pre-stage, staged, committed, and final inventory;
- the staged tree, commit, tree, parent, subject, counts, and modes;
- exact syntax, focused-test, PHPStan, style, audit, platform, canonical quality, link, encoding, catalog, secret, and file-safety results;
- every warning and failed command with safe resolution;
- confirmation no tracked content was edited by Phase 0N.1;
- confirmation the only authorized filesystem mutation before report population was removal of the two named metadata streams;
- proof only the populated correction report remains untracked, unstaged, and outside `HEAD`;
- line-by-line PASS/FAIL against Section 11; and
- final report size and SHA-256 in the accompanying response, with the report noting the self-digest limitation.

For a completed run, Sections 8, 9, 10, and 20 say `None`. Section 22 must contain exactly:

```text
Await architect review before Phase 0O.
```

---

# 11. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Exact Section 3 baseline, 35 tracked modifications, 17 untracked paths, 52 total status paths, and zero staged paths.
- [ ] All original 13 accepted record hashes and all four Phase 0N/0N.1 caller records are exact.
- [ ] Exactly two pre-correction non-default streams exist, both named `Zone.Identifier`, both 342 bytes, on only the two Section 4 paths.
- [ ] The two primary sizes and SHA-256 values match before correction.
- [ ] Exactly two scoped `Unblock-File` operations remove exactly those streams.
- [ ] Both primary streams remain byte-identical afterward.
- [ ] Repository-wide non-default stream count becomes zero.
- [ ] Git status, tracked diff, index, and locked record bytes remain unchanged by the correction.
- [ ] Full restarted Phase 0N syntax, tests, analysis, quality, audit, links, catalog, security, and file-safety gates pass.
- [ ] Exactly 51 paths are staged: 35 modified and 16 added.
- [ ] The index contains exactly 208 ordinary `100644` files and no completion report.
- [ ] The staged tree is exact and only the zero-byte correction report is untracked.
- [ ] Exactly one fifth commit is created on `main` with the exact parent and subject.
- [ ] The commit tree equals the staged tree and contains exactly 208 files.
- [ ] The parent delta is exactly 51 paths: 35 modified and 16 added.
- [ ] No deletion, rename, copy, type change, mode change, branch, remote, tag, push, bypass, configuration change, or history rewrite occurs.
- [ ] All post-commit validation passes and earlier commits/trees remain unchanged.
- [ ] The only final status path is the populated Phase 0N.1 report, untracked, unstaged, and absent from `HEAD`.
- [ ] The report has exact structure and text integrity; its final size and SHA-256 are returned.
- [ ] Section 22 is exactly `Await architect review before Phase 0O.`

Any failure makes the status `Blocked` or `Partial`, never `Complete`.

---

# 12. FINAL EXECUTOR INSTRUCTION

Remove only the two verified `Zone.Identifier` metadata streams, prove both primary Markdown files remain byte-identical, restart all Phase 0N gates, and create the exact corrected checkpoint containing 208 ordinary files.

Do not change tracked content. Do not weaken the safety rule. Do not begin Phase 0O.

If any baseline, stream, path, byte, hash, count, mode, validation, caller record, staged tree, or Git fact differs, stop and return a truthful report rather than guessing or fixing anything else.

