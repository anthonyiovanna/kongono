# CODEX FINAL CLOSURE DIRECTIVE - KONGONO PHASE 2H
## Consolidated Fixture Style and Trusted-Output Test Correction

**Directive type:** consolidated final closure authority, bounded two-file correction, validation resume, checkpoint, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H final closure

**Depends on:** authenticated Phase 2H.7 stopped after focused PHPStan passed and focused PHP CS Fixer found one unused import

---

## 1. Purpose

Close Phase 2H without creating another numbered correction phase for two known,
test-owned mechanical defects:

1. `Phase2FixtureRunner.php` retains an unused `JsonException` import; and
2. `Phase2RunnableWebFrameworkTest.php` serializes the complete runner result
   envelope—including its required `scenario` field—while asserting that the
   scenario token is absent from trusted delivered output.

The second defect was found by the architect's consolidated review before
issuing this authority. The current focused black-box suite reaches:

```text
tests       16
assertions  233
failures    1
```

The sole failure is
`testRequestAndInvocationValuesNeverEnterTrustedOutput`: the serialized runner
envelope intentionally contains `"scenario":"diagnostic-failure"`, while the
test correctly forbids `diagnostic-failure` from trusted response headers/body.
The test selected the wrong disclosure boundary; production output did not leak
the scenario.

Correct both known issues together, resume focused Gate C, and finish the Phase
2H checkpoint. This is the final consolidated closure authority, not Phase
2H.8.

---

## 2. Architect decision

Authorize edits in exactly these two already-created Phase 2H test paths:

```text
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

Authorize exactly:

- deletion of the unused `use JsonException;` import; and
- changing the trusted-output disclosure test to serialize only the delivered
  response `headers` and `body`, not the runner envelope's control metadata.

The runner result schema must continue to include `scenario`. The test must
continue to forbid the scenario token, mode, URI, route, sentinels, local path,
and other request/invocation values from the delivered headers/body.

This is a test correction, not a relaxation of the disclosure policy. The
trusted HTML and headers already exclude `diagnostic-failure`; only the
out-of-band black-box result envelope contains the scenario name so PHPUnit can
associate each result with its requested scenario.

Every other Phase 2H through Phase 2H.7 contract remains locked:

- all production and package-test files remain frozen;
- focused PHPStan has already reached zero errors after the header correction;
- the exact runner grammar and result schema remain;
- `KGN-KERNEL-9001` remains test-only;
- trusted output requires independent bootstrap and delivery permission;
- denied, unsupported, and presentation-failure paths remain generic;
- exact created/modified inventories remain;
- accepted Phase 2H.2 Gate B evidence must not be rerun;
- later canonical overlays use separate cleanup predicates;
- all documentation, autoload, checkpoint, post-commit, report, security,
  prohibited-scope, and stop requirements remain.

---

## 3. Independent directive authentication

Before broad validation, editing, staging, Git writes, or report population,
compare this directive with the byte count and SHA-256 supplied independently
by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md
```

Do not self-authenticate from the local file. Stop if either supplied value is
absent or differs.

The caller must also provide:

```text
docs/task reports/Phase_2H_final_closure.md
```

It must be an ordinary, untracked, unstaged file absent from `HEAD`, zero bytes,
with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte through every non-report gate and never stage it.

---

## 4. Required authorities and immutable caller records

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
docs/task reports/Phase_2H_4_correction.md
docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md
docs/task reports/Phase_2H_5_correction.md
docs/dev_plan/kongono_phase_2H_6_codex_directive_correction.md
docs/task reports/Phase_2H_6_correction.md
docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md
docs/task reports/Phase_2H_7_correction.md
docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

Verify exact prior directive identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2H_codex_directive.md` | 57027 | `29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC` |
| `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md` | 20701 | `5302D1A8EE111F0E03EB5BAAA6EF0FF199A1D449A6F55A696032E14D9203F048` |
| `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md` | 21134 | `A6A53FF03C7D52D80381C5C6C71C154A6D475A997BF915A47E24D639C5BE80B1` |
| `docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md` | 19559 | `F804DBCE8863CB1D95E561598BE11A5CBBA343CF2422FB6AC8C3B408337541F3` |
| `docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md` | 20948 | `56808440DBD44AA4FAA96CAC9B02FE9AABF29F224F99F4288518A743B6152D4E` |
| `docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md` | 31674 | `6B4EE677C7CCC1BB498DEE03858D1A23185C12655754BCD78A1AA668AB325B29` |
| `docs/dev_plan/kongono_phase_2H_6_codex_directive_correction.md` | 27191 | `600CD03C9FAF00B43223E432F16985AA77C878BD2CDCA76B7893D0158F03A453` |
| `docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md` | 23583 | `A704A4C8AD780190B93E37E71535BD3D46EE59D945DC2DB6A82A8C58B77F37A6` |

All eight earlier reports must remain ordinary, untracked, unstaged, absent
from `HEAD`, zero bytes, and have the empty-file digest above:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
docs/task reports/Phase_2H_2_correction.md
docs/task reports/Phase_2H_3_correction.md
docs/task reports/Phase_2H_4_correction.md
docs/task reports/Phase_2H_5_correction.md
docs/task reports/Phase_2H_6_correction.md
docs/task reports/Phase_2H_7_correction.md
```

Do not populate or alter them. They become intentional zero-byte historical
records only in the completed checkpoint.

---

## 5. Accepted entry state

### 5.1 Before final-closure caller setup

Require:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        177
total status paths     204
staged paths             0
Git-visible paths      535
```

Accepted validation evidence is:

- the Phase 2H.7 authenticated entry manifest passed;
- the exact header-name guard and separately constructed value lists are now in
  `FixtureDelivery.php`;
- all twelve Phase 2H PHP files pass syntax;
- focused PHPStan passes with zero errors;
- focused PHP CS Fixer reports only the unused `JsonException` import in
  `Phase2FixtureRunner.php`, one of twelve files fixable, exit 8;
- the architect's diagnostic black-box run reached 16 tests, 233 assertions,
  one failure only in the disclosure-boundary test described in Section 1;
- both original direct runner scenarios execute successfully with exact bounded
  JSON-lines output and no stderr;
- no production, documentation, configuration, dependency, manifest, lock,
  index, history, or caller-record mutation followed the stop; and
- Gate B was not rerun.

The architect's diagnostic PHPUnit invocation used a deprecated no-cache CLI
option solely to avoid repository cache mutation. That invocation is evidence
of the test defect, not an executor acceptance command and not permission to use
the deprecated option in final validation.

### 5.2 After final-closure caller setup

After adding only this authenticated directive and its zero-byte report,
require:

```text
tracked modifications  27
untracked paths        179
total status paths     206
staged paths             0
Git-visible paths      537
tracked files          358
```

The only two new paths are:

```text
docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md
docs/task reports/Phase_2H_final_closure.md
```

Require one local branch, no remotes, no tags, no unexpected repository-owned
refs, ordinary/default-stream files, intended modes, and exact empty reports.
Enumerate and preserve `refs/codex/turn-diffs/**` separately.

---

## 6. Direct authenticated current-state manifest

Before editing, enumerate Git-visible paths, normalize separators to `/`, and
exclude exactly the eighteen Phase 2H-family caller paths: the eight prior
directive/report pairs plus this final-closure directive/report pair.

Do not exclude implementation, documentation, tooling, or any other path. Do
not reverse or synthesize bytes. Use the established Windows PowerShell
`Sort-Object` ordering and record format:

```text
path + NUL + invariant-decimal-byte-length + NUL + uppercase-SHA-256 + LF
```

Require:

```text
records                 519
manifest bytes          63928
manifest SHA-256        A7D578F19FD1B1BBC8555AA6ABC19BCB3D672758C34956D0EF4732D2F9625316
aggregate file bytes    5450953
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

This is the direct architect-authenticated state after the Phase 2H.7 header
correction and before the two closure edits. It is entry evidence only and will
change after Section 7. Stop on any mismatch; do not reconstruct the original
505 paths or adopt another observed identity.

Also verify exact fourteen-created/twenty-two-modified inventories, all nine
zero-byte reports, file safety/modes, and protected production, package-test,
manifest, dependency, lockfile, and diagnostic-catalog hashes.

---

## 7. Exact consolidated closure edits

### 7.1 Remove the unused import

In exactly:

```text
tests/BlackBox/Fixture/Phase2FixtureRunner.php
```

delete exactly:

```php
use JsonException;
```

Require it to occur exactly once before deletion and zero times afterward. Do
not reorder or otherwise change imports, catches, JSON encoding, runner grammar,
result fields, output, exit behavior, or any other byte in that file.

### 7.2 Correct the trusted-output disclosure boundary

In exactly:

```text
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

within `testRequestAndInvocationValuesNeverEnterTrustedOutput()`, retain:

```php
$result = $this->successful('developer-trusted', 'diagnostic-failure');
```

Replace only:

```php
$serialized = $this->serialized($result);
```

with:

```php
$serialized = $this->serialized([
    'headers' => $result['headers'],
    'body' => $result['body'],
]);
```

Keep every forbidden value unchanged, including `developer-trusted` and
`diagnostic-failure`. Keep the runner result's `scenario` field and every exact
result key unchanged.

The corrected test proves the intended security boundary:

- trusted delivered headers/body contain no request or invocation value;
- the out-of-band runner envelope may retain its required scenario association;
- no production output or projection behavior changes; and
- no security assertion is deleted, weakened, skipped, inverted, or replaced
  by source-text inspection.

### 7.3 Bounded mechanical formatting authority

After applying Sections 7.1 and 7.2, the executor may run PHP CS Fixer in write
mode against exactly these two files once if the focused dry-run reports only
formatting consequences of these exact edits. Then rerun the focused dry-run.

No semantic edit is authorized by this allowance. Stop if PHP CS Fixer proposes
or applies a change outside the two paths, if another unused import appears, or
if any finding is unrelated to the exact edits.

Within these two files only, one additional correction-and-rerun cycle is
authorized for a PHPStan/PHP CS Fixer finding mechanically caused by the exact
array literal/import deletion. It may adjust only whitespace, line wrapping,
or an honest local PHPDoc type; it may not alter behavior, assertions, result
fields, forbidden values, or analyzer configuration. Report the exact change.

This bounded allowance prevents another directive for harmless local formatting
while preserving the closed semantic boundary.

---

## 8. Consolidated Gate C and remaining validation

Do not rerun Gate B.

After Section 7:

1. run syntax checks on the two edited files and all twelve Phase 2H PHP files;
2. rerun focused PHPStan and require zero errors;
3. rerun focused PHP CS Fixer dry-run and require zero of twelve files fixable;
4. run the original black-box PHPUnit command and require all 16 tests to pass,
   with the final assertion total reported exactly rather than predicted;
5. run both original direct fixture commands and every additional denied,
   unsupported, invalid-input, multi-scenario, cleanup, and isolation probe
   required by the Phase 2H family;
6. prove exact JSON-lines result keys retain `scenario` while delivered
   headers/body exclude every forbidden disclosure value;
7. prove status, headers, body, middleware order, request/send/close counts,
   permission evaluation, stream bounds, active operations, cleanup, health,
   and isolation remain exact;
8. validate all documentation, ADRs, links, current/future language, encodings,
   line endings, streams, modes, and exact path inventories;
9. prove production, package tests, manifests, dependencies, lockfile,
   diagnostic catalog, and tooling remain frozen except the already-authorized
   `tests/BlackBox` PHPStan path;
10. run final pre-staging canonical quality through the corrected temporary
    512M overlay with separate Boolean cleanup predicates;
11. permit only the existing single identical approved-access retry when that
    canonical run fails solely because managed network/cache access is denied;
12. prove permanent PHP restoration to 128M, main `C:\php\php.ini`, typed false
    for no scanned files, absent scan variable, and absent temporary paths;
13. run strict dev/no-dev/dev optimized autoload proof;
14. execute the checkpoint in Section 10;
15. run every post-commit focused, direct, canonical, documentation, security,
    inventory, preservation, and history proof; and
16. populate only the final-closure report after every non-report gate passes.

Any new semantic test failure, analyzer error, unrelated style finding, or
protected-state mismatch activates the stop rule. The bounded allowance in
Section 7.3 does not authorize guessing through another defect.

---

## 9. Final inventories before staging

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

Before staging require:

```text
tracked modifications  27
untracked paths        179
total status paths     206
staged paths             0
Git-visible paths      537
tracked files          358
```

The increase beyond the original 505-path snapshot is exactly eighteen
Phase 2H-family caller records and fourteen Phase 2H-created paths. The two
closure edits add no path.

---

## 10. Final corrected checkpoint

### 10.1 Staging

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. the Phase 2H directive and zero-byte report;
3. each Phase 2H.1 through Phase 2H.7 directive and zero-byte report;
4. `docs/dev_plan/kongono_phase_2H_final_closure_codex_directive.md`; and
5. the fourteen original Phase 2H-created implementation/documentation paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_final_closure.md
```

Materialize exactly 205 literal staging paths using the locked explicit,
pathspec-safe, no-wildcard, no-directory-staging procedure.

Require:

```text
staged paths                  205
staged modified paths          27
staged added paths            178
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 536
index modes other than 100644   0
```

The sole untracked path is the zero-byte final-closure report. All eight prior
reports are staged as empty blobs.

### 10.2 Tree and commit

Require exact staged paths, blob/worktree equality, modes, protected hashes, no
conflict stages, clean `git diff --cached --check`, and active-report absence
from the index. `git write-tree` must contain exactly 536 paths, all `100644`.

Use exactly:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      536
tree modes      all 100644
delta paths     205
modified        27
added           178
deleted         0
```

Semantic staged paths, blobs, modes, and tree remain authoritative over
cache-tree metadata in raw index bytes. Retain all original author, hook,
signing, branch, ref, remote, tag, and history rules. Do not amend or reset.

### 10.3 Post-commit

Before report population require:

```text
tracked files          536
tracked modifications   0
staged paths             0
untracked paths          1
total status paths       1
```

The sole path is the zero-byte final-closure report. All eight earlier reports
must exist in `HEAD` as exact empty blobs. Complete every post-commit gate before
populating the report.

---

## 11. Acceptance criteria

Every unsuperseded Phase 2H through Phase 2H.7 criterion remains. Require:

1. final-closure directive identity matched independently supplied values;
2. all nine reports were exact zero-byte caller records at entry;
3. exact `27/179/206/0`, 537-visible, 358-tracked entry state;
4. exact 519-record `A7D578F1...` current-state manifest;
5. only the two Section 7 files changed under final closure;
6. the unused `JsonException` import is absent and no other runner byte changed;
7. the disclosure test serializes only response headers/body;
8. every forbidden value remains asserted absent from headers/body;
9. the runner envelope retains the exact `scenario` field and result schema;
10. syntax passes for all twelve Phase 2H PHP files;
11. focused PHPStan reports zero errors;
12. focused style reports zero of twelve files fixable;
13. all 16 black-box tests pass with the exact observed assertion total;
14. every direct runner scenario and security/isolation proof passes;
15. no production disclosure, policy, API, dependency, manifest, lockfile,
    diagnostic, documentation, or configuration behavior changed;
16. `KGN-KERNEL-9001` remains test-only and trusted output requires both
    permissions;
17. final canonical quality, environment restoration, and strict autoload gates
    pass;
18. exactly 205 paths are staged: 27 modified, 178 added, zero deleted;
19. index/tree contain exactly 536 ordinary `100644` files;
20. the exact single commit is created with the locked parent, subject, author,
    tree, delta, and history shape and is not amended;
21. every post-commit gate passes;
22. all eight earlier reports are committed as exact empty blobs;
23. only the populated final-closure report remains outside `HEAD` and index;
24. the report is populated only after all other criteria pass; and
25. Phase 2 is declared fully closed only with no blocker or failed criterion.

Any failure means status cannot be Complete.

---

## 12. Prohibited scope

Do not:

- create another numbered Phase 2H correction;
- modify a third file under this closure authority;
- alter the result schema or remove `scenario`;
- delete or weaken a forbidden disclosure assertion;
- change production or package tests;
- change manifests, dependencies, lockfile, diagnostics, documentation,
  runtime policy, or tool configuration;
- add suppressions, ignores, baselines, stubs, exclusions, or skipped tests;
- change response bytes, headers, status, scenarios, middleware, counts,
  lifecycle, cleanup, trust, or isolation behavior;
- rerun Gate B;
- populate an earlier report;
- stage the final-closure report;
- clean unrelated caller-managed state;
- amend, reset, rewrite, merge, or expand history; or
- begin Phase 3 before architect review.

---

## 13. Stop rules

Stop and preserve exact state if:

- authentication, caller records, state, manifest, inventory, hash, mode,
  stream, or accepted evidence differs;
- either exact source pattern is absent or occurs other than once;
- the edits require a third file or semantic expansion;
- any remaining/new PHPStan, style, PHPUnit, direct-run, security, trust,
  isolation, cleanup, documentation, canonical, autoload, staging, tree,
  commit, or post-commit failure exists after the bounded allowance;
- corrected counts differ;
- temporary PHP state is not restored; or
- a prohibited operation would be required.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS OR EXPAND SCOPE
```

Preserve an exact staged index after a post-staging failure. Never discard the
accepted implementation and never amend or reset a created commit.

---

## 14. Final closure report

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_final_closure.md
```

Use exactly these headings:

```text
# PHASE 2H FINAL CLOSURE TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. DIRECT CURRENT-STATE MANIFEST
## 6. CONSOLIDATED REVIEW FINDINGS
## 7. FINAL CLOSURE EDITS
## 8. GATE B EVIDENCE
## 9. STATIC ANALYSIS AND STYLE
## 10. FILES CREATED
## 11. FILES MODIFIED
## 12. RUNNABLE FIXTURE BOUNDARY
## 13. DELIVERY AND OUTPUT CONTRACT
## 14. BLACK-BOX ACCEPTANCE
## 15. SECURITY AND TRUST PROOF
## 16. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 17. ARCHITECTURAL DECISIONS
## 18. DEVIATIONS FROM DIRECTIVE
## 19. DEPENDENCIES ADDED/REMOVED
## 20. ENVIRONMENT
## 21. COMMANDS RUN
## 22. PRE-COMMIT VALIDATION RESULTS
## 23. STAGING EVIDENCE
## 24. COMMIT EVIDENCE
## 25. POST-COMMIT VALIDATION RESULTS
## 26. DOCUMENTATION REVIEW
## 27. SECURITY AND FILE-SAFETY REVIEW
## 28. WARNINGS / KNOWN ISSUES
## 29. QUESTIONS / BLOCKERS
## 30. FINAL GIT STATUS
## 31. ACCEPTANCE CRITERIA
## 32. NEXT STEP
```

Include all original Phase 2H report evidence plus:

- independently supplied final-closure identity;
- identities of all Phase 2H-family directives/reports;
- exact 519-record `A7D578F1...` entry manifest;
- unused-import deletion and disclosure-boundary before/after proof;
- the architect diagnostic result and final passing black-box result;
- exact PHPStan/style/test/direct/canonical/autoload evidence;
- whether the bounded mechanical allowance was used and exact changes;
- no-production-change and no-policy-weakening proof;
- all eight earlier reports staged and committed empty;
- exact `27/179/206/0`, 537-visible pre-stage evidence;
- exact 205-path staging and 536-path tree/commit evidence;
- final sole-report status;
- explicit declaration that Phase 2 is fully closed; and
- final report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The report must be strict UTF-8 without BOM, LF-only, exactly one final LF, no
trailing whitespace or NUL, ordinary, untracked, unstaged, absent from `HEAD`,
and the sole final status path.

---

## 15. Required outcome

Phase 2H closes with clean static analysis and style, a disclosure test aligned
to the actual delivered-output boundary, the runner's scenario association
preserved, all black-box behavior passing, and no production or architectural
change. The exact Phase 2 checkpoint is created once and proven completely.

All eight earlier Phase 2H reports remain zero-byte historical records in
`HEAD`; only the populated final-closure report remains outside the commit.
Then await architect review before Phase 3 planning.
