# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4B.1

## Ordinal Manifest Authentication Correction and Phase 4B Resume

**Directive type:** authenticated narrow correction and bounded resume  
**Repository:** `C:\Users\antho\Dev\kongono`  
**Phase corrected:** Phase 4B  
**Correction:** Phase 4B.1  
**Date:** 2026-08-22  
**Execution posture:** executor, not architect  

---

## 1. Purpose

Correct exactly two invalid locked manifest SHA-256 values in the authenticated
Phase 4B directive, make the required ordinal ordering procedure mechanically
unambiguous, and authorize the executor to resume and complete the otherwise
unchanged Phase 4B documentation-only boundary.

The blocked Phase 4B execution correctly established that the original complete
and protected manifest digests were produced by culture-sensitive ordering even
though Section 5.3 required ordinal, case-sensitive ordering. The historical
manifest used the required ordering and remains correct.

This correction does not change Phase 4B architecture, doctrine, file content
requirements, production behavior, future scope, or quality standards. It does
not authorize editing the original directive or its blocked zero-byte report.

---

## 2. Independent authentication gate

Before reading this file beyond the minimum needed to authenticate it, the
executor must receive from the architect, outside this file:

```text
expected byte count
expected SHA-256
```

The executor must calculate this file's byte count and SHA-256 independently
and compare both values exactly.

If either value is absent or differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

The local file must not authenticate itself. Do not begin quality checks,
implementation, staging, report population, repair, or cleanup before this gate
passes.

---

## 3. Binding authority and precedence

After this correction authenticates, read completely:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

Then read every additional authority required by the original Phase 4B
directive before changing repository content.

The original Phase 4B directive remains binding in full except for the explicit
corrections in this file. This file has precedence only where the two directives
conflict.

The following original Phase 4B requirements are superseded:

1. the complete-manifest SHA-256 in Section 5.3;
2. the protected-manifest SHA-256 in Section 5.4;
3. entry and final counts affected solely by the two Phase 4B.1 caller paths;
4. the active completion-report path and report title; and
5. references that treat only the original Phase 4B caller pair as excluded or
   active.

Everything else remains exact, including:

- the Phase 4B goal and semantic contract;
- all source-authority, source-state, provenance, claim, conflict,
  supersession, citation, version, disclosure, workspace-separation, and
  Phase 4C handoff requirements;
- exactly two created documentation paths;
- exactly fifteen modified documentation paths;
- every accepted starting file identity;
- all current/planned/deferred distinctions;
- the no-runtime, no-schema, no-package, and no-mechanism boundary;
- both canonical quality gates;
- all security and file-safety checks; and
- the prohibition on staging, committing, or changing Git history.

Do not edit, normalize, rename, move, replace, or regenerate either directive.

---

## 4. Caller-record identities

The original Phase 4B directive is an immutable caller record:

```text
Path:
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md

Bytes:
50718

SHA-256:
92F11560F90EB17488C2CCB9FB2EF1C7E036AD2B0555BAC0D2368E16EA1C48A0
```

The original blocked Phase 4B report must remain an ordinary, untracked,
unstaged, zero-byte file absent from `HEAD`:

```text
Path:
docs/task_reports/phase_4/Phase_4B.md

Bytes:
0

SHA-256:
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The active correction report must begin as an ordinary, untracked, unstaged,
zero-byte file absent from `HEAD`:

```text
Path:
docs/task_reports/phase_4/Phase_4B_1_correction.md

Bytes:
0

SHA-256:
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep both report files zero-byte through every non-report gate. Populate only
the Phase 4B.1 correction report after every other criterion passes.

---

## 5. Exact corrected manifest rule

### 5.1 Required path universe

Use only Git-visible repository paths returned by the equivalent of:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Normalize path separators to `/` if necessary. Do not include ignored files,
`.git` internals, alternate streams, directory entries, or ambient files.

### 5.2 Required ordering

Every manifest path collection must be a typed `System.String[]` and must be
sorted with `System.StringComparer.Ordinal`.

The required PowerShell shape is:

```powershell
[string[]] $paths = @($candidatePaths)
[Array]::Sort($paths, [StringComparer]::Ordinal)
```

An untyped array, current-culture sort, invariant-culture sort,
`Sort-Object`, case-folded order, filesystem enumeration order, or locale-
dependent comparison is not accepted.

For each sorted path, construct exactly one line:

```text
<repository-relative-path><TAB><file-byte-count><TAB><uppercase-SHA-256>
```

Join lines with one LF byte between adjacent lines and no terminal LF. Hash the
UTF-8 bytes without BOM. Sum the physical bytes of the represented files for
the aggregate file-byte value.

### 5.3 Complete frozen baseline

Reconstruct the pre-Phase-4B content baseline by excluding exactly these four
caller-record paths from the current Git-visible universe:

```text
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

Require exactly:

```text
records:              639
manifest bytes:       80322
SHA-256:              4A3B533B7F2A7C02ED76F81D61737F4FB915BE2261E51B02F7F807D338E9C4C6
aggregate file bytes: 7189092
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

This SHA-256 replaces only the complete-manifest SHA-256 supplied by the
original Phase 4B Section 5.3. The other scalar evidence was already correct.

### 5.4 Protected frozen baseline

From the same Git-visible universe, exclude:

1. the four caller-record paths in Section 5.3; and
2. all seventeen original Phase 4B authorized content paths: the two created
   paths and the fifteen modified paths listed exactly in original Section 15.

Require exactly:

```text
records:              624
manifest bytes:       78902
SHA-256:              EBD7EF3591EA88DD06E2273B7463787E0C997F701F9A13EAFA8DAA7885C8D860
aggregate file bytes: 6870035
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

This SHA-256 replaces only the protected-manifest SHA-256 supplied by the
original Phase 4B Section 5.4. The other scalar evidence was already correct.

The protected manifest must reproduce exactly after implementation and before
report population by applying the same exclusions to the final worktree.

### 5.5 Historical caller archive

Exclude the active Phase 4B.1 directive and correction report, but include the
immutable original Phase 4B directive and its zero-byte blocked report.

The exact accepted historical archive is:

```text
records:              224
manifest bytes:       28084
SHA-256:              2A779A542CC5F0595D4AE10D6A48C6932294677E0A8541EB22DBAA8159B27613
aggregate file bytes: 4617976
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_4/Phase_4B.md
```

This is the original 222-record archive plus the authenticated original Phase
4B caller pair. Do not modify any of those 224 records.

### 5.6 Rejected hashes

The following original values are known culture-sensitive artifacts and are
not authority:

```text
complete:  FEB57DC62600E2AA499D76A52226CBC8C0A5EC7E59CC6389F1FE5CABD73080E2
protected: F68C10E148CD5003C7A94686403A5AC9D97542EC1ABE855A109EDB525D024F09
```

Do not adopt culture-sensitive ordering to reproduce them. Do not edit a file,
change a path, or reconstruct caller records to force either digest.

The original historical SHA-256 remains valid under ordinal ordering. Section
5.5 provides its correction-era extension.

---

## 6. Corrected entry state

After the caller has placed this authenticated directive and created the
zero-byte Phase 4B.1 report, but before any implementation mutation, require:

```text
branch                 main
HEAD                   6aacafc92f07b567818173d660e2e182dfa83d23
tracked modifications  18
untracked paths         14
total status paths      32
staged paths             0
Git-visible paths      643
tracked files          629
package directories     14
package manifests       14
production PHP files   126
test PHP files         151
style-covered PHP      277
ADR files               60
phase_4 dev-plan files   4
phase_4 task reports     4
```

Also require:

- the two Phase 4B-created content paths are absent;
- all fifteen original target identities match original Section 15.3;
- the original Phase 4B report and active correction report are both zero-byte;
- the semantic index equals `HEAD` and no path is staged;
- no Phase 4B implementation mutation exists;
- all other original Phase 4B entry and repository invariants match; and
- the complete, protected, and historical manifests in Section 5 match.

If any condition differs, stop without repair.

---

## 7. Exact authorized content boundary

Create exactly the original two paths:

```text
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/knowledge-foundation.md
```

Modify exactly the original fifteen paths:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/adr/README.md
docs/architecture/developer-intelligence.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/phase-4-roadmap.md
docs/engineering/error-handling.md
docs/engineering/testing.md
```

Populate only this report after all non-report gates pass:

```text
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

The correction directive is caller-supplied and is not an executor-created
content mutation.

No other path may be created, modified, deleted, renamed, moved, normalized,
restored, regenerated, staged, or committed.

---

## 8. Resume procedure

### Gate A — authenticate and reconcile

1. Authenticate this correction independently.
2. Verify Section 4 caller records.
3. Verify the exact Section 6 entry state.
4. Reconstruct all three Section 5 manifests using typed ordinal arrays.
5. Read every binding authority completely.
6. Complete every original Phase 4B Gate A check not already evidenced by the
   blocked report.

Do not rely only on the prior blocked report. It is evidence of the mismatch,
not a substitute for current read-only verification.

### Gate B — pre-change canonical quality

Run the original literal pre-change `composer quality` gate. Preserve its exact
network/cache retry rule. Require:

```text
security advisories       0
platform requirements     pass
PHP CS Fixer              0 of 277 files fixable
PHPStan                   zero errors
PHPUnit                   561 tests, 13004 assertions
```

Do not weaken, skip, rename, or substitute any quality stage.

### Gate C — original Phase 4B implementation

Execute the original Phase 4B Gate B and Gate C requirements exactly within the
seventeen-document content boundary.

ADR 0061, `knowledge-foundation.md`, all required reconciliations, all semantic
counts, all prohibitions, every current/planned/deferred distinction, and the
Phase 4C handoff remain unchanged from the authenticated original directive.

This correction does not authorize an artifact, schema, machine vocabulary,
identifier, digest contract, generator, validator, source inventory, command,
package, API, Application Graph export, retrieval engine, model, provider,
assistant runtime, workspace reader, Composer change, or runtime behavior.

### Gate D — final validation

Run every original focused documentation, link, terminology, disclosure,
encoding, newline, whitespace, ordinary-file, reparse-point, alternate-stream,
mode, Git, and preservation proof.

Run the original literal final `composer quality` gate and require the exact
Gate B result. The original final network/cache retry rule remains binding.

### Gate E — preservation and report

Before report population prove:

- exactly two authorized documentation paths were created;
- exactly fifteen authorized existing documentation paths were modified;
- every other repository content path is unchanged;
- the corrected 624-record protected manifest reproduces exactly;
- all 224 historical caller records reproduce exactly;
- the original Phase 4B report remains the authenticated zero-byte record;
- the active correction report remains zero-byte;
- no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git configuration mutation occurred;
- the exact final state in Section 9 is present; and
- every original Phase 4B non-report acceptance criterion, as corrected here,
  passes.

Only then populate the Phase 4B.1 correction report.

---

## 9. Exact expected final state

Before and after active report population require:

```text
tracked modifications   18
untracked paths          16
total status paths       34
staged paths              0
Git-visible paths       645
tracked files           629
package directories      14
package manifests        14
production PHP files    126
test PHP files          151
style-covered PHP       277
ADR files                61
phase_4 dev-plan files    4
phase_4 task reports      4
```

The exact sixteen untracked paths are:

```text
docs/adr/0059-rolling-current-minor-php-baseline.md
docs/adr/0060-human-centered-developer-intelligence-family.md
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/developer-intelligence.md
docs/architecture/knowledge-foundation.md
docs/architecture/phase-4-roadmap.md
docs/dev_plan/phase_4/kongono_phase_4A_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4A_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

The original Phase 4B report remains zero-byte. The Phase 4B.1 correction
report is the sole populated Phase 4B completion record.

No staging or commit is authorized.

---

## 10. Stop rules

Stop immediately if:

- this correction does not authenticate;
- any caller record differs;
- any Section 5 manifest scalar or endpoint differs;
- an untyped or non-ordinal manifest procedure would be required;
- the Section 6 entry state differs;
- an original target identity differs;
- either created content path already exists at entry;
- pre-change or final canonical quality fails, except for the original narrowly
  authorized managed network/cache retry;
- the implementation would leave the exact seventeen-document boundary;
- a required semantic decision is ambiguous;
- a prohibited schema, mechanism, runtime, package, or later-phase capability
  would be required;
- preservation or final-state evidence differs; or
- any stage, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git configuration mutation occurs.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Within the original seventeen-document content boundary, the original
directive's permission for ordinary link, Markdown, spelling, terminology, and
internal-consistency corrections remains unchanged.

A non-mutating checker, parser, or quoting defect may be simplified and rerun
only under the original rule and only after proving repository state unchanged.

---

## 11. Phase 4B.1 correction task completion report

After every non-report gate passes, populate only:

```text
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

Use exactly these headings:

```text
# PHASE 4B.1 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. MANIFEST ORDERING CORRECTION
## 6. FILES CREATED
## 7. FILES MODIFIED
## 8. SOURCE AUTHORITY CLASSES
## 9. SOURCE-STATE IDENTITY
## 10. PROVENANCE AND CLAIM CLASSES
## 11. CONFLICT AND SUPERSESSION
## 12. CITATION AND VERSION MATCHING
## 13. FRAMEWORK AND WORKSPACE SEPARATION
## 14. PHASE 4C HANDOFF
## 15. ARCHITECTURAL DECISIONS
## 16. DEVIATIONS FROM DIRECTIVE
## 17. DEPENDENCIES ADDED/REMOVED
## 18. ENVIRONMENT
## 19. COMMANDS RUN
## 20. VALIDATION RESULTS
## 21. DOCUMENTATION REVIEW
## 22. SECURITY AND FILE-SAFETY REVIEW
## 23. WARNINGS / KNOWN ISSUES
## 24. QUESTIONS / BLOCKERS
## 25. FINAL GIT STATUS
## 26. ACCEPTANCE CRITERIA
## 27. NEXT STEP
```

The report must state explicitly:

- this correction authenticated independently;
- the original Phase 4B directive and zero-byte report stayed immutable;
- typed `System.String[]` plus `StringComparer.Ordinal` produced the corrected
  complete and protected digests;
- culture-sensitive ordering was not adopted;
- the complete, protected, and historical manifest proofs;
- exact two-created and fifteen-modified content inventories;
- all original Phase 4B semantic results;
- exact pre-change and final quality results;
- proof that no schema, mechanism, artifact, package, API, assistant runtime,
  workspace reader, Composer change, or runtime behavior was implemented;
- exact final `18/16/34/0` state, 645 visible paths, and 629 tracked files;
- proof of zero staging, commit, ref, history, or Git-configuration mutation;
- every criterion in Section 12 as PASS or FAIL; and
- its final byte count and SHA-256 returned independently outside its body.

Its exact final sentence must be:

```text
Await architect review before Phase 4C.
```

---

## 12. Acceptance criteria

Phase 4B.1 is Complete only when all are true:

1. this correction matched independently supplied byte and SHA-256 values;
2. the original Phase 4B directive matched 50,718 bytes and its locked SHA-256;
3. the original Phase 4B report remained the immutable zero-byte blocked record;
4. the active correction report began zero-byte and stayed zero-byte through
   every non-report gate;
5. the exact correction entry state matched;
6. the typed ordinal complete manifest matched all six corrected scalars;
7. the typed ordinal protected manifest matched all six corrected scalars;
8. the typed ordinal 224-record historical archive matched all six scalars;
9. no culture-sensitive or untyped ordering was used as authority;
10. all remaining original Phase 4B Gate A and authority checks passed;
11. pre-change literal `composer quality` passed with zero advisories, platform
    success, 0/277 style findings, zero PHPStan errors, and 561 tests/13004
    assertions;
12. exactly two authorized documentation paths were created;
13. exactly fifteen authorized existing documentation paths were modified;
14. no other repository content changed except final correction-report
    population;
15. all original Phase 4B source-authority, source-state, provenance, claim,
    conflict, supersession, citation, version, disclosure, workspace-separation,
    and Phase 4C handoff requirements passed;
16. ADR 0061 is Accepted, correctly dated, and within the original semantic
    boundary;
17. current documentation accurately distinguishes implemented, accepted,
    planned, and deferred behavior;
18. no knowledge artifact, schema, machine vocabulary, identifier, digest
    contract, generator, validator, source inventory, command, package, API,
    Graph export, retrieval engine, model, provider, assistant runtime,
    workspace reader, Composer change, or runtime behavior was introduced;
19. every original focused documentation, link, terminology, disclosure,
    encoding, newline, whitespace, NUL, ordinary-file, reparse, stream, mode,
    and `git diff --check` gate passed;
20. final literal `composer quality` passed with the same exact results;
21. the 624 protected paths and 224 prior caller records remained exact;
22. all PHP, packages, manifests, lockfile, dependencies, tool configuration,
    tests, fixtures, runtime, historical records, and non-authorized
    documentation remained unchanged;
23. no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
    ignore, attribute, or Git configuration mutation occurred;
24. final state is exactly `18/16/34/0`, 645 visible, and 629 tracked;
25. no Phase 4C or later implementation occurred; and
26. the populated correction report satisfies its exact heading, evidence,
    final-sentence, file-quality, and independent-identity contract.

Any failed criterion means status cannot be Complete.

---

## 13. Required final outcome

The two erroneous manifest hashes are corrected without weakening Kongono's
ordinal, case-sensitive authentication rule or changing any accepted repository
bytes merely to fit a digest.

Phase 4B then completes exactly its original documentation-only purpose:
Kongono has one Accepted, versioned, inspectable, AI-independent knowledge-
source and provenance architecture. Sources retain distinct authority roles;
claims bind to exact immutable source states; provenance terminates in
appropriate evidence; conflicts, stale data, mixed versions, incomplete proof,
and disclosure uncertainty fail visibly; framework truth remains separate from
workspace observation; and derived knowledge never becomes its own authority.

No knowledge artifact, schema, package, generator, validator, source inventory,
digest contract, command, index, retrieval engine, model, provider, workspace
reader, assistant, Composer change, API, or runtime behavior exists because of
Phase 4B or Phase 4B.1.

Then await architect review before Phase 4C.
