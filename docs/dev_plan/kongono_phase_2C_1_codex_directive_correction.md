# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.1

## Codex-Managed Turn-Diff Ref Exemption

**Directive type:** narrow execution-contract correction and Phase 2C resume

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Corrects:** Phase 2C Gate A and final ref/count/report predicates only

**Does not replace:** the Phase 2C implementation contract

---

## 1. Correction mission

Phase 2C stopped correctly before mutation because the original directive said
that `refs/heads/main` must be the only local Git ref. The Codex desktop
environment also maintains application bookkeeping below:

```text
refs/codex/turn-diffs/**
```

Those refs are not branches, remotes, tags, release markers, or repository
architecture. Their presence does not change `HEAD`, the `main` branch, the
accepted commit graph reachable from `main`, the index, or worktree content.

This correction:

1. exempts only `refs/codex/turn-diffs/**` from the original ref-count and
   unchanged-ref-set predicates;
2. keeps `refs/heads/main` as the only permitted branch;
3. continues to require zero remote refs, zero tags, and zero refs in every
   other namespace;
4. forbids the executor from creating, updating, deleting, renaming, packing,
   pruning, or otherwise manipulating any exempt ref;
5. treats the exempt ref count and tips as application-managed observations
   that may change independently while execution proceeds;
6. adjusts only caller-record/status/report counts for the two Phase 2C.1
   records; and
7. resumes the complete original Phase 2C implementation from Gate A.

No production, test, diagnostic, dependency, lockfile, documentation, tooling,
API, or prohibited-scope requirement from the authenticated Phase 2C directive
is changed.

---

## 2. Authority, authentication, and stop rule

### 2.1 Required authority reading

Before broad validation or mutation, read completely:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_2C_codex_directive.md`;
3. this Phase 2C.1 correction directive; and
4. every authority required by original Phase 2C Section 2.1 that was not read
   completely during the blocked attempt.

Previously completed authority reading may be reused only if the local
authority file still matches its locked identity. The original Phase 2C
directive remains controlling except where Section 5 below expressly
supersedes it.

### 2.2 Independent correction identity

The architect supplies this correction directive's byte count and uppercase
SHA-256 outside this file. Compare those independently supplied values with the
local copy before `composer quality`, implementation, dependency resolution, or
other mutation.

Do not use the local file to authenticate itself. If the independently supplied
identity is absent or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.3 Caller-managed correction report

The caller creates exactly:

```text
docs/task reports/Phase_2C_1_correction.md
```

It must initially be an ordinary, untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Do not populate it until the complete resumed
Phase 2C implementation passes every permitted non-report gate.

The original report remains immutable at:

```text
docs/task reports/Phase_2C.md
bytes: 0
SHA-256: E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not populate, truncate, replace, or otherwise modify the original report.

### 2.4 Stop rather than assume

The original mandatory stop rule remains in force. Stop on the first mismatch,
unexpected non-exempt ref, unauthorized mutation, failed validation, or
conflict. Preserve all content, the index, and user-controlled refs exactly.

Never delete or rewrite a `refs/codex/turn-diffs/**` ref to make a count pass.

---

## 3. Locked resume baseline before Phase 2C.1 caller setup

The correctly blocked Phase 2C state is:

| Item | Required value |
|---|---|
| Branch | `main` |
| `HEAD` | `d7846f137a1d53f5306f48d19196b916fde44ac2` |
| `HEAD` tree | `bfa1908c619069b699bc9b08668db17ea2555eb3` |
| `HEAD` parent | `8fe793b124384744ac43887037360154c149241a` |
| Commit count reachable from `main` | `9` |
| Branch refs | exactly `refs/heads/main` |
| Remote refs | `0` |
| Tag refs | `0` |
| Unexpected non-Codex refs | `0` |
| Observed Codex turn-diff refs at architect audit | `37` (informational only) |
| Modified tracked paths | `11` |
| Untracked paths with `-uall` | `18` |
| Total status paths with `-uall` | `29` |
| Staged paths | `0` |
| Tracked diff | `11 files, 312 insertions, 4 deletions` |
| Package directories | `8` |
| Package manifests | `8` |
| Production PHP files | `53` |
| Test/fixture PHP files | `85` |

All 29 original Phase 2C Gate A status paths remain exact. In particular:

```text
docs/dev_plan/kongono_phase_2C_codex_directive.md
bytes: 64069
SHA-256: 49A69D9B7BC46BAA980E0FBB1235805BE5826B65A3EABC87A97AAB3FA05553E3

docs/task reports/Phase_2C.md
bytes: 0
SHA-256: E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The original lockfile and every earlier locked caller/architecture identity
remain exactly as specified by Phase 2C Section 3.

No Phase 2C implementation file exists, no dependency/lock change occurred, no
quality check ran, and no path is staged.

After the caller copies this correction and creates its report placeholder, the
exact pre-implementation status becomes:

```text
11 modified tracked
20 untracked with -uall
31 total status paths
0 staged
```

The two additional paths are exactly:

```text
docs/dev_plan/kongono_phase_2C_1_codex_directive_correction.md
docs/task reports/Phase_2C_1_correction.md
```

---

## 4. Exact ref classification and validation algorithm

### 4.1 User-controlled repository refs

For this correction, the user-controlled repository ref invariant covers only:

```text
refs/heads/**
refs/remotes/**
refs/tags/**
```

The required state is exactly:

```text
refs/heads/main: present and the only branch ref
refs/remotes/**: absent
refs/tags/**: absent
```

`main` must continue to resolve to the locked `HEAD`. Its first-parent/history,
tree, commit count, worktree diff, and index predicates remain authoritative.

### 4.2 Exempt Codex-managed namespace

Every ref whose name begins exactly with:

```text
refs/codex/turn-diffs/
```

is application-managed metadata exempt only from:

- the statement that `refs/heads/main` is the only local ref;
- exact total-ref count;
- exact ref-name-set equality across the execution window;
- exact exempt-ref tip/object equality across the execution window; and
- commit-count calculations that enumerate all refs rather than commits
  reachable from `main`.

The exemption does not grant the executor authority to write those refs or
their objects. It does not make them repository history, branch evidence,
release evidence, accepted content, staging evidence, or implementation output.

The observed count `37` is nonbinding. Codex may create, retire, or update these
refs outside executor control. Record their start/end counts in the completion
report without treating a difference as failure.

### 4.3 Unexpected refs remain blocking

Enumerate all refs read-only. After removing exactly `refs/heads/main` and every
name under `refs/codex/turn-diffs/**`, the result must be empty.

Any ref under another namespace, including another `refs/heads/**` branch,
remote, tag, stash, replace, notes, bisect, or differently named Codex ref, is a
blocker unless a later authenticated correction expressly authorizes it.

Do not infer an exemption from a similar prefix.

### 4.4 Executor command prohibition

The executor must not run any ref-writing operation, including:

```text
git branch creation/deletion/rename
git tag
git update-ref
git symbolic-ref mutation
git replace
git notes mutation
git stash
git fetch/pull/push
git remote mutation
git pack-refs
git reflog expire/delete
manual writes below .git/refs or .git/packed-refs
```

Read-only `for-each-ref`, `show-ref`, `rev-parse`, `rev-list`, `log`, and object
inspection remain permitted.

---

## 5. Exact supersession of the original Phase 2C directive

This section is exhaustive. No other original requirement changes.

### 5.1 Ref wording superseded

Every original Phase 2C phrase equivalent to:

```text
the only local ref must be refs/heads/main
ref set remains unchanged
refs remain unchanged
```

is replaced by:

> The only branch is `refs/heads/main`; zero remotes, tags, or unexpected refs
> exist. `refs/codex/turn-diffs/**` is exempt application metadata whose count
> and tips may change independently, and the executor must not mutate it.

This applies to original Sections 3.1, 3.5, 16 Gate A, 16 Gate F, 16 Gate G,
17 criterion 25, 19, and every equivalent report/final-outcome instruction.

### 5.2 Gate A count supersession

Original Phase 2C Gate A's `11/18/29/0` post-caller status is superseded by:

```text
11 modified tracked
20 untracked
31 total status paths
0 staged
```

Both correction caller records must authenticate before Gate B.

### 5.3 Final Git count supersession

Original Phase 2C Sections 5.3, 16 Gate F, 16 Gate G, and acceptance criterion
24 are superseded only as follows:

```text
modified tracked paths: 18
untracked paths with -uall: 53
total status paths with -uall: 71
staged paths: 0
```

The 71 final paths are exactly:

1. the 31 corrected Gate A paths;
2. the original 33 implementation records; and
3. the original seven newly modified tracked paths.

All original package/declaration/test/diagnostic counts remain unchanged:

```text
package directories: 9
package manifests: 9
Composer manifests including root: 10
production PHP files: 70
test/fixture PHP files: 98
HTTP diagnostic IDs: 28
repository diagnostic IDs: 96
```

### 5.4 Report target supersession

Every original instruction to populate `docs/task reports/Phase_2C.md` is
superseded by an instruction to populate only:

```text
docs/task reports/Phase_2C_1_correction.md
```

The original report remains zero bytes. The correction report contains the
full implementation evidence required by original Phase 2C Section 18 plus the
ref-exemption evidence in this correction.

### 5.5 No implementation change

The following original requirements remain exact and must not be
reinterpreted:

- 33 implementation records;
- 20 authorized existing modification paths;
- 17 production declarations;
- five PSR-7 roles and five PSR-17 factories;
- 28 HTTP diagnostics and total 96-diagnostic split;
- two PSR interface dependencies and exact lock boundary;
- 13 package test files and all behavioral scenarios;
- ADR 0045 and documentation reconciliation;
- all tooling, quality, security, file-safety, and prohibited-scope rules;
- zero staging/commit/history mutation; and
- Phase 2D deferral.

---

## 6. Resume procedure

### Gate A.1 - authenticate correction and resume baseline

1. authenticate this correction from the independently supplied identity;
2. authenticate the original directive and both report placeholders;
3. verify exact `11/20/31/0` status counts and closed path union;
4. verify locked `HEAD`, tree, parent, `main` history, diff, lockfile, caller
   records, package counts, modes, streams, and ordinary-file predicates;
5. enumerate every ref using `git for-each-ref` or equivalent;
6. require exactly one branch, `refs/heads/main`;
7. require zero remotes, zero tags, and zero unexpected refs;
8. record but do not lock the count of `refs/codex/turn-diffs/**` refs;
9. verify no Phase 2C implementation/ADR path exists; and
10. verify zero staged paths.

If this gate passes, continue with original Phase 2C Gate B. Do not skip the
pre-change `composer quality`, because the blocked attempt did not run it.

### Gates B through F - complete original implementation

Execute original Phase 2C Gates B through F exactly, subject only to this
correction's ref and count predicates. Stop on any other mismatch.

### Gate G.1 - correction report and final integrity

After every non-report gate passes:

1. populate only `Phase_2C_1_correction.md` using Section 8;
2. keep `Phase_2C.md` exactly zero bytes;
3. verify exact final `18/53/71/0` status counts;
4. verify `main`, `HEAD`, tree, parent, and its nine-commit history unchanged;
5. verify zero remote, tag, or unexpected ref;
6. record the final Codex turn-diff ref count as informational;
7. verify the executor performed no ref-writing operation;
8. verify both Phase 2C directives and the original report are unchanged;
9. verify the correction report is ordinary, untracked, unstaged, absent from
   `HEAD`, UTF-8/LF, non-empty, with no trailing whitespace and one final LF;
   and
10. return its byte count and uppercase SHA-256 outside the report body.

Do not edit the report after calculating its final identity.

---

## 7. Corrected acceptance criteria

All original Phase 2C acceptance criteria remain mandatory with these exact
additions/supersessions:

1. correction directive authentication passed independently;
2. original directive and original zero-byte report remained exact;
3. corrected Gate A status is exactly `11/20/31/0`;
4. exactly one branch exists: `refs/heads/main`;
5. zero remote refs, zero tag refs, and zero unexpected refs exist;
6. every exempt ref begins exactly `refs/codex/turn-diffs/`;
7. exempt ref count/tips are reported as nonbinding observations;
8. no executor command or direct write mutated any ref;
9. complete original Phase 2C implementation and validation passed;
10. original production/package/test/diagnostic/dependency counts remain exact;
11. final Git status is exactly `18/53/71/0`;
12. `main`, `HEAD`, tree, parent, and nine-commit history remain unchanged;
13. original `Phase_2C.md` remains zero bytes;
14. only `Phase_2C_1_correction.md` is populated after non-report success;
15. correction report contains full original and correction evidence;
16. report identity is returned outside its body; and
17. next step remains architect review before Phase 2D.

The observed start/end Codex turn-diff counts may differ without failing a
criterion. Any non-exempt ref or executor-caused ref mutation fails acceptance.

---

## 8. Standard correction completion report

Populate `docs/task reports/Phase_2C_1_correction.md` with exactly these
headings, in order:

```markdown
# PHASE 2C.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. CORRECTION AUTHORITY AND AUTHENTICATION

## 4. BASELINE VERIFIED

## 5. REF CLASSIFICATION AND EXEMPTION EVIDENCE

## 6. CALLER RECORDS

## 7. FILES CREATED

## 8. FILES MODIFIED

## 9. HTTP MESSAGE AND FACTORY CONTRACTS

## 10. DIAGNOSTIC AND EXCEPTION BOUNDARY

## 11. DEPENDENCIES AND LOCKFILE

## 12. TEST IMPLEMENTATION AND COVERAGE

## 13. ARCHITECTURAL DECISIONS

## 14. DEVIATIONS FROM DIRECTIVES

## 15. ENVIRONMENT

## 16. COMMANDS RUN

## 17. VALIDATION RESULTS

## 18. DOCUMENTATION AND ADR REVIEW

## 19. SECURITY AND FILE-SAFETY REVIEW

## 20. WARNINGS AND KNOWN ISSUES

## 21. QUESTIONS OR BLOCKERS

## 22. GIT DIFF SUMMARY

## 23. ACCEPTANCE CRITERIA

## 24. NEXT STEP
```

In addition to every evidence item required by original Phase 2C Section 18,
the report must include:

- supplied and observed Phase 2C.1 identity;
- exact original directive/original-report identities;
- start and final counts for `refs/codex/turn-diffs/**`;
- exact one-branch/zero-remote/zero-tag/zero-unexpected-ref proof;
- explicit confirmation that no ref-writing command or direct ref mutation was
  performed;
- corrected `11/20/31/0` baseline and `18/53/71/0` final status;
- confirmation that original `Phase_2C.md` remains zero bytes; and
- PASS/FAIL for every original and corrected acceptance criterion.

For success, Section 24 must end exactly:

```text
Await architect review before Phase 2D.
```

If blocked before complete success, leave both reports in their current state
and return the blocker directly unless later authenticated authority says
otherwise.

---

## 9. Required outcome

Phase 2C.1 changes no architecture. It corrects an environmental predicate so
the exact Phase 2C implementation can proceed without treating Codex desktop
bookkeeping as a user-created branch or destructively deleting application
metadata.

On success, Kongono has the exact original Phase 2C HTTP message/factory
foundation, all original gates pass, user-controlled Git history remains
unchanged, application-managed turn-diff refs remain untouched by the executor,
and the full evidence is recorded only in the Phase 2C.1 correction report.

Phase 2D remains prohibited until architect review and acceptance.
