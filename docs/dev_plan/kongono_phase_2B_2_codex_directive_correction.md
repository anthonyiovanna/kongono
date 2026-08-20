# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2B.2

## Completion-Report Dependency-Wording Correction

**Directive type:** authenticated report-only correction

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Branch:** `main`

**Date:** 2026-08-16

**Corrects:** one inaccurate dependency sentence in the Phase 2B.1 completion report

**Runtime and documentation scope:** none

---

## 1. Purpose and acceptance status

Independent architect review accepts the Phase 2B implementation and
documentation. ADR 0044, the HTTP message model, every authorized
reconciliation, the protected manifest, lockfile, Git state, links, file
safety, and canonical quality results pass.

One sentence in `docs/task reports/Phase_2B_1_correction.md` is inaccurate. It
first lists `kongono/diagnostics: *` as an exact future dependency, then says
the package has no dependency on “diagnostic packages.” The broad phrase
literally includes the required Diagnostics package and contradicts both ADR
0044 and the preceding dependency list.

This correction is report-only. It preserves all accepted source,
documentation, caller records, quality evidence, architecture, and Git state.
It does not reopen Phase 2B implementation.

---

## 2. Independent identity and caller placeholder

### 2.1 Correction directive

The architect will provide this file's byte count and uppercase SHA-256 outside
the file. Authenticate the copied file at:

```text
docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md
```

Do not self-authenticate. If the independent values are absent or different:

```text
STOP
ASK
DO NOT GUESS
```

### 2.2 Correction report

The caller creates exactly:

```text
docs/task reports/Phase_2B_2_correction.md
```

Before execution it must be an ordinary, untracked, unstaged, zero-byte file
absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not create it for the caller. Do not modify any earlier report.

---

## 3. Required authority and locked records

Read completely before report population:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_2B_codex_directive.md`;
3. `docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md`;
4. `docs/task reports/Phase_2B_1_correction.md`;
5. `docs/adr/0044-http-message-and-interoperability-architecture.md`;
6. `docs/architecture/http-message-model.md`; and
7. this Phase 2B.2 correction directive.

Required identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2B_codex_directive.md` | 39261 | `0A7E7445E49A17383CAE16D7608C4E48F88E04357EB3791CA091561DE27D1527` |
| `docs/task reports/Phase_2B.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |
| `docs/dev_plan/kongono_phase_2B_1_codex_directive_correction.md` | 14406 | `8CED7E155EFF568613DB7244BE9334B4595C00E721B975CF9D8FD5D3E9FD191B` |
| `docs/task reports/Phase_2B_1_correction.md` | 20177 | `62AEC1C828F37A31E35098CC09A5341216EE88F6B0E1DE5DE938A040E4AC8DB7` |
| `docs/adr/0044-http-message-and-interoperability-architecture.md` | 15451 | `F3E2220ACBEFCCB1A53328459748FDFB3F45F3AB5BCD22B6F91763793F91C103` |
| `docs/architecture/http-message-model.md` | 10942 | `874801781BF0429C9012BC049372142344CC39A0DB150EF9F180D0CDCB539DF0` |

The prior reports and documents are immutable historical records. Do not edit
them to make the historical wording disappear.

---

## 4. Exact correction statement

The Phase 2B.2 report must state that the following Phase 2B.1 report wording
is inaccurate and superseded:

```text
The package has no reverse API Metadata or Diagnostics edge and no dependency
on Kernel, Dependency Injection, Application Graph, diagnostic packages,
PSR-15, routing, adapters, runtimes, or another message implementation.
```

The exact corrected architectural statement is:

```text
The package has no reverse API Metadata or Diagnostics edge and no dependency
on Kernel, Dependency Injection, Application Graph, Diagnostic Activation,
Diagnostic Presentation, Diagnostic Handler, PSR-15, routing, adapters,
runtimes, or another message implementation.
```

The correction must also affirm that the future direct requirements remain
exactly:

```text
php: ^8.5
kongono/api-metadata: *
kongono/diagnostics: *
psr/http-message: ^2.0
psr/http-factory: ^1.1
```

No other Phase 2B.1 statement is superseded.

---

## 5. Exact baseline and final Git state

Before Phase 2B.2 caller setup, the accepted state is:

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

After the caller copies this directive and creates the zero-byte report, and
after report population, required final counts are:

```text
modified tracked paths: 11
untracked paths with -uall: 16
total status paths with -uall: 27
staged paths: 0
```

The only new paths are:

```text
docs/dev_plan/kongono_phase_2B_2_codex_directive_correction.md
docs/task reports/Phase_2B_2_correction.md
```

All 25 prior status paths must retain their exact pre-correction bytes. The
original Phase 2B report remains zero bytes. The Phase 2B.1 report remains
20,177 bytes with its locked SHA-256.

---

## 6. Permitted validation

Use read-only checks only:

1. authenticate this directive and zero-byte report;
2. verify Section 3 identities;
3. verify the exact branch, `HEAD`, tree, commit count, refs, status counts,
   untracked inventory, and empty index;
4. verify all 25 prior status-path bytes are unchanged;
5. confirm the inaccurate phrase occurs exactly once in the Phase 2B.1 report;
6. confirm ADR 0044 and the HTTP message model contain the corrected dependency
   boundary;
7. verify the original Phase 2B report remains zero bytes;
8. verify ordinary-file, default-stream, non-reparse, strict UTF-8, LF-only,
   exactly-one-final-LF, NUL, and trailing-whitespace rules for the new report;
   and
9. verify no staged path or history/ref change exists.

Do not rerun:

- `composer quality`;
- Composer audit, validation, or platform checks;
- PHPUnit;
- PHPStan;
- PHP CS Fixer; or
- any broad source or documentation validation.

Those gates already passed and no accepted implementation or documentation
byte may change in this report-only correction.

---

## 7. Prohibited scope

Do not:

- edit the Phase 2B.1 report;
- edit any source, documentation, test, fixture, manifest, lockfile, tooling,
  package README, or historical caller record;
- create any file other than caller setup and the populated Phase 2B.2 report;
- add, remove, update, or resolve a dependency;
- stage, commit, branch, tag, add a remote, push, reset, restore, or mutate
  history;
- normalize or clean adjacent files;
- rerun quality or implementation tests;
- expand or revise the accepted Phase 2B architecture;
- begin Phase 2C; or
- populate the report before every read-only prerequisite passes.

At the first mismatch, stop, preserve state, and report expected versus
observed values. Do not guess or repair.

---

## 8. Phase 2B.2 report

Populate only:

```text
docs/task reports/Phase_2B_2_correction.md
```

Use exactly these headings:

```markdown
# PHASE 2B.2 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. AUTHENTICATION

## 4. LOCKED RECORDS

## 5. REPORT CORRECTION

## 6. VALIDATION RESULTS

## 7. FILES CREATED OR MODIFIED

## 8. DEVIATIONS

## 9. FINAL GIT STATE

## 10. ACCEPTANCE AND NEXT STEP
```

The report must:

- reproduce the inaccurate and corrected statements exactly;
- affirm the five exact future requirements;
- state that no architecture or repository documentation changed;
- state that canonical Phase 2B quality remains accepted but was not rerun;
- provide exact final Git evidence;
- confirm the Phase 2B.1 report remains immutable; and
- confirm Phase 2C did not begin.

The final line must be exactly:

```text
Await architect review before Phase 2C.
```

After population, return the report byte count and uppercase SHA-256 outside
the report body.

---

## 9. Acceptance criteria

Phase 2B.2 is complete only if:

1. the directive and zero-byte report authenticate;
2. all required authorities are read completely;
3. all locked records and prior status-path bytes remain exact;
4. the single inaccurate dependency phrase is identified and superseded;
5. the corrected statement names Diagnostic Activation, Diagnostic
   Presentation, and Diagnostic Handler while retaining Diagnostics as an
   exact dependency;
6. no earlier report, architecture document, or implementation file changes;
7. no quality or test command is rerun;
8. no dependency, stage, commit, ref, or history mutation occurs;
9. final status is exactly 11 modified, 16 untracked, 27 total, and zero staged;
10. the Phase 2B.2 report alone is populated after all prerequisites pass; and
11. Phase 2C remains unstarted.

No partial result is a pass.
