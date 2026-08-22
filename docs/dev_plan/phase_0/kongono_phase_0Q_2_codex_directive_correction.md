# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0Q.2

## Correct the Successful Completion Report's String-Key Security Evidence

## Mission

Correct exactly two inaccurate validation statements in
`docs/task reports/Phase_0Q_1_correction.md`.

The Phase 0Q implementation is substantively accepted. Independent inspection
and execution confirmed that `ApplicationGraph::firstUnexpectedListKey()` obeys
the locked security rule:

- when the first non-list key is an integer, its canonical base-10 string is
  captured as the optional Protected `first_unexpected_key`; and
- when the first non-list key is a string, the field is omitted entirely, the
  string is not retained, and later keys are not inspected.

The Phase 0Q.1 completion report instead contains one imprecise sentence and one
false sentence saying string keys are retained. Production source, tests, ADR
0030, and current documentation are correct. This is a report-only correction.

Do not rerun implementation, edit production code, tests, documentation,
manifests, lock content, prior directives, or the historical blocked Phase 0Q
report.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the executor, not the architect.

Read completely before editing:

1. `AGENTS.md`;
2. `docs/dev_plan/kongono_phase_0Q_codex_directive.md`;
3. `docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md`;
4. `docs/task reports/Phase_0Q.md`;
5. `docs/task reports/Phase_0Q_1_correction.md`;
6. this correction directive;
7. `packages/application-graph/src/Model/ApplicationGraph.php`;
8. `packages/application-graph/tests/Unit/ApplicationGraphTest.php`;
9. `docs/adr/0030-application-graph-diagnostic-context-integration.md`; and
10. the exact string-key rules in `SECURITY.md`,
    `docs/architecture/application-graph.md`,
    `docs/engineering/error-handling.md`, and
    `packages/application-graph/README.md`.

If any Git fact, caller record, byte/hash, source behavior, exact old sentence,
or closed path inventory differs:

```text
STOP
ASK
DO NOT GUESS
```

Do not repair any adjacent wording or reformat the report.

---

# 2. LOCKED PRE-CORRECTION BASELINE

Before caller setup, verify exactly:

```text
Branch: main
Local branches: 1
HEAD: 75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree: 5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
Commit count: 5
Tracked files in HEAD: 214
Tracked modifications: 39
Untracked paths: 38
Total status paths: 77
Staged paths: 0
Tracked diff: 39 files changed, 1,545 insertions, 119 deletions
Remotes: 0
Tags: 0
Tracked modes: unchanged
Non-default alternate data streams: 0
```

The exact modified and untracked inventories must match Phase 0Q Section 17.6
as superseded by Phase 0Q.1 Section 9. No path may be added, removed, staged, or
reclassified before caller setup.

Preserve `HEAD`, tree, parent, history, refs, index, modes, remotes, and tags.

---

# 3. LOCKED ARTIFACT IDENTITIES

Verify:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0Q_codex_directive.md` | 50,661 | `F0F29634FB9B4943BD43EC398999CBA9099BAA508CA57CFE70EAADE5E600E3E3` |
| `docs/task reports/Phase_0Q.md` | 4,001 | `74EC1B831B492C4AEBD6A9F2A072D6608F90CC439AE05818A825790D228FE0FF` |
| `docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md` | 15,725 | `E9F246B602FFAEFF96244BD67DBDC1F61063574C2110BB0B23C98F6CB16EAD5E` |
| `docs/task reports/Phase_0Q_1_correction.md` | 20,468 | `E15ED137C17482B6FFA2DFA483DE3B57424767DD05EACE02FC4A6E5DDCFC3334` |
| `packages/application-graph/src/Model/ApplicationGraph.php` | 10,464 | `F255ED36BEB24612DED80776A0895821A223ECD1DA67A1C2F8001B52E1C079B5` |
| `packages/application-graph/src/Diagnostic/GraphDiagnostic.php` | 11,041 | `F3CA45559442A1884FD68EC53B2569329431A9616351F3E3D9383A32A7C2FEE4` |
| `packages/application-graph/src/Exception/ExceptionInterface.php` | 813 | `284415870ADB280824B384B3C89916EC6DD845A1BF55DE92B4CDBB4089B73514` |
| `packages/application-graph/src/Exception/GraphException.php` | 1,528 | `8BA96A520C4E3668045160EF44197FF80DF29853C0F973BFD140FE9B2A3A4016` |
| `packages/application-graph/tests/Unit/ApplicationGraphTest.php` | 37,943 | `41C49379CCF752E139C584D1C32BAA0F1508631F333EF762E6870D9631FBF633` |
| `packages/application-graph/tests/Unit/GraphDiagnosticContextTest.php` | 17,818 | `9FEAF0BA395352409D0C0CB68536CE49577A8B475AF9F35CA57C74048479D419` |
| `docs/adr/0030-application-graph-diagnostic-context-integration.md` | 7,759 | `3244D6ECBBA0F7C22D864612E924741663DF487664D5133BF4FB0686E49AF683` |
| `docs/engineering/error-handling.md` | 50,405 | `8F4F5F4753F6DB8D3E40D0802BA566346CC8E9C8A364949136DBB7BC627BECD5` |
| `composer.lock` | 178,429 | `879ABC517B15C16B7AFD25C4CE2958619C90D56F9F591B070281E2F892E4806D` |

The current successful report must be strict UTF-8 without BOM, LF-only,
exactly one-final-LF terminated, untracked, unstaged, absent from `HEAD`, and
contain all 16 required headings once and in order.

---

# 4. CALLER SETUP

The caller will copy this directive to:

```text
docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md
```

and create:

```text
docs/task reports/Phase_0Q_2_correction.md
```

Verify the copied directive is byte-identical to the architect-delivered
artifact and record its size and SHA-256.

The new report must initially be exactly zero bytes with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup, require exactly:

```text
Tracked modifications: 39
Untracked paths: 40
Total status paths: 79
Staged paths: 0
```

Do not populate the new report until every non-report correction check passes.

---

# 5. EXACT AUTHORIZED EDIT

Modify exactly:

```text
docs/task reports/Phase_0Q_1_correction.md
```

Apply exactly these two whole-line replacements.

## 5.1 Throw-site proof sentence

Replace exactly:

```text
- Throw-site proof: all 11 failures construct schema-valid values; optional string `first_unexpected_key` is omitted when no string key is available, numeric unexpected keys are canonically captured as text, and no raw invalid value is retained.
```

with exactly:

```text
- Throw-site proof: all 11 failures construct schema-valid values; optional `first_unexpected_key` is omitted when the first unexpected key is a string, numeric unexpected keys are canonically captured as text, and no raw invalid value is retained.
```

## 5.2 Key-algorithm sentence

Replace exactly:

```text
- Key algorithm: the first position whose actual key differs strictly from its expected zero-based integer is selected; string keys are retained exactly and numeric keys are converted canonically to decimal text.
```

with exactly:

```text
- Key algorithm: the first position whose actual key differs strictly from its expected zero-based integer is selected; string keys are omitted entirely and numeric keys are converted canonically to decimal text.
```

Each old line must occur exactly once before the edit and zero times afterward.
Each new line must occur zero times before the edit and exactly once afterward.

No other byte in the report may change. The corrected report must be exactly:

```text
Bytes: 20,471
SHA-256: D205F624600F60F5C458279CADF4367598F59397B545A52F51DDFF9CC0A714CB
```

It must retain the exact 16 headings, all other prose, strict UTF-8 without BOM,
LF-only endings, exactly one final LF, untracked/unstaged state, and absence from
`HEAD`.

---

# 6. PROHIBITED ACTIONS

Do not:

- modify `docs/task reports/Phase_0Q.md`;
- modify any production source, test, ADR, guide, README, manifest, lock,
  configuration, directive, or earlier report other than the exact two lines;
- rerun Composer update, autoload generation, PHPUnit, PHPStan, PHP CS Fixer,
  canonical quality, or implementation validation;
- reimplement, refactor, format, or clean up Phase 0Q;
- change a field schema, capture rule, message, test expectation, dependency,
  warning policy, or API;
- create any artifact other than the two caller-created Phase 0Q.2 records;
- stage, commit, reset, amend, switch branches, create refs, add remotes, tag,
  push, or alter history; or
- change ignored caches merely to produce new evidence.

The already verified quality result remains accepted:

```text
182 tests
2,257 assertions
zero PHPStan errors
0 of 110 files fixable
zero security advisories
```

This correction verifies preservation; it does not rerun implementation work.

---

# 7. REQUIRED READ-ONLY VALIDATION

Before editing:

1. verify Sections 2 through 4 exactly;
2. verify the two old lines occur exactly once;
3. verify the implementation actually omits a first string mismatch and captures
   a first integer mismatch as canonical text;
4. verify current source, tests, ADR 0030, security guide, Graph guide, error
   guide, and package README agree on that rule; and
5. verify the correction report remains zero bytes.

After the two-line edit:

1. verify the corrected report's exact bytes/hash from Section 5;
2. reconstruct the old report by reversing only the two replacements and prove
   its bytes/hash equal Section 3;
3. prove every corrected byte outside the two exact line spans equals the old
   byte at its corresponding unchanged position;
4. re-prove all Section 3 identities other than the intentionally corrected
   report;
5. re-prove exact Git identity, index, refs, modes, streams, and closed path
   inventories;
6. run `git diff --check` and prove the cached diff is empty;
7. prove no implementation or tracked diff changed; and
8. only then populate the Phase 0Q.2 report.

After report population, the exact Git state remains:

```text
Tracked modifications: 39
Untracked paths: 40
Total status paths: 79
Staged paths: 0
Tracked diff: 39 files changed, 1,545 insertions, 119 deletions
```

The final untracked inventory is the Phase 0Q.1 38-path inventory plus exactly:

```text
docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md
docs/task reports/Phase_0Q_2_correction.md
```

---

# 8. ACCEPTANCE CRITERIA

- [ ] Exact pre-correction 39/38/77/0 Git state and Section 3 identities match.
- [ ] Phase 0Q.2 caller records match delivered directive and zero-byte
  placeholder requirements.
- [ ] Exactly the two Section 5 lines are replaced.
- [ ] Corrected Phase 0Q.1 report is exactly 20,471 bytes with the locked hash.
- [ ] Old-report reconstruction proves no other report byte changed.
- [ ] Corrected report truthfully states string-key omission and numeric-key
  capture.
- [ ] Production source, tests, ADR, guides, package documentation, manifests,
  lock, prior records, and tracked diff remain unchanged.
- [ ] Historical blocked `Phase_0Q.md` remains byte-identical.
- [ ] No implementation or quality command is rerun.
- [ ] HEAD, tree, parent, history, refs, index, modes, remotes, tags, and streams
  remain unchanged.
- [ ] Final state is exactly 39 modified, 40 untracked, 79 total, zero staged.
- [ ] The Phase 0Q.2 report follows Section 9 and is nonzero, strict UTF-8,
  LF-only, exactly one-final-LF terminated, untracked, unstaged, and absent from
  `HEAD`.
- [ ] Its final byte count and SHA-256 are returned separately.
- [ ] Its next step is exact.

---

# 9. STANDARD CORRECTION COMPLETION REPORT

After every non-report gate passes, populate
`docs/task reports/Phase_0Q_2_correction.md` with exactly these headings once
each and in order:

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

- Phase 0Q implementation and quality were already accepted and not rerun;
- the defect existed only in two Phase 0Q.1 report sentences;
- exact old/new report bytes and hashes;
- exact two-line replacement and reverse reconstruction proof;
- implementation, tests, docs, dependencies, lock, tracked diff, and historical
  report remained byte-identical;
- final Git/caller-record evidence;
- no other correction or known issue remains; and
- its own final digest is returned separately.

Section 16 must say exactly:

```text
Await architect review before Phase 0R.
```

---

# 10. FINAL EXECUTOR INSTRUCTION

Correct only the two inaccurate string-key statements in the successful Phase
0Q.1 report. Preserve every implementation artifact and historical record,
perform read-only proof only, populate the new correction report after success,
and stop before Phase 0R.
