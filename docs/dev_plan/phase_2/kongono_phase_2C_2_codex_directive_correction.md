# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2C.2

## Exact Existing Authority Paths and Phase 2C Resume

**Directive type:** narrow required-authority correction

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Corrects:** Phase 2C Section 2.1 authority paths and cumulative caller counts/report target

**Preserves:** the Phase 2C implementation contract and Phase 2C.1 ref exemption

---

## 1. Correction purpose

Phase 2C.1 stopped correctly during mandatory authority reading because eight
ADR filenames named by the original Phase 2C directive do not exist. Matching
ADR numbers do not authorize substitution, and some existing same-number ADRs
have materially different titles from the nonexistent names.

This correction:

1. replaces the entire original Phase 2C Section 2.1 authority list with one
   exact list of existing paths;
2. voids only the eight nonexistent ADR path names identified in Section 5;
3. does not authorize number-based, title-based, subject-based, or approximate
   substitution;
4. incorporates the already accepted exact authority mapping from Phase 2B.1;
5. preserves the authenticated Phase 2C.1 exemption for only
   `refs/codex/turn-diffs/**`;
6. adjusts cumulative caller-record and final status counts for the two new
   Phase 2C.2 records;
7. moves successful completion evidence to the Phase 2C.2 correction report
   while preserving both earlier Phase 2C reports as zero-byte historical
   evidence; and
8. resumes the complete Phase 2C implementation from corrected Gate A.1.

No implementation class, API, diagnostic, dependency, lockfile, test,
documentation, tooling, file boundary, or prohibited-scope decision changes.

---

## 2. Authentication, caller records, and stop rule

### 2.1 Independent correction identity

The architect supplies this Phase 2C.2 directive's byte count and uppercase
SHA-256 outside this file. Compare those independently supplied values with the
local copy before `composer quality`, package creation, dependency resolution,
or any mutation.

The local file does not authenticate itself. If the independently supplied
identity is missing or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.2 Caller-managed Phase 2C.2 report

The caller creates exactly:

```text
docs/task reports/Phase_2C_2_correction.md
```

It must initially be an ordinary, untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Populate it only after every complete resumed
Phase 2C non-report gate passes.

The two earlier report records remain immutable and zero-byte:

```text
docs/task reports/Phase_2C.md
docs/task reports/Phase_2C_1_correction.md
```

Do not populate, truncate, replace, delete, or otherwise modify either earlier
report.

### 2.3 Stop rather than assume

The original Phase 2C and Phase 2C.1 mandatory stop rules remain in force.
Stop on any absent exact authority path, identity mismatch, unexpected ref,
unauthorized path, failed validation, dependency/lock mismatch, or conflict.

Do not restore or create a nonexistent ADR merely to satisfy an obsolete name.
Do not infer authority from an ADR number.

---

## 3. Locked correctly blocked baseline

Before Phase 2C.2 caller setup, the preserved state is:

| Item | Required value |
|---|---|
| Branch | `main` |
| `HEAD` | `d7846f137a1d53f5306f48d19196b916fde44ac2` |
| `HEAD` tree | `bfa1908c619069b699bc9b08668db17ea2555eb3` |
| `HEAD` parent | `8fe793b124384744ac43887037360154c149241a` |
| Commits reachable from `main` | `9` |
| Branch refs | exactly `refs/heads/main` |
| Remote refs | `0` |
| Tag refs | `0` |
| Unexpected non-Codex refs | `0` |
| Exempt Codex turn-diff refs observed by blocked executor | `38` (informational only) |
| Modified tracked paths | `11` |
| Untracked paths with `-uall` | `20` |
| Total status paths with `-uall` | `31` |
| Staged paths | `0` |
| Tracked diff | `11 files, 312 insertions, 4 deletions` |
| Binary tracked-diff Git object ID | `b19c5a07271c58a78575894aa569f80ff2db0f46` |
| Package directories | `8` |
| Package manifests | `8` |
| Production PHP files | `53` |
| Test/fixture PHP files | `85` |

The locked Phase 2C-family caller records are:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2C_codex_directive.md` | 64069 | `49A69D9B7BC46BAA980E0FBB1235805BE5826B65A3EABC87A97AAB3FA05553E3` |
| `docs/task reports/Phase_2C.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_2C_1_codex_directive_correction.md` | 16735 | `DE9546ECB1F80F59E19B2641EBFDAD4835493427AF670AD6C675E6C305B13293` |
| `docs/task reports/Phase_2C_1_correction.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

The original `composer.lock` and every earlier locked record remain exactly as
specified in Phase 2C Section 3.

No implementation or ADR 0045 file exists. No quality check, Composer update,
file mutation, staging, commit, or ref-writing command occurred.

After the caller copies this directive and creates its report placeholder, the
exact corrected pre-implementation status becomes:

```text
11 modified tracked
22 untracked with -uall
33 total status paths
0 staged
```

The two new paths are exactly:

```text
docs/dev_plan/kongono_phase_2C_2_codex_directive_correction.md
docs/task reports/Phase_2C_2_correction.md
```

---

## 4. Corrected complete authority list

This section supersedes the entire original Phase 2C Section 2.1 authority
list. Read every following existing file completely before `composer quality`
or mutation:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `README.md`;
4. `SECURITY.md`;
5. `CONTRIBUTING.md`;
6. `docs/doctrine.md`;
7. `docs/documentation-standard.md`;
8. `docs/engineering/api-status.md`;
9. `docs/engineering/error-handling.md`;
10. `docs/engineering/package-layout.md`;
11. `docs/architecture/overview.md`;
12. `docs/architecture/runtime-model.md`;
13. `docs/architecture/phase-2-roadmap.md`;
14. `docs/architecture/http-message-model.md`;
15. `docs/adr/README.md`;
16. `docs/adr/0001-kongono-identity.md`;
17. `docs/adr/0003-monorepo-initial-development.md`;
18. `docs/adr/0004-microkernel-capability-architecture.md`;
19. `docs/adr/0006-persistent-runtime-first.md`;
20. `docs/adr/0013-documentation-doctrine.md`;
21. `docs/adr/0014-php-engineering-toolchain.md`;
22. `docs/adr/0019-composer-dependency-security-policy.md`;
23. `docs/adr/0020-kernel-operation-lifecycle-boundary.md`;
24. `docs/adr/0021-machine-checkable-api-status.md`;
25. `docs/adr/0023-explicit-typed-dependency-resolution.md`;
26. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
27. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`;
28. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
29. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`;
30. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`;
31. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
32. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
33. `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`;
34. `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md`;
35. `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md`;
36. `docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md`;
37. `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md`;
38. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`;
39. `docs/adr/0044-http-message-and-interoperability-architecture.md`;
40. `docs/dev_plan/kongono_phase_2A_codex_directive.md`;
41. `docs/task reports/Phase_2A.md`;
42. `docs/dev_plan/kongono_phase_2B_codex_directive.md`;
43. `docs/task reports/Phase_2B.md`;
44. `docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md`;
45. `docs/task reports/Phase_2B_1_correction.md`;
46. `docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md`;
47. `docs/task reports/Phase_2B_2_correction.md`;
48. `docs/dev_plan/kongono_phase_2C_codex_directive.md`;
49. `docs/task reports/Phase_2C.md`;
50. `docs/dev_plan/kongono_phase_2C_1_codex_directive_correction.md`;
51. `docs/task reports/Phase_2C_1_correction.md`; and
52. `docs/dev_plan/kongono_phase_2C_2_codex_directive_correction.md`.

Every path above must exist after proper caller setup and match its locked or
accepted current identity. Zero-byte report files are still read completely by
verifying their empty identity.

If any listed path is absent, stop and name that exact path. Do not choose an
alternative.

---

## 5. Void nonexistent path names

The following eight path names from original Phase 2C Section 2.1 are void and
must not be sought, restored, generated, copied, or treated as authorities:

```text
docs/adr/0001-record-architecture-decisions.md
docs/adr/0003-monorepo-package-boundaries.md
docs/adr/0013-error-handling-and-actionable-diagnostics.md
docs/adr/0014-api-stability-and-versioning.md
docs/adr/0016-package-dependency-direction-and-composition-root.md
docs/adr/0026-actionable-diagnostic-runtime-metadata.md
docs/adr/0028-presentation-neutral-diagnostic-context.md
docs/adr/0031-neutral-api-metadata-package-boundary.md
```

The exact existing paths in Section 4 are authoritative because they were
explicitly selected by the architect and previously reconciled in authenticated
Phase 2B.1—not merely because some share numbers with void names.

This correction makes no general rule that same-number ADRs substitute for one
another.

---

## 6. Exact supersession and cumulative counts

This section is exhaustive. No other provision changes.

### 6.1 Authority supersession

Original Phase 2C Section 2.1 is replaced in full by Section 4 of this
correction. Phase 2C.1 Section 2.1 now means: read Section 4's exact list and no
void path from Section 5.

All semantic standards, installed-interface authority, implementation rules,
and stop rules elsewhere in Phase 2C remain binding.

### 6.2 Ref exemption remains exact

Phase 2C.1 Sections 4 and 5.1 remain binding without change:

- only `refs/heads/main` is a branch;
- zero remotes, tags, or unexpected refs exist;
- only `refs/codex/turn-diffs/**` is exempt application metadata;
- its count/tips may change independently; and
- the executor must not mutate any ref.

The observed count `38` is informational, not a locked final count.

### 6.3 Gate A count supersession

Phase 2C.1's corrected `11/20/31/0` Gate A count is superseded by:

```text
11 modified tracked
22 untracked
33 total status paths
0 staged
```

All three directive identities and all three report placeholders must pass
before Gate B.

### 6.4 Final Git count supersession

Original Phase 2C final counts and Phase 2C.1's `18/53/71/0` counts are
superseded only by:

```text
modified tracked paths: 18
untracked paths with -uall: 55
total status paths with -uall: 73
staged paths: 0
```

The 73 final paths are exactly:

1. the 33 corrected Gate A paths;
2. the original 33 implementation records; and
3. the original seven newly modified tracked paths.

All implementation inventory remains unchanged:

```text
package directories: 9
package manifests: 9
Composer manifests including root: 10
production PHP files: 70
test/fixture PHP files: 98
HTTP diagnostic IDs: 28
repository diagnostic IDs: 96
```

### 6.5 Report target supersession

Every instruction in Phase 2C or Phase 2C.1 to populate an earlier Phase 2C
report is superseded. Populate only:

```text
docs/task reports/Phase_2C_2_correction.md
```

`Phase_2C.md` and `Phase_2C_1_correction.md` remain zero-byte, untracked,
unstaged, absent from `HEAD`, and otherwise unchanged.

### 6.6 Implementation authority unchanged

The complete original Phase 2C implementation remains exact, including:

- 33 implementation records and 20 authorized existing modification paths;
- 17 production declarations;
- five selected PSR-7 values and five PSR-17 factories;
- 28 HTTP diagnostics and 96 repository diagnostics;
- two exact PSR dependencies and closed lock delta;
- 13 package test files and every required scenario;
- ADR 0045 and all documentation reconciliation;
- tooling, autoload, quality, security, file-safety, and link gates;
- all prohibited scope; and
- zero staging, commit, history, or user-controlled ref mutation.

---

## 7. Resume gates

### Gate A.2 - authenticate and complete authority reading

1. authenticate this Phase 2C.2 directive independently;
2. authenticate the original and Phase 2C.1 directives;
3. verify all three report placeholders remain exact zero-byte caller records;
4. verify corrected `11/22/33/0` status and closed path union;
5. verify the locked Git, diff, lockfile, package, file-safety, and earlier
   caller-record baseline;
6. apply the Phase 2C.1 ref classification and record the current exempt count;
7. read every Section 4 file completely;
8. confirm every Section 5 void name remains absent;
9. verify no Phase 2C implementation/ADR 0045 path exists; and
10. verify no quality check, mutation, staging, commit, or ref write occurred
    during either blocked attempt.

Previously read authority content may be reused only after the corresponding
exact path and current accepted identity are verified. If any uncertainty
exists, reread it completely.

### Gates B through F - original Phase 2C implementation

After Gate A.2 passes, execute original Phase 2C Gates B through F exactly,
subject only to Phase 2C.1's ref exemption and this correction's authority/count
supersessions.

The blocked attempts did not run `composer quality`; original Gate B remains
mandatory.

### Gate G.2 - final report and proof

After all non-report gates pass:

1. populate only `Phase_2C_2_correction.md` using Section 9;
2. preserve both earlier Phase 2C reports at zero bytes;
3. verify exact final `18/55/73/0` Git counts;
4. verify all original implementation inventories and gates;
5. verify `main`, `HEAD`, tree, parent, and nine-commit history unchanged;
6. verify zero remotes, tags, unexpected refs, staging, or executor ref writes;
7. record the final exempt Codex turn-diff count as informational;
8. verify all three directives unchanged;
9. verify the final report is ordinary, non-empty, untracked, unstaged, absent
   from `HEAD`, UTF-8/LF, no trailing whitespace, one final LF; and
10. return its byte count and uppercase SHA-256 outside its body.

Do not edit the report after calculating its identity.

---

## 8. Corrected acceptance criteria

All original Phase 2C and Phase 2C.1 criteria remain mandatory with these exact
additions/supersessions:

1. Phase 2C.2 authentication passed independently;
2. every exact Section 4 authority exists and was read completely;
3. all eight void Section 5 names remain absent and were not created;
4. no number-based or approximate substitution occurred;
5. all three directives and three report placeholders authenticated at Gate A;
6. corrected Gate A status is exactly `11/22/33/0`;
7. Phase 2C.1's exact ref exemption passed without executor ref mutation;
8. complete original Phase 2C implementation and validation passed;
9. all original production/package/test/diagnostic/dependency counts are exact;
10. final Git status is exactly `18/55/73/0`;
11. `main`, `HEAD`, tree, parent, and nine-commit history remain unchanged;
12. both earlier Phase 2C reports remain zero bytes;
13. only `Phase_2C_2_correction.md` is populated after non-report success;
14. the final report contains full original, Phase 2C.1, and Phase 2C.2
    evidence;
15. its final identity is returned outside its body; and
16. next step remains architect review before Phase 2D.

Any absent exact authority, failed/partial/not-run gate, non-exempt ref, guessed
substitution, or unauthorized mutation means the phase is not complete.

---

## 9. Standard Phase 2C.2 completion report

Populate `docs/task reports/Phase_2C_2_correction.md` with exactly these
headings, in order:

```markdown
# PHASE 2C.2 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. CORRECTION AUTHORITY AND AUTHENTICATION

## 4. BASELINE VERIFIED

## 5. EXACT AUTHORITIES READ

## 6. REF CLASSIFICATION AND EXEMPTION EVIDENCE

## 7. CALLER RECORDS

## 8. FILES CREATED

## 9. FILES MODIFIED

## 10. HTTP MESSAGE AND FACTORY CONTRACTS

## 11. DIAGNOSTIC AND EXCEPTION BOUNDARY

## 12. DEPENDENCIES AND LOCKFILE

## 13. TEST IMPLEMENTATION AND COVERAGE

## 14. ARCHITECTURAL DECISIONS

## 15. DEVIATIONS FROM DIRECTIVES

## 16. ENVIRONMENT

## 17. COMMANDS RUN

## 18. VALIDATION RESULTS

## 19. DOCUMENTATION AND ADR REVIEW

## 20. SECURITY AND FILE-SAFETY REVIEW

## 21. WARNINGS AND KNOWN ISSUES

## 22. QUESTIONS OR BLOCKERS

## 23. GIT DIFF SUMMARY

## 24. ACCEPTANCE CRITERIA

## 25. NEXT STEP
```

In addition to every original Phase 2C and Phase 2C.1 report requirement, the
report must include:

- supplied/observed Phase 2C.2 identity;
- identities of both earlier directives and all three report records;
- confirmation that all 52 exact authorities were read;
- confirmation that all eight void names remained absent;
- explicit confirmation that no substitution by ADR number occurred;
- start/final exempt turn-diff counts and one-branch/zero-other-ref proof;
- corrected `11/22/33/0` baseline and `18/55/73/0` final status;
- complete implementation, dependency, lock, tests, quality, documentation,
  security, file-safety, and prohibited-scope evidence;
- confirmation that both earlier reports remain zero bytes; and
- PASS/FAIL for every original and corrected acceptance criterion.

For success, Section 25 must end exactly:

```text
Await architect review before Phase 2D.
```

If blocked, preserve all three report records as observed and return the exact
blocker directly unless later authenticated authority says otherwise.

---

## 10. Required outcome

Phase 2C.2 changes no Kongono architecture. It provides exact existing
authority paths so execution can resume without guessing, fabricating ADRs, or
repeating Phase 2B's filename mismatch.

On success, the exact original Phase 2C HTTP message/factory implementation is
complete, the Phase 2C.1 Codex-ref exemption is honored without ref mutation,
both earlier reports remain immutable zero-byte evidence, and the full outcome
is recorded in the Phase 2C.2 correction report.

Phase 2D remains prohibited until architect review and acceptance.
