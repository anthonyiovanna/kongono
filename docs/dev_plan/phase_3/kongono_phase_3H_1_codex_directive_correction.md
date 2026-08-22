# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3H.1
## Effective External Raw-Query Boundary and Checkpoint Reconciliation

**Directive date:** 2026-08-22

**Repository:** `C:\Users\antho\Dev\kongono`

**Execution role:** executor, not architect

**Scope:** Phase 3H.1 correction and resumed Phase 3H completion only

**Checkpoint:** preserve the Phase 3H checkpoint requirement; one exact local
commit, no tag, remote, or push

---

## 1. Mission

Correct one impossible external raw-query boundary in the authenticated Phase
3H directive, preserve every frozen production byte, resume the original Phase
3H implementation at its authorized boundary, complete every focused,
external, canonical, documentation, safety, checkpoint, and post-commit gate,
and populate only the Phase 3H.1 correction report after complete success.

The original directive required an externally accepted 8,192-byte raw query.
That is impossible under the accepted combined grammar:

- `REQUEST_URI` is an origin-form target and must begin with `/`;
- a nonempty query embedded in that target requires the separator `?`;
- the embedded query must equal `QUERY_STRING`; and
- the complete request target may contain at most 8,192 bytes.

Therefore the maximum externally representable nonempty raw query is 8,190
bytes: two bytes are consumed by `/?`. This correction changes no production
limit or semantic. It makes the external proof accurately exercise the frozen
composition of the two accepted limits.

Do not begin Phase 4.

---

## 2. Authority and authentication

### 2.1 Read completely before validation or mutation

Read these exact files completely:

```text
AGENTS.md
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
.php-cs-fixer.dist.php

docs/doctrine.md
docs/documentation-standard.md
docs/architecture/phase-3-roadmap.md
docs/architecture/php-cgi-fastcgi-profile.md
docs/engineering/php-cgi-deployment.md

docs/adr/0051-phase-3-production-http-integration-milestone.md
docs/adr/0052-php-cgi-fastcgi-responder-profile.md
docs/adr/0053-bounded-php-cgi-inbound-conversion.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md
docs/adr/0056-php-cgi-production-delivery-and-entry-composition.md
docs/adr/0057-bounded-php-cgi-front-controller-lifecycle-and-deployment-constraints.md

packages/php-cgi/src/Inbound/CgiServerRequestConverter.php
packages/php-cgi/src/Internal/CgiQueryParser.php
packages/php-cgi/src/Internal/CgiRequestValidation.php
packages/php-cgi/tests/Unit/Inbound/CgiServerRequestConverterTest.php

docs/dev_plan/phase_3/kongono_phase_3G_2_codex_directive_correction.md
docs/task_reports/phase_3/Phase_3G_2_correction.md
docs/dev_plan/phase_3/kongono_phase_3H_codex_directive.md
docs/task_reports/phase_3/Phase_3H.md
```

Then read this correction directive completely. Every authority incorporated by
the original Phase 3H directive remains binding. Do not substitute a similarly
named or numbered path.

### 2.2 Independent correction authentication

The architect must supply this correction directive's exact byte count and
uppercase SHA-256 outside the file. The caller places it at exactly:

```text
docs/dev_plan/phase_3/kongono_phase_3H_1_codex_directive_correction.md
```

The caller also creates exactly this ordinary zero-byte placeholder:

```text
docs/task_reports/phase_3/Phase_3H_1_correction.md
```

Require the report to be untracked, unstaged, absent from `HEAD`, and to have
the empty-file SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Do not treat the local correction directive as self-authenticating. On an
absent path, absent external identity, or mismatch:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT RUN QUALITY OR MUTATE THE REPOSITORY
```

### 2.3 Locked original caller identities

Require:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/phase_3/kongono_phase_3H_codex_directive.md` | 60,197 | `F87D21BA2374B0101F538E075B62DEEE5B4FBD2E69A7ED350CFBC27D695FB42F` |
| `docs/task_reports/phase_3/Phase_3H.md` | 0 | `E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855` |

The original directive remains immutable historical authority except where
this correction explicitly supersedes it. The original Phase 3H report remains
zero bytes permanently and becomes committed historical evidence at the Phase
3 checkpoint. Do not populate, rename, delete, or replace it.

---

## 3. Accepted blocked execution and frozen entry state

### 3.1 Accepted execution evidence

Accept the stopped Phase 3H execution as evidence that:

- the original directive identity passed;
- all 79 original authorities were read completely;
- every original Section 3 repository, history, caller, manifest, index,
  lockfile, and PHP-CGI identity passed;
- the first `composer quality` attempt failed solely because the managed
  environment could not access Composer cache and Packagist;
- the exact directive-authorized retry passed with zero advisories, zero style
  findings across 266 files, zero PHPStan errors, and 560 tests with 11,878
  assertions;
- `C:\php\php-cgi.exe` matched the authenticated PHP 8.5.9 `cgi-fcgi` tool and
  its help confirmed `-b` listener support;
- two defective read-only custom PowerShell probes produced no accepted
  evidence and no mutation; corrected smaller read-only probes subsequently
  matched the locked baseline; and
- no implementation, staging, commit, report population, index change,
  history change, or caller-record mutation occurred.

The two read-only probe defects must be reported truthfully as procedural
deviations in the final correction report. They do not authorize a repository
change and do not invalidate the accepted independent baseline evidence.

Do not rerun pre-change `composer quality`. The original pre-change quality
gate is complete. Every original pre-stage and post-commit focused, external,
and canonical validation gate remains mandatory.

### 3.2 Frozen state before the Phase 3H.1 caller pair

Require:

```text
branch:                       main
local branches:                 1
HEAD:                         647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                         3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                       d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:                  10
remotes:                        0
tags:                           0
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               264
total status paths:            479
staged paths:                    0
tracked index paths:           536
physical Git-visible files:    615
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                140
style-covered PHP files:       266
docs/dev_plan/phase_3 files:     15
docs/task_reports/phase_3 files: 15
```

The semantic index equals `HEAD`. Existing `refs/codex/**` application-managed
refs are permitted and must not be deleted or rewritten. Other than
`refs/heads/main`, require no branch, remote, tag, or unexpected ref.

The root lockfile remains:

```text
bytes:   197527
SHA-256: 823D849823E7403BD5F45C3DD85BAC17974D2FC66D004D454D9D06FE5AA1EC84
```

The tracked binary diff remains:

```text
bytes:   4045448
SHA-256: 001A25245612770640C314C14ED856D55E907123F119C707B9401DC9BAC05837
```

### 3.3 Frozen physical, status, untracked, and archive manifests

Use the exact serialization and ordinal rules from original Phase 3H Sections
3.2 through 3.4.

Before adding the Phase 3H.1 caller pair, the physical manifest is:

```text
records:               615
manifest bytes:      77436
manifest SHA-256:    805B4817F333A29D5711A8156C3EAFAD5F73FACE7722AA604DC2AC0085E2A9AB
aggregate file bytes: 6862416
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

The complete serialized status-record manifest is:

```text
records:        479
manifest bytes: 47087
manifest SHA-256: 58E7E27A7863CF49EC8C3628347E0DF101E51067A4CB2FF9BA984E7C61ADBB3E
first record:    <space>D<TAB>docs/dev_plan/kongono_phase_0A_codex_directive.md<TAB>-<TAB>-
last path:       packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
```

The untracked physical-file manifest is:

```text
records:               264
manifest bytes:      33191
manifest SHA-256:    7450F27EFBAD85E3026DA9E497003116F7CF6904F6508385CF51DEC05A8F2ED4
aggregate file bytes: 4771385
first path:           docs/adr/0051-phase-3-production-http-integration-milestone.md
last path:            packages/php-cgi/tests/Unit/Internal/CgiTrustedPresentationPermissionEvaluatorTest.php
```

The numbered directive/report archive is:

```text
records:               216
manifest bytes:      27085
manifest SHA-256:    9971B05D448AF496A11B740DE8F050A2BA1D8A1A50D6BEFA3F615CFFC634A472
aggregate file bytes: 4421931
first path:           docs/dev_plan/phase_0/kongono_phase_0A_codex_directive.md
last path:            docs/task_reports/phase_3/Phase_3H.md
```

The original 580-record protected manifest from Phase 3H remains exact:

```text
records:               580
manifest bytes:      73626
manifest SHA-256:    3AF017E4591B444AF7B8468843E401B09BB5A9A0FEA00005A5C4ACEF3A8207AA
aggregate file bytes: 6236666
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

When verifying these frozen manifests after the caller adds the Phase 3H.1
directive and report, exclude exactly those two caller paths. Do not derive a
new accepted baseline from local bytes.

### 3.4 Entry state with the Phase 3H.1 caller pair

Before implementation require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               266
total status paths:            481
staged paths:                    0
physical Git-visible files:    617
tracked index paths:           536
docs/dev_plan/phase_3 files:     16
docs/task_reports/phase_3 files: 16
```

The only additions relative to Section 3.2 are the authenticated correction
directive and its exact zero-byte active report. If any invariant differs,
stop before implementation. Do not repair, restore, truncate, rename, stage,
commit, or adopt a local mismatch.

---

## 4. Exact semantic correction

### 4.1 Superseded raw-query row

Original Phase 3H Section 8.4 contains this impossible row:

```text
| raw query bytes | 8,191 | 8,192 | 8,193 |
```

It is superseded in full by exactly:

```text
| effective externally representable raw query bytes | 8,189 | 8,190 | 8,191 |
```

Interpret the three columns as:

| Query bytes | Target bytes using `/?<query>` | Expected outcome |
| ---: | ---: | --- |
| 8,189 | 8,191 | accepted |
| 8,190 | 8,192 | accepted boundary |
| 8,191 | 8,193 | rejected as `target_too_long` |

The plus-one case proves the effective external composition boundary. It does
not prove the intrinsic `QUERY_STRING` check fired, because the complete target
ceiling rejects first.

### 4.2 Exact valid query construction

For each length `N` in `8189`, `8190`, and `8191`, construct exactly:

```text
a=<4096 literal v bytes>&b=<(N - 4101) literal v bytes>
```

This produces:

| N | First value | Second value | Total query bytes |
| ---: | ---: | ---: | ---: |
| 8,189 | 4,096 | 4,088 | 8,189 |
| 8,190 | 4,096 | 4,089 | 8,190 |
| 8,191 | 4,096 | 4,090 | 8,191 |

The two keys are unique, every value remains within the accepted 4,096-byte
decoded-value ceiling, pair count remains two, all bytes satisfy the flat query
grammar, and no other request limit causes the accepted cases to fail.

Set both:

```text
QUERY_STRING=<constructed-query>
REQUEST_URI=/?<constructed-query>
```

Do not use one oversized key, one oversized value, invalid percent encoding,
duplicate keys, nesting, an empty field, or another failure to simulate the
raw-query boundary.

### 4.3 Boundaries that do not change

Preserve without modification:

- the independent request-target triad of 8,191 / 8,192 / 8,193 bytes;
- the production target ceiling of 8,192 bytes;
- the production intrinsic query-string ceiling of 8,192 bytes;
- exact target/query equality;
- all query key, value, pair, encoding, UTF-8, control, nesting, and duplicate
  rules;
- every other Phase 3H request and response boundary triad;
- every production source and package manifest byte; and
- every original prohibited-scope rule.

Do not change production to make an 8,191- or 8,192-byte query that passes the
isolated query-length check externally representable under the current
origin-form target contract. Do not claim that Phase 3H externally proves
isolated acceptance at the intrinsic 8,192-byte query ceiling. Existing
package-level source and tests remain the authority for the intrinsic check.

For original Phase 3H response-boundary rows, `distinct response fields` and
`total response field name/value bytes` continue to count application-provided
fields at production preflight. The external CGI response must additionally
contain exactly one adapter-computed `Content-Length`; an optional CGI `Status`
line is parsed as status metadata rather than an application response field.
This clarifies external observation of the unchanged rows and changes no
response limit.

### 4.4 Documentation language

ADR 0058, the external-acceptance guide, the Phase 3 roadmap, the PHP-CGI
profile/deployment documentation, and the final report must distinguish:

- intrinsic `QUERY_STRING` validation: 8,192-byte source ceiling; and
- effective externally representable query: 8,190-byte maximum when the same
  query is embedded in an origin-form target capped at 8,192 bytes.

The effective maximum is intentional composition evidence for the frozen
profile, not a production defect, silent truncation, parser workaround, or new
framework semantic.

---

## 5. Resumption authority

After Sections 2 through 4 pass:

1. preserve the completed original authority, baseline, host-tool, and
   pre-change quality evidence from Section 3.1;
2. resume original Phase 3H at Section 5, before creating any implementation
   path;
3. implement exactly the original thirteen paths and modify exactly the
   original 33 existing paths;
4. apply the corrected raw-query matrix from this directive everywhere the
   original row governs;
5. run every original focused, external, canonical, documentation, safety,
   cleanup, pre-stage, checkpoint, and post-commit gate; and
6. populate only the active Phase 3H.1 correction report after every non-report
   post-commit gate passes.

All original architecture, API, process, environment, FastCGI, trust,
disclosure, cleanup, retry, documentation, prohibited-scope, commit-subject,
identity, and stop rules remain binding unless this correction explicitly
supersedes them.

The original Phase 3H report stays zero bytes. The correction report must
include the blocked execution, the reason for the corrected external boundary,
the exact valid query construction, and the final observed results.

---

## 6. Reconciled pre-stage and checkpoint inventories

### 6.1 Final pre-stage state

After implementation and every pre-stage gate require:

```text
modified tracked paths:        30
deleted tracked paths:         185
untracked paths:               279
total status paths:            494
staged paths:                    0
physical Git-visible files:    630
tracked index paths:           536
package directories:            14
package manifests:              14
production PHP files:          126
test PHP files:                151
style-covered PHP files:       277
docs/dev_plan/phase_3 files:     16
docs/task_reports/phase_3 files: 16
```

Serialize every final status path alone as `normalized-path<LF>`, sort with
`[StringComparer]::Ordinal`, and encode strict UTF-8 without BOM. Require:

```text
records: 494
bytes:   24941
SHA-256: 4AA3B6EFB882F2D77D144053D4A4B54956D01060B0A8C47947F1A5400D59953F
first:   .php-cs-fixer.dist.php
last:    tests/External/PhpCgi/run.php
```

The exact set is the authenticated 479 starting status paths, the two Phase
3H.1 caller records, and the thirteen original Phase 3H implementation paths.
No other path may appear. Reconstruct the exact protected manifest after
excluding both Phase 3H caller pairs and the thirteen implementation paths.
The active correction report remains zero bytes.

### 6.2 Exact staging set

Exclude exactly:

```text
docs/task_reports/phase_3/Phase_3H_1_correction.md
```

The remaining path-only staging manifest must be:

```text
records: 493
bytes:   24890
SHA-256: EEDFE2B4C5F8AC74C437FD9363DA3DCC750930AC680C41B0383CA297F51C693D
first:   .php-cs-fixer.dist.php
last:    tests/External/PhpCgi/run.php
```

Materialize those exact 493 normalized paths into a PowerShell string array
from the authenticated 494-path manifest. Never use a wildcard, directory
path, glob, `git add -A`, `git add .`, shell expansion, or unauthenticated path.
Use the original pathspec-safe staging and managed-filesystem retry rules.

Require:

```text
staged paths:                  493
staged modified paths:         30
staged added paths:            278
staged deleted paths:          185
unstaged tracked paths:          0
untracked paths:                 1
index entries:                 629
index modes other than 100644:   0
```

The sole untracked path is the zero-byte Phase 3H.1 correction report. The
original Phase 3H directive and empty report, this correction directive, and
every earlier accepted caller record are staged. The active correction report
is not staged.

Require exact staged/worktree blob equality, deletion absence, no conflict
stage, exact path/mode evidence, and `git diff --cached --check` success.

### 6.3 Tree and commit reconciliation

The authenticated tree contains exactly 629 paths, all mode `100644`. Its delta
from parent `647053d4bddc4c76090bca9bf3d693faa5880c52` is exactly:

```text
paths:     493
modified:   30
added:     278
deleted:   185
```

Use the original `write-tree` semantic proof and cache-tree rule. Create one
normal non-amending commit with the unchanged exact subject:

```text
Complete Kongono Phase 3 production HTTP integration milestone
```

Use the unchanged effective identity:

```text
Anthony Iovanna <anthonyiovanna@gmail.com>
```

Require one parent, the proven tree, commit count eleven, the exact 493-path
delta, only `main`, no remote/tag/unexpected ref, and no rewrite of
`refs/codex/**`. Do not amend, reset, restore, rebuild, or otherwise alter the
commit after creation.

### 6.4 Post-commit state

Before active report population require:

```text
tracked files:             629
tracked modifications:       0
staged paths:                 0
untracked paths:              1
total status paths:           1
physical Git-visible files: 630
```

The sole path is the exact zero-byte Phase 3H.1 correction report. The original
Phase 3H report is an exact zero-byte blob in `HEAD`.

Rerun every original Phase 3H post-commit gate in its exact order, substituting
only the corrected matrix and reconciled inventory values from this directive.
If a post-commit gate fails, preserve the commit, keep the correction report
zero bytes, and request the smallest authenticated correction. Do not amend,
reset, revert, or populate the report.

---

## 7. Active correction report

Only after every post-commit non-report gate passes, populate:

```text
docs/task_reports/phase_3/Phase_3H_1_correction.md
```

Use exactly:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. EFFECTIVE QUERY BOUNDARY CORRECTION
## 8. EXTERNAL ACCEPTANCE ARCHITECTURE
## 9. EXTERNAL ACCEPTANCE RESULTS
## 10. ARCHITECTURAL DECISIONS
## 11. DEVIATIONS FROM DIRECTIVE
## 12. DEPENDENCIES ADDED/REMOVED
## 13. ENVIRONMENT
## 14. COMMANDS RUN
## 15. FOCUSED VALIDATION RESULTS
## 16. CANONICAL QUALITY RESULTS
## 17. DOCUMENTATION REVIEW
## 18. SECURITY AND FILE-SAFETY REVIEW
## 19. STAGING EVIDENCE
## 20. COMMIT EVIDENCE
## 21. POST-COMMIT VALIDATION RESULTS
## 22. WARNINGS / KNOWN ISSUES
## 23. QUESTIONS / BLOCKERS
## 24. FINAL GIT STATUS
## 25. ACCEPTANCE CRITERIA
## 26. NEXT STEP
```

In addition to every original reporting requirement, include:

- the original and correction directive identities;
- exact proof that both reports stayed zero until all non-report gates passed;
- the frozen source identities and the effective 8,189 / 8,190 / 8,191 proof;
- the exact two-field query construction and plus-one `target_too_long`
  classification;
- the accepted pre-change quality evidence and both read-only probe deviations;
- all corrected inventory, staging, tree, commit, and post-commit evidence; and
- an explicit statement that no production byte or intrinsic limit changed.

`NEXT STEP` must be exactly:

```text
Phase 3 is complete. Await architect direction for Phase 4; do not begin it automatically.
```

After population, verify the report is strict UTF-8 without BOM, LF-only,
final-LF, no trailing whitespace, no NUL, ordinary, default-stream-only,
non-reparse, untracked, unstaged, and absent from `HEAD`. Rerun final Git,
history, report-only status, environment, process, listener, socket, journal,
and temp cleanup proofs.

Return the report's final byte count and uppercase SHA-256 outside its body. Do
not embed a self-referential digest.

---

## 8. Prohibited scope

In addition to every original Phase 3H prohibition, do not:

- modify `CgiRequestValidation`, `CgiQueryParser`, the converter, a production
  PHP file, package manifest, or existing package test;
- change the 8,192-byte target or intrinsic query ceiling;
- separate the target and query contract in production;
- accept a target over 8,192 bytes;
- truncate, omit, normalize, or mismatch the embedded query;
- misclassify the external plus-one as `query_string_too_long`;
- claim isolated external proof that the 8,192-byte query-length check is
  reachable through a matching origin-form target;
- use a malformed query to satisfy the corrected triad;
- modify either original Phase 3H caller record;
- populate the original Phase 3H report;
- stage the active Phase 3H.1 correction report;
- change another matrix row, path boundary, package, dependency, lockfile,
  environment policy, API, diagnostic, or architecture decision; or
- begin Phase 4.

---

## 9. Acceptance criteria

Every criterion must pass:

1. The Phase 3H.1 correction directive independently authenticates.
2. Every required correction and incorporated original authority is read.
3. Original Phase 3H directive and zero-byte report identities remain exact.
4. Frozen repository, history, lockfile, binary diff, physical, status,
   untracked, archive, protected, caller, index, and host-tool evidence passes.
5. Entry state is exactly 30 modified, 185 deleted, 266 untracked, 481 total,
   and zero staged paths.
6. The completed original pre-change quality evidence is preserved and not
   rerun.
7. The only semantic correction is the effective external raw-query triad of
   8,189 / 8,190 / 8,191 bytes.
8. The exact two-field valid query construction is used.
9. The 8,189- and 8,190-byte queries pass with exact matching origin-form
   targets of 8,191 and 8,192 bytes.
10. The 8,191-byte query is rejected because its exact matching target is
    8,193 bytes and produces `target_too_long`.
11. No production source, manifest, package test, intrinsic limit, diagnostic,
    API, or runtime semantic changes.
12. Every other original Phase 3H implementation and acceptance requirement
    passes unchanged.
13. Documentation distinguishes intrinsic and effective external query limits
    without calling the frozen contract defective.
14. All focused, external, canonical, disclosure, process, network, cleanup,
    documentation, and safety gates pass before staging.
15. Final pre-stage state and the exact 494-path manifest pass.
16. Exactly 493 paths are staged: 30 modified, 278 added, and 185 deleted; the
    active correction report is sole untracked path.
17. Semantic index contains 629 exact ordinary 100644 entries with no conflict
    and `git diff --cached --check` passes.
18. Exact 629-path tree and 493-path parent delta pass.
19. Exactly one checkpoint commit is created with the unchanged parent,
    subject, effective identity, tree, and eleven-commit history.
20. No extra commit, branch, remote, tag, push, ref rewrite, amend, reset,
    restore, or history mutation occurs.
21. Every post-commit external, focused, canonical, safety, cleanup,
    documentation, and Git gate passes.
22. The original Phase 3H report is committed as an empty blob; the active
    correction report remains zero bytes until all non-report gates pass.
23. The correction report truthfully records the query correction, accepted
    blocked execution, two read-only probe deviations, retries, results,
    checkpoint, and remaining unsupported scope.
24. Final report integrity and report-only untracked state pass.
25. Report byte count and SHA-256 are returned outside its body.
26. Phase 4 remains unstarted.

Any failure means Phase 3H.1 is not complete. Preserve the exact reached state,
keep the active report zero bytes unless every non-report gate passed, and
request the smallest authenticated correction. A created checkpoint commit
remains immutable even if a later gate fails.

---

## 10. Final instruction

Apply only the effective external raw-query correction in this directive, then
resume and complete the original Phase 3H implementation. Preserve every
frozen production byte and every incorporated original boundary. Prove the
valid 8,189 / 8,190 / 8,191 query triad through the actual PHP-CGI FastCGI
listener, finish the exact Phase 3 checkpoint, rerun all post-commit gates, and
populate only the Phase 3H.1 correction report after full success.

Do not begin Phase 4.
