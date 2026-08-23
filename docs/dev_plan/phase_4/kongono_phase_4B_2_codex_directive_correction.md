# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4B.2

## No-Terminal-LF Manifest Scalar Correction and Phase 4B Resume

**Directive type:** authenticated exact correction and bounded resume  
**Repository:** `C:\Users\antho\Dev\kongono`  
**Phase corrected:** Phase 4B.1  
**Correction:** Phase 4B.2  
**Date:** 2026-08-22  
**Execution posture:** executor, not architect  

---

## 1. Purpose

Correct the four manifest scalars that Phase 4B.1 left inconsistent with its
explicit no-terminal-LF serialization rule, preserve the valid ordinal ordering
decision, and authorize resumption of the otherwise unchanged Phase 4B work.

Phase 4B.1 correctly required typed `System.String[]` paths ordered by
`System.StringComparer.Ordinal`. It also required manifest lines joined with one
LF between adjacent records and no terminal LF. Its complete and protected byte
counts and SHA-256 values, however, still represented serialization with one
additional terminal LF.

The blocked Agent execution correctly stopped before quality checks or mutation.

This directive makes no architecture decision and changes no Phase 4B content,
semantic, quality, security, or prohibited-scope requirement.

---

## 2. Independent authentication gate

Before reading this file beyond the minimum required to authenticate it, the
executor must receive from the architect, outside this file:

```text
expected byte count
expected SHA-256
```

Calculate both values independently and compare them exactly. The local file
must not authenticate itself.

If either value is absent or differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Do not begin quality checks, implementation, repair, cleanup, staging, commit,
or report population before authentication passes.

---

## 3. Binding authorities and precedence

After this directive authenticates, read completely:

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
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

Then read every additional authority required by the original Phase 4B
directive before modifying repository content.

The original Phase 4B and Phase 4B.1 directives remain binding except where
this directive explicitly supersedes them. This directive supersedes only:

1. Phase 4B.1 Section 5.3 complete-manifest byte count and SHA-256;
2. Phase 4B.1 Section 5.4 protected-manifest byte count and SHA-256;
3. caller/archive identities and entry/final counts affected solely by the
   Phase 4B.2 directive and report paths;
4. the active completion-report path and report title; and
5. references that identify Phase 4B.1 as the active correction.

All other original and Phase 4B.1 requirements remain exact, including:

- typed ordinal, case-sensitive path ordering;
- no terminal LF in any manifest;
- the exact two-created/fifteen-modified documentation boundary;
- every accepted target identity;
- all Phase 4B semantic requirements and exact counts;
- both canonical quality gates;
- all focused documentation and file-safety gates;
- all current, accepted, planned, and deferred distinctions;
- the complete no-runtime/no-schema/no-package/no-mechanism boundary; and
- the prohibition on staging, commits, or Git-history mutation.

Do not edit, normalize, rename, move, replace, or regenerate any directive or
historical report.

---

## 4. Exact caller records

Require these immutable directive identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md` | 50718 | `92F11560F90EB17488C2CCB9FB2EF1C7E036AD2B0555BAC0D2368E16EA1C48A0` |
| `docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md` | 23062 | `40C41B3923F8E51D96F65AE591FA47A18480C6C8E0FCDC2971EA0FB0BCED329E` |

Require both prior blocked reports to remain ordinary, untracked, unstaged,
zero-byte files absent from `HEAD`:

```text
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
```

Each must have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The active report must begin with the same zero-byte identity and remain
untracked, unstaged, and absent from `HEAD`:

```text
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

Keep all three reports zero-byte through every non-report gate. Populate only
the Phase 4B.2 report after every other criterion passes.

---

## 5. Canonical manifest construction

### 5.1 Path universe

Use only Git-visible paths equivalent to:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Use repository-relative `/` separators. Exclude ignored files, `.git`
internals, directory entries, alternate streams, and ambient files.

### 5.2 Typed ordinal order

Every path collection must be a typed `System.String[]` and must be ordered
with `System.StringComparer.Ordinal`:

```powershell
[string[]] $paths = @($candidatePaths)
[Array]::Sort($paths, [StringComparer]::Ordinal)
```

Do not use an untyped array, `Sort-Object`, culture-sensitive order,
case-folding, filesystem enumeration order, or locale-dependent comparison.

### 5.3 Exact record and serialization

For each ordered path, construct exactly:

```text
<repository-relative-path><TAB><physical-file-byte-count><TAB><uppercase-SHA-256>
```

Serialize using the semantic equivalent of:

```powershell
$serialized = [string]::Join("`n", [string[]] $lines.ToArray())
$manifestBytes = [Text.UTF8Encoding]::new($false).GetBytes($serialized)
```

The manifest has:

- one LF byte only between adjacent records;
- no CR byte;
- no BOM;
- no leading LF;
- no terminal LF; and
- no extra empty record.

Prove independently that the final manifest byte is not `0A`.

Do not use a serializer whose collection coercion, pipeline output, or newline
behavior is ambiguous.

---

## 6. Corrected manifests

### 6.1 Complete frozen baseline

Exclude exactly these six caller paths from the current Git-visible universe:

```text
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/dev_plan/phase_4/kongono_phase_4B_1_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_2_codex_directive_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

Require exactly:

```text
records:              639
manifest bytes:       80321
SHA-256:              A3124F71F4B2FB8C6403047ED7A424D6D2B15688624BB0AE5450EA0E383E95F5
aggregate file bytes: 7189092
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
terminal LF:          absent
```

These byte and SHA-256 values replace Phase 4B.1 Section 5.3 values `80322`
and `4A3B533B7F2A7C02ED76F81D61737F4FB915BE2261E51B02F7F807D338E9C4C6`.

### 6.2 Protected frozen baseline

From the same universe, exclude:

1. all six caller paths in Section 6.1; and
2. all seventeen original Phase 4B authorized content paths: the two created
   and fifteen modified paths listed in original Section 15.

Require exactly:

```text
records:              624
manifest bytes:       78901
SHA-256:              507C003107644F3CEA1C279CC74FD8120CA540886CEDD451874508038535D28F
aggregate file bytes: 6870035
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
terminal LF:          absent
```

These byte and SHA-256 values replace Phase 4B.1 Section 5.4 values `78902`
and `EBD7EF3591EA88DD06E2273B7463787E0C997F701F9A13EAFA8DAA7885C8D860`.

Reproduce this exact protected manifest after implementation and before report
population using the same exclusions.

### 6.3 Historical caller archive

Exclude only the active Phase 4B.2 directive/report pair from the historical
caller-path universe. Include the immutable original Phase 4B and Phase 4B.1
caller pairs.

Require exactly:

```text
records:              226
manifest bytes:       28344
SHA-256:              1857BE08672CE804F28D79B3F2E07B55D9EF9216EB18C4CDB8C079D70F816B0B
aggregate file bytes: 4641038
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_4/Phase_4B_1_correction.md
terminal LF:          absent
```

Do not modify any of these 226 records.

### 6.4 Rejected manifest values

The following values are not authority:

```text
Original culture-sensitive complete SHA-256:
FEB57DC62600E2AA499D76A52226CBC8C0A5EC7E59CC6389F1FE5CABD73080E2

Original culture-sensitive protected SHA-256:
F68C10E148CD5003C7A94686403A5AC9D97542EC1ABE855A109EDB525D024F09

Phase 4B.1 terminal-LF complete SHA-256:
4A3B533B7F2A7C02ED76F81D61737F4FB915BE2261E51B02F7F807D338E9C4C6

Phase 4B.1 terminal-LF protected SHA-256:
EBD7EF3591EA88DD06E2273B7463787E0C997F701F9A13EAFA8DAA7885C8D860
```

Do not change ordering or manifest termination to reproduce a rejected value.
Do not change repository bytes, paths, or caller records to force a digest.

---

## 7. Exact corrected entry state

After the caller places this authenticated directive and creates the zero-byte
Phase 4B.2 report, but before implementation mutation, require:

```text
branch                 main
HEAD                   6aacafc92f07b567818173d660e2e182dfa83d23
tracked modifications  18
untracked paths         16
total status paths      34
staged paths             0
Git-visible paths      645
tracked files          629
package directories     14
package manifests       14
production PHP files   126
test PHP files         151
style-covered PHP      277
ADR files               60
phase_4 dev-plan files   5
phase_4 task reports     5
```

Also require:

- both original Phase 4B-created paths remain absent;
- all fifteen target identities still match original Section 15.3;
- all three Phase 4B-family reports are zero-byte;
- the complete, protected, and historical manifests in Section 6 match;
- the semantic index equals `HEAD` and no path is staged;
- no Phase 4B content implementation exists; and
- every other original Phase 4B entry invariant matches.

If any condition differs, stop without repair or adoption.

---

## 8. Authorized content boundary

Create exactly:

```text
docs/adr/0061-versioned-knowledge-source-and-provenance-contract.md
docs/architecture/knowledge-foundation.md
```

Modify exactly:

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

After all non-report gates pass, populate only:

```text
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

The caller-supplied Phase 4B.2 directive is not an executor-created content
mutation. No other path may be changed in any way.

---

## 9. Resume gates

### Gate A — authenticate and prove entry

1. Authenticate this directive.
2. Verify every Section 4 caller record.
3. Verify the exact Section 7 entry state.
4. Reconstruct all three Section 6 manifests with the exact Section 5
   serializer.
5. Prove no manifest has a terminal LF.
6. Read all binding authorities and every additional original Phase 4B
   authority completely.
7. Complete every original Phase 4B preflight not superseded here.

The blocked reports are evidence, not substitutes for current verification.

### Gate B — pre-change canonical quality

Run literal `composer quality` under the original Phase 4B network/cache retry
rule. Require exactly:

```text
security advisories       0
platform requirements     pass
PHP CS Fixer              0 of 277 files fixable
PHPStan                   zero errors
PHPUnit                   561 tests, 13004 assertions
```

Do not weaken, skip, rename, or substitute a quality stage.

### Gate C — original Phase 4B content work

Execute the original Phase 4B documentation implementation exactly. Create the
two and modify the fifteen paths in Section 8. Satisfy every original semantic,
count, current/planned/deferred, security, disclosure, and Phase 4C handoff
requirement.

This correction adds no content discretion and authorizes no runtime behavior,
schema, package, mechanism, or later-phase implementation.

### Gate D — focused and canonical validation

Run every original Phase 4B focused documentation, link, terminology,
disclosure, encoding, newline, whitespace, NUL, ordinary-file, reparse,
alternate-stream, mode, Git, and preservation gate.

Run literal final `composer quality` under the original retry rule and require
the exact Gate B results.

### Gate E — final preservation and report

Before report population prove:

- exactly two authorized content paths were created;
- exactly fifteen authorized existing content paths were modified;
- the corrected 624-record protected manifest reproduces exactly;
- all 226 historical caller records reproduce exactly;
- the two prior blocked reports remain zero-byte;
- the active report remains zero-byte;
- all non-authorized content remains unchanged;
- no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git-configuration mutation occurred;
- Section 10 final state is exact; and
- every original Phase 4B non-report criterion, as corrected by Phase 4B.1 and
  this directive, passes.

Only then populate the active report.

---

## 10. Exact final state

Before and after report population require:

```text
tracked modifications   18
untracked paths          18
total status paths       36
staged paths              0
Git-visible paths       647
tracked files           629
package directories      14
package manifests        14
production PHP files    126
test PHP files          151
style-covered PHP       277
ADR files                61
phase_4 dev-plan files    5
phase_4 task reports      5
```

The exact eighteen untracked paths are:

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
docs/dev_plan/phase_4/kongono_phase_4B_2_codex_directive_correction.md
docs/dev_plan/phase_4/kongono_phase_4B_codex_directive.md
docs/engineering/php-support-policy.md
docs/task_reports/phase_3/Phase_3H_1_correction.md
docs/task_reports/phase_4/Phase_4A.md
docs/task_reports/phase_4/Phase_4A_1_correction.md
docs/task_reports/phase_4/Phase_4B.md
docs/task_reports/phase_4/Phase_4B_1_correction.md
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

The original Phase 4B and Phase 4B.1 reports remain zero-byte. Phase 4B.2 is the
sole populated Phase 4B completion report.

No staging or commit is authorized.

---

## 11. Stop rules

Stop immediately if:

- directive authentication fails;
- any caller record differs;
- a manifest uses anything except the exact typed ordinal, no-terminal-LF
  contract;
- any Section 6 scalar, endpoint, or terminal-byte proof differs;
- entry state or a starting target identity differs;
- either created content path already exists at entry;
- canonical quality fails except for the original narrowly authorized managed
  network/cache retry;
- the implementation would exceed the seventeen-document content boundary;
- an original semantic requirement is ambiguous;
- a prohibited artifact, schema, mechanism, package, runtime, or later-phase
  capability would be required;
- preservation or final state differs; or
- any stage, object, commit, ref, history, branch, tag, remote, hook, signing,
  ignore, attribute, or Git-configuration mutation occurs.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

The original narrow permission for ordinary documentation corrections within
the exact content boundary remains binding. The original non-mutating checker,
parser, or quoting-defect rule also remains binding.

---

## 12. Phase 4B.2 correction task completion report

After every non-report gate passes, populate only:

```text
docs/task_reports/phase_4/Phase_4B_2_correction.md
```

Use exactly:

```text
# PHASE 4B.2 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. MANIFEST SERIALIZATION CORRECTION
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

The report must include:

- independent Phase 4B.2 authentication;
- immutable identities for both prior directives and zero-byte reports;
- exact typed ordinal/no-terminal-LF manifest construction evidence;
- explicit rejection of culture-sensitive and terminal-LF digests;
- complete, protected, and 226-record historical proofs;
- exact two-created and fifteen-modified inventories;
- all original Phase 4B semantic results;
- exact pre-change and final quality evidence;
- proof no artifact, schema, mechanism, package, API, assistant runtime,
  workspace reader, Composer change, or runtime behavior was introduced;
- exact final `18/18/36/0` state, 647 visible, and 629 tracked;
- proof of no staging, commit, ref, history, or Git-configuration mutation;
- every Section 13 criterion as PASS or FAIL; and
- its byte count and SHA-256 returned independently outside the report body.

Its exact final sentence must be:

```text
Await architect review before Phase 4C.
```

---

## 13. Acceptance criteria

Phase 4B.2 is Complete only when all are true:

1. this directive matched independently supplied byte and SHA-256 values;
2. both prior directives matched their exact identities;
3. both prior reports remained immutable zero-byte blocked records;
4. the active report began zero-byte and remained so through all non-report
   gates;
5. exact entry state matched;
6. complete manifest matched all seven Section 6.1 values;
7. protected manifest matched all seven Section 6.2 values;
8. historical manifest matched all seven Section 6.3 values;
9. every manifest used typed ordinal paths, exact UTF-8 records, inter-record LF,
   and no terminal LF;
10. no rejected culture-sensitive or terminal-LF digest was adopted;
11. all remaining original Phase 4B authority and preflight gates passed;
12. pre-change literal `composer quality` passed with zero advisories, platform
    success, 0/277 style findings, zero PHPStan errors, and 561 tests/13004
    assertions;
13. exactly two authorized documentation paths were created;
14. exactly fifteen authorized existing documentation paths were modified;
15. no other content changed except active report population;
16. every original Phase 4B semantic and exact-count requirement passed;
17. ADR 0061 is Accepted and correctly dated;
18. current documentation accurately separates implemented, accepted, planned,
    and deferred behavior;
19. no knowledge artifact, schema, vocabulary, identifier, digest contract,
    generator, validator, source inventory, command, package, API, Graph export,
    retrieval engine, model, provider, assistant runtime, workspace reader,
    Composer change, or runtime behavior was introduced;
20. every original focused documentation, link, terminology, disclosure,
    encoding, newline, whitespace, NUL, ordinary-file, reparse, stream, mode,
    and `git diff --check` gate passed;
21. final literal `composer quality` passed with the exact pre-change results;
22. all 624 protected paths and 226 historical caller records remained exact;
23. all PHP, packages, manifests, lockfile, dependencies, tool configuration,
    tests, fixtures, runtime, and non-authorized documentation stayed unchanged;
24. no stage, object, commit, ref, history, branch, tag, remote, hook, signing,
    ignore, attribute, or Git-configuration mutation occurred;
25. final state is exactly `18/18/36/0`, 647 visible, and 629 tracked;
26. no Phase 4C or later implementation occurred; and
27. the active report satisfies its exact title, headings, evidence,
    final-sentence, file-quality, and independent-identity contract.

Any failed criterion means status cannot be Complete.

---

## 14. Required final outcome

Kongono retains one consistent manifest contract: typed ordinal,
case-sensitive path ordering; exact UTF-8 records; one LF only between adjacent
records; and no terminal LF. No repository content or caller record is changed
to fit a digest.

Phase 4B then completes its original documentation-only outcome: one Accepted,
versioned, inspectable, AI-independent knowledge-source and provenance
architecture with explicit authority roles, immutable source-state binding,
terminating provenance, visible conflict and stale-state handling, version-
matched citation, disclosure rules, and separation of framework knowledge from
workspace observation.

No knowledge artifact, schema, package, generator, validator, source inventory,
digest contract, command, index, retrieval engine, model, provider, workspace
reader, assistant, Composer change, API, or runtime behavior exists because of
Phase 4B, Phase 4B.1, or Phase 4B.2.

Then await architect review before Phase 4C.
