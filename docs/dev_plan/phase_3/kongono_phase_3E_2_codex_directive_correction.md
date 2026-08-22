# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3E.2

## Numbered Phase Archive Preservation and Reorganization Closure

**Repository:** `C:\Users\antho\Dev\kongono`
**Role:** executor, not architect
**Required branch:** `main`
**Date:** 2026-08-21
**Phase:** 3E.2 correction only

---

## 1. Mission

Preserve the caller-selected numbered documentation structure while restoring
every historical directive and task-completion report to its authenticated
historical bytes.

The accepted structure from this correction forward is:

```text
docs/dev_plan/phase_<major>/
docs/task_reports/phase_<major>/
```

For the current archive, `<major>` is exactly `0`, `1`, `2`, or `3`. Future
Phase 4 records belong under `phase_4`, Phase 5 records under `phase_5`, and so
on.

The caller-authorized reorganization already moved 202 records into these
folders. It also mechanically rewrote path text inside many historical files.
That content rewrite is not accepted: historical directives and reports record
what existed and what was executed at their original time. Their embedded old
paths, commands, hashes, byte counts, and status evidence are historical facts,
not live navigation that may be rewritten after the fact.

Phase 3E.2 must therefore:

1. keep all 202 records at their new numbered-folder filesystem paths;
2. keep all 185 former tracked root-level archive paths absent;
3. restore the 185 tracked historical records from their exact `HEAD` blobs;
4. restore the seventeen previously untracked accepted records to their exact
   authenticated bytes;
5. preserve the one intentional current memory-handoff link update;
6. prove the numbered archive, protected repository state, quality gates, and
   file safety;
7. populate only the Phase 3E.2 correction report after every non-report gate
   passes; and
8. stop before Phase 3F.

This is archive preservation, not framework implementation. No production,
test, package, dependency, tooling, ADR, architecture, or runtime behavior is
authorized to change.

---

## 2. Mandatory stop rules

Stop immediately, preserve the exact reached state, report expected and
observed values, and request the smallest authenticated correction if any
identity, count, mapping, blob, manifest, quality result, or file-safety
predicate differs.

Do not:

- self-authenticate this directive or adopt a locally observed mismatch;
- return any archive file to its former root-level location;
- retain rewritten current-location text inside a historical record;
- alter a historical byte beyond restoration to the exact accepted content;
- edit the intentional current memory-handoff link;
- create an archive index, redirect, symlink, junction, reparse point, duplicate
  compatibility copy, or placeholder;
- modify production, tests, packages, manifests, lockfile, tooling, ADRs,
  architecture, current framework documentation, or application behavior;
- stage, commit, amend, reset, clean, switch branches, create/delete refs,
  rewrite history, or change the semantic index; or
- begin Phase 3F or later work.

One unchanged retry of literal `composer quality` is permitted only when its
sole failure is managed network/cache denial. Record both attempts. No
non-network retry is authorized.

On any stop:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS, CLEAN UP, OR EXPAND SCOPE
```

---

## 3. Independent authentication and active caller records

The architect must provide this directive's byte count and SHA-256 outside the
file body:

```text
docs/dev_plan/phase_3/kongono_phase_3E_2_codex_directive_correction.md
```

Before reading beyond authentication or running quality, restoration, staging,
or commit operations, compare the local file to that independently supplied
identity. The local file is not self-authenticating.

The caller must also create:

```text
docs/task_reports/phase_3/Phase_3E_2_correction.md
```

It must initially be an ordinary, untracked, unstaged, `HEAD`-absent, zero-byte
file with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero bytes until every non-report gate passes.

The accepted Phase 3E caller records are now located under `phase_3`, but their
content must return to the original authenticated identities:

| New filesystem path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3E_codex_directive.md` | 44,585 | `E2F7A114C5DD99F7FBA4E1E7CC4B73C01FA927F477C65EADF0755A93E81FF044` |
| `docs/task_reports/phase_3/Phase_3E.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/phase_3/kongono_phase_3E_1_codex_directive_correction.md` | 20,859 | `FD94AFE1804082A1CFD3CD65534253BA382270D00140F78F9AF3734E0076CC0B` |
| `docs/task_reports/phase_3/Phase_3E_1_correction.md` | 14,060 | `750189DD8F7AC3CFC0939FFE7CF9F6FB1FF5FAE525E4D2AB788B0A85F587C699` |

Filesystem relocation does not authorize changing these content identities.

---

## 4. Required authorities

After authentication and before restoration, read completely:

```text
AGENTS.md
docs/documentation-standard.md
docs/memory handoff/20260812-131141-kongono-phase-0a-complete.md
```

Also inspect, without trusting rewritten content as authority:

```text
docs/dev_plan/phase_0/
docs/dev_plan/phase_1/
docs/dev_plan/phase_2/
docs/dev_plan/phase_3/
docs/task_reports/phase_0/
docs/task_reports/phase_1/
docs/task_reports/phase_2/
docs/task_reports/phase_3/
```

The rewritten Phase 3E files are inspection targets, not pre-restoration
authorities. Historical content authority comes from exact accepted blobs and
hashes in Sections 7 and 8, not from rewritten path text currently present in
the files. Read the restored Phase 3E directive, correction, and completion
report completely only after their final identities pass.

---

## 5. Locked correction entry state

These values apply after the caller copies this directive into `phase_3` and
creates the zero-byte correction report there.

### 5.1 Git and inventory identity

```text
branch:                       main
local branches:               1
HEAD:                         647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                         3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                       d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:                 10
remotes:                      0
tags:                         0
modified tracked paths:      28
deleted tracked paths:       185
untracked paths:             241
total status paths:          454
staged paths:                  0
tracked index paths:         536
physical Git-visible files:  592
package directories:          14
package manifests:            14
production PHP files:        121
test PHP files:              137
style-covered PHP files:     258
```

The two active Phase 3E.2 caller records account for the difference from the
observed pre-copy `28 modified / 185 deleted / 239 untracked / 452 total`
state and 590 physical files.

Exactly one local branch ref, `refs/heads/main`, is permitted. Existing
application-managed `refs/codex/**` refs are exempt from the branch count but
must not be created, deleted, or changed. No unexpected ref is permitted.

The index must remain semantically equal to `HEAD`. The 185 deletions are
worktree deletions only; none is staged.

### 5.2 Exact numbered-folder inventory

Before adding the two active Phase 3E.2 caller records, the accepted moved
archive contains exactly:

```text
docs/dev_plan/phase_0:      50 files
docs/dev_plan/phase_1:      14 files
docs/dev_plan/phase_2:      29 files
docs/dev_plan/phase_3:       8 files
docs/task_reports/phase_0:  50 files
docs/task_reports/phase_1:  14 files
docs/task_reports/phase_2:  29 files
docs/task_reports/phase_3:   8 files
historical records total:  202 files
```

After adding the active directive and report, only the two `phase_3` counts
become nine. The historical-record manifests always exclude the two active
Phase 3E.2 caller records and continue to contain exactly 202 records.

No file may remain directly under `docs/dev_plan`. No `docs/task reports`
historical file may remain. The only entries directly below `docs/dev_plan`
and `docs/task_reports` are the four exact phase directories.

### 5.3 Current rewritten-archive manifest

Before restoration, build a manifest from the exact 202 historical records,
excluding the two active Phase 3E.2 caller records. Normalize paths to `/`,
sort with:

```powershell
$paths.Sort([System.StringComparer]::Ordinal)
```

Serialize each record as:

```text
path<TAB>byte-count<TAB>uppercase-SHA-256<LF>
```

Encode as UTF-8 without BOM. The exact starting result is:

```text
records:               202
manifest bytes:      25327
manifest SHA-256:    5ED61024AA9BD920341D29622470E071DBB6B13381A7AE67EDAB883300CDA627
aggregate file bytes: 4134249
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3E_1_correction.md
```

This manifest authenticates the exact flawed starting archive. If it differs,
stop; do not attempt restoration.

### 5.4 Protected physical-file manifest

Build a manifest from every existing physical file returned by:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Exclude:

- all paths in the eight numbered historical folders;
- the two active Phase 3E.2 caller records; and
- `docs/memory handoff/20260812-131141-kongono-phase-0a-complete.md`.

Ignore cached paths that are absent from the worktree. Use the same ordinal
sorting and record format as Section 5.3. The exact result is:

```text
records:               387
manifest bytes:      48811
manifest SHA-256:    061E84AD23121BDED626F0BCF5960A8C04380DD469E8F465EDF401D75C6FB720
aggregate file bytes: 2326526
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

It must remain exact before restoration, after restoration, and after report
population.

### 5.5 Intentional current memory-handoff update

This one current link update is accepted and frozen:

```text
path:    docs/memory handoff/20260812-131141-kongono-phase-0a-complete.md
bytes:   2023
SHA-256: 69174962A827605F8F2C241107A4E17EEDE5CDBE9568B48151699F8128A8FEC2
```

It points to:

```text
docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
```

Do not alter any other byte in that file.

---

## 6. Exact authorized file boundary

Phase 3E.2 may overwrite content only at:

1. the exact 202 historical record paths already present under the eight
   numbered folders; and
2. after every non-report gate passes,
   `docs/task_reports/phase_3/Phase_3E_2_correction.md`.

No path may be created, deleted, moved, renamed, duplicated, formatted, staged,
or committed by the executor. The caller already created the active zero-byte
report and copied the active directive.

The 185 former tracked root-level paths must remain absent. All other physical
files, including the memory handoff, are protected.

---

## 7. Exact tracked-record restoration

Exactly 185 of the 202 historical records have a corresponding tracked blob in
`HEAD` at their former path.

Construct a mapping for every file under:

```text
docs/dev_plan/phase_0
docs/dev_plan/phase_1
docs/dev_plan/phase_2
docs/task_reports/phase_0
docs/task_reports/phase_1
docs/task_reports/phase_2
```

The mapping is:

```text
docs/dev_plan/phase_N/<name>     <- HEAD:docs/dev_plan/<name>
docs/task_reports/phase_N/<name> <- HEAD:docs/task reports/<name>
```

where `N` is exactly `0`, `1`, or `2` and the filename is unchanged.

One moved Phase 2 record has no corresponding `HEAD` blob and is excluded from
this mapping:

```text
docs/task_reports/phase_2/Phase_2H_final_closure.md
```

For each of the other 185 records:

1. require the former path to exist in the `HEAD` tree;
2. require the new path to exist as an ordinary file;
3. read the exact `HEAD` blob without text decoding or newline conversion;
4. overwrite only the new path with those exact blob bytes;
5. leave the former worktree path absent; and
6. prove `git hash-object <new-path>` equals `git rev-parse HEAD:<old-path>`.

Use binary-safe Git blob output and a byte-array write. Do not use
`Get-Content`/`Set-Content`, `Out-File`, shell text redirection, line joining,
encoding conversion, newline normalization, or an editor for these 185 files.

Before restoration, serialize the mapping exactly as:

```text
old-path<TAB>new-path<TAB>HEAD-blob-OID<LF>
```

Sort by the old path with `StringComparer.Ordinal`. The exact mapping identity
is:

```text
records:            185
mapping bytes:    26457
mapping SHA-256:  339D61CAF36829D7FBDE0190C1A00C0A0F15C2070818B70EE5F8602D216A10D0
first old path:    docs/dev_plan/kongono_phase_0A_codex_directive.md
last old path:     docs/task reports/Phase_2H_7_correction.md
```

If the mapping differs, stop before writing.

---

## 8. Exact accepted identities for the seventeen untracked records

The seventeen records without a tracked `HEAD` source must finish with these
exact identities at their new filesystem paths:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3A_codex_directive.md` | 36,848 | `FD7CB6F72B8C9DF2FD41174C137EDA876A139118B8299870AD9B65D58A8682EA` |
| `docs/dev_plan/phase_3/kongono_phase_3B_codex_directive.md` | 37,311 | `6D7F597C2EC64CE78DF02E55F893ED084072B914D84E6256F94FC16A57793D43` |
| `docs/dev_plan/phase_3/kongono_phase_3B_1_codex_directive_correction.md` | 25,177 | `F4B6AE5DCAE502929D1FA765C47A7CCC692186A184F3743F60A695C58C60E829` |
| `docs/dev_plan/phase_3/kongono_phase_3C_codex_directive.md` | 59,785 | `9916516E28FD20A1BB06A6BCE34B7ED050650297CCF862B9D5AFD127A5C73E38` |
| `docs/dev_plan/phase_3/kongono_phase_3D_codex_directive.md` | 39,679 | `33EA26695BAB62B1B1C6B9513FBAADD0E9333BBC1238A2F0FA335FE72EFC5647` |
| `docs/dev_plan/phase_3/kongono_phase_3D_1_codex_directive_correction.md` | 26,465 | `DE91986A9AAF5CF4A9A7B49370C9D799D9CBF62B71DB864DD6B4D0520F2CB53E` |
| `docs/dev_plan/phase_3/kongono_phase_3E_codex_directive.md` | 44,585 | `E2F7A114C5DD99F7FBA4E1E7CC4B73C01FA927F477C65EADF0755A93E81FF044` |
| `docs/dev_plan/phase_3/kongono_phase_3E_1_codex_directive_correction.md` | 20,859 | `FD94AFE1804082A1CFD3CD65534253BA382270D00140F78F9AF3734E0076CC0B` |
| `docs/task_reports/phase_2/Phase_2H_final_closure.md` | 16,240 | `F187072B7B69327BB2129152AFF1114B36F6D2611A4E986CB06F95955341C245` |
| `docs/task_reports/phase_3/Phase_3A.md` | 19,513 | `28B462AE7066F8E4C783901FC13879A47925B62B707979E53E84B4AA0F4FDB88` |
| `docs/task_reports/phase_3/Phase_3B.md` | 19,233 | `D7AB7DC0885D7E32A81B5CC1E51598C895443C3E8AB38B745F448FFD2EF7ACB8` |
| `docs/task_reports/phase_3/Phase_3B_1_correction.md` | 19,285 | `24D9BDC3B4640A254C21FFF8AFC5C679CD3AB9DB9D739A78BEEA007526974CEF` |
| `docs/task_reports/phase_3/Phase_3C.md` | 20,752 | `6972877D3E6CC037EAD40FE23438DD33EF32826F2185B5F40F367D5ED855B169` |
| `docs/task_reports/phase_3/Phase_3D.md` | 16,434 | `D786136B0BCE72994FFDEC3D1AD02DB9BB64B704ED4A220BFC648F22D2D90285` |
| `docs/task_reports/phase_3/Phase_3D_1_correction.md` | 16,984 | `33B32B8C95676156B5A818C652FE41EB4256DEB4992CE44AFDAF76EBB6A3AE3F` |
| `docs/task_reports/phase_3/Phase_3E.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/task_reports/phase_3/Phase_3E_1_correction.md` | 14,060 | `750189DD8F7AC3CFC0939FFE7CF9F6FB1FF5FAE525E4D2AB788B0A85F587C699` |

For the sixteen nonempty files, reverse only the mechanical relocation text
substitutions already present. The original old path text is intentionally
restored inside the content while the file itself stays at the new path.

The permitted reversal vocabulary is limited to:

```text
docs/dev_plan/phase_N/      -> docs/dev_plan/
docs/task_reports/phase_N/  -> docs/task reports/
docs\dev_plan\phase_N\      -> docs\dev_plan\
docs\task_reports\phase_N\  -> docs\task reports\
docs\\dev_plan\\phase_N\\    -> docs\\dev_plan\\
docs\\task_reports\\phase_N\\ -> docs\\task reports\\
docs/task_reports/          -> docs/task reports/
docs\task_reports\          -> docs\task reports\
docs\\task_reports\\        -> docs\\task reports\\
docs/task_reports:          -> docs/task reports:
```

where `N` is exactly `0`, `1`, `2`, or `3`. The backslash forms apply to the
literal escaped path text in Markdown, not filesystem traversal.

Do not use broad `phase_` removal, regex inference, case folding, whitespace
normalization, or any other replacement. Require each final identity in the
table. If any identity does not match after the closed reversal, stop with the
result preserved; do not invent another transformation.

The zero-byte Phase 3E report remains untouched.

---

## 9. Exact final archive manifest

After Sections 7 and 8, reconstruct the exact 202-record manifest using the
same paths, sorting, encoding, and record format as Section 5.3.

The required result is:

```text
records:               202
manifest bytes:      25327
manifest SHA-256:    81413DAB9A298C8EBA3EC35DF3481144926F01335B47894ECB99766E6D424A6D
aggregate file bytes: 4115487
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3E_1_correction.md
```

This manifest must remain exact after quality and after report population.

---

## 10. Historical-path and current-path rules

After restoration:

- filesystem discovery uses the new numbered folders;
- Phase 3F authority paths must use `docs/dev_plan/phase_3/...` and
  `docs/task_reports/phase_3/...`;
- future directives/reports use the folder matching their major phase;
- current nonhistorical documentation may link to numbered paths;
- historical directives and reports retain the old path text they originally
  recorded;
- an old path embedded in historical content is not a live link and must not be
  rewritten merely because the archive moved;
- byte counts and SHA-256 values authenticate content independently from its
  relocated filesystem path; and
- no compatibility copy remains at an old archive path.

Do not run a link-rewriter over historical files. For link validation, treat
the 202 archive records as immutable evidence and validate links only in current
nonhistorical documentation plus the intentional memory-handoff link.

---

## 11. Validation workflow

### Gate A - authentication and baseline

1. Authenticate this directive independently.
2. Read every Section 4 authority completely.
3. Verify the active zero-byte report. Verify the four relocated Phase 3E caller
   paths exist and are accounted for by the starting archive manifest; do not
   require their final accepted identities before restoration.
4. Verify exact `28 modified / 185 deleted / 241 untracked / 454 total / 0
   staged`, 536 index paths, and 592 physical files.
5. Verify branch, HEAD, tree, parent, commit count, refs, remotes, tags, semantic
   index, modes, streams, and ordinary files.
6. Verify exact folder counts, the 202-record starting archive manifest, the
   185-record mapping manifest, the 387-record protected manifest, and the
   memory-handoff identity.
7. Verify the exact seventeen starting paths exist and the 185 former paths are
   absent from the worktree.

If any item differs, stop before restoration or broad quality.

### Gate B - exact restoration

1. Restore the exact 185 tracked records through binary-safe `HEAD` blob reads.
2. Prove each new path's Git blob OID equals its former `HEAD` blob OID.
3. Apply only the closed Section 8 reversal to the sixteen nonempty untracked
   records and preserve the zero-byte report.
4. Prove all seventeen final byte counts and SHA-256 values.
5. Prove every former path remains absent and every new path remains present.
6. Reconstruct the final 202-record manifest exactly.
7. Reconstruct the protected manifest and memory-handoff identity exactly.

No report population is authorized yet.

### Gate C - file safety and historical integrity

Prove:

- all 202 historical records are ordinary files with default streams;
- strict UTF-8 without BOM for every nonempty record;
- LF-only content where the accepted historical bytes use LF;
- exact zero-byte records remain zero;
- no NUL, reparse point, executable mode, nested repository, duplicate old
  path, or unauthorized file exists;
- no historical content contains any of the new relocation prefixes from
  Section 8;
- the eight new folders contain the exact 202 historical paths plus only the
  two active Phase 3E.2 caller records;
- current framework documentation outside the archive was not rewritten;
- the intentional memory-handoff link resolves to the relocated Phase 0A
  directive; and
- `git diff --check` passes for nonhistorical tracked changes and reports no
  new archive-content defect.

Historical embedded old paths are expected and are not broken-current-link
failures.

### Gate D - canonical quality

Run literal:

```powershell
composer quality
```

If and only if it fails solely because the managed environment cannot reach
the locked advisory service or normal cache, repeat the identical command once
with approved network/cache access.

Require zero advisories, platform pass, zero style findings, zero PHPStan
errors, and complete PHPUnit success. Expected current test evidence is 534
tests and 11,337 assertions; record actual results rather than forcing those
counts if a legitimate unchanged tool reports differently.

### Gate E - final preservation and report

Before report population, verify again:

```text
modified tracked paths:      28
deleted tracked paths:       185
untracked paths:             241
total status paths:          454
staged paths:                  0
tracked index paths:         536
physical Git-visible files:  592
package directories:          14
package manifests:            14
production PHP files:        121
test PHP files:              137
style-covered PHP files:     258
```

Verify the exact directory inventory, 202-record final archive manifest,
185-record blob mapping, seventeen accepted identities, 387-record protected
manifest, memory-handoff identity, branch, refs, history, semantic index,
modes, streams, and no staging.

Only then populate:

```text
docs/task_reports/phase_3/Phase_3E_2_correction.md
```

After population, rerun report integrity, all manifests and identities, exact
inventories, Git identity/status, no-staging, and no-history-change proofs. The
populated report changes no path count.

No staging or commit is authorized.

---

## 12. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. NUMBERED STRUCTURE ACCEPTED
## 4. HISTORICAL CONTENT RESTORED
## 5. BASELINE VERIFIED
## 6. CALLER RECORDS
## 7. FILES CREATED
## 8. FILES MODIFIED
## 9. TRACKED BLOB RESTORATION
## 10. UNTRACKED RECORD RESTORATION
## 11. ARCHIVE MANIFESTS
## 12. PROTECTED STATE
## 13. ARCHITECTURAL DECISIONS
## 14. DEVIATIONS FROM DIRECTIVE
## 15. DEPENDENCIES ADDED/REMOVED
## 16. ENVIRONMENT
## 17. COMMANDS RUN
## 18. VALIDATION RESULTS
## 19. DOCUMENTATION REVIEW
## 20. SECURITY AND FILE-SAFETY REVIEW
## 21. WARNINGS / KNOWN ISSUES
## 22. QUESTIONS / BLOCKERS
## 23. FINAL GIT STATUS
## 24. ACCEPTANCE CRITERIA
## 25. NEXT STEP
```

State clearly that filesystem paths moved while historical content retained its
original path evidence. Do not claim the old embedded paths are current paths.

The final next step must be exactly:

```text
Await architect review before Phase 3F.
```

After completion, return the report byte count and SHA-256 outside the report
body. Do not put self-referential identity values inside the report.

---

## 13. Strict acceptance criteria

All must pass:

1. Independent directive authentication matched.
2. Every required authority was read completely.
3. Active caller records began with the exact directive and zero-byte report.
4. Repository identity and exact `28/185/241/454/0` entry state matched.
5. The eight historical folders and exact 202-record inventory matched.
6. The starting 202-record rewritten-archive manifest matched.
7. The 185-record old/new/blob mapping matched.
8. Every 185 new path finished byte-identical to its former `HEAD` blob.
9. Every former tracked archive path remained absent from the worktree.
10. All seventeen previously untracked records finished at their exact accepted
    identities.
11. No new relocation prefix remains inside any of the 202 historical records.
12. The final 202-record archive manifest matched exactly.
13. The 387-record protected manifest and current memory-handoff identity
    remained exact.
14. The numbered filesystem structure is accepted while historical content
    truthfully retains original path evidence.
15. No duplicate, redirect, symlink, junction, reparse point, archive index, or
    compatibility copy was introduced.
16. No production, test, package, manifest, lockfile, tooling, ADR,
    architecture, framework documentation, dependency, or runtime behavior
    changed.
17. UTF-8, LF, zero-byte, NUL, ordinary-file, stream, mode, and scoped
    whitespace proofs passed.
18. Canonical quality passed with zero advisories, platform pass, zero style
    findings, zero PHPStan errors, and complete PHPUnit success.
19. Final state remained exactly `28/185/241/454/0`, 536 index paths, 592
    physical files, fourteen packages, 121 production PHP files, 137 test PHP
    files, and 258 style-covered files.
20. Branch, refs, HEAD, tree, parent, history, and semantic index remained
    unchanged.
21. Nothing was staged or committed.
22. Only the Phase 3E.2 correction report was populated after all non-report
    gates passed.
23. Report structure, truthful evidence, exact next step, and external report
    identity passed.
24. No Phase 3F or later work began.

Any failure means the archive reorganization is not accepted. Preserve the
exact reached state, leave the report zero bytes unless all non-report gates
passed, and request the smallest authenticated correction.

---

## 14. Prohibited scope

Phase 3E.2 must not implement or modify:

- concrete PHP CGI delivery;
- Web Runtime composition or entry;
- request capture, response emission, resource cleanup, or trust decisions;
- operator profiles or diagnostics;
- routing, middleware, Kernel, Web Application, or Application Graph behavior;
- configuration, DI, database, ORM, templates, frontend, telemetry, deployment,
  hardening, or external acceptance;
- any PHP source or test;
- any Composer/package/tooling file;
- any ADR or current architecture decision; or
- Phase 3F through 3H behavior.

No Symfony, Laravel, or other framework component is introduced.

---

## 15. Final instruction

Execute Phase 3E.2 only.

Keep the numbered folders. Restore historical content, not historical
locations. Authenticate every blob and accepted record, preserve all framework
bytes, run canonical quality, and populate only the active correction report
after every non-report gate passes.

Do not stage, commit, or begin Phase 3F.

Stop after returning the completed report's independently computed byte count
and SHA-256.
