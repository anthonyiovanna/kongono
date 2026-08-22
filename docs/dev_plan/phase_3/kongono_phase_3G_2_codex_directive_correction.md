# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3G.2
## Corrected Three-File Historical Restoration and Final Closure

**Directive date:** 2026-08-21

**Repository:** `C:\Users\antho\Dev\kongono`

**Execution role:** executor, not architect

**Scope:** Phase 3G.2 correction only

**Checkpoint:** none; do not stage or commit

---

## 1. Mission

Resume the still-unapplied Phase 3G.1 historical-path correction using a
mechanism that cannot lose a PowerShell module separator.

Phase 3G.1 authenticated all three misplaced root files and their absent
destinations. Its first mutation attempt passed the malformed token:

```text
Microsoft.PowerShell.ManagementMove-Item
```

PowerShell rejected that token before any filesystem mutation. All three root
sources therefore remain exact, all three original destinations remain absent,
both prior Phase 3G reports remain zero bytes, and the worktree, index, history,
implementation, and caller records remain preserved.

Phase 3G.2 authorizes exactly:

1. authenticate the preserved post-Phase-3G.1-failure state;
2. move the same three exact files to the same exact original archive paths by
   calling `[System.IO.File]::Move(string, string)` directly;
3. run the final read-only restoration, manifest, inventory, link,
   file-safety, Git, and report-readiness proofs required by Phase 3G.1; and
4. after every non-report criterion passes, populate only
   `docs/task_reports/phase_3/Phase_3G_2_correction.md` as the authoritative
   Phase 3G closure report.

This directive explicitly supersedes only:

- Phase 3G.1's malformed fully-qualified `Move-Item` mechanism;
- Phase 3G.1's prohibition on another attempt after that exact no-mutation
  command failure; and
- Phase 3G.1's correction-report target.

Every other Phase 3G and Phase 3G.1 requirement remains locked. Do not begin
Phase 3H.

---

## 2. Mandatory authority and authentication

### 2.1 Read completely

Read these exact files completely before validation or mutation:

```text
AGENTS.md
docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md
docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md
```

Read this Phase 3G.2 directive completely after the caller places it at:

```text
docs/dev_plan/phase_3/kongono_phase_3G_2_codex_directive_correction.md
```

Do not substitute a similarly named or numbered file.

### 2.2 Independent identity

The architect must provide this directive's exact byte count and uppercase
SHA-256 outside the file. Compare those supplied values with the local `.md`
file before continuing. The `.md` path itself is mandatory.

If the supplied values are missing, the local path is absent, or an identity
differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT RUN QUALITY OR MUTATE THE REPOSITORY
```

### 2.3 Locked earlier caller records

Require:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3G_codex_directive.md` | 44,636 | `25071053D424DF6B67D6170B82E5183186C3B9E73A68858F26FE06A1200674B8` |
| `docs/dev_plan/phase_3/kongono_phase_3G_1_codex_directive_correction.md` | 22,085 | `BAB511130C449756C1414B130E4BCAF9335ECC193F14A26B416A6B38C5C7BD55` |
| `docs/task_reports/phase_3/Phase_3G.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_3/Phase_3G_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

All four remain ordinary, untracked, unstaged, `HEAD`-absent files. Do not edit,
rename, move, truncate, populate, or otherwise repair them.

---

## 3. Authenticated preserved entry state

### 3.1 Repository state before Phase 3G.2 caller records

Require:

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
untracked paths:               260
total status paths:            475
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    611
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
docs/dev_plan/phase_3 files:     13
docs/task_reports/phase_3 files: 13
```

Existing `refs/codex/**` application-managed refs remain permitted and
untouched. Require no unexpected branch, remote, tag, or non-application ref.
The semantic index equals `HEAD`.

The root lockfile remains:

```text
bytes:   197527
SHA-256: 823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
```

### 3.2 Exact preserved source and destination state

Require exactly:

| Root source | Original destination | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| `kongono_phase_0A_codex_directive.md` | `docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md` | 24,156 | `4B8F25D85AD43A18F8E9B41D44261B2ADE076A28BEA9EE729E01F61E7EDAFD46` |
| `kongono_phase_0B_1_codex_directive_correction.md` | `docs/dev_plan/phase_0/kongono_phase_0B_1_codex_directive_correction.md` | 1,567 | `99A1B0E1F97CB4067739A5AD7175AE8E47DB9BF76D45E83C3461B23A8AC3C489` |
| `kongono_phase_0B_2_codex_directive_correction.md` | `docs/dev_plan/phase_0/kongono_phase_0B_2_codex_directive_correction.md` | 2,168 | `17CCC9914D82F86675889B85F83A069FAEF3E931BF3387D35C0CD34A20E481B5` |

For every row, before mutation require:

- the exact root source exists;
- the exact destination is absent;
- the source byte count and SHA-256 match;
- the source is ordinary, non-reparse, and default-stream-only;
- no additional physical same-name copy exists in the repository;
- neither path is staged; and
- resolved source and destination paths are contained by the fixed repository
  root.

The prior malformed token must have caused no partial move. If any one source
is absent or any one destination exists, this entry state differs: stop and do
not use the state-aware resumption allowance from Phase 3G.1.

### 3.3 Archive proof before mutation

Using Phase 3G's exact ordinal UTF-8 manifest algorithm, require the failed
on-disk numbered archive excluding the Phase 3G, Phase 3G.1, and Phase 3G.2
caller pairs to remain:

```text
records:               205
manifest bytes:      25682
manifest SHA-256:    4DA9FF19A7E0DEE31CDDE61A52E8CF177394613DB36DF61B51C681091B6F8209
aggregate file bytes: 4234383
first path:           docs/dev_plan/phase_0/kongono_phase_0B_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3F_1_correction.md
```

Repeat the Phase 3G.1 virtual read-only reconstruction mapping the three exact
root sources to their destination path strings. Require:

```text
records:               208
manifest bytes:      26093
manifest SHA-256:    88CC71C9CECF05CC9C2B63F6963F57103281E078656AC446494E297074AF5A81
aggregate file bytes: 4262274
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3F_1_correction.md
```

No locally observed replacement value may be adopted.

### 3.4 Phase 3G.2 caller records

The caller must place exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3G_2_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3G_2_correction.md
```

The directive must match the independently supplied identity. The report must
be ordinary, untracked, unstaged, `HEAD`-absent, and zero bytes with the
empty-file SHA-256.

With the two Phase 3G.2 records present and before mutation, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               262
total status paths:            477
staged paths:                    0
physical Git-visible files:    613
tracked index paths:           536
docs/dev_plan/phase_3 files:     14
docs/task_reports/phase_3 files: 14
```

If any Gate A condition differs, stop before mutation. Do not rerun Composer or
quality commands.

---

## 4. Exact authorized move

### 4.1 Closed mutation set

The only non-report mutations authorized are the same three moves:

```text
kongono_phase_0A_codex_directive.md
  -> docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md

kongono_phase_0B_1_codex_directive_correction.md
  -> docs/dev_plan/phase_0/kongono_phase_0B_1_codex_directive_correction.md

kongono_phase_0B_2_codex_directive_correction.md
  -> docs/dev_plan/phase_0/kongono_phase_0B_2_codex_directive_correction.md
```

Do not change any file byte.

### 4.2 Required .NET mechanism

Use one PowerShell process rooted at `C:\Users\antho\Dev\kongono`. Define the
three source/destination/byte/hash records literally. For each record:

1. combine the fixed repository root with the exact relative source and
   destination;
2. canonicalize both with `[System.IO.Path]::GetFullPath(...)`;
3. prove each canonical path starts with the fixed canonical repository root
   followed by the directory separator, using
   `[StringComparison]::OrdinalIgnoreCase`;
4. prove the source exists and destination does not;
5. revalidate exact source size, SHA-256, ordinary-file, stream, and reparse
   properties;
6. call exactly:

```powershell
[System.IO.File]::Move($source, $destination)
```

7. immediately prove the source is absent and the destination exists with the
   exact locked size and SHA-256 before processing the next row.

Do not use `mi`, `Move-Item`, a module-qualified command, `Rename-Item`,
`Copy-Item`, a wildcard, recursion, another shell, Git, or string-built command
execution.

The two-argument .NET overload is required. Do not use an overwrite overload.
An existing destination must therefore remain a hard stop rather than being
replaced.

If any check or `[System.IO.File]::Move(...)` call throws, stop immediately.
Report the exact reached source/destination states. Do not roll back a completed
pair, retry, switch mechanisms, copy, delete, or improvise without new
authenticated authority.

---

## 5. Post-move validation

After all three moves, require:

1. every root source is absent;
2. every original destination exists with its exact locked identity;
3. no duplicate same-name physical file exists;
4. every destination is ordinary, non-reparse, and default-stream-only;
5. the exact accepted 208-record historical manifest passes on disk;
6. the exact Phase 3G 570-record protected manifest passes when all three
   correction caller pairs are added to the original exclusions:

```text
records:               570
manifest bytes:      72266
manifest SHA-256:    DB2AB4C0520651D3C019FECF34B5383FC3C64484FB647583D8999CEBFA8F64EC
aggregate file bytes: 6083739
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

7. Phase 3G implementation, tests, ADR 0057, current documentation, Composer
   manifests, lockfile, quality configuration, and all prior caller records are
   byte-identical to entry;
8. all current nonhistorical local Markdown links resolve;
9. encoding, LF, final-LF, whitespace, NUL, ordinary-file, stream, reparse,
   nested-repository, tracked-mode, and `git diff --check` proofs pass;
10. branch, permitted refs, `HEAD`, tree, parent, commit count, semantic index,
    remotes, tags, lockfile, and environment remain exact;
11. zero paths are staged; and
12. no commit, history, ref, dependency, or unrelated filesystem mutation
    occurred.

Do not rerun Composer audit, `composer quality`, PHPStan, PHP CS Fixer,
PHPUnit, autoload generation, behavioral runners, or network checks. Preserve
and report the Phase 3G evidence already obtained before the path defect.

After restoration and before report population, require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               262
total status paths:            477
staged paths:                    0
physical Git-visible files:    613
tracked index paths:           536
docs/dev_plan/phase_3 files:     14
docs/task_reports/phase_3 files: 14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
```

Excluding all three correction caller pairs, the original Phase 3G final state
must remain `30 modified / 185 deleted / 258 untracked / 473 total / 0 staged`
with 609 physical files.

Any mismatch leaves the active Phase 3G.2 report zero bytes and blocks
completion.

---

## 6. Authoritative closure report

Only after every non-report criterion passes, populate:

```text
docs/task_reports/phase_3/Phase_3G_2_correction.md
```

Keep both earlier reports unchanged and zero bytes:

```text
docs/task_reports/phase_3/Phase_3G.md
docs/task_reports/phase_3/Phase_3G_1_correction.md
```

Phase 3G.2 becomes the authoritative Phase 3G closure record. Use exactly:

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

The report must disclose both failed attempts accurately:

- Phase 3G's audit accidentally invoked the `mi` alias and moved three
  historical files to the repository root;
- Phase 3G.1's intended module-qualified token lost its separator and was
  rejected before mutation; and
- Phase 3G.2 used the exact authorized two-argument .NET file move and restored
  the accepted paths without changing bytes.

Do not report `None` under deviations. Separate earlier Phase 3G commands and
results from Phase 3G.2 commands. State explicitly that semantic and quality
commands were not rerun in Phase 3G.2.

`NEXT STEP` must be exactly:

```text
Await architect review before Phase 3H. Do not begin Phase 3H automatically.
```

After report population, rerun only report integrity, caller identities,
historical/protected manifests, exact inventory, Git status/identity,
no-staging, and no-history-change proofs. The report must be strict UTF-8
without BOM, LF-only, final-LF, no trailing whitespace, no NUL, ordinary,
non-reparse, default-stream-only, untracked, unstaged, and absent from `HEAD`.

Return the final report byte count and uppercase SHA-256 outside the report
body.

---

## 7. Prohibited scope

Do not:

- use the malformed Phase 3G.1 command token;
- use any move or copy mechanism other than the exact .NET call in Section 4;
- move, copy, delete, rename, restore, or edit any other path;
- change the contents of a moved historical file;
- modify or populate either earlier zero-byte report;
- modify any directive or historical caller record;
- modify implementation, tests, ADRs, current documentation, dependencies,
  Composer files, lockfile, PHP constraints, policies, tooling, or public API;
- run prohibited semantic or quality commands;
- create an agent, package, runtime, deployment, acceptance, database, ORM,
  template, CLI, telemetry, or later capability;
- stage, commit, write-tree, update-index, restore, checkout, reset, clean,
  stash, branch, tag, remote, or mutate refs;
- touch `refs/codex/**`;
- populate the active report before every non-report gate passes; or
- begin Phase 3H.

---

## 8. Acceptance criteria

Every criterion must pass:

1. Independent Phase 3G.2 directive identity matches.
2. Every required authority was read completely.
3. Exact repository, Git, caller, inventory, lockfile, environment, and
   zero-byte report entry state matches.
4. All three root sources and absent destinations match the locked matrix.
5. The 205-record failed archive and virtual 208-record accepted archive pass
   before mutation.
6. Only three exact two-argument `[System.IO.File]::Move(...)` calls mutate
   non-report filesystem state.
7. Every move preserves the exact source bytes at its exact destination.
8. Root sources are absent, destinations exact, and duplicates absent.
9. The on-disk 208-record historical manifest passes after restoration.
10. The 570-record protected manifest passes with correction callers excluded.
11. Phase 3G implementation, tests, ADR, current documentation, Composer,
    lockfile, tooling, policy, and prior caller records remain byte-identical.
12. Links and all file-safety proofs pass.
13. Exact final inventory, Git identity, semantic index, refs, lockfile,
    environment, zero staging, and unchanged history pass.
14. No prohibited semantic or quality command was rerun.
15. Earlier zero-byte reports remain exact.
16. Only Phase 3G.2 report is populated, and only after all non-report gates.
17. The report contains every exact heading and truthfully records both prior
    failures, the .NET correction, prior Phase 3G evidence, and actual Phase
    3G.2 commands.
18. Final report integrity and post-population preservation proofs pass.
19. Report bytes and SHA-256 are returned outside the report body.
20. Nothing was staged or committed.
21. Phase 3H was not started.

On failure, stop immediately, preserve the exact reached state, report expected
and observed values, and request the smallest authenticated correction.

---

## 9. Final instruction

Use only `[System.IO.File]::Move($source, $destination)` for the three exact
authenticated pairs. Restore their accepted paths without changing bytes.
Preserve the implementation, all prior caller records, both earlier zero-byte
reports, index, refs, and history.

After the authorized read-only proof passes, populate only the Phase 3G.2
correction report, return its independent identity, and await architect review.
Do not begin Phase 3H.
