# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2B.1

## Required-Authority Filename Correction and Phase 2B Resume

**Directive type:** authenticated corrective authority

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Corrects:** Phase 2B Section 2.1 authority paths and dependent caller/report counts

**Runtime scope:** none

---

## 1. Correction purpose

The authenticated Phase 2B directive named twelve ADR paths that do not exist
in the accepted repository. The executor correctly stopped before validation or
mutation.

The repository is not missing those files. The filenames in the directive were
incorrect.

This correction:

1. supersedes the entire Phase 2B Section 2.1 required-authority list with the
   exact existing paths in Section 4;
2. accepts the executor's no-mutation blocked state;
3. preserves the original Phase 2B directive and zero-byte Phase 2B report as
   immutable caller records;
4. authorizes resumption of Phase 2B from authority reading and Gate A;
5. adjusts only the caller-record and final status counts for this correction;
6. moves successful completion reporting to the Phase 2B.1 correction report;
   and
7. changes no HTTP interoperability, package, security, validation, or
   prohibited-scope decision from the authenticated Phase 2B directive.

No missing ADR may be fabricated, restored, renamed, copied, or inferred.

---

## 2. Independent correction identity and report placeholder

### 2.1 Independent identity

The architect will provide this correction directive's byte count and uppercase
SHA-256 outside the file. Before any broad validation or mutation, compare
those independently supplied values with:

```text
docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md
```

Do not self-authenticate the local artifact. If the independent values are
absent or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.2 Caller-created correction report

Before execution, the caller creates exactly:

```text
docs/task reports/Phase_2B_1_correction.md
```

It must initially be an ordinary, untracked, unstaged, zero-byte file absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Do not populate it until every permitted
non-report Phase 2B gate passes.

### 2.3 Stop rule

At the first mismatch, ambiguity, unexpected path, changed protected byte,
failed gate, unauthorized warning, or prohibited side effect:

1. stop immediately;
2. preserve the worktree and index exactly;
3. do not repair, normalize, restore, reset, stage, commit, or widen scope;
4. leave the correction report zero bytes unless Section 8 authorizes
   population; and
5. report the exact expected and observed values, viable options, and one
   recommendation.

---

## 3. Accepted blocked baseline

### 3.1 Original Phase 2B caller records

The original directive authenticated successfully and is now an immutable
caller record:

```text
path: docs/dev_plan/kongono_phase_2B_codex_directive.md
bytes: 39261
SHA-256: 0A7E7445E49A17383CAE16D7608C4E48F88E04357EB3791CA091561DE27D1527
state: untracked, unstaged, absent from HEAD
```

The original report remains an immutable zero-byte blocked-run record:

```text
path: docs/task reports/Phase_2B.md
bytes: 0
SHA-256: E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
state: untracked, unstaged, absent from HEAD
```

Do not populate, truncate, delete, rename, stage, or otherwise modify either
record.

### 3.2 Git state after the blocked run and before correction caller setup

Required:

```text
branch: main
local branches: 1
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent: 8fe793b124384744ac43887037360154c149241a
commit count: 9
modified tracked paths: 11
untracked paths with -uall: 10
total status paths with -uall: 21
staged paths: 0
remotes: 0
tags: 0
```

The ten untracked paths are exactly:

```text
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/architecture/phase-2-roadmap.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/dev_plan/kongono_phase_2A_codex_directive.md
docs/dev_plan/kongono_phase_2B_codex_directive.md
docs/task reports/Phase_1I.md
docs/task reports/Phase_1I_1_correction.md
docs/task reports/Phase_2A.md
docs/task reports/Phase_2B.md
```

The blocked execution created or modified no repository content.

### 3.3 State after correction caller setup

After the caller copies this correction directive and creates its report
placeholder, before executor validation or edits, required counts are:

```text
modified tracked paths: 11
untracked paths with -uall: 12
total status paths with -uall: 23
staged paths: 0
```

The two additional caller records are exactly:

```text
docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md
docs/task reports/Phase_2B_1_correction.md
```

All original Phase 2B Section 3.2 byte identities, the 151-entry protected
manifest, and `composer.lock` remain binding and must match before quality or
mutation.

---

## 4. Corrected complete authority list

This section supersedes the entire original Phase 2B Section 2.1 list. Read
every following file completely before `composer quality` or mutation:

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
14. `docs/adr/README.md`;
15. `docs/adr/0001-kongono-identity.md`;
16. `docs/adr/0003-monorepo-initial-development.md`;
17. `docs/adr/0004-microkernel-capability-architecture.md`;
18. `docs/adr/0006-persistent-runtime-first.md`;
19. `docs/adr/0013-documentation-doctrine.md`;
20. `docs/adr/0014-php-engineering-toolchain.md`;
21. `docs/adr/0019-composer-dependency-security-policy.md`;
22. `docs/adr/0020-kernel-operation-lifecycle-boundary.md`;
23. `docs/adr/0021-machine-checkable-api-status.md`;
24. `docs/adr/0023-explicit-typed-dependency-resolution.md`;
25. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
26. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`;
27. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
28. `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md`;
29. `docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md`;
30. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
31. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
32. `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`;
33. `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md`;
34. `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md`;
35. `docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md`;
36. `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md`;
37. `docs/adr/0043-phase-2-runnable-web-framework-milestone.md`;
38. `docs/dev_plan/kongono_phase_2A_codex_directive.md`;
39. `docs/task reports/Phase_2A.md`;
40. `docs/dev_plan/kongono_phase_2B_codex_directive.md`; and
41. this Phase 2B.1 correction directive.

Every path above exists after proper caller setup. If any is absent, stop and
report the exact missing path. Do not substitute another file.

The following twelve nonexistent names from the original directive are void and
must not be sought, restored, or created:

```text
docs/adr/0001-record-architecture-decisions.md
docs/adr/0003-monorepo-package-boundaries.md
docs/adr/0013-error-handling-and-actionable-diagnostics.md
docs/adr/0014-api-stability-and-versioning.md
docs/adr/0016-package-dependency-direction-and-composition-root.md
docs/adr/0022-dependency-injection-package-boundary.md
docs/adr/0026-actionable-diagnostic-runtime-metadata.md
docs/adr/0028-presentation-neutral-diagnostic-context.md
docs/adr/0031-neutral-api-metadata-package-boundary.md
docs/adr/0036-diagnostic-presentation-package-architecture.md
docs/adr/0038-trusted-developer-diagnostic-projection.md
docs/adr/0039-two-gate-trusted-diagnostic-activation.md
```

---

## 5. Exact resume authority

After Sections 2 through 4 pass:

1. resume original Phase 2B Gate A using the corrected authority list;
2. begin at the remaining read-only baseline, caller, protected-manifest,
   lockfile, stream, and file-safety checks;
3. run original Phase 2B Gate B pre-change `composer quality` only after every
   Gate A condition passes;
4. implement only the exact original Phase 2B Section 7 documentation scope;
5. run the original focused architecture and final quality gates;
6. preserve every original HTTP interoperability and package decision; and
7. stop before Phase 2C.

Already completed read-only authentication of the original Phase 2B directive
need not be repeated, but its identity must be rechecked as an immutable caller
record.

This correction does not authorize any repository edit beyond the original
Phase 2B Section 7 paths and the eventual Phase 2B.1 report population.

---

## 6. Superseded original provisions

Only these original provisions are superseded:

1. original Section 2.1 is replaced by this correction's Section 4;
2. original Gate A authority-reading item uses the corrected list;
3. original final Git counts and untracked inventory use Section 7 below;
4. original report population moves from `Phase_2B.md` to
   `Phase_2B_1_correction.md`; and
5. original report headings are replaced by Section 9 below.

Everything else in the authenticated original Phase 2B directive remains
binding, including:

- mission and selected architecture;
- standards evidence;
- exact future package and dependency boundary;
- PSR-7, PSR-17, and PSR-15 decisions;
- semantic, security, diagnostic, and persistent-runtime rules;
- exact documentation paths;
- prohibited scope;
- pre-change and final quality requirements;
- protected manifest and lockfile identities;
- file-safety rules; and
- no staging, commit, history mutation, or Phase 2C work.

---

## 7. Corrected final Git proof

After successful Phase 2B documentation work and before correction-report
population, required state is:

```text
branch: main
HEAD: d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree: bfa1908c619069b699bc9b08668db17ea2555eb3
commit count: 9
modified tracked paths: 11
untracked paths with -uall: 14
total status paths with -uall: 25
staged paths: 0
remotes: 0
tags: 0
```

The fourteen untracked paths must be exactly:

```text
docs/adr/0043-phase-2-runnable-web-framework-milestone.md
docs/adr/0044-http-message-and-interoperability-architecture.md
docs/architecture/http-message-model.md
docs/architecture/phase-2-roadmap.md
docs/dev_plan/kongono_phase_1I_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/dev_plan/kongono_phase_2A_codex_directive.md
docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_2B_codex_directive.md
docs/task reports/Phase_1I.md
docs/task reports/Phase_1I_1_correction.md
docs/task reports/Phase_2A.md
docs/task reports/Phase_2B.md
docs/task reports/Phase_2B_1_correction.md
```

The eleven modified tracked paths remain the original Phase 2B Section 3.2
tracked inventory. The original Phase 2B report remains zero bytes.

Correction-report population changes no path count.

---

## 8. Corrected completion authority and acceptance

Populate only:

```text
docs/task reports/Phase_2B_1_correction.md
```

Population is authorized only after:

1. correction authentication passes;
2. all corrected authorities are read completely;
3. the blocked baseline and all caller records match;
4. the original protected manifest and lockfile match;
5. original pre-change quality passes with 254 tests and 4,279 assertions;
6. the exact Phase 2B documentation implementation is complete;
7. every original focused architecture criterion passes;
8. original final quality passes with 254 tests and 4,279 assertions;
9. Section 7 final Git proof passes;
10. the original Phase 2B report remains zero bytes;
11. no unauthorized file, dependency, implementation, stage, commit, ref, or
    history operation occurred; and
12. Phase 2C did not begin.

The populated correction report must remain ordinary, untracked, unstaged,
absent from `HEAD`, strict UTF-8 without BOM, LF-only, free of NUL and trailing
whitespace, and terminated by exactly one final LF.

Return its final byte count and uppercase SHA-256 outside the report body.

No partial result is a pass.

---

## 9. Standard correction completion report

Use exactly these headings in this order:

```markdown
# PHASE 2B.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. CORRECTION AUTHENTICATION

## 4. BASELINE VERIFIED

## 5. CALLER RECORDS

## 6. AUTHORITIES READ

## 7. FILES CREATED

## 8. FILES MODIFIED

## 9. HTTP INTEROPERABILITY DECISION

## 10. PACKAGE AND DEPENDENCY DECISION

## 11. ARCHITECTURAL DECISIONS

## 12. DEVIATIONS FROM DIRECTIVES

## 13. DEPENDENCIES ADDED OR REMOVED

## 14. ENVIRONMENT

## 15. COMMANDS RUN

## 16. VALIDATION RESULTS

## 17. DOCUMENTATION REVIEW

## 18. SECURITY AND FILE-SAFETY REVIEW

## 19. WARNINGS AND KNOWN ISSUES

## 20. QUESTIONS OR BLOCKERS

## 21. GIT DIFF SUMMARY

## 22. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must state that the original blocker was caused solely by incorrect
directive filenames, that no repository authority was missing, and that the
original blocked run performed no mutation.

The final line must be exactly:

```text
Await architect review before Phase 2C.
```

---

## 10. Required final outcome

At successful completion:

- the authority-filename defect is corrected without fabricating files;
- Phase 2B is completed under its original architecture and scope;
- the original Phase 2B directive and zero-byte report remain immutable caller
  records;
- the Phase 2B.1 report contains the completion evidence;
- no runtime, dependency, Composer, test, staging, or history change exists;
  and
- Phase 2C remains unstarted pending architect review.
