# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0O.2

## Cumulative Diff Reconciliation and Validation Resumption

## Mission

Resume Phase 0O acceptance after the Phase 0O.1 executor correctly applied the
authorized documentation replacement and correctly stopped on an erroneous
cumulative diff-stat requirement.

This directive corrects only that acceptance statistic. The pre-correction
Phase 0O diff was 493 insertions and 28 deletions. The exact Phase 0O.1
replacement changes four lines that were already present in `HEAD`, so the
correct final cumulative diff is 497 insertions and 32 deletions.

Make no content edit. Revalidate the exact existing worktree, populate the
caller-created Phase 0O.2 report, and stop before Phase 0P.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before acting:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `docs/doctrine.md`;
5. `docs/documentation-standard.md`;
6. `docs/engineering/error-handling.md`;
7. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
8. `docs/adr/0027-package-local-runtime-diagnostic-metadata.md`;
9. `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md`;
10. `docs/dev_plan/kongono_phase_0O_codex_directive.md`;
11. `docs/task reports/Phase_0O.md`;
12. `docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md`;
13. `docs/task reports/Phase_0O_1_correction.md`; and
14. this directive.

The original Phase 0O and Phase 0O.1 directives remain authoritative except
that every Phase 0O.1 requirement claiming the final cumulative tracked diff
must remain 493 insertions and 28 deletions is superseded by this directive.
The correct post-correction value is exactly 497 insertions and 32 deletions.

Stop without changing any file if the locked state below does not match. Do not
repair, normalize, regenerate, edit, stage, commit, amend, reset, restore,
clean, stash, or otherwise reconcile a mismatch by assumption.

---

# 2. ACCEPTED BLOCKER AND DECISION

The Phase 0O.1 executor stopped correctly. Its required replacement changed:

```text
keys were not zero-based and consecutive. Expected list keys 0..2; received a
safe representation of keys 0, 2, 3. Reindex the node array as a PHP list.
```

to:

```text
keys were not zero-based and consecutive. Expected 3 zero-based consecutive
entries; first unexpected key 2. Reindex the node array as a PHP list.
```

and changed:

```text
metadata. The combined rendering, safe key representation, and formatting shown
above remain future work.
```

to:

```text
metadata. The combined rendering, Safe entry-count representation, Protected
first-unexpected-key representation, and formatting shown above remain future work.
```

Those four changed lines existed in `HEAD`. Relative to `HEAD`, the replacement
therefore adds four insertions and four deletions to the prior Phase 0O total:

```text
pre-correction Phase 0O: 493 insertions, 28 deletions
Phase 0O.1 replacement:    4 insertions,  4 deletions
correct final total:     497 insertions, 32 deletions
```

Decision: authorize and require the corrected final cumulative statistic of
exactly 497 insertions and 32 deletions. The 493/28 value remains valid only as
historical pre-correction Phase 0O evidence.

No architecture, schema, prose, source, test, dependency, or Git mutation is
authorized by this decision.

---

# 3. LOCKED CURRENT BASELINE

Before the caller copies this directive and creates its report placeholder, the
live repository must be exactly:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
tracked modes: all 100644
tracked modifications: 13
staged paths: 0
untracked paths: 6
total status paths: 19
tracked diff: 497 insertions, 32 deletions
remotes: 0
tags: 0
non-default alternate data streams: 0
```

The exact 13 modified tracked paths are:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/error-handling.md
docs/glossary.md
packages/application-graph/README.md
packages/dependency-injection/README.md
packages/kernel/README.md
```

The exact six current untracked paths are:

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
docs/task reports/Phase_0O_1_correction.md
```

Locked artifacts:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/adr/0028-bounded-diagnostic-context-and-disclosure.md` | 10,151 | `78E1DC7F4058A798EB411867FE06C817B1BABDD20BB7C6E58EF17A842ADF7016` |
| `docs/dev_plan/kongono_phase_0O_codex_directive.md` | 42,924 | `4FD514E5D92B2AD0AAAF852FABD48844C0C3F5CCE189F9DC4F9DBA2F9070FB5A` |
| `docs/task reports/Phase_0N_4_correction.md` | 17,247 | `8AFD9BC46D29B0DB0570B87C8B947C65AE82FD9C967DCCBDEDB68C3CEC62661C` |
| `docs/task reports/Phase_0O.md` | 21,340 | `5913B03A4CF6ADC22E298AF81B1819E462742F3A5982E9899202D87BE4AB17E6` |
| `docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md` | 15,805 | `E916B3751C69BD7E917B11B461F6A32B61761F7F52557AC2164A952D7B16445C` |
| `docs/task reports/Phase_0O_1_correction.md` | 2,512 | `F3FD7B7F2CF91193C76E4060E0AEB71B6A94B4BC603AEB162E2970ED3A1B825B` |
| `docs/engineering/error-handling.md` | 41,828 | `C71C78E0DDCB2FE58FD29C9937C2ACAACD9864A842401F37100DAEBC8D55B4CA` |

The Phase 0O.1 report is an immutable partial record. Its statement that its
report “remains zero bytes” describes its stop-time expectation and is not a
current-state assertion; the populated report is now exactly the 2,512-byte
caller record locked above. Do not edit or reinterpret it.

After the caller copies this directive and creates
`docs/task reports/Phase_0O_2_correction.md`, preflight must additionally prove:

- the copied directive matches the architect-delivered byte count and SHA-256;
- the new report is exactly zero bytes with SHA-256
  `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855`;
- there are exactly eight untracked paths and 21 total status paths; and
- the two added paths are exactly:

```text
docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md
docs/task reports/Phase_0O_2_correction.md
```

---

# 4. AUTHORIZED ACTION

Do not edit any content file.

The only authorized write is populating the caller-created zero-byte file:

```text
docs/task reports/Phase_0O_2_correction.md
```

That population may occur only after every non-report validation in Section 6
passes.

Do not rerun or reapply the Phase 0O.1 replacement. The corrected guide is
already the canonical required result.

---

# 5. PRESERVED CONTRACT AND PROHIBITED SCOPE

Preserve byte-for-byte every production, test, documentation, ADR, directive,
and earlier report file, including the corrected engineering guide.

Do not:

- change the exact 42-row schema, the corrected example, ADR 0028, or any other
  documentation;
- add or change production code, tests, fixtures, Composer files, dependencies,
  autoloading, tooling, or API status;
- implement a diagnostics package, context value, accessor, sanitizer,
  renderer, transport, logger, telemetry hook, error page, or later capability;
- implement DI/container, HTTP/router, Application Graph schema/IDs/
  serialization, ORM, agents, extensions, CLI, frontend, or telemetry;
- stage or commit any path;
- create, delete, rename, switch, or merge branches;
- create remotes or tags;
- amend, reset, rebase, restore, checkout, clean, stash, or rewrite history;
- change file modes or alternate streams;
- edit the Phase 0O.1 report to revise its historical conclusion; or
- populate the Phase 0O.2 report before all other validation passes.

---

# 6. REQUIRED VALIDATION

## 6.1 Exact preflight

Prove and record:

- branch, only refs, HEAD, tree, parent, commit count, subject, tracked count,
  and all tracked modes;
- zero staged paths, zero remotes, zero tags;
- exactly the 13 modified tracked paths in Section 3;
- exactly eight untracked paths after caller setup and 21 status paths;
- exact byte counts and SHA-256 digests for every locked artifact, the delivered
  Phase 0O.2 directive, and the zero-byte Phase 0O.2 report;
- the guide is exactly 41,828 bytes with SHA-256
  `C71C78E0DDCB2FE58FD29C9937C2ACAACD9864A842401F37100DAEBC8D55B4CA`;
- cumulative tracked diff is exactly 13 files, 497 insertions, and 32 deletions;
- zero non-default alternate data streams; and
- no content or index state changed since the Phase 0O.1 stop.

## 6.2 Documentation and schema proof

Without editing, prove:

- each corrected prose block occurs exactly once and each superseded block is
  absent;
- the future example uses only Safe `entry_count = 3` and Protected
  `first_unexpected_key = 2`;
- it does not display the full received-key sequence;
- the normative schema table remains byte-for-byte identical to the Phase 0O
  directive table;
- the table has exactly 42 unique rows split 6 Kernel, 11 Graph, and 25 DI;
- the four and only optional fields remain the authorized
  `first_unexpected_key` fields;
- current runtime behavior remains distinguished from future architecture;
- no runtime diagnostics package, context type, accessor, renderer, or
  transport is claimed;
- `git diff --check` passes;
- all local Markdown links resolve under the established historical-record
  exception;
- all changed and new text files are strict UTF-8 without BOM, LF-only, have
  exactly one final LF, and contain no extra terminal blank line, NUL,
  replacement character, mojibake marker, conflict marker, or trailing
  whitespace; and
- no secret or prohibited-scope artifact appears.

## 6.3 Canonical quality

Run exactly:

```powershell
composer quality
```

Required substantive result:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 92 files fixable
PHPStan: 0 errors
PHPUnit: 134 tests, 1099 assertions
```

Only the already accepted missing-license and authorized local `@dev` or `*`
constraint warning categories may remain.

If the managed sandbox alone blocks advisory network or cache access, rerun the
identical fail-closed command once with authorized normal access. Do not weaken
the policy or change flags.

## 6.4 Final proof and report population

Before populating the report, re-prove:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
tracked modifications: 13
tracked diff: 497 insertions, 32 deletions
staged paths: 0
untracked paths: 8
total status paths: 21
remotes: 0
tags: 0
```

Only after every other gate passes may the executor populate
`docs/task reports/Phase_0O_2_correction.md`. Then re-prove the same Git state,
the exact closed path inventories, all prior caller-record hashes, and report
integrity. Return the report's final byte count and SHA-256 outside the report.

---

# 7. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] The exact Phase 0O.1 stopped baseline and all caller records match.
- [ ] The Phase 0O.2 directive matches its architect-delivered bytes and hash;
  its report began as an exact zero-byte placeholder.
- [ ] No content, source, test, dependency, tooling, index, ref, or history
  mutation occurs.
- [ ] The corrected guide retains its exact bytes and hash.
- [ ] The schema remains exact and the corrected example uses only authorized
  fields and disclosures.
- [ ] The correct cumulative tracked diff is accepted as exactly 497 insertions
  and 32 deletions; 493/28 is treated only as the pre-correction statistic.
- [ ] Text integrity, links, streams, security, and prohibited-scope checks pass.
- [ ] Canonical quality passes with 134 tests and 1,099 assertions.
- [ ] Final state is exactly 13 modified tracked paths, eight untracked paths,
  zero staged paths, and 21 total status paths.
- [ ] The Phase 0O.2 report follows Section 8, is nonzero, strict UTF-8,
  LF-only, exactly one-final-LF terminated, untracked, unstaged, and absent from
  `HEAD`.
- [ ] The next step is exact.

---

# 8. CORRECTION COMPLETION REPORT

Write `docs/task reports/Phase_0O_2_correction.md` with exactly these numbered
sections:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

The report must state plainly:

- Complete or Blocked status;
- no file was created or modified by the executor other than population of the
  caller-created Phase 0O.2 report;
- the corrected final statistic is 497 insertions and 32 deletions and the
  Phase 0O.1 493/28 criterion was architect error;
- the corrected guide byte count and SHA-256;
- exact schema, quality, link, text-integrity, stream, and security results;
- exact environment versions, commands, caller-record hashes, and final Git
  counts;
- warning, retry, deviation, and blocker state;
- no dependency or executable behavior changed; and
- the report's final byte count and digest are returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0P.
```

---

# 9. FINAL EXECUTOR INSTRUCTION

Do not edit the worktree. Accept the corrected cumulative diff statistic,
revalidate the exact Phase 0O.1 result, populate only the caller-created Phase
0O.2 completion report after all gates pass, and stop before Phase 0P.
