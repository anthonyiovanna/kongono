# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.4

## Uri starting-digest correction and Phase 2C.3 resume authority

**Status:** Executor-ready correction directive
**Date:** 2026-08-16
**Repository:** `C:\Users\antho\Dev\kongono`
**Authority:** Architect-issued correction to Phase 2C.3

---

## 1. Purpose

Phase 2C.3 stopped correctly before quality checks, probes, or mutation because
the architect transcribed an incomplete SHA-256 for:

```text
packages/http-message/src/Message/Uri.php
```

The byte count was correct, but the directive's digest contained only 61
hexadecimal characters. This Phase 2C.4 correction supplies the authenticated
64-character value, preserves the stopped repository state, and authorizes the
executor to resume the unchanged Phase 2C.3 correction boundary.

This document corrects authority only. It does not authorize an edit to
`Uri.php` until all corrected pre-change gates pass, and it does not widen the
Phase 2C.3 implementation, test, dependency, documentation, Git, or reporting
scope.

---

## 2. Authentication and required reading

### 2.1 Independent identity

The caller must supply this Phase 2C.4 directive's byte count and SHA-256
outside this file. Compare those independently supplied values before quality,
probes, or mutation. The local file may not self-authenticate.

If either value is absent or differs:

```text
STOP
ASK
DO NOT GUESS
DO NOT MUTATE
```

### 2.2 Read completely

Before resuming, read completely:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_2C_3_codex_directive_correction.md`;
3. `docs/task reports/Phase_2C_3_correction.md`; and
4. this Phase 2C.4 correction directive.

The prior execution already read the Phase 2C.3 authorities and authorized
source/test files completely. If that same execution context is continuing,
do not repeat those completed reads. If execution context was lost, read every
authority and authorized target required by Phase 2C.3 before mutation.

Phase 2C.3 remains authoritative except for the two exact corrections in
Sections 4 and 5 of this document.

---

## 3. Caller records and locked resume state

### 3.1 Caller setup

The caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_2C_4_codex_directive_correction.md
```

and create this ordinary zero-byte report placeholder:

```text
docs/task reports/Phase_2C_4_correction.md
```

The new report must begin untracked, unstaged, absent from `HEAD`, zero bytes,
and have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not modify this directive. Keep the Phase 2C.4 report zero-byte until every
permitted non-report gate passes.

### 3.2 Authenticated Phase 2C.3 directive

The prior directive is immutable historical authority:

```text
Path: docs/dev_plan/kongono_phase_2C_3_codex_directive_correction.md
Bytes: 33378
SHA-256: E40B22C646E62460D241A2B174EA110CE3D3A013349224A53E551DFCE5D4CE37
```

Do not edit, normalize, replace, stage, or commit it. Its incomplete `Uri.php`
digest remains visible as the historical reason for this correction and is
superseded only during evaluation by Section 4 below.

### 3.3 Preserved stopped report

The stopped Phase 2C.3 report must remain immutable and empty:

```text
Path: docs/task reports/Phase_2C_3_correction.md
Bytes: 0
SHA-256: E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
State: untracked, unstaged, absent from HEAD, ordinary file
```

Do not populate, truncate, delete, rename, stage, or commit it.

### 3.4 Repository state

Before the caller adds the two Phase 2C.4 records, the stopped state is:

```text
Branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
Tree: bfa1908c619069b699bc9b08668db17ea2555eb3
Parent: 8fe793b124384744ac43887037360154c149241a
Commit count: 9
Tracked modifications: 18
Untracked paths: 57
Total status paths: 75
Staged paths: 0
```

After caller setup, require exactly:

```text
Tracked modifications: 18
Untracked paths: 59
Total status paths: 77
Staged paths: 0
```

Retain the Phase 2C.3 branch/ref rule: exactly one local branch, zero remotes,
zero tags, and only `refs/codex/turn-diffs/**` exempt from the ordinary-ref
count. Do not mutate any ref.

Verify that no implementation, test, dependency, lock, tooling, documentation,
ADR, Git, file-mode, alternate-stream, or protected-state mutation occurred
during the stopped attempt. Any mismatch outside the two new caller records is
a stop condition.

---

## 4. Exact Uri identity correction

Replace the incomplete Phase 2C.3 Section 6.1 table value **for evaluation
only** with this authenticated starting identity:

```text
Path: packages/http-message/src/Message/Uri.php
Bytes: 10165
SHA-256: BD947D0119D3380EA5D763DA0679328566D86F1093081392BF64C6AA2FB5ED57
```

This is the accepted current artifact. The 61-character value in Phase 2C.3:

```text
BD947D0119D3380EA5D763DA0679328566F1093081392BF64C6AA2FB5ED57
```

is rejected as an architect transcription error. It is not an alternate digest,
does not authenticate any file, and must not be padded, repaired, or inferred by
the executor.

The byte count and full corrected digest must both match before resuming. If
either differs, stop without mutation.

Every other starting identity in Phase 2C.3 remains exact and unchanged.

---

## 5. Report-target correction

Because Phase 2C.3 stopped before any non-report work and its zero-byte report is
now historical evidence, supersede every Phase 2C.3 instruction to populate:

```text
docs/task reports/Phase_2C_3_correction.md
```

with authority to populate only:

```text
docs/task reports/Phase_2C_4_correction.md
```

The Phase 2C.4 report uses the exact 21-heading completion-report contract from
Phase 2C.3 Section 11, except its top-level title must be:

```text
# PHASE 2C.4 CORRECTION TASK COMPLETION REPORT
```

Under `CALLER RECORDS`, record both Phase 2C.3 stopped records and both Phase
2C.4 records. Under `BASELINE VERIFIED`, explicitly record that the full
64-character `Uri.php` digest matched and that the incomplete historical digest
was superseded by this correction.

The exact complete next step remains:

```text
Await architect review before Phase 2D.
```

Return the final Phase 2C.4 report byte count and SHA-256 outside the report
body. Do not edit it afterward.

---

## 6. Resume sequence

After Sections 2 through 4 pass:

1. verify every remaining Phase 2C.3 locked target identity and repository gate;
2. run the Phase 2C.3 pre-change canonical quality gate;
3. record all six Phase 2C.3 bounded failing probes;
4. implement only the authorized Phase 2C.3 production corrections;
5. close the complete Phase 2C test matrix in only the authorized existing
   tests;
6. run the exact Phase 2C.3 validation sequence;
7. complete every Phase 2C.3 non-report acceptance criterion, applying the full
   corrected `Uri.php` digest from Section 4;
8. verify the final Git state adds no path beyond the authorized Phase 2C.3
   content changes and the two Phase 2C.4 caller records;
9. populate only the Phase 2C.4 report; and
10. validate and return its final identity.

Do not repeat a completed and still-verifiable authority read merely to inflate
the command record. Do re-run the broad quality gate because the stopped Phase
2C.3 execution did not reach it.

---

## 7. Acceptance criteria

Phase 2C.4 is complete only if:

1. its independently supplied identity matches;
2. all four Phase 2C.3/2C.4 caller records match their locked state;
3. the stopped repository state is preserved exactly before implementation;
4. `Uri.php` matches 10165 bytes and the full authenticated 64-character digest;
5. every other Phase 2C.3 starting gate passes unchanged;
6. every Phase 2C.3 implementation, test, validation, security, file-safety,
   inventory, protected-state, and prohibited-scope criterion passes;
7. the incomplete historical digest is neither used nor silently repaired;
8. no prior directive or report changes;
9. only the Phase 2C.3 authorized production/test files and the populated Phase
   2C.4 report change during execution;
10. the Phase 2C.3 report remains zero-byte, untracked, and unstaged;
11. the Phase 2C.4 report is populated only after every non-report gate passes;
12. zero staged paths and no commit/ref/history mutation exist;
13. the final report is truthful, structurally exact, strict UTF-8, LF-only,
    free of NUL/trailing whitespace, and has exactly one final LF; and
14. its final byte count and SHA-256 are returned outside the report body.

On any mismatch:

```text
STOP
ASK
DO NOT GUESS
DO NOT REPAIR ADJACENT STATE
```

---

## 8. Prohibited scope

All Phase 2C.3 prohibited scope remains in force. Additionally, do not:

- edit Phase 2C.3 to repair its text;
- truncate or reuse its report;
- treat the incomplete digest as a valid hash;
- rerun already completed work before corrected authentication passes;
- add another implementation, test file, dependency, package, API, diagnostic,
  schema, ADR, or documentation change;
- stage, commit, restore, reset, clean, stash, branch, tag, push, fetch, merge,
  rebase, or mutate refs; or
- proceed to Phase 2D.

---

## 9. Required outcome

The required outcome is the unchanged Phase 2C.3 semantic and test correction,
executed against the authenticated current `Uri.php` artifact, with the stopped
Phase 2C.3 records preserved and the final evidence recorded only in the Phase
2C.4 completion report.
