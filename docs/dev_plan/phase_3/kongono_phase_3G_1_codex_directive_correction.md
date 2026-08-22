# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3G.1
## Exact Historical-Path Restoration and Phase 3G Closure

**Directive date:** 2026-08-21

**Repository:** `C:\Users\antho\Dev\kongono`

**Execution role:** executor, not architect

**Scope:** Phase 3G.1 correction only

**Checkpoint:** none; do not stage or commit

---

## 1. Mission

Correct one executor-caused Phase 3G preservation defect without changing any
accepted file bytes.

During final Phase 3G auditing, PowerShell's `mi` alias invoked `Move-Item` and
relocated exactly three immutable historical directive files from
`docs/dev_plan/phase_0/` to the repository root. Their contents remain exact,
but their paths no longer satisfy the authenticated 208-record historical
manifest or the protected-path proof.

Phase 3G.1 authorizes exactly:

1. authenticate the three root files and prove that their three original
   destination paths are absent;
2. move each exact file back to its exact original
   `docs/dev_plan/phase_0/` path without changing its bytes;
3. rerun the read-only final Phase 3G preservation, inventory, link,
   file-safety, and report-readiness proofs affected by those paths; and
4. after every non-report criterion passes, populate only
   `docs/task_reports/phase_3/Phase_3G_1_correction.md` as the authoritative
   Phase 3G closure report.

This correction does not authorize any production, test, Composer, lockfile,
tooling, ADR, current-document, Phase 3G implementation, original Phase 3G
report, staging, commit, branch, ref, or history mutation.

The zero-byte original `docs/task_reports/phase_3/Phase_3G.md` remains preserved
as evidence of the stopped Phase 3G execution. The completed Phase 3G.1 report
supersedes only the original directive's requirement that Phase 3G itself be
the populated closure report.

Do not begin Phase 3H.

---

## 2. Locked authority

### 2.1 Read completely before validation or mutation

Read these exact files completely:

```text
AGENTS.md
ARCHITECTURE.md
README.md
SECURITY.md
docs/doctrine.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/engineering/php-cgi-deployment.md
docs/dev_plan/phase_3/kongono_phase_3F_1_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3F_1_correction.md
docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md
```

Read this Phase 3G.1 directive completely after the caller places it at:

```text
docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md
```

Do not substitute similarly named or numbered files. Do not interpret a root
copy of one of the three misplaced files as its accepted archive location.

### 2.2 Independent directive authentication

The architect must provide this directive's byte count and SHA-256 outside the
file. Compare those independently supplied values with the local copy before
continuing.

Do not treat the local file as self-authenticating. If the values are absent or
do not match:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT RUN QUALITY OR MUTATE THE REPOSITORY
```

### 2.3 Doctrine and authority

Preserve every accepted Kongono doctrine and Phase 3G contract. In particular:

- executor, not architect;
- stop rather than guess;
- explicit paths and deterministic proof;
- historical directives and reports are immutable evidence;
- no ambient state, hidden cleanup, or opportunistic repair;
- no dependency, policy, API, runtime, or public-contract expansion;
- no staging or commit without explicit authority; and
- reports must disclose verified facts, deviations, and failures truthfully.

This directive is the exact architect authorization required by the Phase 3G
stop. It authorizes no other restoration, cleanup, rename, relocation, or
normalization.

---

## 3. Authenticated entry state

### 3.1 Repository and completed Phase 3G implementation state

Before the two Phase 3G.1 caller records are added, require the Phase 3G
post-implementation state to remain:

```text
branch:                       main
local branches:                 1
HEAD:                         647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                         3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                       d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:                  10
remotes:                        0
tags:                           0
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               258
total status paths:            473
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    609
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
docs/dev_plan/phase_3 files:     12
docs/task_reports/phase_3 files: 12
```

Existing `refs/codex/**` application-managed refs are permitted and remain
untouched. Other than `refs/heads/main`, require no branch, remote, tag, or
unexpected non-application ref. The semantic index must equal `HEAD`.

The root lockfile remains:

```text
bytes:   197527
SHA-256: 823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
```

The Phase 3G directive remains:

```text
bytes:   44636
SHA-256: 25071053D424DF6B67D6170B82E5183186C3B9E73A68858F26FE06A1200674B8
```

The original Phase 3G report remains an ordinary, untracked, unstaged,
`HEAD`-absent zero-byte file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not rerun completed Phase 3G PHP, Composer, PHPStan, PHP CS Fixer, PHPUnit,
autoload, behavioral, or canonical-quality commands during Gate A. Their
accepted pre-block evidence must be reported truthfully in the correction
handoff, but this correction changes no analyzed or executable byte.

### 3.2 Exact misplaced-file matrix

Require exactly this entry state:

| Root source | Original destination | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| `kongono_phase_0A_codex_directive.md` | `docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md` | 24,156 | `4B8F25D85AD43A18F8E9B41D44261B2ADE076A28BEA9EE729E01F61E7EDAFD46` |
| `kongono_phase_0B_1_codex_directive_correction.md` | `docs/dev_plan/phase_0/kongono_phase_0B_1_codex_directive_correction.md` | 1,567 | `99A1B0E1F97CB4067739A5AD7175AE8E47DB9BF76D45E83C3461B23A8AC3C489` |
| `kongono_phase_0B_2_codex_directive_correction.md` | `docs/dev_plan/phase_0/kongono_phase_0B_2_codex_directive_correction.md` | 2,168 | `17CCC9914D82F86675889B85F83A069FAEF3E931BF3387D35C0CD34A20E481B5` |

For each row before mutation require:

- the root source exists as one ordinary file;
- the source has the exact byte count and SHA-256 above;
- the source has no reparse point and only its default data stream;
- the exact destination is absent;
- neither the root source nor destination is staged;
- no additional same-name copy exists elsewhere in the repository; and
- the resolved source and destination remain inside
  `C:\Users\antho\Dev\kongono`.

If any identity, source, destination, duplicate, stream, reparse, containment,
or index condition differs, stop without moving anything.

### 3.3 Exact failed archive evidence

Excluding the active Phase 3G pair, the on-disk numbered archive currently has:

```text
records:               205
manifest bytes:      25682
manifest SHA-256:    4DA9FF19A7E0DEE31CDDE61A52E8CF177394613DB36DF61B51C681091B6F8209
aggregate file bytes: 4234383
first path:           docs/dev_plan/phase_0/kongono_phase_0B_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3F_1_correction.md
```

Serialize paths with the exact Phase 3G Section 3.2 rules: normalized `/`
paths, `[StringComparer]::Ordinal`, UTF-8 without BOM, and
`path<TAB>byte-count<TAB>uppercase-SHA-256<LF>` records.

Before mutation, also perform a virtual read-only reconstruction that maps the
three exact root sources to their three destination path strings without
moving them. It must reproduce the accepted archive exactly:

```text
records:               208
manifest bytes:      26093
manifest SHA-256:    88CC71C9CECF05CC9C2B63F6963F57103281E078656AC446494E297074AF5A81
aggregate file bytes: 4262274
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3F_1_correction.md
```

This virtual proof is mandatory. It establishes that the defect is path-only
and that the authorized move restores the exact accepted archive. Do not adopt
a locally observed replacement manifest.

### 3.4 Phase 3G.1 caller records

The caller must add exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3G_1_correction.md
```

The directive must match the independently supplied identity. The report must
be an ordinary, untracked, unstaged, `HEAD`-absent zero-byte file with the
empty-file SHA-256.

With these two records present and before restoration, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               260
total status paths:            475
staged paths:                    0
physical Git-visible files:    611
tracked index paths:           536
docs/dev_plan/phase_3 files:     13
docs/task_reports/phase_3 files: 13
```

When reconstructing Phase 3G's accepted manifests, exclude the two Phase 3G.1
caller records in addition to the exclusions required by the original Phase 3G
directive.

---

## 4. Authorized mutation

### 4.1 Closed mutation boundary

The only non-report filesystem mutations authorized are:

```text
MOVE kongono_phase_0A_codex_directive.md
  TO docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md

MOVE kongono_phase_0B_1_codex_directive_correction.md
  TO docs/dev_plan/phase_0/kongono_phase_0B_1_codex_directive_correction.md

MOVE kongono_phase_0B_2_codex_directive_correction.md
  TO docs/dev_plan/phase_0/kongono_phase_0B_2_codex_directive_correction.md
```

No bytes may be edited, normalized, regenerated, copied, truncated, appended,
or reformatted. Do not use a wildcard, recursive move, Git restore, checkout,
reset, clean, stash, patch, formatter, editor, or cross-shell file operation.

### 4.2 Exact state-aware move procedure

Run the operation in PowerShell from the repository root. Use absolute literal
paths constructed from the fixed repository path. Do not use `mi` or another
alias.

The implementation must be state-aware for each exact pair:

1. If the exact source exists and the exact destination is absent, revalidate
   the source byte count, SHA-256, ordinary-file status, default stream,
   non-reparse status, and repository containment, then use the fully qualified
   `Microsoft.PowerShell.Management\Move-Item` cmdlet with `-LiteralPath`, the
   exact destination, and `-ErrorAction Stop`.
2. If the exact source is absent and the exact destination exists, accept that
   pair only when the destination already has the exact locked identity and
   safety properties. Do not move or rewrite it again.
3. Every other source/destination combination is a hard stop.
4. After each pair, require the source absent, destination present, and exact
   destination byte count and SHA-256 before continuing.

This state-aware rule permits safe resumption after an environmental
interruption without overwriting an accepted destination or duplicating a
historical file. It does not authorize a retry with a different command or any
repair outside the three pairs.

If PowerShell reports an error, if a destination appears unexpectedly, or if a
post-move identity differs, stop immediately and report the exact reached pair
states. Do not roll back a successful pair and do not improvise.

---

## 5. Mandatory post-restoration proof

After all three moves, require:

1. all three repository-root source paths are absent;
2. all three original destination paths exist;
3. each destination has the exact byte count and SHA-256 in Section 3.2;
4. each destination remains an ordinary, non-reparse file with only its default
   data stream;
5. no duplicate same-name file exists elsewhere in the repository;
6. the exact 208-record historical manifest from Section 3.3 passes on disk;
7. Phase 3G's 570-record protected manifest passes after adding only the two
   Phase 3G.1 caller-record exclusions:

```text
records:               570
manifest bytes:      72266
manifest SHA-256:    DB2AB4C0520651D3C019FECF34B5383FC3C64484FB647583D8999CEBFA8F64EC
aggregate file bytes: 6083739
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

8. every Phase 3G created and modified path remains byte-identical to the state
   reached before the failed preservation check;
9. root and package Composer manifests, `composer.lock`, tooling, production
   PHP, tests, ADR 0057, and current documentation remain unchanged by Phase
   3G.1;
10. all current nonhistorical local Markdown links resolve;
11. all physical Git-visible files satisfy the original Phase 3G UTF-8/LF,
    whitespace, NUL, ordinary-file, stream, reparse, nested-repository, and mode
    rules applicable to them;
12. `git diff --check` passes;
13. branch, refs, `HEAD`, tree, parent, commit count, semantic index, remotes,
    tags, lockfile, and PHP environment remain exact; and
14. no path is staged and no commit or history object was created by this
    correction.

Do not rerun Composer audit, canonical quality, PHPStan, PHP CS Fixer, PHPUnit,
autoload generation, focused PHP tests, behavioral runners, or external
network checks. Those gates passed before this path-only preservation defect;
their evidence remains valid because every executable, configuration,
dependency, and current-document byte is protected and unchanged. Report the
original evidence as previously observed, not as rerun Phase 3G.1 evidence.

After restoration and before report population, the state including the two
Phase 3G.1 caller records must be:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               260
total status paths:            475
staged paths:                    0
physical Git-visible files:    611
tracked index paths:           536
docs/dev_plan/phase_3 files:     13
docs/task_reports/phase_3 files: 13
```

Excluding the Phase 3G.1 caller pair, the original Phase 3G final state must
remain exactly `30 modified / 185 deleted / 258 untracked / 473 total / 0
staged`, 609 physical files, 536 tracked index paths, 126 production PHP files,
140 test PHP files, and 266 style-covered PHP files.

Any mismatch stops completion. Preserve the reached state and keep the active
correction report zero bytes.

---

## 6. Report population and final evidence

Only after every non-report gate in this correction passes, populate:

```text
docs/task_reports/phase_3/Phase_3G_1_correction.md
```

Do not populate or otherwise modify:

```text
docs/task_reports/phase_3/Phase_3G.md
```

The correction report is the authoritative closure record for Phase 3G and
must truthfully incorporate the already-observed Phase 3G implementation and
quality evidence plus the Phase 3G.1 path-restoration evidence. It must not
claim that Phase 3G.1 reran commands prohibited by Section 5.

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MOVED
## 7. FILES MODIFIED
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. VALIDATION RESULTS
## 14. PHASE 3G IMPLEMENTATION AND QUALITY EVIDENCE
## 15. HISTORICAL AND PROTECTED MANIFEST PROOF
## 16. DOCUMENTATION REVIEW
## 17. SECURITY AND FILE-SAFETY REVIEW
## 18. WARNINGS / KNOWN ISSUES
## 19. QUESTIONS / BLOCKERS
## 20. FINAL GIT STATUS
## 21. ACCEPTANCE CRITERIA
## 22. NEXT STEP
```

Required truthfulness details:

- `FILES CREATED` identifies the two caller-supplied Phase 3G.1 records and
  states that the executor created no implementation file.
- `FILES MOVED` lists the three exact source-to-destination pairs and their
  unchanged identities.
- `FILES MODIFIED` states that only the active correction report was populated
  after all non-report gates passed.
- `DEVIATIONS FROM DIRECTIVE` records the original Phase 3G `mi` alias accident
  and states that Phase 3G.1 restored the exact accepted paths under explicit
  authority. Do not state `None`.
- `COMMANDS RUN` separates commands run before the original Phase 3G stop from
  commands run during Phase 3G.1.
- `VALIDATION RESULTS` states that quality was not rerun during Phase 3G.1 and
  identifies the exact path, manifest, link, file-safety, Git, and report checks
  actually run.
- `PHASE 3G IMPLEMENTATION AND QUALITY EVIDENCE` records the prior Phase 3G
  results without presenting them as fresh Phase 3G.1 runs.
- `QUESTIONS / BLOCKERS` is `None` only when every criterion passes.
- `NEXT STEP` is exactly:

```text
Await architect review before Phase 3H. Do not begin Phase 3H automatically.
```

After report population, rerun only report integrity, caller-record status,
historical/protected manifests, exact inventory, Git identity/status,
no-staging, and no-history-change proofs. The report must be strict UTF-8
without BOM, LF-only, final-LF, no trailing whitespace, no NUL, ordinary,
non-reparse, default-stream-only, untracked, unstaged, and absent from `HEAD`.

Return the final report byte count and uppercase SHA-256 outside the report
body. Do not embed a self-referential digest in the report.

---

## 7. Prohibited scope

Phase 3G.1 must not:

- change any file contents except population of the active correction report;
- move any path except the three exact root sources in Section 4;
- copy instead of move, create a duplicate, or overwrite a destination;
- rewrite, normalize, re-encode, truncate, or regenerate a historical file;
- modify the original Phase 3G directive or zero-byte report;
- modify Phase 3G implementation, tests, ADR 0057, deployment documentation,
  package README files, current architecture documents, or quality evidence;
- run a formatter in write mode;
- run dependency installation or update;
- alter Composer manifests, the lockfile, platform policy, PHP constraints,
  dependency policy, plugin policy, or tooling configuration;
- rerun the semantic or quality commands prohibited by Section 5;
- add a package, API, diagnostic, schema, dependency, configuration, runtime,
  listener, deployment artifact, acceptance fixture, agent, CLI, database,
  ORM, template, telemetry, or later capability;
- use `mi`, wildcards, recursive moves, another shell, Git restore, checkout,
  reset, clean, stash, add, commit, write-tree, update-index, branch, tag,
  remote, or ref mutation;
- remove or modify `refs/codex/**`;
- populate a report before all non-report gates pass; or
- begin Phase 3H.

---

## 8. Acceptance criteria

Every item must pass:

1. This directive matches the independently supplied byte count and SHA-256.
2. Every required authority was read completely.
3. Repository, caller, report-placeholder, Git, index, lockfile, environment,
   inventory, and Phase 3G implementation state match the authenticated entry.
4. The three root sources have the exact identities in Section 3.2, their
   destinations are absent, and no duplicate exists before mutation.
5. The exact 205-record failed on-disk archive is reproduced.
6. The virtual three-path reconstruction reproduces the exact accepted
   208-record historical manifest before mutation.
7. Only the three explicitly authorized moves occur.
8. Every moved destination retains its exact original bytes and SHA-256.
9. All three root sources are absent and all three original destinations are
   present after restoration.
10. The exact 208-record historical manifest passes on disk.
11. The exact 570-record protected manifest passes with only the authorized
    Phase 3G.1 caller exclusions added.
12. Phase 3G implementation, tests, ADR, current documentation, Composer,
    lockfile, tooling, dependency, and quality-config bytes remain unchanged.
13. Current links, encoding, LF, whitespace, NUL, ordinary-file, stream,
    reparse, nested-repository, mode, and `git diff --check` proofs pass.
14. Original Phase 3G final inventory and status pass when the two Phase 3G.1
    caller records are excluded.
15. Corrected final inventory and status including the caller pair are exact.
16. Branch, refs, `HEAD`, tree, parent, history, semantic index, remotes, tags,
    lockfile, and environment remain exact.
17. No semantic or quality command prohibited by Section 5 was rerun.
18. Nothing was staged or committed.
19. The original Phase 3G report remains zero bytes and unchanged.
20. The active Phase 3G.1 report remains zero bytes until every non-report gate
    passes and is then populated once.
21. The report has all exact headings and truthfully discloses the alias
    accident, correction authority, move evidence, prior quality evidence, and
    commands actually run.
22. Final report integrity, manifests, inventory, Git, index, history, and
    caller-record proofs pass.
23. The report byte count and SHA-256 are returned outside the report body.
24. Phase 3H was not started.

Any failure means Phase 3G.1 is not complete. Stop, preserve the exact reached
state, report expected and observed values, and do not expand scope.

---

## 9. Final instruction

Restore only the three authenticated historical directive paths. Preserve every
file byte, every Phase 3G implementation result, the zero-byte original Phase
3G report, the index, and history. Use the state-aware literal-path move rule;
never use `mi` or a wildcard.

After exact restoration, run only the authorized read-only preservation and
report-readiness checks. Populate only the Phase 3G.1 correction report after
all non-report criteria pass, return its independent identity, and await
architect review. Do not begin Phase 3H.
