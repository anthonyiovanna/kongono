# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0Y.1 CORRECTION

## Projected Diagnostic Prose Reconciliation

**Directive status:** LOCKED for execution
**Phase type:** Exact documentation correction only
**Architect:** ChatGPT
**Executor:** Codex
**Repository:** `C:\Users\antho\Dev\kongono`
**Date:** 2026-08-14

---

## 1. Correction objective

Phase 0Y correctly selected the planned trusted-developer HTML diagnostic page,
but three authorized documents retain five stale uses of `catalog title` or
`catalog remediation` inside descriptions of the projected presentation model.

Those phrases are too narrow. Current Kongono failures obtain title and
remediation from package-owned catalogs, but the implemented Experimental
projection boundary also accepts structurally valid consumer-defined marker
metadata and permits direct construction of a valid
`TrustedDeveloperDiagnostic`. ADRs 0034 and 0035 explicitly state that valid
consumer metadata does not become a Kongono-owned catalog assignment.

The Phase 0Y directive therefore selected the display value as the exact
**projected** remediation, not necessarily a Kongono catalog remediation. The
same distinction applies to the title stored in the projected model.

Phase 0Y.1 must make only five literal prose replacements across exactly three
existing documents. It must not alter the accepted renderer architecture,
runtime code, tests, dependencies, tooling, Phase 0Y report, or any other file.

---

## 2. Authority and mandatory stop rule

Read completely, in order:

1. `AGENTS.md`;
2. this Phase 0Y.1 correction directive;
3. `docs/dev_plan/kongono_phase_0Y_codex_directive.md`;
4. `docs/task reports/Phase_0Y.md`;
5. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
6. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
7. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
8. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`; and
9. `docs/engineering/error-handling.md`.

The executor is not authorized to interpret, improve, reflow, or expand the
replacement text. If any old block is absent, duplicated, differently wrapped,
or otherwise not byte-for-byte available as written in Section 5:

```text
STOP
ASK
DO NOT GUESS
```

Do not approximate the replacement, repair neighboring prose, or substitute a
different term.

Before mutation, compare this directive's byte count and SHA-256 with the
architect-delivered values supplied outside the file. If either is absent or
different, stop. The local file may not authenticate itself.

The correction report must begin as an ordinary, untracked, unstaged, zero-byte
file absent from HEAD:

```text
docs/task reports/Phase_0Y_1_correction.md
```

It may be populated only after every correction and validation gate passes.

---

## 3. Locked pre-correction state

Before caller setup, the accepted live state is:

```text
branch                     main
local branches             1
HEAD                       7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                  b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                75749a37a184996203e569e039bddbc3ec10cdf7
commit count               6
remotes                    0
tags                       0
tracked modifications      30
untracked paths            30
total status paths         60
staged paths               0
tracked diff               30 files, 829 insertions, 186 deletions
```

After the caller copies this directive and creates the zero-byte correction
report, the expected pre-mutation counts are:

```text
tracked modifications      30
untracked paths            32
total status paths         62
staged paths               0
```

The only caller-added paths must be:

```text
docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md
docs/task reports/Phase_0Y_1_correction.md
```

The protected executable/tooling inventory remains exactly 138 files with
aggregate SHA-256:

```text
3B45B766429127708C1D1609C428CA7E73DE9E91E3E3065F2D98FD3975A0AEE9
```

`composer.lock` remains exactly:

```text
bytes       180654
SHA-256     FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387
```

The Phase 0Y records are locked at:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_0Y_codex_directive.md` | 49492 | `21E8232B0E305DC03618F3EEA2EC8E02BEE42093B0D1A7355AD92EEA8FFEBD00` |
| `docs/task reports/Phase_0Y.md` | 19190 | `C8DC0AEAB1E2A3D6688FDECD207B98645871F05180965440D2B65DEF0F4A720A` |
| `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md` | 14436 | `412FE7491AA11F2BE18331E3EEA7EBFFAC0A2D63C48F11298355FBFAEE0A2CD6` |

The three correction targets must begin at:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md` | 23517 | `B881686245A68E1AC58FDB976583FD6A4E626752C8720BEB43F47D46C60A15DC` |
| `docs/adr/0034-trusted-developer-diagnostic-projection.md` | 12480 | `430A9040D6121D09D8885A56F4AF55128B3E00E0C71BB157D9641D742FC35ABD` |
| `docs/engineering/error-handling.md` | 61481 | `31E60AEAF950068F58AE442B484C8ED4EEDC1A9210421B5B4F306FEBA0374FB5` |

Stop before mutation on any mismatch. Do not reset, restore, checkout, stash,
clean, stage, commit, amend, or otherwise manufacture the baseline.

---

## 4. Exact authorized file boundary

Modify exactly:

```text
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/engineering/error-handling.md
```

After successful validation, populate exactly:

```text
docs/task reports/Phase_0Y_1_correction.md
```

No other existing or new path may change. In particular, do not modify the
Phase 0Y directive, Phase 0Y report, ADR 0035, ADR 0036, any PHP file, test,
manifest, lockfile, tooling configuration, or other documentation.

---

## 5. Exact literal replacements

Each old block must occur exactly once before replacement. Each new block must
occur exactly once afterward. Preserve all surrounding bytes.

### 5.1 ADR 0026 information architecture

In `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`, replace:

```text
How to fix it          catalog remediation
```

with:

```text
How to fix it          projected remediation
```

### 5.2 ADR 0034 model description

In `docs/adr/0034-trusted-developer-diagnostic-projection.md`, replace:

```text
One `TrustedDeveloperDiagnostic` contains only the derived exact owner;
stable identifier; precise catalog title; fixed context operation; fixed
violated invariant or expectation; catalog remediation; and context fields in
canonical schema order. One `TrustedDeveloperDiagnosticField` contains
```

with:

```text
One `TrustedDeveloperDiagnostic` contains only the derived exact owner;
stable identifier; precise projected title; fixed context operation; fixed
violated invariant or expectation; projected remediation; and context fields in
canonical schema order. One `TrustedDeveloperDiagnosticField` contains
```

### 5.3 ADR 0034 information architecture

In the same ADR, replace:

```text
How to fix it          catalog remediation
```

with:

```text
How to fix it          projected remediation
```

### 5.4 Error-handling model description

In `docs/engineering/error-handling.md`, replace:

```text
One `TrustedDeveloperDiagnostic` contains only the exact derived owner;
stable identifier; catalog title; fixed context operation; fixed violated
invariant or expectation; catalog remediation; and fields in canonical schema
order. One `TrustedDeveloperDiagnosticField` contains only canonical
```

with:

```text
One `TrustedDeveloperDiagnostic` contains only the exact derived owner;
stable identifier; projected title; fixed context operation; fixed violated
invariant or expectation; projected remediation; and fields in canonical schema
order. One `TrustedDeveloperDiagnosticField` contains only canonical
```

### 5.5 Error-handling information architecture

In the same guide, replace:

```text
How to fix it          catalog remediation
```

with:

```text
How to fix it          projected remediation
```

Do not change `catalog` where it truthfully describes the 42 package-owned
diagnostic catalogs, their exact titles/remediations, catalog tests, or catalog
mapping. This correction applies only to the five presentation-model phrases
above.

---

## 6. Meaning preserved by the correction

After correction, the documents must remain explicit that:

- current Kongono Kernel, Graph, and DI diagnostic titles/remediations are
  package-owned catalog values;
- a valid projected title/remediation is not necessarily a Kongono-owned catalog
  assignment;
- the renderer will display only the exact title/remediation already stored in
  `TrustedDeveloperDiagnostic`;
- the renderer performs no lookup, catalog validation, enrichment, inference,
  reclassification, or authorization;
- the planned renderer remains unimplemented;
- ADR 0036 and all Phase 0Y architecture remain unchanged;
- Safe and Protected disclosure, output escaping, accessibility, bounds,
  determinism, trust separation, and production separation remain unchanged;
  and
- no runtime behavior or API status changes.

Do not add a new explanation, note, ADR amendment, test, or API. The exact word
changes are sufficient.

---

## 7. Validation sequence

### Gate A: authority, directive identity, and baseline

1. Read every authority in Section 2 completely.
2. Match this correction directive to the externally supplied byte count and
   SHA-256.
3. Match the zero-byte correction report:

```text
bytes       0
SHA-256     E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

4. Match every repository, protected, lockfile, Phase 0Y record, and target-file
   identity in Section 3.
5. Verify exactly 30 modified, 32 untracked, 62 total, and zero staged paths.
6. Stop on any mismatch.

### Gate B: literal correction

1. Verify each Section 5 old block occurs exactly once in its specified file.
2. Apply exactly the five replacements.
3. Verify each old block is absent.
4. Verify each new block occurs exactly once in its specified location.
5. Verify no other byte range or path changed during Phase 0Y.1.

### Gate C: focused validation

Verify:

- `catalog remediation` is absent from the three Phase 0Y information-
  architecture blocks;
- `catalog title` and `catalog remediation` are absent from the two trusted
  projected-model descriptions;
- `projected title` and `projected remediation` occur in the exact replacement
  locations;
- other truthful package-catalog language remains present and unchanged;
- ADR 0035 and ADR 0036 remain byte-identical to Section 3;
- all local Markdown links in the three targets still resolve;
- each target is strict UTF-8 without BOM, LF-only, one final LF, no NUL, and no
  trailing whitespace;
- `git diff --check` passes;
- the 138 protected-file count and digest remain exact;
- `composer.lock`, package/manifests, production PHP, and test PHP identities and
  counts remain unchanged;
- Git status contains exactly the expected 62 paths, zero staged paths, and no
  ref/history/mode/stream/reparse changes; and
- the Phase 0Y directive and report remain byte-identical.

Do **not** run `composer quality`, focused PHPUnit, PHPStan, PHP CS Fixer, or a
Composer update/audit command in this correction. Phase 0Y canonical quality was
already independently reverified after the accepted executable digest, and
Phase 0Y.1 changes only five literal Markdown phrases. Preserve and cite that
accepted evidence; do not create fresh network/cache/tool side effects for a
check that does not validate these prose replacements.

### Gate D: correction report

Only after Gates A through C pass, populate
`docs/task reports/Phase_0Y_1_correction.md` using Section 9. Verify its strict
text properties, compute its final byte count and uppercase SHA-256 after the
last write, and return those values outside the report body.

If any gate fails, leave the report zero bytes and return a blocked report in
the executor response.

---

## 8. Prohibited scope and final state

Do not:

- modify wording beyond the five exact replacements;
- modify or recreate Phase 0Y historical records;
- change ADR 0035 or ADR 0036;
- change code, tests, fixtures, dependencies, manifests, lockfile, tooling,
  package topology, API status, schemas, identifiers, or runtime behavior;
- implement the renderer, HTML, CSS, template, trusted activation, HTTP adapter,
  production page, provider, logging, telemetry, CLI, frontend, or later work;
- run formatter or bulk normalization over the documentation;
- stage, commit, reset, restore, checkout, clean, stash, amend, rebase, tag,
  branch, add a remote, push, or rewrite history; or
- begin Phase 0Z.

The successful final Git state must be:

```text
branch                     main
local branches             1
HEAD                       7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree                  b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent                75749a37a184996203e569e039bddbc3ec10cdf7
commit count               6
remotes                    0
tags                       0
tracked modifications      30
untracked paths            32
total status paths         62
staged paths               0
```

Only the two caller records increase the accepted Phase 0Y path counts. All
three correction targets were already modified or untracked, so their status
classification does not change.

---

## 9. Correction completion report

Use exactly:

```text
# PHASE 0Y.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. COMMANDS RUN
## 11. VALIDATION RESULTS
## 12. DOCUMENTATION REVIEW
## 13. WARNINGS / KNOWN ISSUES
## 14. QUESTIONS / BLOCKERS
## 15. GIT DIFF SUMMARY
## 16. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must:

- state `Complete` only if every gate passed;
- identify the correction directive's externally confirmed identity;
- record the zero-byte starting report and locked Phase 0Y records;
- list no created implementation/documentation file; the report is the only
  newly populated caller record;
- list the three modified documents and exact phrase corrections;
- state that no executor-authored architecture decision or dependency change
  occurred;
- list exact material read-only and replacement operations;
- state explicitly that canonical PHP/Composer quality was not rerun because
  this directive prohibited it, while preserving the accepted Phase 0Y evidence;
- report exact phrase occurrence checks, links, encoding, protected digest,
  lockfile identity, and Git state;
- preserve the original Phase 0Y report as historical evidence and identify this
  report as the superseding correction evidence; and
- end with exactly:

```text
Await architect review before Phase 0Z.
```

Do not put the correction report's final byte count or SHA-256 inside its body.
Return them separately after final read-back.

---

## 10. Acceptance criteria

Phase 0Y.1 is complete only if:

1. the correction directive and caller records match independently supplied
   identities;
2. the exact pre-correction repository and target identities match;
3. exactly the five Section 5 replacements are applied;
4. exactly the three authorized existing documents change;
5. projected-model prose uses `projected title` and `projected remediation`;
6. truthful package-catalog language outside the five blocks remains unchanged;
7. ADR 0035, ADR 0036, Phase 0Y directive, and Phase 0Y report remain
   byte-identical;
8. protected executable/tooling identities and lockfile remain exact;
9. focused links, encoding, whitespace, ordinary-file, mode, stream, and Git
   validations pass;
10. no prohibited quality, Composer mutation, Git mutation, implementation, or
    adjacent documentation change occurs;
11. final status is exactly 30 modified, 32 untracked, 62 total, and zero
    staged with unchanged HEAD and refs; and
12. the correction report is complete, structurally exact, externally
    identified, and ends with the required next step.

Do not begin Phase 0Z.

