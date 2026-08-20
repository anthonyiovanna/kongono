# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.7
## Final Header-Shape Proof and Phase 2H Gate C Resume

**Directive type:** exact one-file static-analysis correction, Gate C resume, checkpoint reconciliation, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.7

**Depends on:** authenticated Phase 2H.6 stopped at focused PHPStan with one remaining fixture header-shape error

---

## 1. Mission

Correct the sole remaining focused PHPStan error in the already-authorized
Phase 2H fixture:

```text
tests/BlackBox/Fixture/FixtureDelivery.php:101
argument.type

FixtureResponse expects  array<string, list<string>>
PHPStan observes         array<list<string>>
```

The first Phase 2H.6 correction appended values directly into
`$headers[$name][]`. That detached the values but did not prove to PHPStan that
the PSR-provided header key is a string. Make the exact executable key/type
proof in Section 7, rerun focused Gate C, and complete every remaining original
Phase 2H gate.

Do not change production, configuration, analyzer policy, documentation,
dependencies, response semantics, or any other fixture/test file.

---

## 2. Architect decision and narrow supersession

The architect authorizes one further correction in exactly:

```text
tests/BlackBox/Fixture/FixtureDelivery.php
```

Replace only the current header detachment loop with an executable string-key
guard and a separately constructed value list. This is the final refinement of
Phase 2H.5 Section 6.2.

The architect does not authorize:

- a string cast;
- an assertion-only lie;
- a PHPStan suppression, baseline, ignore, stub, or exclusion;
- changing `FixtureResponse`;
- changing any production or package-test file;
- dropping, sorting, joining, normalizing, or otherwise changing response
  headers; or
- modifying any second file.

Every other Phase 2H through Phase 2H.6 contract remains locked, including the
four already-applied static-analysis corrections, direct partial-state manifest
policy, accepted Gate B evidence, exact fixture behavior, trusted/denied policy,
documentation, canonical quality, autoload, checkpoint, report, prohibited
scope, and stop rules.

---

## 3. Independent correction authentication

Before broad validation, editing, staging, Git writes, or report population,
compare this directive with the byte count and SHA-256 supplied independently
by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either independently
supplied value is absent or differs.

The caller must also provide:

```text
docs/task reports/Phase_2H_7_correction.md
```

It must be an ordinary, untracked, unstaged file absent from `HEAD`, zero bytes,
with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte through every non-report gate and never stage it.

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
docs/task reports/Phase_2H_4_correction.md
docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md
docs/task reports/Phase_2H_5_correction.md
docs/dev_plan/kongono_phase_2H_6_codex_directive_correction.md
docs/task reports/Phase_2H_6_correction.md
docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md
tests/BlackBox/Fixture/FixtureDelivery.php
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

All seven earlier reports must remain ordinary, untracked, unstaged, absent
from `HEAD`, zero bytes, and have the empty-file digest above:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
docs/task reports/Phase_2H_2_correction.md
docs/task reports/Phase_2H_3_correction.md
docs/task reports/Phase_2H_4_correction.md
docs/task reports/Phase_2H_5_correction.md
docs/task reports/Phase_2H_6_correction.md
```

Do not populate or change them. They become intentional empty historical
records in the checkpoint only after every corrected pre-commit gate passes.

---

## 5. Accepted entry state

### 5.1 Before Phase 2H.7 caller setup

Require:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        175
total status paths     202
staged paths             0
Git-visible paths      533
```

Require all seven earlier reports to remain zero-byte. Require no executor
change after the Phase 2H.6 focused PHPStan stop.

The accepted correction state is:

- all twelve Phase 2H PHP files pass syntax;
- `FixtureDelivery` has the corrected single-EOF stream loop;
- `FixtureDelivery` currently detaches headers through direct
  `$headers[$name][]` append;
- `Phase2FixtureRunner` has honest raw input PHPDoc and uses a validated
  `list<string>`;
- `Fixture/run.php` uses normalized `$_SERVER['argv']`;
- the black-box test has the executable decoded string-key proof;
- focused PHPStan reports exactly one `argument.type` error at the
  `FixtureResponse` construction and no other error;
- no style, PHPUnit, direct-run, documentation, canonical, autoload, staging,
  commit, or post-commit gate ran after that failure; and
- no production, package-test, configuration, dependency, manifest, lock,
  documentation, index, history, or caller-record mutation occurred during the
  failed correction.

The initial read-only Phase 2H.6 manifest command that used incorrect report
directory prefixes is accepted as a non-mutating executor transcription error.
The corrected command subsequently matched the architect-authenticated
manifest exactly. Do not repeat or treat the discarded 526-record observation
as authority.

### 5.2 After Phase 2H.7 caller setup

After adding only this authenticated directive and its zero-byte report,
require:

```text
tracked modifications  27
untracked paths        177
total status paths     204
staged paths             0
Git-visible paths      535
tracked files          358
```

The only two new paths are:

```text
docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md
docs/task reports/Phase_2H_7_correction.md
```

Require one local branch, no remotes, no tags, and no unexpected
repository-owned refs. Enumerate and preserve environment-managed
`refs/codex/turn-diffs/**` separately.

---

## 6. Direct authenticated post-correction-attempt manifest

Before editing, authenticate the exact current partial state. Enumerate:

```text
git ls-files --cached --others --exclude-standard
```

Normalize separators to `/` and exclude exactly the sixteen Phase 2H through
Phase 2H.7 caller paths: each phase-family directive and each corresponding
report path. Use the exact explicit paths from Section 4 plus the active
Phase 2H.7 pair; do not derive report paths by changing only a directory prefix.

Do not exclude implementation, documentation, tooling, or any other path. Do
not reverse or synthesize file bytes.

Use the Phase 2H.6 established Windows PowerShell `Sort-Object` ordering and
serialize every remaining record as UTF-8 without BOM:

```text
path + NUL + invariant-decimal-byte-length + NUL + uppercase-SHA-256 + LF
```

Require exactly:

```text
records                 519
manifest bytes          63928
manifest SHA-256        32E1A1277A9A5D4D8875135C334327A7B24179EA4638A89D090437CBC13E3BB0
aggregate file bytes    5450730
first path              .editorconfig
last path               tests/Tooling/EnvironmentTest.php
```

This identity directly authenticates the four applied Phase 2H.6 corrections
and the one remaining header-shape error. It is an entry-only baseline and is
expected to change after Section 7. Do not adopt a different observed identity,
reconstruct the original 505 paths, or reverse any accepted byte.

Also verify the exact fourteen-created and twenty-two-modified inventories,
all eight zero-byte reports, file safety, modes, and protected production,
package-test, manifest, lockfile, dependency, and diagnostic-catalog hashes.

---

## 7. Exact one-file header-shape correction

In exactly:

```text
tests/BlackBox/Fixture/FixtureDelivery.php
```

retain:

```php
/** @var array<string, list<string>> $headers */
$headers = [];
```

Replace only the current direct-append loop:

```php
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        $headers[$name][] = $value;
    }
}
```

with this executable proof and detached-list construction:

```php
foreach ($response->getHeaders() as $name => $values) {
    if (!is_string($name)) {
        throw new RuntimeException('Fixture response header name must be a string.');
    }

    $headerValues = [];

    foreach ($values as $value) {
        $headerValues[] = $value;
    }

    $headers[$name] = $headerValues;
}
```

The existing `RuntimeException` import remains. The new failure is fixture-only
and fail-closed. It is not a Kongono diagnostic, production exception, or new
runtime policy.

Require:

- PHPStan proves `$headers` as `array<string, list<string>>` without a cast,
  assertion lie, suppression, or analyzer configuration change;
- every PSR header is copied exactly once;
- header names remain byte-exact and case-exact;
- header insertion order remains exact;
- each header's value order and duplicates remain exact;
- an empty source value array remains an explicitly present empty list rather
  than dropping the header;
- the source response is not mutated;
- the snapshot owns freshly constructed arrays; and
- status, body, send/close behavior, stream limits, cleanup, and all other
  fixture behavior remain unchanged.

If PHPStan reports that `is_string($name)` is redundant or another value-type
error remains, stop. Do not add another edit or workaround under this directive.

---

## 8. Focused Gate C resume

Do not rerun Gate B.

After Section 7:

1. run `php -l` on `FixtureDelivery.php`;
2. run `php -l` on all twelve Phase 2H PHP files;
3. rerun the exact focused PHPStan command:

```text
php vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress tests/BlackBox
```

Require zero errors. The former six findings and the remaining constructor
`argument.type` finding must all be absent. No new finding is accepted.

Then resume the remaining Phase 2H.6 Gate C and later sequence without another
architectural change:

- focused PHP CS Fixer dry-run with zero fixable files;
- black-box and complete/focused PHPUnit suites;
- all success, unmatched, generic, trusted, denied, unsupported, invalid-input,
  multi-scenario, cleanup, health, and isolation direct probes;
- exact response/header/body, JSON-lines, key-order, output, exit-code,
  delivery-count, stream-bound, active-operation, security, and trust proof;
- documentation, ADR, link, current/future, encoding, line-ending, stream,
  ordinary-file, and mode validation;
- exact created/modified/frozen inventories;
- final pre-staging canonical quality through the corrected temporary 512M
  overlay and separate Boolean cleanup predicates;
- permanent PHP restoration to 128M, `C:\php\php.ini`, typed false for no
  scanned files, absent scan variable, and absent temporary paths;
- strict dev/no-dev/dev optimized autoload proof;
- the corrected checkpoint in Section 10;
- every post-commit focused, direct, canonical, documentation, security,
  inventory, preservation, and history gate; and
- population of only the Phase 2H.7 report after every non-report gate passes.

The accepted Phase 2H.2 Gate B evidence remains sufficient. Later mandatory
canonical runs are not Gate B retries.

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

Before staging require:

```text
tracked modifications  27
untracked paths        177
total status paths     204
staged paths             0
Git-visible paths      535
tracked files          358
```

The increase beyond the original 505-path snapshot is exactly sixteen Phase
2H-family caller records and fourteen Phase 2H-created paths. The one-file
correction does not add or remove a path.

---

## 10. Corrected checkpoint boundary

### 10.1 Exact staging set

Stage exactly:

1. all 174 accepted pre-Phase-2H status paths;
2. Phase 2H directive and zero-byte report;
3. Phase 2H.1 directive and zero-byte report;
4. Phase 2H.2 directive and zero-byte report;
5. Phase 2H.3 directive and zero-byte report;
6. Phase 2H.4 directive and zero-byte report;
7. Phase 2H.5 directive and zero-byte report;
8. Phase 2H.6 directive and zero-byte report;
9. `docs/dev_plan/kongono_phase_2H_7_codex_directive_correction.md`; and
10. the fourteen original Phase 2H-created paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_7_correction.md
```

Materialize exactly 203 literal staging paths using the original explicit,
pathspec-safe, no-wildcard, no-directory-staging rules.

After staging require:

```text
staged paths                  203
staged modified paths          27
staged added paths            176
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 534
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.7 report. All seven earlier
reports are staged as exact empty blobs.

### 10.2 Tree and commit

Require exact staged inventory, staged blob/worktree equality, intended modes,
no conflict stages, clean `git diff --cached --check`, protected hashes, and
active-report absence from the index.

`git write-tree` must contain exactly 534 paths, all `100644`. Semantic staged
paths, blobs, modes, and tree remain authoritative over cache-tree metadata in
the raw index bytes.

Use exactly:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      534
tree modes      all 100644
delta paths     203
modified        27
added           176
deleted         0
```

Retain all original author, hook, signing, branch, ref, remote, tag, history,
and prohibited Git-operation rules. Do not amend or reset.

### 10.3 Post-commit state

Before report population require:

```text
tracked files          534
tracked modifications   0
staged paths             0
untracked paths          1
total status paths       1
```

The sole path is the zero-byte Phase 2H.7 report. All seven earlier reports must
exist in `HEAD` as exact empty blobs. Run every post-commit gate before report
population.

---

## 11. Acceptance criteria

Every original Phase 2H through Phase 2H.6 criterion remains unless explicitly
replaced below:

1. Phase 2H.7 directive identity matched independently supplied values.
2. All eight reports were exact zero-byte caller records at entry.
3. The exact `27/177/204/0`, 535-visible, 358-tracked state matched.
4. The direct post-correction-attempt manifest matched exactly 519 records,
   63928 bytes, `32E1A127...`, aggregate 5450730 bytes, and exact first/last
   paths.
5. The manifest used actual bytes and exact explicit caller exclusions.
6. The first four Phase 2H.6 corrections remained present and confined to their
   authorized paths.
7. Only `FixtureDelivery.php` changed under Phase 2H.7.
8. Header names receive an executable string proof.
9. Each header value sequence is constructed separately as a fresh ordered
   list before assignment.
10. Empty lists, duplicates, byte/case, insertion order, and value order are
    preserved.
11. Focused PHPStan reports zero errors without suppression or weakened scope.
12. All syntax, style, PHPUnit, direct-run, security, trust, cleanup, isolation,
    documentation, link, encoding, stream, mode, inventory, canonical, and
    autoload gates pass.
13. Production, package tests, configuration, manifests, dependencies,
    lockfile, diagnostic catalog, and runtime policy remain frozen.
14. `KGN-KERNEL-9001` remains test-only; trusted output requires both
    permissions and other paths remain generic.
15. Final canonical overlays clean up completely and permanent PHP state is
    restored exactly.
16. Exactly 203 paths are staged: 27 modified, 176 added, zero deleted.
17. Index and tree contain exactly 534 ordinary `100644` files.
18. The exact single checkpoint commit is created with the locked parent,
    subject, author, tree, delta, and history shape and is not amended.
19. Every post-commit gate passes.
20. All seven prior reports are committed as empty blobs.
21. Only the Phase 2H.7 report remains outside `HEAD`, index, and tracked state.
22. The active report is populated only after every non-report gate passes.
23. Phase 2 is declared closed only if no blocker or failed criterion remains.

Any failure means status cannot be Complete.

---

## 12. Prohibited scope

The complete prohibited scope of Phase 2H through Phase 2H.6 remains. Also do
not:

- modify a second file under this correction;
- cast the header key or values;
- use `assert()`, analyzer-only assertions, suppression, ignore, baseline, stub,
  exclusion, or configuration change to force the type;
- change `FixtureResponse`, PSR interfaces, production, package tests,
  documentation, manifests, dependencies, lockfile, or diagnostic catalog;
- sort, normalize, merge, split, join, filter, or drop headers;
- omit an empty header value list;
- change response status/body, stream behavior, send/close counts, cleanup,
  scenarios, output protocol, trust policy, or isolation;
- rerun Gate B;
- populate an earlier report;
- stage the Phase 2H.7 report;
- repair unrelated caller-managed state;
- amend, reset, rewrite, merge, or expand history; or
- begin Phase 3.

---

## 13. Stop rules

Stop and preserve exact state if:

- directive identity, caller record, entry state, direct manifest, inventory,
  hash, mode, stream, or accepted correction evidence differs;
- the exact current loop is not present once before editing;
- the correction requires another file or semantic change;
- focused PHPStan reports any error after the exact edit;
- syntax, style, PHPUnit, direct runner, security, trust, isolation, cleanup,
  documentation, canonical, autoload, staging, tree, commit, or post-commit
  proof fails;
- corrected counts differ;
- temporary PHP state cannot be proven restored; or
- a prohibited operation would be needed.

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

## 14. Phase 2H.7 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_7_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.7 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. DIRECT PARTIAL-STATE MANIFEST
## 6. GATE B EVIDENCE
## 7. STATIC ANALYSIS CORRECTIONS
## 8. HEADER-SHAPE PROOF
## 9. FILES CREATED
## 10. FILES MODIFIED
## 11. RUNNABLE FIXTURE BOUNDARY
## 12. DELIVERY AND OUTPUT CONTRACT
## 13. BLACK-BOX ACCEPTANCE
## 14. SECURITY AND TRUST PROOF
## 15. PERSISTENT-RUNTIME AND CLEANUP PROOF
## 16. ARCHITECTURAL DECISIONS
## 17. DEVIATIONS FROM DIRECTIVE
## 18. DEPENDENCIES ADDED/REMOVED
## 19. ENVIRONMENT
## 20. COMMANDS RUN
## 21. PRE-COMMIT VALIDATION RESULTS
## 22. STAGING EVIDENCE
## 23. COMMIT EVIDENCE
## 24. POST-COMMIT VALIDATION RESULTS
## 25. DOCUMENTATION REVIEW
## 26. SECURITY AND FILE-SAFETY REVIEW
## 27. WARNINGS / KNOWN ISSUES
## 28. QUESTIONS / BLOCKERS
## 29. FINAL GIT STATUS
## 30. ACCEPTANCE CRITERIA
## 31. NEXT STEP
```

Include every original Phase 2H/Phase 2H.6 report requirement plus:

- independently supplied Phase 2H.7 identity;
- identities of all Phase 2H-family directives and reports;
- exact 519-record `32E1A127...` entry-manifest evidence;
- the exact before/after header loop and one-file correction proof;
- the eight original/follow-up PHPStan findings and final zero-error result;
- explicit no-cast, no-suppression, no-production-change proof;
- syntax, style, PHPUnit, direct-run, security, trust, isolation, cleanup,
  documentation, canonical, and autoload results with exact observed totals;
- all seven earlier zero-byte reports staged and committed;
- exact `27/177/204/0`, 535-visible pre-stage evidence;
- exact 203-path staging and 534-path tree/commit evidence;
- final sole-report status;
- whether Phase 2 is fully closed; and
- final report byte count and SHA-256 returned independently outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 3 planning.
```

The report must be strict UTF-8 without BOM, LF-only, exactly one final LF, no
trailing whitespace or NUL, ordinary, untracked, unstaged, absent from `HEAD`,
and the sole final status path.

---

## 15. Required final outcome

The fixture proves its PSR header names at runtime and detaches each value
sequence into a fresh ordered list. Focused PHPStan passes without a cast,
suppression, analyzer exception, or production change. All previously corrected
fixture behavior remains exact.

Phase 2H then completes every original behavioral, security, documentation,
canonical, autoload, checkpoint, and post-commit gate. All seven earlier reports
remain zero-byte historical records in `HEAD`; only the populated Phase 2H.7
report remains outside the commit. Then await architect review before Phase 3
planning.
