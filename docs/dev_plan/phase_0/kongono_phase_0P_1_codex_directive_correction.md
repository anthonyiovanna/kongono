# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0P.1

## Completion-Report Heading Correction and Final Validation

## Mission

Correct one formatting defect in the otherwise successful Phase 0P completion
report. The report begins `+1. STATUS`; the mandatory 16-section contract
requires `1. STATUS` exactly.

Remove only that one leading plus byte, preserve the complete Phase 0P
implementation and all other report content byte-for-byte, rerun the required
validation, populate the caller-created Phase 0P.1 correction report, and stop
before Phase 0Q.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before acting:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0P_codex_directive.md`;
3. `docs/task reports/Phase_0P.md`;
4. ADRs 0028 and 0029;
5. `docs/engineering/error-handling.md`; and
6. this directive.

The Phase 0P directive remains authoritative except for this exact report
correction. No implementation, architecture, API, schema, dependency, tooling,
documentation, or Git change is reopened.

Stop without editing if any baseline, path inventory, byte count, digest, Git
identity, stream, mode, or caller-record condition below does not match. Do not
repair, normalize, stage, commit, reset, restore, clean, amend, or broaden scope.

---

# 2. ACCEPTED REVIEW FINDING

Independent architect review found no substantive Phase 0P implementation
defect. The complete quality gate passes with 170 tests and 1,908 assertions,
zero PHPStan errors, clean style across 109 files, and zero advisories.

The sole acceptance failure is the first report line:

```text
+1. STATUS
```

Section 22 of the Phase 0P directive requires:

```text
1. STATUS
```

This is a one-byte report defect. It does not alter the Phase 0P implementation
or its architectural outcome.

---

# 3. LOCKED CURRENT BASELINE

Before caller setup, prove exactly:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
modified tracked paths: 28
untracked paths: 30
total status paths: 58
staged paths: 0
tracked diff: 28 files, 771 insertions, 82 deletions
remotes: 0
tags: 0
non-default alternate data streams: 0
```

The exact modified and created implementation inventories remain Sections 4
and 5 of the Phase 0P directive. No path outside those inventories changed.

Lock these artifacts:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0P_codex_directive.md` | 47,365 | `305EB2499DAFF7297222414EAF802E82808777BA364C17A271C3ED1F3455689D` |
| `docs/task reports/Phase_0P.md` | 17,853 | `84EFEB3F68E4B40BB83DEE28E99E8E30B09E7275A94F15EAC021EA029B5222D2` |
| `docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md` | 5,340 | `A2EC6D406B0CDD0FDD6850EE6383FCA4E7AB81F224AD95F5BBFAFD9C1FDEAFFD` |
| `composer.lock` | 178,385 | `88C701552763F09DB8EFB6FEEB8E2604F4C6475DB2D62A5450CDA9A27EAD8087` |
| `docs/engineering/error-handling.md` | 47,474 | `70D87560771EEB7E0F553CB2B9AECCE5B127AE91148322C9A03F907FDCC1ECA5` |

The Phase 0P report must be strict UTF-8 without BOM, LF-only, one-final-LF
terminated, and have exactly one occurrence of `+1. STATUS` at byte zero. It
must contain no occurrence of a correctly unprefixed first heading before the
authorized edit.

After the caller copies this directive and creates the report placeholder,
these two additional untracked paths must exist:

```text
docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md
docs/task reports/Phase_0P_1_correction.md
```

The copied directive must match the architect-delivered bytes and SHA-256. The
new report must be exactly zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

At that point the exact state is 28 modified tracked paths, 32 untracked paths,
zero staged paths, and 60 total status paths.

---

# 4. EXACT AUTHORIZED CHANGE

Modify exactly one existing file:

```text
docs/task reports/Phase_0P.md
```

Remove exactly the first `+` byte so the first line changes from:

```text
+1. STATUS
```

to:

```text
1. STATUS
```

Do not rewrite, reflow, normalize, or otherwise change the file. Every byte
after the removed first byte must remain identical and in the same position
relative to the corrected content.

The corrected report must be exactly:

```text
bytes: 17852
SHA-256: 3196A3FC87B4BDB42F09A910A2BC0E1C3E68C65B9C15D076A558ACD70B21579A
```

It must then contain exactly these 16 numbered headings in order:

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

The only other authorized write is population of the caller-created
`docs/task reports/Phase_0P_1_correction.md` after every non-report validation
passes.

---

# 5. PROHIBITED SCOPE

Do not modify any production source, test, fixture, manifest, lockfile, ADR,
README, guide, tool configuration, directive, or prior report other than the
exact Phase 0P report byte above.

Do not:

- change any Phase 0P API, encoding, schema, diagnostic, exception, dependency,
  documentation, or test decision;
- rerun Composer update or alter `composer.lock`;
- add a dependency, plugin, suppression, baseline, exclusion, ignored error, or
  policy exception;
- integrate context into the existing 42 failures;
- implement rendering, HTTP, CLI, logging, telemetry, serialization, source,
  incidents, trusted developer mode, or another later capability;
- stage or commit;
- create, delete, rename, switch, or merge branches;
- create a remote or tag;
- amend, rebase, reset, restore, checkout, clean, or stash; or
- populate the Phase 0P.1 report before every other gate passes.

---

# 6. REQUIRED VALIDATION

## 6.1 Preflight

Prove every Section 3 value, exact closed path inventory, locked digest, stream,
mode, and caller record before editing. Run unchanged `composer quality` and
record the 170-test / 1,908-assertion baseline.

If the managed sandbox alone denies advisory network/cache access, rerun the
identical fail-closed command once with authorized normal access. Do not change
flags or policy.

## 6.2 Exact report proof

After removing the first byte, prove:

- Phase 0P report is exactly 17,852 bytes with SHA-256
  `3196A3FC87B4BDB42F09A910A2BC0E1C3E68C65B9C15D076A558ACD70B21579A`;
- first line is exactly `1. STATUS`;
- `+1. STATUS` is absent;
- the 16 exact headings occur once each and in order;
- every corrected byte after byte zero equals the original report byte at the
  immediately following offset;
- strict UTF-8 without BOM, LF-only, exactly one final LF, no extra terminal
  blank line, NUL, replacement character, mojibake, conflict marker, or trailing
  whitespace;
- the report remains untracked, unstaged, and absent from `HEAD`; and
- every other existing file retains its preflight digest.

## 6.3 Independent Phase 0P regression proof

Without editing implementation files, prove:

- exact inventories: 40 production, 69 test, 110 syntax, and 109 style files;
- exact 53 diagnostic IDs split 6 Kernel, 11 Graph, 25 DI, 11 Diagnostics;
- exact 42-row accepted Kernel/Graph/DI schema table remains byte-identical to
  the Phase 0O directive;
- lock delta remains only the local `kongono/diagnostics` package, content hash,
  and stability flag, with no third-party version change;
- diagnostics source imports only Kernel `Api`/`ApiStatus` metadata;
- reference detachment and bounded text behavior remain covered and passing;
- `git diff --check` passes;
- local Markdown links resolve under the historical-record exception;
- text integrity and alternate-stream checks pass; and
- no prohibited capability appears.

Run exactly:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 109 files fixable
PHPStan: 0 errors
PHPUnit: 170 tests, 1908 assertions
```

## 6.4 Final Git proof and report population

Before populating the correction report, prove:

```text
branch: main
only local branch: main
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
commit count: 5
tracked files: 214
modified tracked paths: 28
untracked paths: 32
total status paths: 60
staged paths: 0
tracked diff: 28 files, 771 insertions, 82 deletions
remotes: 0
tags: 0
```

Only after all non-report gates pass may the executor populate
`docs/task reports/Phase_0P_1_correction.md`. Then re-prove the same Git state,
closed inventories, caller-record integrity, and the correction report's text
integrity. Return its final byte count and SHA-256 outside the report.

---

# 7. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] Exact Phase 0P stopped baseline and caller records match.
- [ ] Phase 0P.1 directive matches architect-delivered bytes/hash; its report
  began as the exact zero-byte placeholder.
- [ ] Exactly one existing byte is removed from exactly the Phase 0P report.
- [ ] Corrected Phase 0P report has the exact required size and SHA-256.
- [ ] Its 16 headings are exact, unique, ordered, and no leading plus remains.
- [ ] No implementation, documentation, dependency, tool, test, ref, index,
  mode, history, or other caller record changes.
- [ ] Exact Phase 0P inventories, schemas, IDs, lock delta, dependency boundary,
  security rules, and prohibited scope remain intact.
- [ ] Canonical quality passes with 170 tests and 1,908 assertions.
- [ ] Links, text integrity, streams, and `git diff --check` pass.
- [ ] Final state is exactly 28 modified, 32 untracked, zero staged, and 60
  status paths.
- [ ] Phase 0P.1 report follows Section 8, is nonzero, strict UTF-8, LF-only,
  exactly one-final-LF terminated, untracked, unstaged, and absent from `HEAD`.
- [ ] Next step is exact.

Any failure makes status Partial or Blocked, never Complete.

---

# 8. CORRECTION COMPLETION REPORT

Write `docs/task reports/Phase_0P_1_correction.md` with exactly:

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
- no file was created by the executor other than population of the
  caller-created correction report;
- exactly `docs/task reports/Phase_0P.md` was corrected by one removed byte;
- old and new report bytes/hashes and exact heading proof;
- no substantive implementation correction was required;
- exact quality, inventory, ID, schema, lock, link, stream, and Git results;
- exact warnings, retries, deviations, and blockers;
- no dependency or executable behavior changed; and
- the correction report digest is returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0Q.
```

---

# 9. FINAL EXECUTOR INSTRUCTION

Remove only the erroneous leading plus byte from the Phase 0P report. Preserve
all implementation and every other record. Revalidate the full Phase 0P result,
populate only the caller-created Phase 0P.1 report after success, and stop before
Phase 0Q.
