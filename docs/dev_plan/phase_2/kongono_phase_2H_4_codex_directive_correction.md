# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.4
## Test-Only Kernel Diagnostic Identifier Reconciliation and Phase 2H Resume

**Directive type:** exact fixture identifier correction, Gate C resume, checkpoint reconciliation, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.4

**Depends on:** authenticated Phase 2H.3 partially implemented Gate C stopped on the trusted diagnostic proof

---

## 1. Mission

Correct the sole trusted-fixture interoperability mismatch without changing any
production code or policy.

The Phase 2H fixture currently returns:

```text
FIXTURE-KERNEL-0001
```

from its test-only object implementing the Public Kernel exception marker. The
accepted production projector correctly requires Kernel diagnostic identifiers
to match:

```regex
/^KGN-KERNEL-[0-9]{4}$/D
```

Because the fixture identifier is outside that grammar, the real presenter
rejects it and Web Runtime correctly falls back to the fixed generic response.

The architect selects the smallest correction:

```text
old test-only identifier  FIXTURE-KERNEL-0001
new test-only identifier  KGN-KERNEL-9001
```

`KGN-KERNEL-9001` is reserved only as the Phase 2 black-box test specimen. It is
not a production diagnostic assignment, enum case, catalog row, supported
application error, or new Kernel behavior. It appears only in test-owned Phase
2H fixture/test/documentation evidence needed to exercise the already-Public
marker and projector grammar.

Resume Gate C, prove the trusted path through the unchanged production stack,
complete the remaining two documentation creations and all original
reconciliations, then execute the corrected canonical, checkpoint, and
post-commit gates.

---

## 2. Architect decision and narrow supersession

The architect selects the executor's recommended option:

> Change only the fixture identifier to one architect-selected value satisfying
> the existing production Kernel diagnostic grammar. Keep production frozen.

This directive supersedes only the exact `FIXTURE-KERNEL-0001` requirement in
Phase 2H and any test/documentation assertion derived from it. Replace that
value with exactly:

```text
KGN-KERNEL-9001
```

All other Phase 2H-family decisions remain locked, including:

- production projector regex and supported-source policy;
- no production package/source/test change;
- test-owned `FixtureKernelDiagnosticException` shape, fixed context, title,
  remediation, and zero request-derived values;
- both affirmative permissions for trusted output;
- generic fallback under bootstrap denial, delivery denial, unsupported source,
  or presentation failure;
- exact twelve-PHP-file and fourteen-created-path inventories;
- exact twenty-two modified existing paths;
- accepted Phase 2H.2 Gate B evidence with no rerun;
- separate later-overlay cleanup predicates from Phase 2H.3;
- final pre-staging and post-commit canonical quality;
- exact checkpoint subject and parent; and
- all prohibited scope and stop rules.

Do not expand the production regex, add a fixture prefix to production policy,
change Web Runtime fallback, or remove the trusted-path acceptance test.

---

## 3. Independent correction authentication

Before editing the fixture, completing documentation, another overlay, staging,
or Git writes, compare this correction with the byte count and SHA-256 supplied
independently by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

---

## 4. Required authorities and caller records

Read completely:

```text
AGENTS.md
docs/dev_plan/kongono_phase_2H_codex_directive.md
docs/task reports/Phase_2H.md
docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md
docs/task reports/Phase_2H_1_correction.md
docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md
docs/task reports/Phase_2H_2_correction.md
docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md
docs/task reports/Phase_2H_3_correction.md
docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md
tests/BlackBox/Fixture/FixtureKernelDiagnosticException.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php
```

The filenames are exact. Do not substitute another path based on class name.
Verify the locked regex is exactly the one reported above.

Verify exact prior directive identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2H_codex_directive.md` | 57027 | `29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC` |
| `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md` | 20701 | `5302D1A8EE111F0E03EB5BAAA6EF0FF199A1D449A6F55A696032E14D9203F048` |
| `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md` | 21134 | `A6A53FF03C7D52D80381C5C6C71C154A6D475A997BF915A47E24D639C5BE80B1` |
| `docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md` | 19559 | `F804DBCE8863CB1D95E561598BE11A5CBBA343CF2422FB6AC8C3B408337541F3` |

All four earlier Phase 2H-family reports must remain ordinary, untracked,
unstaged, absent from `HEAD`, zero bytes, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Their paths are:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
docs/task reports/Phase_2H_2_correction.md
docs/task reports/Phase_2H_3_correction.md
```

Do not populate or change them. Stage and commit them as intentional zero-byte
historical records only after all corrected pre-commit gates pass.

The caller must additionally provide:

```text
docs/task reports/Phase_2H_4_correction.md
```

At entry it must be ordinary, untracked, unstaged, absent from `HEAD`, zero
bytes, and have the empty-file digest above. Keep it zero-byte through every
non-report gate and never stage it.

---

## 5. Accepted partial Gate C state

### 5.1 Before Phase 2H.4 caller setup

Require:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        167
total status paths     194
staged paths           0
Git-visible paths      525
```

Accepted implementation progress is exactly:

- all twelve Phase 2H PHP paths under `tests/BlackBox/` exist;
- `phpstan.neon.dist` contains the one authorized `tests/BlackBox` path addition;
- the two new Phase 2H documentation paths do not yet exist;
- no existing documentation reconciliation has begun;
- syntax passes for all twelve PHP files;
- matched success, middleware order, unmatched 404, native generic failure,
  request/send/close counts, zero active operations, cleanup, and sequential
  isolation behave correctly;
- the trusted diagnostic scenario returns generic only because the test-only ID
  fails the frozen projector grammar;
- no production, package-test, dependency, manifest, lock, index, history, or
  permanent environment state changed; and
- all four prior reports remain zero-byte.

Gate B remains accepted from Phase 2H.2 and must not be rerun.

### 5.2 Correction entry after caller setup

After adding only this authenticated directive and the zero-byte Phase 2H.4
report, require:

```text
tracked modifications  27
untracked paths        169
total status paths     196
staged paths           0
Git-visible paths      527
tracked files          358
```

The only new paths are:

```text
docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md
docs/task reports/Phase_2H_4_correction.md
```

Reconstruct the original authenticated 505-path snapshot by excluding the ten
Phase 2H-family caller paths and the twelve partially created fixture PHP paths.
Also reverse only the authorized `tests/BlackBox` PHPStan line to prove the
original `phpstan.neon.dist` bytes. Require the original manifest identity:

```text
records          505
manifest bytes   62292
SHA-256          A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
```

Stop if any partial-state fact differs. Do not re-create, discard, or normalize
the accepted files.

---

## 6. Exact identifier correction

In exactly:

```text
tests/BlackBox/Fixture/FixtureKernelDiagnosticException.php
```

replace the sole exact literal:

```php
return 'FIXTURE-KERNEL-0001';
```

with:

```php
return 'KGN-KERNEL-9001';
```

Before editing require:

- the old return line occurs exactly once in that file;
- `KGN-KERNEL-9001` occurs zero times in any production PHP file;
- `KGN-KERNEL-9001` occurs zero times in the fixture PHP boundary; and
- no production diagnostic enum or catalog assigns `KGN-KERNEL-9001`.

After editing require:

- old identifier occurs zero times in all `tests/BlackBox` PHP;
- new identifier occurs exactly where required by fixture source and explicit
  test assertions;
- it matches `/^KGN-KERNEL-[0-9]{4}$/D`;
- it remains absent from all production PHP and production diagnostic catalogs;
- production diagnostic inventory remains exactly 113 unique IDs;
- title, remediation, operation, invariant, native message, context shape, and
  marker type remain unchanged; and
- every other pre-existing fixture byte changes only as still required to finish
  the original authenticated Gate C implementation.

The exact number `9001` is reserved by this directive only for the Phase 2
fixture. Do not infer a general `9000` test range, add a registry entry, reserve
other values, or promise future compatibility.

---

## 7. Trusted-path proof after correction

Rerun the focused developer-trusted diagnostic scenario through the real
fixture entry and production stack.

Require:

- bootstrap permission `Allowed`;
- delivery permission `Allowed`;
- delivery evidence evaluated exactly once;
- request, send, and close each called exactly once;
- real Web Application Kernel operation count returns to zero;
- status `500`;
- trusted HTML content type and all exact Phase 2G security headers;
- body contains exactly the test-only `KGN-KERNEL-9001` diagnostic meaning,
  fixed title, remediation, operation, and invariant as selected by the
  existing renderer;
- body contains no route URI, path parameter, scenario token, CLI mode, native
  sentinel, trace, local path, credential, or request-derived value; and
- runtime/application remain healthy for the next permitted sequential
  operation.

Also rerun bootstrap-denied, delivery-denied, and unsupported-native-failure
scenarios. They must remain exact generic output and must not contain the new
test-only ID.

If the trusted scenario still falls back or requires production modification,
stop. Do not widen the projector or weaken the acceptance proof.

---

## 8. Complete original Gate C and later gates

After the identifier proof passes:

1. finish only the original twelve-file fixture implementation as necessary to
   satisfy its locked contracts;
2. create the two remaining original documentation paths, ADR 0050 and the
   runnable-fixture model;
3. complete exactly the original twenty-two existing-file reconciliations;
4. run every focused syntax, PHPStan, style, black-box PHPUnit, direct runner,
   security, isolation, and documentation proof;
5. run final pre-staging canonical quality with the Phase 2H.3 separate cleanup
   booleans;
6. run strict dev/no-dev/dev optimized autoload proof;
7. execute the corrected checkpoint in Section 10;
8. run every post-commit focused/canonical/preservation proof; and
9. populate only the Phase 2H.4 report after all non-report gates pass.

Do not rerun pre-change Gate B. Retain every original Phase 2H architectural,
file, test, documentation, security, overlay, Git, stop, and prohibited-scope
rule not superseded here.

---

## 9. Corrected final inventories before staging

Framework inventories remain:

```text
package directories       13
package manifests         13
production PHP files      100
test PHP files            130
style-covered PHP files   230
production diagnostic IDs 113 unique
locked production         17
locked development        66
```

After the remaining two documentation paths are created and all implementation
is complete, before staging require:

```text
tracked modifications  27
untracked paths        171
total status paths     198
staged paths           0
Git-visible paths      529
tracked files          358
```

The increase beyond the authenticated 505-path snapshot is exactly:

- ten Phase 2H-family caller records; and
- fourteen original Phase 2H created implementation/documentation paths.

Replace every earlier Phase 2H pre-stage/status total with
`27/171/198/0`, 529 Git-visible, and 358 tracked.

---

## 10. Corrected checkpoint boundary

### 10.1 Exact staging set

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. Phase 2H directive and zero-byte report;
3. Phase 2H.1 directive and zero-byte report;
4. Phase 2H.2 directive and zero-byte report;
5. Phase 2H.3 directive and zero-byte report;
6. `docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md`; and
7. the fourteen original Phase 2H created paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_4_correction.md
```

Materialize exactly 197 literal ordinal staging paths and retain all original
pathspec-safe/no-wildcard rules.

After staging require:

```text
staged paths                  197
staged modified paths          27
staged added paths            170
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 528
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.4 report. All four earlier
reports are staged empty blobs.

### 10.2 Tree and commit

Require staged blob/worktree equality, exact modes, no conflict stages, and
clean `git diff --cached --check`. `git write-tree` must contain exactly 528
paths, all `100644`.

Use the unchanged subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      528
tree modes      all 100644
delta paths     197
modified        27
added           170
deleted         0
```

All original author, hook, signing, ref, branch, remote, tag, history, and
prohibited Git-operation rules remain.

Replace every earlier staging/tree count with 197 staged, 27 modified, 170
added, zero deleted, and 528 index/tree paths.

### 10.3 Post-commit state

Before report population require:

```text
tracked files          528
tracked modifications 0
staged paths           0
untracked paths        1
total status paths     1
```

The sole path is the zero-byte Phase 2H.4 report. All four earlier reports must
exist in `HEAD` as exact empty blobs.

Run every original post-commit focused, direct runner, canonical overlay,
inventory, documentation, link, file-safety, mode, and history proof. Only then
populate the active report.

---

## 11. Corrected acceptance substitutions

Every original Phase 2H implementation criterion remains, with these exact
substitutions:

- fixture diagnostic ID is `KGN-KERNEL-9001` test-only;
- active report is `Phase_2H_4_correction.md`;
- four earlier reports remain zero-byte and are committed;
- pre-stage state is `27/171/198/0`, 529 visible, 358 tracked;
- staged state is 197 paths: 27 modified, 170 added, zero deleted;
- index/checkpoint tree contain 528 `100644` paths;
- commit delta is 197 paths: 27 modified, 170 added, zero deleted;
- post-commit tracked file count is 528; and
- Phase 2H.4 report is the sole final status path.

Add these Phase 2H.4 criteria:

1. Phase 2H.4 directive authenticated independently;
2. partial Gate C state and four zero-byte reports matched;
3. old fixture ID occurred exactly once before correction and nowhere after;
4. new ID matches the frozen production Kernel grammar;
5. new ID remains absent from production source/catalog assignments;
6. production projector and all other production bytes remained frozen;
7. production diagnostic count remains 113 unique;
8. trusted diagnostic scenario returns exact trusted HTML only with both
   permissions;
9. denied and unsupported scenarios remain generic and exclude the new ID;
10. no request-derived value enters trusted output;
11. all original implementation/checkpoint/post-commit criteria pass; and
12. only the Phase 2H.4 report is populated after every other gate.

Any failure means status cannot be Complete.

---

## 12. Prohibited scope

The complete prohibited scope of Phase 2H through Phase 2H.3 remains. Also do
not:

- modify the production projector, renderer, presentation model, Web Runtime,
  Kernel, or diagnostic grammar;
- add `KGN-KERNEL-9001` to a production enum/catalog/schema;
- assign another fixture ID or reserve a range;
- keep or accept `FIXTURE-KERNEL-0001`;
- change the trusted scenario to generic fallback;
- make fixture input authorize trusted output;
- create another fixture/source file;
- rerun Gate B;
- populate an earlier report;
- stage the Phase 2H.4 report; or
- begin Phase 3.

---

## 13. Stop rules

Stop and preserve exact state if:

- correction identity, caller records, partial state, or original manifest
  differs;
- the old identifier occurrence is not exact;
- the new identifier collides with production source/catalog assignments;
- trusted output still falls back after the exact correction;
- production bytes would need modification;
- denied/unsupported output exposes the new ID;
- any original focused, canonical, autoload, documentation, staging, tree,
  commit, or post-commit gate fails;
- corrected counts differ; or
- a prohibited operation would be needed.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Preserve an exact staged index after a post-staging failure and never amend or
reset a created commit.

---

## 14. Phase 2H.4 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_4_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.4 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. GATE B EVIDENCE
## 6. TRUSTED IDENTIFIER CORRECTION
## 7. FILES CREATED
## 8. FILES MODIFIED
## 9. RUNNABLE FIXTURE BOUNDARY
## 10. DELIVERY AND OUTPUT CONTRACT
## 11. BLACK-BOX ACCEPTANCE
## 12. SECURITY AND TRUST PROOF
## 13. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 14. ARCHITECTURAL DECISIONS
## 15. DEVIATIONS FROM DIRECTIVE
## 16. DEPENDENCIES ADDED/REMOVED
## 17. ENVIRONMENT
## 18. COMMANDS RUN
## 19. PRE-COMMIT VALIDATION RESULTS
## 20. STAGING EVIDENCE
## 21. COMMIT EVIDENCE
## 22. POST-COMMIT VALIDATION RESULTS
## 23. DOCUMENTATION REVIEW
## 24. SECURITY AND FILE-SAFETY REVIEW
## 25. WARNINGS / KNOWN ISSUES
## 26. QUESTIONS / BLOCKERS
## 27. FINAL GIT STATUS
## 28. ACCEPTANCE CRITERIA
## 29. NEXT STEP
```

Include every original Phase 2H implementation/checkpoint report requirement
plus:

- independent Phase 2H.4 identity;
- all Phase 2H-family directive/report identities;
- accepted partial Gate C state;
- exact old/new identifier replacement and production absence proof;
- trusted/denied/unsupported black-box results;
- explicit statement that production projector/catalog remained frozen;
- all four earlier zero-byte reports staged and committed;
- exact `27/171/198/0`, 529-visible pre-stage evidence;
- exact 197-path stage and 528-path tree/commit;
- final sole-report status;
- whether Phase 2 is fully closed; and
- final report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The report must be strict UTF-8 without BOM, LF-only, exactly one final LF, no
trailing whitespace/NUL, ordinary, untracked, unstaged, absent from `HEAD`, and
the sole final status path.

---

## 15. Required final outcome

The test-owned supported diagnostic uses one grammar-valid, architect-selected
fixture identifier while production ownership and policy remain unchanged. The
trusted path is proved through the real projector, renderer, handler, Web
Runtime, Web Application, and Kernel boundaries; denied and unsupported paths
remain generic.

Phase 2H then completes its original runnable fixture, black-box proof,
documentation reconciliation, and exact checkpoint. All four earlier reports
remain zero-byte historical records in `HEAD`; only the populated Phase 2H.4
report remains outside the commit. Then await architect review before Phase 3
planning.
