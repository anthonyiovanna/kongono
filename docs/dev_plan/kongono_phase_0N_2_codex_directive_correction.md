# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0N.2

## Remove the Remaining Phase 0B Zone Metadata Stream and Complete the Checkpoint

## Mission

Remove exactly the one remaining 342-byte Windows `Zone.Identifier` alternate data stream from `docs/dev_plan/kongono_phase_0B_codex_directive.md` using one directly scoped PowerShell operation. Prove the primary Markdown stream remains byte-identical. Then restart all Phase 0N checkpoint validation, create the fifth immutable local commit through accepted Phase 0M, and leave only the Phase 0N.2 completion report untracked.

The exact commit subject remains:

```text
Establish Kongono framework baseline through Phase 0M
```

This is a filesystem-metadata correction plus the previously authorized checkpoint. It does not authorize a tracked content edit or weaken the alternate-stream prohibition.

---

# 1. AUTHORITY AND EXACT SUPERSESSION

You are the executor, not the architect.

Read completely:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0N_codex_directive.md`;
3. `docs/task reports/Phase_0N.md`;
4. `docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md`;
5. `docs/task reports/Phase_0N_1_correction.md`;
6. this Phase 0N.2 directive; and
7. every authority, accepted file, directive, and report required by Phase 0N.

The populated Phase 0N and Phase 0N.1 reports are immutable historical records, not active instructions.

This directive supersedes Phase 0N and Phase 0N.1 only for:

- the expected pre-correction stream inventory: Phase 0A is already clean and Phase 0B alone retains one stream;
- authorization for the one exact `Remove-Item` operation in Section 4;
- acceptance and staging of the populated Phase 0N.1 blocked report;
- addition of this Phase 0N.2 directive and its report placeholder;
- revised status, index, staged, committed-file, and commit-delta counts; and
- ownership of the final completion report by `Phase_0N_2_correction.md`.

Every other original checkpoint rule, content hash, path inventory, quality gate, security policy, prohibited scope, commit subject, parent, and history fact remains binding.

```text
STOP
REPORT
DO NOT GUESS
DO NOT RETRY
DO NOT FIX ANY OTHER PATH
DO NOT COMMIT A MISMATCH
```

Stop if any prerequisite differs, the scoped removal fails, the stream remains, a primary byte changes, another stream exists, an unexpected path appears, a validation fails, or the intended index/commit cannot be proven exactly.

---

# 2. LOCKED FINAL OUTCOME

Require exactly:

```text
Branch: main
Only local branch: main
Commit count after checkpoint: 5
New commit parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit subject: Establish Kongono framework baseline through Phase 0M
Committed files: 210
Committed modes: 210 x 100644
Parent delta paths: 53
Parent delta: 35 modified, 18 added, 0 deleted
Final untracked path: docs/task reports/Phase_0N_2_correction.md
Remotes: 0
Tags: 0
Push: prohibited
```

The 18 additions are the original 13 accepted untracked Phase 0L/0M paths plus:

```text
docs/dev_plan/kongono_phase_0N_codex_directive.md
docs/task reports/Phase_0N.md
docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md
docs/task reports/Phase_0N_1_correction.md
docs/dev_plan/kongono_phase_0N_2_codex_directive_correction.md
```

The Phase 0N.2 completion report stays outside the commit.

The commit and tree hashes are outputs. Record them; do not invent them.

---

# 3. REQUIRED PRE-CORRECTION BASELINE

Before the stream operation, require:

```text
Branch: main
Only local branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
Commit count: 4
HEAD subject: Establish Kongono framework baseline through Phase 0J
Tracked files: 192
Tracked modes: 192 x 100644
Tracked modifications: 35
Untracked paths: 19
Total status paths: 54
Staged paths: 0
Tracked diff: 35 files, 1265 insertions, 214 deletions
Remotes: 0
Tags: 0
```

The exact 35 tracked modifications and original 13 accepted untracked paths must match Sections 3 and 4 of Phase 0N, including all locked hashes and sizes.

The six additional caller records must be:

```text
docs/dev_plan/kongono_phase_0N_codex_directive.md
  30,853 bytes
  SHA-256 809D2539AC7B73CEAE4B7FC9D0898CB9B106D00862781DCAE5C97C9B32A44F3B

docs/task reports/Phase_0N.md
  3,871 bytes
  SHA-256 3E65139E86EBC5939A2305378685156B32BD68B89A2E84F7F0AFCE58DA7C8C70

docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md
  19,131 bytes
  SHA-256 8B47BBA1B0C036A1FF74A8EC342D1BD9C2808AA63271F976C4A1FC90B0E4EB53

docs/task reports/Phase_0N_1_correction.md
  5,436 bytes
  SHA-256 452E270ACBAE3A6FDF3D7726CBB906A00B371BF718112B05697ACED9DC11058F

docs/dev_plan/kongono_phase_0N_2_codex_directive_correction.md
  non-empty exact caller-supplied copy of this artifact
  record its observed byte count and SHA-256; do not edit

docs/task reports/Phase_0N_2_correction.md
  exactly zero bytes
  ordinary, untracked, unstaged, and absent from HEAD
```

No other path is authorized.

Verify the four-commit chain and all prior trees exactly as Phase 0N requires. Verify author identity and signing/hook state without changing configuration. Redact the email in the report.

---

# 4. ONE AUTHORIZED STREAM REMOVAL

Resolve the absolute target and prove it remains beneath:

```text
C:\Users\antho\Dev\kongono\docs\dev_plan
```

Before mutation, inspect streams without printing their contents.

Require exactly:

```text
docs/dev_plan/kongono_phase_0A_codex_directive.md
  only :$DATA
  primary size 24,156 bytes
  primary SHA-256 4B8F25D85AD43A18F8E9B41D44261B2ADE076A28BEA9EE729E01F61E7EDAFD46

docs/dev_plan/kongono_phase_0B_codex_directive.md
  :$DATA size 25,399 bytes
  primary SHA-256 6E68D7ECD8EC1029A5103B9D8C1766F01269434A4C94E85424BC7EDA22729D57
  Zone.Identifier size 342 bytes
```

A repository-wide stream inventory must find exactly one non-default stream: that Phase 0B `Zone.Identifier`. If any other stream exists, stop.

After all preconditions match, run exactly once:

```powershell
Remove-Item -LiteralPath "C:\Users\antho\Dev\kongono\docs\dev_plan\kongono_phase_0B_codex_directive.md" -Stream "Zone.Identifier"
```

This operation is authorized only to remove that named stream. No wildcard, recursion, variable-computed path, second attempt, alternate command, primary-file recreation, copy, permission change, or broader deletion is authorized.

Immediately prove:

- Phase 0A and Phase 0B expose only `:$DATA`;
- repository-wide non-default stream count is zero;
- both primary sizes and SHA-256 values remain exact;
- Git still shows the exact same 35 tracked modifications;
- diff remains 1265 insertions and 214 deletions;
- status still contains exactly 54 paths;
- index remains empty;
- every caller record remains byte-identical; and
- no primary content, path, mode, or Git metadata changed.

If the command exits nonzero, the stream remains, or any primary byte changes, stop. Do not retry, restore, reset, recreate, or improvise.

---

# 5. RESTART ALL CHECKPOINT GATES

After the exact stream removal, restart Phase 0N validation from its beginning. Do not substitute evidence from either blocked run for a required live check.

Use these revised counts:

```text
Pre-stage tracked modifications: 35
Pre-stage untracked paths: 19
Pre-stage status paths: 54
Staged paths: 53
Staged modifications: 35
Staged additions: 18
Index/commit files: 210
Final report: docs/task reports/Phase_0N_2_correction.md
```

All accepted implementation gates remain:

- exactly 93 first-party PHP/configuration files pass syntax;
- Kernel: 17 tests, 84 assertions;
- Application Graph: 48 tests, 273 assertions;
- Dependency Injection: 62 tests, 473 assertions;
- API-status architecture: 6 tests, 267 assertions;
- PHPStan: zero errors;
- PHP CS Fixer: 92 files, zero fixable files;
- `composer quality`: at least 134 tests and 1099 assertions;
- locked audit: zero advisories;
- platform requirements: pass;
- Composer warnings limited to missing license and authorized local `@dev` or `@dev/*` categories;
- current local Markdown links pass under the historical-record policy;
- all textual candidates pass strict UTF-8, BOM, CRLF, NUL, replacement-character, mojibake, conflict-marker, trailing-whitespace, and final-newline checks;
- all 42 diagnostic mappings reconcile exactly as 6 Kernel, 11 Graph, and 25 DI;
- no secret, unsafe type, unexpected mode, non-default stream, or unexpected path exists.

Rerun the identical fail-closed dependency audit with authorized access only if its first failure is solely sandbox network/cache denial. Never weaken policy.

---

# 6. REVISED EXACT STAGING CONTRACT

Do not use `git add .`, `git add -A`, wildcards, or interactive staging.

Stage the exact 35 tracked modifications:

```powershell
git add -u -- .
```

Verify the staged tracked list exactly matches Phase 0N Section 3. Then add exactly these 18 files using literal paths:

```powershell
git add -- "docs/adr/0026-actionable-diagnostics-and-error-boundaries.md"
git add -- "docs/adr/0027-package-local-runtime-diagnostic-metadata.md"
git add -- "docs/dev_plan/kongono_phase_0L_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md"
git add -- "docs/dev_plan/kongono_phase_0N_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md"
git add -- "docs/dev_plan/kongono_phase_0N_2_codex_directive_correction.md"
git add -- "docs/engineering/error-handling.md"
git add -- "docs/task reports/Phase_0K.md"
git add -- "docs/task reports/Phase_0L.md"
git add -- "docs/task reports/Phase_0M.md"
git add -- "docs/task reports/Phase_0M_1_correction.md"
git add -- "docs/task reports/Phase_0N.md"
git add -- "docs/task reports/Phase_0N_1_correction.md"
git add -- "packages/application-graph/src/Diagnostic/GraphDiagnostic.php"
git add -- "packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php"
git add -- "packages/kernel/src/Diagnostic/LifecycleDiagnostic.php"
```

Never stage, populate early, delete, rename, or move:

```text
docs/task reports/Phase_0N_2_correction.md
```

After staging, require:

```text
53 staged paths
  35 modified
  18 added
   0 deleted, renamed, copied, type-changed, or mode-changed

210 index entries
210 ordinary 100644 files
0 non-default alternate data streams
0 unstaged tracked paths

1 untracked path
  docs/task reports/Phase_0N_2_correction.md
```

Run every Phase 0N staged-state, cached-diff, blob-identity, `git write-tree`, security, file-safety, encoding, link, syntax, focused-test, analysis, and canonical quality check. The report must remain zero bytes until post-commit gates pass.

---

# 7. COMMIT AND POST-COMMIT CONTRACT

Create exactly one ordinary non-merge commit on `main` with:

```text
Establish Kongono framework baseline through Phase 0M
```

It must:

```text
be the fifth reachable commit
have exactly one parent
have parent 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
contain exactly 210 ordinary 100644 files
equal the verified staged tree
change exactly 53 paths from its parent
record exactly 35 modifications and 18 additions
record no deletion, rename, copy, type change, Gitlink, or mode change
contain both blocked reports and all three Phase 0N-family directives
exclude the zero-byte Phase 0N.2 completion report
use the exact subject with no body or trailers
```

Retain every Phase 0N prohibition on amendment, hook/signing bypass, identity/configuration change, branch, remote, tag, push, merge, rebase, reset, or history rewrite.

Before populating the report, rerun every post-commit gate. Prove the tree matches, the delta/counts are exact, earlier commits/trees remain unchanged, both primary hashes remain exact, repository-wide non-default stream count is zero, index/tracked worktree are clean, all quality and security gates pass, and only the zero-byte Phase 0N.2 report is untracked.

If a post-commit gate fails, preserve the commit and report truthfully. Do not reset, amend, revert, or add a corrective commit.

Only after all gates pass may `docs/task reports/Phase_0N_2_correction.md` be populated. Its final form must be nonzero, strict UTF-8 without BOM, LF-only, final-newline terminated, untracked, unstaged, and absent from `HEAD`. Return its final byte count and SHA-256 after the last write.

---

# 8. PROHIBITED SCOPE

Apart from the one exact `Remove-Item -Stream` operation, all original Phase 0N content-immutability and prohibited-scope rules remain in force.

Do not edit a primary file or any accepted content. Do not remove another stream. Do not alter dependencies, manifests, lockfiles, tooling, policies, warnings, suppressions, baselines, or exclusions.

Do not build structured context, redaction, error pages, renderers, HTTP/CLI mapping, logging, telemetry, incidents, retry, cleanup, runtime adapters, DI expansion, Graph expansion, ORM, agents, extensions, frontend, deployment, or any later capability.

---

# 9. PHASE 0N.2 COMPLETION REPORT

Populate `docs/task reports/Phase_0N_2_correction.md` only after complete post-commit success.

Use exactly one outer `text` fence, no content outside it, and these 22 numbered top-level sections:

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

Mandatory evidence includes:

- exact pre/post stream inventories without stream contents;
- both primary file sizes/hashes before and after;
- proof the one metadata removal changed no primary byte, Git diff, index, mode, or caller record;
- exact baseline, caller hashes, history, status, staged, tree, commit, parent-delta, and final evidence;
- exact syntax, focused tests, PHPStan, style, audit, platform, canonical test/assertion counts, links, encoding, catalog, secret, and file-safety results;
- every warning and failed command with resolution;
- confirmation no tracked content was edited;
- proof the only final status path is the populated report outside `HEAD`;
- line-by-line PASS/FAIL against Section 10; and
- final report size/SHA-256 in the accompanying response, with the self-digest limitation noted in the report.

For completion, Sections 8, 9, 10, and 20 say `None`. Section 22 must be exactly:

```text
Await architect review before Phase 0O.
```

---

# 10. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Baseline is exact: 35 tracked modifications, 19 untracked paths, 54 status paths, zero staged paths, and all locked Git facts.
- [ ] All original 13 accepted records and six Phase 0N-family caller records match.
- [ ] Phase 0A has no non-default stream and retains its exact primary size/hash.
- [ ] Phase 0B alone has exactly one 342-byte `Zone.Identifier` and retains its exact primary size/hash.
- [ ] One literal `Remove-Item -Stream Zone.Identifier` operation removes that stream.
- [ ] Both primary files remain byte-identical and repository-wide non-default stream count becomes zero.
- [ ] Git diff, status, index, modes, and all caller bytes remain unchanged by the correction.
- [ ] All restarted Phase 0N quality, test, syntax, analysis, audit, link, catalog, security, and file-safety gates pass.
- [ ] Exactly 53 paths are staged: 35 modified and 18 added.
- [ ] The index contains exactly 210 ordinary `100644` files and excludes the completion report.
- [ ] Only the zero-byte Phase 0N.2 report remains untracked before commit.
- [ ] Exactly one fifth commit is created with the exact parent and subject.
- [ ] Commit tree equals staged tree; parent delta is exactly 53 paths.
- [ ] No unauthorized mutation, deletion, rename, copy, mode change, branch, remote, tag, push, bypass, config change, or rewrite occurs.
- [ ] All post-commit gates pass and prior commits/trees remain unchanged.
- [ ] Only the populated Phase 0N.2 report remains untracked, unstaged, and absent from `HEAD`.
- [ ] Report structure/integrity pass and final report size/SHA-256 are returned.
- [ ] Section 22 is exactly `Await architect review before Phase 0O.`

Any failure means `Blocked` or `Partial`, never `Complete`.

---

# 11. FINAL EXECUTOR INSTRUCTION

Remove only the remaining Phase 0B `Zone.Identifier` using the exact literal command, prove primary bytes are unchanged, restart every checkpoint gate, and create the exact 210-file checkpoint.

Do not retry the stream operation. Do not change tracked content. Do not weaken safety. Do not begin Phase 0O.

If any path, stream, byte, hash, count, mode, result, staged tree, or Git fact differs, stop and report rather than guessing or fixing anything else.

