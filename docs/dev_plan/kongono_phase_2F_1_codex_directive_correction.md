# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2F.1
## Roadmap Current-State and Completion-Report Evidence Correction

**Directive type:** narrow documentation and report correction

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2F.1

**Depends on:** authenticated Phase 2F implementation and its populated completion report

---

## 1. Mission

Correct exactly two independently verified Phase 2F completion defects:

1. `docs/architecture/phase-2-roadmap.md` marks Phase 2F complete in its fixed
   sequence and current-state table but still says in its opening summary that
   Phase 2F is the next bounded phase; and
2. `docs/task reports/Phase_2F.md` records the focused architecture suite as
   `6 tests, 2155 assertions`, while an independent execution of the exact
   focused command passes with `6 tests, 2160 assertions`.

Make only the exact replacements in Section 7. Do not reopen Phase 2F design,
change runtime behavior, add tests, alter dependencies, or advance Phase 2G.

Phase 2F.1 is complete only when the two corrected files, all protected Phase 2F
implementation bytes, the live quality gates, repository state, and a new
correction completion report satisfy this directive exactly.

---

## 2. Independent directive authentication

Before any quality command or mutation, compare this file with the byte count
and SHA-256 supplied independently by the architect outside this document.

Required path:

```text
docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md
```

Do not derive the expected identity from the local file. If either independently
supplied value is absent or differs, stop before quality checks or mutation and
report the observed values.

---

## 3. Required authorities and caller records

Read completely before mutation:

```text
AGENTS.md
ARCHITECTURE.md
docs/documentation-standard.md
docs/architecture/phase-2-roadmap.md
docs/dev_plan/kongono_phase_2F_codex_directive.md
docs/task reports/Phase_2F.md
docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md
```

The correction report must already exist at:

```text
docs/task reports/Phase_2F_1_correction.md
```

At entry it must be an ordinary, untracked, unstaged, zero-byte file, absent
from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The accepted Phase 2F caller records must initially match exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2F_codex_directive.md` | 53418 | `5D25D4FA8A34F410548836B673AD3F77845F509DD45DB0039C1858572DF58EB4` |
| `docs/task reports/Phase_2F.md` | 25844 | `FAFA58920F624988458A24C7F2176D64BAB5D34070A3A0454B4FE9EEC32356B7` |

The Phase 2F directive remains immutable. The Phase 2F report may change only
through the two exact evidence replacements authorized by Section 7.2.

Stop if a caller record, placeholder, ordinary-file property, index state, or
starting identity differs. Do not repair or normalize it by assumption.

---

## 4. Accepted starting state

Before the two new Phase 2F.1 caller records are placed, the independently
observed repository state is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
tracked modifications  20
untracked paths        122
total status paths     142
staged paths           0
tracked files          358
Git-visible paths      480
```

After the caller copies this authenticated directive and creates the zero-byte
correction report, the expected entry status is:

```text
tracked modifications  20
untracked paths        124
total status paths     144
staged paths           0
Git-visible paths      482
```

The only two additional paths must be:

```text
docs/dev_plan/kongono_phase_2F_1_codex_directive_correction.md
docs/task reports/Phase_2F_1_correction.md
```

The index must remain empty. There must be one local branch (`main`), zero
remotes, zero tags, and no unexpected Git refs other than environment-managed
`refs/codex/turn-diffs/**`, if present. Do not stage, commit, amend, reset,
restore, clean, switch branches, create refs, or rewrite history.

The exact accepted correction targets begin as:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/architecture/phase-2-roadmap.md` | 11887 | `C5E6FCF3FF84214A11C84A40CC3D452BDC09147830C986D8379F7B02B1AB2DDD` |
| `docs/task reports/Phase_2F.md` | 25844 | `FAFA58920F624988458A24C7F2176D64BAB5D34070A3A0454B4FE9EEC32356B7` |

Stop before mutation if any accepted starting fact differs.

---

## 5. Frozen boundary

Only these two existing files may be modified before final correction-report
population:

```text
docs/architecture/phase-2-roadmap.md
docs/task reports/Phase_2F.md
```

Only this caller-provided zero-byte file may be populated after every other gate
passes:

```text
docs/task reports/Phase_2F_1_correction.md
```

Do not modify any production source, test, fixture, Composer manifest, lockfile,
tool configuration, ADR, README, security document, architecture document other
than the one named above, prior directive, prior correction report, or Git
metadata.

In particular, preserve every byte under:

```text
packages/
tests/
vendor/
```

and preserve exactly:

```text
composer.json
composer.lock
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
```

No package, dependency, API, diagnostic, runtime behavior, test, fixture,
suppression, baseline, policy, adapter, I/O, or Phase 2G capability is authorized.

---

## 6. Verified defects

### 6.1 Roadmap contradiction

The current roadmap simultaneously says:

- Phase 2F is complete in the fixed A-H table;
- the current-state table is explicitly “Current after Phase 2F”; and
- Phase 2G is the next phase throughout the accepted Phase 2F authorities.

Its opening Phase 2B paragraph nevertheless ends with `Phase 2F is the next
bounded phase.` This is current roadmap narration, not a quoted historical
record. It violates Phase 2F Directive Sections 11.3 and 15, which require Phase
2F implemented and Phase 2G next.

### 6.2 Focused architecture evidence mismatch

The exact command:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

independently passes with:

```text
OK (6 tests, 2160 assertions)
```

The Phase 2F report contains `6 tests, 2155 assertions` exactly twice. The full
canonical suite independently remains:

```text
OK (437 tests, 7853 assertions)
```

Do not change tests to manufacture either total. This correction changes report
evidence only.

---

## 7. Exact authorized corrections

### 7.1 Roadmap whole-block replacement

In `docs/architecture/phase-2-roadmap.md`, replace this exact block once:

```text
Phase 2B is also architecture and documentation only. It accepts PSR-7 through
`psr/http-message` 2.x, the selected server-side PSR-17 factories, future
PSR-15, and the planned first-party `kongono/http-message` boundary. It creates
no package, dependency, implementation, test, or runtime behavior. Phases 2C,
2D, and 2E are now complete under their separate authorities; Phase 2F is the
next bounded phase.
```

with exactly:

```text
Phase 2B is also architecture and documentation only. It accepts PSR-7 through
`psr/http-message` 2.x, the selected server-side PSR-17 factories, future
PSR-15, and the planned first-party `kongono/http-message` boundary. It creates
no package, dependency, implementation, test, or runtime behavior. Phases 2C
through 2F are now complete under their separate authorities; Phase 2G is the
next bounded phase.
```

The old whole block must occur exactly once before replacement and zero times
afterward. The new whole block must occur zero times before replacement and
exactly once afterward.

Do not rewrite any other Phase 2 historical narrative. Do not alter the fixed
A-H sequence, dependency sequence, current-state table, exclusions, or links.

### 7.2 Phase 2F report evidence replacement

In `docs/task reports/Phase_2F.md`, replace the exact literal:

```text
6 tests, 2155 assertions
```

with:

```text
6 tests, 2160 assertions
```

The old literal must occur exactly twice before replacement and zero times
afterward. The new literal must occur zero times before replacement and exactly
twice afterward.

Do not change any other byte of the Phase 2F report. Its existing documentation
reconciliation statements become accurate only because Section 7.1 corrects
the roadmap. Preserve all headings, other evidence, warnings, acceptance items,
and its exact final sentence.

---

## 8. Execution order and stop rules

Execute in this order:

1. authenticate this directive from independently supplied values;
2. read every authority completely;
3. verify the accepted starting state, caller records, zero-byte report, file
   safety, target hashes, index, history, and exact pre-edit occurrence counts;
4. run the pre-edit focused architecture command and record the actual result;
5. apply only the two replacements in Section 7;
6. verify the exact post-edit occurrence counts and closed three-path correction
   boundary;
7. validate UTF-8, LF-only endings, exactly one final LF, no trailing whitespace,
   no NUL, ordinary files, no reparse points, no alternate streams, and mode
   `100644` for the two edited files;
8. verify local Markdown links remain unbroken;
9. run the focused architecture suite again;
10. run `git diff --check`;
11. run canonical `composer quality` unchanged;
12. reverify protected paths, Git identity, empty index, exact status inventory,
    and caller records;
13. only then populate `docs/task reports/Phase_2F_1_correction.md`;
14. validate the populated correction report and return its byte count and
    SHA-256 outside the report body.

If the unchanged Composer audit is blocked only by managed network/cache access,
retry the identical `composer quality` command with approved access. Do not skip
or weaken `audit:locked`.

At the first mismatch or failing non-environmental gate:

```text
STOP
PRESERVE THE CURRENT STATE
REPORT EXPECTED AND OBSERVED VALUES
DO NOT GUESS
```

Keep the correction report zero-byte unless all non-report gates pass.

---

## 9. Required validation evidence

At minimum, report exact outcomes for:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --check
composer quality
```

The accepted results are observationally expected to include:

```text
focused architecture  6 tests, 2160 assertions
full PHPUnit          437 tests, 7853 assertions
PHPStan               zero errors
PHP CS Fixer          0 of 205 files fixable
security advisories   zero
```

If an actual deterministic total differs, stop and report it. Do not edit tests
or the report to force a target.

Also prove:

- the roadmap old block is absent and the new block occurs once;
- the old focused result is absent from the Phase 2F report and the corrected
  result occurs twice;
- `Phase 2F is the next bounded phase` is absent from the current roadmap;
- `Phase 2G is the next bounded phase` is present in the corrected opening;
- the full Phase 2F result remains `437 tests, 7853 assertions`;
- only the two correction targets differ before report population;
- protected source, tests, manifests, lockfile, tooling, ADR 0048, and Web
  Application model bytes remain unchanged;
- no path is staged and Git history/ref state is unchanged; and
- final status after report population is exactly 20 modified, 124 untracked,
  144 total, zero staged, and 482 Git-visible paths.

Do not count environment-managed `refs/codex/turn-diffs/**` as repository-owned
refs. Report their observed count separately if present.

---

## 10. Prohibited scope

Do not:

- implement or alter Web Application, Kernel, Routing, HTTP Server, HTTP Message,
  diagnostics, exception, middleware, or operation-scope behavior;
- modify tests, fixtures, production declarations, API statuses, diagnostic
  identifiers, Composer data, dependency references, or tooling configuration;
- add an adapter, emitter, request conversion, delivery evidence, generic HTTP
  error response, trusted diagnostic delivery, runnable fixture, or any Phase 2G
  or Phase 2H capability;
- rewrite historical directives or reports other than the two exact evidence
  literals authorized in the Phase 2F report;
- normalize unrelated whitespace or line endings;
- stage, commit, restore, reset, clean, amend, switch branches, create tags,
  manipulate refs, or repair repository state; or
- populate the correction report early.

---

## 11. Correction completion report

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2F_1_correction.md
```

Use exactly these top-level headings:

```text
# PHASE 2F.1 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. ENVIRONMENT
## 11. COMMANDS RUN
## 12. VALIDATION RESULTS
## 13. DOCUMENTATION REVIEW
## 14. WARNINGS / KNOWN ISSUES
## 15. QUESTIONS / BLOCKERS
## 16. GIT DIFF SUMMARY
## 17. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must state explicitly:

- whether the directive identity matched independently supplied values;
- the exact two modified paths and that no implementation path changed;
- exact old/new occurrence counts;
- pre-edit and post-edit focused architecture results;
- final canonical quality results;
- report, source, test, manifest, lockfile, tooling, Git, and caller-record
  preservation evidence;
- final status counts and zero staged paths;
- any approved network/cache retry and existing non-material warnings; and
- whether Phase 2F is now fully closed.

Its exact final sentence must be:

```text
Await architect review before Phase 2G.
```

Return the populated report byte count and SHA-256 outside its body. The report
must be UTF-8 without BOM, LF-only, have exactly one final LF, contain no trailing
whitespace or NUL, remain untracked and unstaged, and remain absent from `HEAD`.

---

## 12. Strict acceptance criteria

Phase 2F.1 is complete only if all are true:

1. this directive matched the independently supplied identity;
2. every required authority was read completely;
3. all accepted starting identities and caller records matched;
4. the correction report began as the exact zero-byte placeholder;
5. the pre-edit roadmap old block occurred once and the pre-edit report literal
   occurred twice;
6. only the exact roadmap whole block was replaced;
7. only the two exact Phase 2F report evidence literals were replaced;
8. the roadmap says Phases 2C through 2F are complete and Phase 2G is next;
9. the Phase 2F report records `6 tests, 2160 assertions` exactly twice;
10. focused architecture validation passes at 6 tests and 2160 assertions;
11. canonical quality passes unchanged at 437 tests and 7853 assertions, zero
    PHPStan errors, zero style findings, and zero advisories;
12. no source, test, fixture, manifest, lockfile, tooling, ADR, or other
    documentation byte changed;
13. all links and file-safety checks pass;
14. `git diff --check` passes;
15. Git branch, HEAD, tree, parent, history, index, remotes, tags, and
    repository-owned refs remain unchanged;
16. final status is exactly 20 modified, 124 untracked, 144 total, zero staged,
    and 482 Git-visible paths;
17. the correction report was populated only after criteria 1-16 passed;
18. the correction report uses the exact required structure and final sentence;
19. its byte count and SHA-256 are returned independently; and
20. no Phase 2G work was begun.

If any item fails, status is not Complete.

---

## 13. Required final outcome

The finished repository must preserve the complete accepted Phase 2F runtime
implementation and quality state while making the roadmap internally current
and the Phase 2F focused architecture evidence truthful. No runtime behavior or
later capability changes.

After successful executor completion, return the Phase 2F.1 report and await
architect review before Phase 2G.
