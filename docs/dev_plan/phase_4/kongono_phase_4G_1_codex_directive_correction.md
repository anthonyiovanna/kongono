# CODEX CORRECTION DIRECTIVE — KONGONO PHASE 4G.1

## Diagnostic Test Boundary Reconciliation and Phase 4G Resume

## 1. Role and exact correction authority

You are the implementation executor for Kongono Phase 4G.1. You are not the
architect. This correction resolves one omitted existing test path and resumes
the already implemented Phase 4G validation chain.

Preserve every Phase 4G implementation byte except the one existing test file
explicitly authorized by Section 6. Do not restart, redesign, revert, clean,
stage, commit, or begin Phase 4H.

If an authenticated identity, manifest, path, or boundary differs:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Routine defects subsequently exposed inside the original Phase 4G Sections 19
and 20 boundary or the one newly authorized test path remain covered by Phase
4G Section 18.1. Use that bounded repair authority and rerun dependent gates.

---

## 2. Independent caller authentication

Correction directive path:

```text
docs/dev_plan/phase_4/kongono_phase_4G_1_codex_directive_correction.md
```

Active correction report path:

```text
docs/task_reports/phase_4/Phase_4G_1_correction.md
```

The architect will independently supply this correction directive's exact byte
count and SHA-256 outside this file. Do not self-authenticate local values.

Require the active correction report to be an ordinary, untracked, unstaged,
zero-byte file absent from `HEAD`, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Authenticate the original Phase 4G caller records:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/phase_4/kongono_phase_4G_codex_directive.md` | 42232 | `0ECAF8F14CDCA34582E1AD1C9AF79BCFE6787CF6BC83695953A07DB5BFE90C31` |
| `docs/task_reports/phase_4/Phase_4G.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

The original report remains zero-byte throughout Phase 4G.1. Populate only the
active correction report after all remaining non-report gates pass.

---

## 3. Accepted partial-state authentication

After the caller adds only this correction directive and its zero-byte report,
require:

```text
branch:                 main
HEAD:                   6aacafc92f07b567818173d660e2e182dfa83d23
HEAD tree:              d7fda17d27a15954cdd213a1c323aaeb37a2b574
HEAD parent:            647053d4bddc4c76090bca9bf3d693faa5880c52
commit count:           11
tracked modifications:  26
untracked paths:        207
total status paths:     233
staged paths:           0
Git-visible paths:      836
tracked files:          629
Phase 4 plan files:     25
Phase 4 report files:   25
```

The twenty Phase 4G created content paths and twenty-eight previously authorized
modified paths must remain present. Phase 4H must remain absent.

### 3.1 Correction-caller-excluded manifests

Use the original Phase 4G typed ordinal manifest algorithm and exact UTF-8/LF,
no-terminal-LF record serialization. Exclude only this Phase 4G.1 directive and
its active report. Require:

| Manifest | Records | Bytes | SHA-256 | Aggregate file bytes |
|---|---:|---:|---|---:|
| Complete partial state | 834 | 104158 | `E422E90BE596D36492AF33D716A97DFD2E29A7916CB8BB1A93A9F6B03A79FE55` | 8438538 |
| Protected, excluding all caller-directory paths | 568 | 70703 | `53BFA4BFEBF463B2A6CDE0FC302AF149BF40FEB753C24102883725BAB3FECFE2` | 3203760 |
| Historical callers | 266 | 33454 | `833382788BF28C9CAA22A712A45077ED530A1DE0A27AB9593CB245D9498CA94B` | 5234778 |

Endpoints must remain `.editorconfig` / `tests/Tooling/EnvironmentTest.php` for
the complete and protected manifests, and the Phase 0A directive /
`docs/task_reports/phase_4/Phase_4G.md` for the historical manifest.

### 3.2 Newly authorized test identity

Require exactly:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` | 11860 | `630E039FAE414D0B5976DC2D086FC2DC32EC46344DE33BD4DF3C4E3F03087931` |

No Phase 4G.1 production, documentation, dependency, configuration, or other
test change is authorized before a later bounded validation repair is actually
required by Phase 4G Section 18.1.

---

## 4. Accepted prior validation evidence

Accept and do not rerun these completed Phase 4G gates before the correction:

- directive and predecessor authentication;
- required authority reading;
- pre-change canonical quality;
- exact Phase 4G implementation and dependency generation;
- syntax for all 24 new or changed PHP files;
- focused PHPStan with zero errors; and
- focused style with 0 of 50 files fixable.

The previous focused PHPUnit run is failing evidence only:

```text
68 tests
8192 assertions
2 failures
3 errors
7 warnings
```

Do not treat those incidental counts as a new locked target. The failure is
caused by the authenticated existing test's ten-case expectations after the
authorized production catalog expanded to eighteen cases.

---

## 5. Exact decision

The omission of
`packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php` from original
Phase 4G Section 20 was an authenticated path-boundary defect.

Authorize that exact existing test as the twenty-ninth modified content path.
Reconcile it with the already required Phase 4G production contract. This does
not authorize any production change or a new architectural decision.

---

## 6. Exact one-file correction

Modify only:

```text
packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php
```

Make only the test changes needed to prove:

1. `ChieDiagnostic::cases()` contains exactly the ordered identifiers
   `KGN-CHIE-1001` through `KGN-CHIE-1018`.
2. The exact ordered titles match all eighteen current production cases.
3. The closed violation vocabulary for `1011` through `1018` is exactly:

```text
1011: target-path, target-sha, before-shape, after-shape
1012: spec-path, spec-read
1013: spec-path, spec-shape, before-shape, after-shape
1014: target-path, target-authority, target-missing
1015: target-sha
1016: match-count, source-limit
1017: diff-limit
1018: response-limit, proposal-shape
```

4. Every one of the eighteen contexts still has exactly one required Safe
   `violation` field and rejects every mismatched/unknown vocabulary token.
5. The exact exception/diagnostic pairs include:

```text
InvalidChieProposalException:
  InvalidProposalRequest
  ProposalSpecInvalid
  ProposalTargetUnsupported
  ProposalReplacementInvalid
  ProposalDiffLimitExceeded
  ProposalConstructionFailed

ProposalSpecUnavailableException:
  ProposalSpecUnavailable

ProposalTargetStaleException:
  ProposalTargetStale
```

6. Each new exception implements the package exception interface, retains its
   permitted native previous cause internally, exposes the correct fixed
   diagnostic identity, and does not disclose the native message or violation
   through stringification.
7. Every invalid diagnostic/type pairing is rejected through the existing
   fixed `InvalidChieValueException` behavior.
8. Existing `1001` through `1010` behavior and the fixed canonical error
   envelope tests remain unchanged.

Use production values as the contract under test. Do not weaken, skip,
conditionally branch, dynamically derive away the exact lists, or exclude the
test.

---

## 7. Resume validation workflow

After the one-file correction, run:

```text
php -l packages/chie/tests/Unit/Diagnostic/ChieDiagnosticTest.php
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/chie tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff --using-cache=no packages/chie/src packages/chie/tests packages/chie/bin/kongono-chie tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/chie/tests tests/Architecture/ApiStatusConventionTest.php
```

Require zero PHPStan errors, zero style findings, and all focused tests passing.
Do not pre-lock incidental test/assertion counts.

Then resume original Phase 4G at Gate E:

1. run the exact real candidate, reverse, stale/invalid/unsupported,
   non-disclosure, isolation, and cleanup proof in original Section 16.2;
2. run literal `composer hantei:verify` and preserve the distinction that PASS
   covers the unchanged repository, not the unapplied candidate;
3. run final literal `composer quality`, with only the original unchanged
   network/cache retry allowance;
4. complete all original Gate G preservation and manifest checks; and
5. populate only `Phase_4G_1_correction.md` after every non-report gate passes.

Original Phase 4G Section 18.1 continues to authorize ordinary repairs within
the now 20-created/29-modified boundary and dependent reruns. Do not request a
new correction for a routine in-boundary defect.

---

## 8. Prohibited scope

Do not:

- modify the original Phase 4G directive or zero-byte report;
- modify any path outside the original Phase 4G Sections 19/20 boundary plus
  the one test added by Section 6;
- add a file, declaration, diagnostic, exception, operation, schema, rule,
  status, dependency, target authority, suppression, baseline, ignore,
  exclusion, or weakened assertion;
- change Chie proposal semantics, Hantei/Oshie/Knowledge behavior, package
  direction, doctrine, security, or API status;
- apply a candidate, write a target, invoke Hantei from Chie, use AI/providers,
  access a network outside the exact Composer audit retry, or begin Phase 4H;
  or
- stage, commit, reset, restore, stash, checkout, clean, move, delete, alter
  refs, Git configuration, or history.

---

## 9. Acceptance criteria

Phase 4G.1 is complete only when:

1. Correction identity was independently authenticated.
2. Active correction report began ordinary, zero-byte, untracked, unstaged,
   absent from `HEAD`, and with the empty digest.
3. Original Phase 4G caller records remain exact.
4. Accepted partial state, manifests, Git identity, and one-file starting
   identity match.
5. Exactly the one new test path was added to the modification boundary.
6. All eighteen exact IDs, titles, vocabularies, contexts, and exception pairs
   are tested without weakening old behavior.
7. Syntax, focused PHPStan, focused style, and focused PHPUnit pass.
8. Every original Phase 4G real Chie proposal and Hantei proof passes.
9. Final literal `composer quality` passes fully.
10. Original Phase 4G created/modified boundaries plus this one path are exact.
11. Historical caller records and all other pre-existing paths remain
    byte-identical.
12. HEAD/tree/parent/branch/commit count/index semantics/staging/config/history
    remain unchanged; nothing is staged or committed.
13. Phase 4H remains absent and unstarted.
14. Only after criteria 1 through 13 pass, the active correction report is
    populated and its byte count and uppercase SHA-256 supplied externally.

---

## 10. Standard executor completion report

Populate `docs/task_reports/phase_4/Phase_4G_1_correction.md` with exactly:

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

Include correction/caller authentication, partial manifests, exact one-file
delta and final identity, focused results, every real proposal/Hantei proof,
final canonical quality, preservation evidence, final report byte/hash, and
the explicit fact that the candidate remained unapplied and unverified.

The final line before the report identity appendix must be exactly:

```text
Phase 4G is accepted only after architect review of this correction report; Phase 4H was not begun.
```

If a non-report gate fails, keep both Phase 4G reports zero-byte and emit the
standard blocked report in the Agent response.
