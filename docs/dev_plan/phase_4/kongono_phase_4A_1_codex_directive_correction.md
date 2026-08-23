# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 4A.1
## Sequential Phase 4 Dependency Map Correction and Closure

**Directive type:** exact two-document architecture correction

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-22

**Phase:** 4A.1

**Depends on:** completed Phase 4A implementation and populated Phase 4A completion report

---

## 1. Mission

Correct one isolated architect-authored ambiguity in the completed Phase 4A
documentation.

Phase 4A correctly locks the A-H roadmap in this execution order:

```text
4A -> 4B -> 4C -> 4D -> 4E -> 4F -> 4G -> 4H
```

ADR 0060 and the roadmap tables state that dependency order correctly. However,
the two architecture diagrams currently branch Phase 4C into parallel 4D, 4E,
and 4F nodes. That picture conflicts with the locked sequential subphase order
and could incorrectly authorize Phase 4E or 4F before the preceding subphase is
accepted.

Phase 4A.1 replaces only those two diagrams and their immediately associated
clarifying prose. It does not change any subphase boundary, role responsibility,
security rule, PHP policy, package, API, schema, implementation, or Phase 4A
completion evidence.

The conceptual roles may consume the same future knowledge foundation and may
eventually exchange evidence. That conceptual relationship does not make their
implementation subphases parallel. Kongono phase execution remains sequential:
each subphase must be accepted before the next begins.

Do not modify the original Phase 4A completion report. It remains immutable
historical evidence of the first execution. This correction report records the
architect review finding and final resolution.

---

## 2. Independent authentication

Before broad reading, validation, or mutation, compare this directive with the
byte count and SHA-256 supplied independently by the architect outside this
file.

Required path:

```text
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

The caller must also provide:

```text
docs/task_reports/phase_4/Phase_4A_1_correction.md
```

At entry it must be an ordinary, untracked, unstaged file absent from `HEAD`,
zero bytes, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte until every permitted non-report gate passes. Never stage or
commit it.

---

## 3. Required authorities

Read completely before editing:

```text
AGENTS.md
ARCHITECTURE.md
docs/doctrine.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/architecture/developer-intelligence.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/task_reports/phase_4/Phase_4A.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
```

The paths are exact. Do not substitute another ADR, directive, report, roadmap,
or architecture document.

---

## 4. Accepted entry state

### 4.1 Git and repository identity before Phase 4A.1 caller setup

Require:

```text
branch                  main
HEAD                    6aacafc92f07b567818173d660e2e182dfa83d23
HEAD tree               d7fda17d27a15954cdd213a1c323aaeb37a2b574
HEAD parent             647053d4bddc4c76090bca9bf3d693faa5880c52
commit count            11
tracked files           629
tracked modifications    18
untracked paths            8
total status paths        26
staged paths               0
Git-visible paths        637
package directories       14
package manifests         14
production PHP files     126
test PHP files           151
style-covered PHP        277
ADR files                 60
phase_4 dev-plan files     1
phase_4 task reports       1
```

Require one local branch, zero remotes, zero tags, and no repository-owned ref
other than `refs/heads/main`. Enumerate any app-managed
`refs/codex/turn-diffs/**` refs separately without locking their count or
mutating them.

The exact eight untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/architecture/developer-intelligence.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
```

The exact eighteen tracked modifications must be the authorized Phase 4A list
recorded in `Phase_4A.md`. Nothing is staged.

### 4.2 Completed Phase 4A caller records

Require:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md` | 52909 | `1BEDE36ED1B25324B9C82C945A0B228DEB7975E9A637DC911AAE23CE41FECF48` |
| `docs/task_reports/phase_4/Phase_4A.md` | 15610 | `EDFC3BD489C6AC078D1AAE31BD5EF46D616E2032F4FF23B089012858BF799202` |

Both remain ordinary, untracked, unstaged, absent from `HEAD`, and byte-
identical throughout Phase 4A.1.

### 4.3 Exact target starting identities

Require:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/architecture/developer-intelligence.md` | 9525 | `EE74AF0E004F946DB52B45A72C1F5E15BDA254C889A6FFF2D96AD873C1FB6992` |
| `docs/architecture/phase-4-roadmap.md` | 12947 | `99B1327D3C11713BF22FA12E653C0EFEE1B77076850401AA0E1246A6C64710D8` |

### 4.4 Locked current manifests

Use exactly this record format:

```text
path<TAB>byte_count<TAB>uppercase_SHA256<LF>
```

Use repository-relative forward-slash paths, exact primary file bytes, ordinal
case-sensitive sorting, UTF-8 without BOM, and one LF after every record.

The complete pre-correction Git-visible snapshot is:

```text
records                  637
manifest bytes         80058
manifest SHA-256       050E1B232548710AD8FAAC108313C2C770B6EFA4F65911B28FEF91CC71A5C7BB
aggregate file bytes  7157430
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

The protected snapshot formed by excluding exactly the two correction targets
is:

```text
records                  635
manifest bytes         79836
manifest SHA-256       0AFD304EDD9D6ACAF7B5AB25FF87CB19C089AD34D13E28BD1E9F8AE253B310ED
aggregate file bytes  7134958
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

The historical caller-record archive before Phase 4A.1 caller setup is:

```text
records                  220
manifest bytes         27587
manifest SHA-256       A8024335AAADA540D8FD314952E557E4C07F6BB263A3CC22C49D3697C6AA5343
aggregate file bytes  4535862
first path              docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path               docs/task_reports/phase_4/Phase_4A.md
```

After caller setup, exclude exactly the two Phase 4A.1 caller paths to
reconstruct these accepted manifests. The two target files are excluded only
from the protected snapshot, never from the complete starting snapshot.

### 4.5 Entry after Phase 4A.1 caller setup

After the caller adds only the authenticated correction directive and zero-byte
correction report, require:

```text
tracked modifications   18
untracked paths          10
total status paths       28
staged paths              0
Git-visible paths       639
tracked files           629
phase_4 dev-plan files    2
phase_4 task reports      2
```

Stop on a real identity, manifest, path, content, mode, stream, status, history,
index, or caller-record mismatch.

---

## 5. Exact authorized correction

Modify exactly these two existing untracked Phase 4A documentation files:

```text
docs/architecture/developer-intelligence.md
docs/architecture/phase-4-roadmap.md
```

Do not create or modify any other repository content except final population
of the active correction report.

### 5.1 Developer-intelligence dependency map

In `docs/architecture/developer-intelligence.md`, replace exactly this complete
block:

````markdown
## Fixed Phase 4 dependency map

```text
4A architecture and doctrine
 |
4B knowledge-source and provenance contract
 |
4C deterministic knowledge artifacts
 +--> 4D read-only Chie
 +--> 4E read-only Hantei
 +--> 4F Oshie
        |
      4G candidate patch proposals
        |
      4H cross-role external acceptance and checkpoint
```

The canonical boundaries and fixed order are defined in the
[Phase 4 roadmap](phase-4-roadmap.md). No subphase may outrun a decision or
deterministic foundation on which it depends.
````

with exactly:

````markdown
## Fixed Phase 4 dependency map

```text
4A architecture and doctrine
 |
4B knowledge-source and provenance contract
 |
4C deterministic knowledge artifacts
 |
4D read-only Chie
 |
4E read-only Hantei
 |
4F Oshie
 |
4G candidate patch proposals
 |
4H cross-role external acceptance and checkpoint
```

The diagram is the execution dependency order, not merely a list of roles.
Although Chie, Hantei, and Oshie consume the same future knowledge foundation,
their implementation subphases are not parallel: each phase must be accepted
before the next begins. The canonical boundaries and fixed order are defined in
the [Phase 4 roadmap](phase-4-roadmap.md).
````

The old block must occur exactly once before and zero times after. The new block
must occur zero times before and exactly once after.

### 5.2 Phase 4 roadmap dependency graph

In `docs/architecture/phase-4-roadmap.md`, replace exactly this complete block:

````markdown
## Dependency graph

```text
Phase 3 checkpoint
       |
      4A
       |
      4B
       |
      4C
   +---+---+
   |   |   |
  4D  4E  4F
   +---+---+
       |
      4G
       |
      4H
```

No subphase may outrun the decision, version identity, deterministic artifact,
authority, disclosure, provenance, or evidence boundary on which it depends.
````

with exactly:

````markdown
## Dependency graph

```text
Phase 3 checkpoint
       |
      4A
       |
      4B
       |
      4C
       |
      4D
       |
      4E
       |
      4F
       |
      4G
       |
      4H
```

This is a sequential execution dependency, not a parallel role fan-out. Each
subphase must be accepted before the next begins. No subphase may outrun the
decision, version identity, deterministic artifact, authority, disclosure,
provenance, or evidence boundary on which it depends.
````

The old block must occur exactly once before and zero times after. The new block
must occur zero times before and exactly once after.

### 5.3 Locked interpretation

The correction means only:

```text
4D accepted before 4E begins
4E accepted before 4F begins
4F accepted before 4G begins
```

It does not change:

- the fact that all three roles use the same future deterministic knowledge
  foundation;
- Chie's read-only 4D boundary;
- Hantei's independent read-only 4E boundary;
- Oshie's 4F teaching boundary;
- Phase 4G candidate proposals;
- Phase 4H external acceptance;
- auto-fix remaining outside Phase 4; or
- any ADR 0059/0060 rule.

---

## 6. Closed scope

Do not modify:

- ADR 0059 or ADR 0060;
- the original Phase 4A directive or completion report;
- any other documentation;
- any PHP source, test, fixture, package README, package manifest, root
  manifest, lockfile, dependency, or tool configuration;
- any package, API, schema, command, knowledge artifact, model, provider,
  retrieval mechanism, assistant runtime, tool authority, patch, or auto-fix;
- any Phase 4B work; or
- Git index, objects, commits, refs, history, branches, tags, remotes, hooks,
  signing, ignore files, attributes, or configuration.

No staging or commit is authorized.

---

## 7. File-quality contract

Both corrected documents and the final correction report must be:

- UTF-8 without BOM;
- LF-only;
- exactly one final LF;
- free of trailing whitespace and NUL;
- ordinary, non-reparse files with only the default data stream; and
- intended as Git mode `100644`.

All local Markdown links in both corrected documents must resolve. Require
`git diff --check` to pass.

---

## 8. Validation sequence

### Gate A - authentication and preservation

Before editing:

- authenticate this correction directive externally;
- verify the zero-byte active correction report;
- read every authority completely;
- verify Section 4 Git, caller, target, manifest, archive, path, mode, and stream
  evidence;
- verify the original Phase 4A directive and report remain exact;
- verify each old block occurs exactly once and each replacement occurs zero
  times; and
- verify no stage or Git mutation exists.

The completed Phase 4A report's canonical quality evidence is accepted as the
pre-correction baseline. Do not rerun pre-change `composer quality`.

### Gate B - exact two-block correction

Apply only the two exact whole-block replacements in Section 5. Do not
reformat, rewrap, normalize, or improve adjacent prose.

Then prove:

- each old block is absent;
- each new block occurs exactly once;
- both diagrams are sequential A through H;
- no `4C` fan-out or parallel `4D  4E  4F` depiction remains in either target;
- ADR 0060 and the roadmap tables remain byte-unchanged within their files
  outside the two replacement blocks;
- links and Section 7 file-quality checks pass; and
- `git diff --check` passes.

### Gate C - canonical final quality

Run literal:

```text
composer quality
```

Require:

```text
Composer validation      pass with only accepted warning categories
locked security audit    zero advisories
platform requirements    pass
PHP CS Fixer             0 of 277 files fixable
PHPStan                  zero errors
PHPUnit                  561 tests, 13004 assertions
```

If the audit fails solely because managed network/cache access is denied,
finish the failed command, prove repository and caller state unchanged, and run
the identical literal command once with approved access. No other retry,
skip, waiver, platform ignore, baseline, suppression, exclusion, memory change,
or count relaxation is authorized.

### Gate D - final preservation and report

Before report population prove:

- only the two target document byte sequences changed from the authenticated
  Phase 4A state;
- the 635-record protected manifest remains exact after excluding the two
  correction targets and the two Phase 4A.1 caller paths;
- all 220 prior caller records reproduce the locked archive manifest after
  excluding the two new caller records;
- the original Phase 4A report remains exact;
- no PHP, package, manifest, lockfile, tooling, dependency, test, runtime, ADR,
  or other documentation changed;
- Section 9 exact status and inventories match;
- the active correction report is still zero-byte; and
- no Git mutation or Phase 4B work occurred.

Only then populate the active correction report.

---

## 9. Exact expected final state

Before and after report population require:

```text
tracked modifications   18
untracked paths          10
total status paths       28
staged paths              0
Git-visible paths       639
tracked files           629
package directories      14
package manifests        14
production PHP files    126
test PHP files          151
style-covered PHP       277
ADR files                60
phase_4 dev-plan files    2
phase_4 task reports      2
```

The exact ten untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/architecture/developer-intelligence.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
```

The exact eighteen tracked modifications remain the original Phase 4A list.
The two correction targets remain untracked Phase 4A-created files, so their
content correction changes no status-path count.

---

## 10. Stop rules

Stop and report exact expected and observed values if:

- directive, report, caller, target, manifest, archive, Git, mode, or stream
  evidence differs;
- an old block is not exact or not unique;
- a replacement would require adjacent editing;
- another document would need correction;
- canonical quality has a genuine non-network failure;
- a protected path changes;
- final scope or counts differ; or
- any prohibited action would be required.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

A non-mutating checker or quoting defect may be simplified and rerun only after
proving repository state unchanged. No wording, formatting, or adjacent
correction authority exists beyond the two literal replacements.

---

## 11. Completion report

After every non-report gate passes, populate only:

```text
docs/task_reports/phase_4/Phase_4A_1_correction.md
```

Use exactly:

```text
# PHASE 4A.1 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. SEQUENTIAL DEPENDENCY CORRECTION
## 8. ARCHITECTURAL DECISIONS
## 9. DEVIATIONS FROM DIRECTIVE
## 10. DEPENDENCIES ADDED/REMOVED
## 11. ENVIRONMENT
## 12. COMMANDS RUN
## 13. VALIDATION RESULTS
## 14. DOCUMENTATION REVIEW
## 15. SECURITY AND FILE-SAFETY REVIEW
## 16. WARNINGS / KNOWN ISSUES
## 17. QUESTIONS / BLOCKERS
## 18. FINAL GIT STATUS
## 19. ACCEPTANCE CRITERIA
## 20. NEXT STEP
```

Report:

- independent correction-directive identity;
- original Phase 4A directive/report identities;
- exact entry manifests and caller state;
- the two old and new block occurrence proofs;
- exact two-document scope;
- confirmation that 4D, 4E, and 4F are sequential implementation subphases;
- confirmation that role responsibilities and shared-foundation relationships
  did not change;
- exact quality results;
- protected and historical manifest proof;
- exact final `18/10/28/0` state;
- no Git mutation or Phase 4B work;
- every acceptance criterion as PASS or FAIL; and
- final report byte count and SHA-256 returned independently outside the report
  body.

Its exact final sentence must be:

```text
Await architect review before Phase 4B.
```

---

## 12. Acceptance criteria

Phase 4A.1 is Complete only when all are true:

1. correction directive identity matched independently supplied values;
2. active correction report began zero-byte and remained so through all
   non-report gates;
3. completed Phase 4A directive/report identities matched;
4. Git, complete-manifest, protected-manifest, archive, target, mode, stream,
   and inventory entry evidence matched;
5. all required authorities were read completely;
6. exactly two existing documentation files were modified;
7. no other repository content changed except final correction-report
   population;
8. both old parallel diagrams are absent;
9. both new sequential diagrams occur exactly once;
10. the final execution order is exactly
    `4A -> 4B -> 4C -> 4D -> 4E -> 4F -> 4G -> 4H`;
11. current documents explicitly state each subphase must be accepted before
    the next begins;
12. ADR 0059, ADR 0060, role responsibilities, knowledge-foundation meaning,
    PHP policy, and every other Phase 4A decision remain unchanged;
13. links, UTF-8, line endings, final LF, whitespace, NUL, ordinary-file,
    reparse, stream, mode, and `git diff --check` gates pass;
14. literal `composer quality` passes with zero advisories, platform success,
    0/277 style findings, zero PHPStan errors, and 561 tests/13004 assertions;
15. the 635 protected paths and 220 prior caller records remain exact;
16. no PHP, package, manifest, lockfile, dependency, tool configuration, test,
    runtime, ADR, historical report, or other documentation changed;
17. no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
    ignore, attribute, or Git configuration mutation occurred;
18. final state is exactly `18/10/28/0`, 639 visible, and 629 tracked;
19. no Phase 4B or later work occurred; and
20. the populated report satisfies the exact heading, final-sentence, file-
    quality, and independent-identity contract.

Any failed criterion means status cannot be Complete.

---

## 13. Required final outcome

The Phase 4 architecture has one unambiguous execution dependency:

```text
4A -> 4B -> 4C -> 4D -> 4E -> 4F -> 4G -> 4H
```

The three roles still share one future deterministic knowledge foundation, but
their implementation subphases are not parallel. Every other accepted Phase 4A
decision and every protected repository byte remains unchanged.

Then await architect review before Phase 4B.
