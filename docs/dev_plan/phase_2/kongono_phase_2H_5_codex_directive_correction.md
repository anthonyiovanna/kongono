# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2H.5
## Focused Black-Box Static-Analysis Corrections and Phase 2H Completion Resume

**Directive type:** exact test-fixture correction, Gate C resume, checkpoint reconciliation, and completion

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2H.5

**Depends on:** authenticated Phase 2H.4 implementation stopped at focused Gate C PHPStan

---

## 1. Mission

Correct exactly the six focused PHPStan findings exposed by the completed Phase
2H black-box fixture. Do not change production code, production behavior,
architecture, dependencies, the trusted-diagnostic policy, or the accepted
Phase 2H.2 Gate B evidence.

The accepted Phase 2H implementation and documentation boundary is already
present. Syntax and direct scenario proofs pass, including the corrected
test-only diagnostic identifier `KGN-KERNEL-9001`. Focused PHPStan found only
these issues:

```text
tests/BlackBox/Fixture/FixtureDelivery.php:83
  remembered eof() state makes a negated expression always true

tests/BlackBox/Fixture/FixtureDelivery.php:93
  PSR header arrays are not proven to be lists

tests/BlackBox/Fixture/Phase2FixtureRunner.php:76
  array_is_list() is redundant under the current PHPDoc

tests/BlackBox/Fixture/Phase2FixtureRunner.php:80
  is_string() is redundant under the current PHPDoc

tests/BlackBox/Fixture/run.php:9
  $argv might be undefined

tests/BlackBox/Phase2RunnableWebFrameworkTest.php:280
  decoded result keys are not proven strings
```

Make only the bounded corrections in Section 6, rerun focused Gate C from the
failed PHPStan step, complete all remaining original Phase 2H validation,
stage the corrected closed checkpoint, commit it once, run post-commit proof,
and populate only the Phase 2H.5 correction report.

---

## 2. Architect decision and narrow supersession

The architect authorizes corrections in exactly these four existing Phase 2H
test-owned PHP files:

```text
tests/BlackBox/Fixture/FixtureDelivery.php
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Fixture/run.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

These files are already inside the authenticated fourteen-created-path Phase
2H boundary. This correction adds no path and changes no file inventory.

The authorized corrections are limited to:

1. remove the redundant second `eof()` check after an empty stream read while
   retaining fail-closed partial-read behavior;
2. detach response header value arrays through ordered append into lists before
   constructing the fixture snapshot;
3. broaden the fixture runner's input PHPDoc to the values it actually accepts,
   then build and use a validated `list<string>`;
4. obtain CLI arguments from a PHPStan-visible `$_SERVER['argv']` value and
   normalize a non-array value to an empty array;
5. prove every decoded JSON result key is a string while constructing a fresh
   `array<string, mixed>`; and
6. make only mechanical local adjustments needed for those exact corrections
   to pass PHPStan, PHP CS Fixer, and PHPUnit without suppression.

This directive supersedes only the original Phase 2H instructions to stop after
the six focused PHPStan findings. Every other Phase 2H-family contract remains
locked, including:

- all production code and package tests remain frozen;
- `KGN-KERNEL-9001` remains the sole test-only supported Kernel identifier;
- both independent permissions remain necessary for trusted developer output;
- denied, unsupported, or failed presentation remains generic;
- exact twelve-PHP-file and fourteen-created-path inventories;
- exact twenty-two modified existing paths;
- accepted Phase 2H.2 Gate B evidence, which must not be rerun;
- separate Boolean cleanup predicates for every later overlay;
- exact pre-stage, staging, checkpoint, and post-commit proof; and
- the complete original prohibited scope and stop rules.

Do not add an ignore, baseline, suppression, analyzer exclusion, type-assertion
lie, environment requirement, or production workaround.

---

## 3. Independent correction authentication

Before broad validation, editing, staging, Git writes, or report population,
compare this correction artifact with the byte count and SHA-256 supplied
independently by the architect outside this file.

Required path:

```text
docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md
```

Do not self-authenticate from the local file. Stop if either independently
supplied value is absent or differs.

The caller must also provide this ordinary zero-byte placeholder:

```text
docs/task reports/Phase_2H_5_correction.md
```

It must be untracked, unstaged, absent from `HEAD`, and have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero-byte through every non-report gate and never stage it.

---

## 4. Required authorities and caller records

Read completely before continuing:

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
tests/BlackBox/Fixture/FixtureDelivery.php
tests/BlackBox/Fixture/Phase2FixtureRunner.php
tests/BlackBox/Fixture/run.php
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

Verify these exact prior directive identities:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_2H_codex_directive.md` | 57027 | `29846CFACBF867827656E317A5156F84C2D0E10ADEFBB69ED83D557788864AAC` |
| `docs/dev_plan/kongono_phase_2H_1_codex_directive_correction.md` | 20701 | `5302D1A8EE111F0E03EB5BAAA6EF0FF199A1D449A6F55A696032E14D9203F048` |
| `docs/dev_plan/kongono_phase_2H_2_codex_directive_correction.md` | 21134 | `A6A53FF03C7D52D80381C5C6C71C154A6D475A997BF915A47E24D639C5BE80B1` |
| `docs/dev_plan/kongono_phase_2H_3_codex_directive_correction.md` | 19559 | `F804DBCE8863CB1D95E561598BE11A5CBBA343CF2422FB6AC8C3B408337541F3` |
| `docs/dev_plan/kongono_phase_2H_4_codex_directive_correction.md` | 20948 | `56808440DBD44AA4FAA96CAC9B02FE9AABF29F224F99F4288518A743B6152D4E` |

The prior Phase 2H reports must remain ordinary, untracked, unstaged, absent
from `HEAD`, zero bytes, and have the empty-file digest above:

```text
docs/task reports/Phase_2H.md
docs/task reports/Phase_2H_1_correction.md
docs/task reports/Phase_2H_2_correction.md
docs/task reports/Phase_2H_3_correction.md
docs/task reports/Phase_2H_4_correction.md
```

Do not populate or change them. They become intentional zero-byte historical
records in the checkpoint only after every corrected pre-commit gate passes.

---

## 5. Accepted entry state

### 5.1 Before Phase 2H.5 caller setup

Require the preserved state reported by Phase 2H.4:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        171
total status paths     198
staged paths           0
Git-visible paths      529
```

The accepted implementation progress is exactly:

- all twelve authorized PHP files under `tests/BlackBox/` exist;
- both authorized new documentation paths exist;
- all twenty-two authorized existing paths contain their Phase 2H work;
- `phpstan.neon.dist` contains exactly the authorized `tests/BlackBox` path;
- every one of the twelve fixture PHP files passes syntax;
- trusted HTML works with `KGN-KERNEL-9001`;
- bootstrap denial, delivery denial, unsupported native failure, and generic
  failure return exact generic output;
- matched success, unmatched 404, middleware order, delivery counts, cleanup,
  subsequent-operation health, and isolation behave correctly;
- `KGN-KERNEL-9001` remains absent from production;
- focused PHPStan reports exactly the six findings listed in Section 1; and
- no staging, commit, ref, history, dependency, production, package-test, or
  permanent-environment mutation occurred.

Gate B remains accepted from Phase 2H.2 and must not be rerun.

### 5.2 After Phase 2H.5 caller setup

After the caller adds only this authenticated directive and its zero-byte
report placeholder, require:

```text
tracked modifications  27
untracked paths        173
total status paths     200
staged paths           0
Git-visible paths      531
tracked files          358
```

The only two new paths are:

```text
docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md
docs/task reports/Phase_2H_5_correction.md
```

Reconstruct and verify the original authenticated 505-path snapshot exactly as
required by Phase 2H.4, now excluding all twelve Phase 2H-family caller paths
and the fourteen Phase 2H-created implementation/documentation paths, and
reversing only the authorized `tests/BlackBox` PHPStan line for the original
`phpstan.neon.dist` proof.

Require:

```text
records          505
manifest bytes   62292
SHA-256          A896603891337710254047456AD949B681B05326C698CC97E4905D0500D323A9
```

Verify the complete created/modified inventories against Phase 2H.4. Stop if a
path, count, hash, mode, stream, report, or accepted partial-state fact differs.
Do not normalize or re-create accepted work.

---

## 6. Exact authorized corrections

### 6.1 `FixtureDelivery.php`: stream progress proof

In:

```text
tests/BlackBox/Fixture/FixtureDelivery.php
```

retain the existing bounded loop, rewind, maximum-body enforcement, ordered
chunk append, and partial-read exception. Inside the loop, replace the empty
chunk condition that calls `eof()` a second time with an empty-chunk condition
that directly throws the same existing partial-read exception.

The resulting rule is:

```php
if ('' === $chunk) {
    throw new RuntimeException('Fixture response body read was partial.');
}
```

Use that exact existing exception text. The surrounding loop condition
remains the only EOF decision for that iteration. Do not treat an empty read
before EOF as successful completion, spin, or silently truncate the body.

This correction must preserve:

- body rewind before reading;
- maximum captured-body size and overflow behavior;
- byte order and exact body bytes;
- a thrown fixture exception for a zero-progress read while the loop is active;
- send/close counts and cleanup; and
- all production delivery behavior.

### 6.2 `FixtureDelivery.php`: detached header lists

Before constructing `FixtureResponse`, obtain the response headers once and
copy them into a new local value declared and proven as:

```php
/** @var array<string, list<string>> $headers */
$headers = [];
```

For every header name and value array, create a fresh local list and append
each string value in original order:

```php
$headerValues = [];

foreach ($values as $value) {
    $headerValues[] = $value;
}

$headers[$name] = $headerValues;
```

Pass `$headers`, not the unproven PSR array, to `FixtureResponse`.

Require:

- header names and values remain exact;
- insertion order and value order remain exact;
- duplicates are preserved;
- no sorting, case normalization, joining, splitting, filtering, or mutation of
  the PSR response occurs;
- no cast is used to conceal an invalid shape; and
- the snapshot owns detached arrays.

If PHPStan cannot prove the header name or value type from the installed PSR
interface, add only an explicit runtime `is_string()` fail-closed guard in this
fixture before assignment. Do not use an assertion-only annotation that
contradicts runtime data.

### 6.3 `Phase2FixtureRunner.php`: honest input type and validated list

In:

```text
tests/BlackBox/Fixture/Phase2FixtureRunner.php
```

change the PHPDoc for the public runner input and its private configuration
parser from `list<string>` to the honest raw-entry type:

```php
@param array<array-key, mixed> $arguments
```

Keep the existing runtime requirements for a list, minimum argument count, and
string-only elements. During that validation, build a fresh list:

```php
/** @var list<string> $validatedArguments */
$validatedArguments = [];

foreach ($arguments as $argument) {
    if (!is_string($argument)) {
        return null;
    }

    $validatedArguments[] = $argument;
}
```

After validation, use `$validatedArguments` for permission-mode selection,
scenario extraction, and every subsequent runner decision. Do not continue to
use the raw array for typed configuration behavior.

The exact accepted public behavior remains:

- non-list, undersized, or non-string input fails through the existing bounded
  CLI usage/error contract;
- only the already-authorized permission modes are accepted;
- scenario order and duplicates remain exact;
- no new option, environment input, authorization signal, or scenario exists;
- user input never controls trusted permission; and
- success and failure exit codes remain unchanged.

Do not delete `array_is_list()` or `is_string()`. Their runtime validation is
intentional; the former PHPDoc was the defect.

### 6.4 `run.php`: PHPStan-visible CLI entry value

In:

```text
tests/BlackBox/Fixture/run.php
```

replace direct reliance on possibly undefined `$argv` with `$_SERVER['argv']`.
Read it once, default to an empty array when absent, and normalize a non-array
value to an empty array before `array_slice()`:

```php
$arguments = $_SERVER['argv'] ?? [];

if (!is_array($arguments)) {
    $arguments = [];
}
```

Then pass `array_slice($arguments, 1)` through the unchanged runner call and
unchanged `STDOUT`/`STDERR` boundary.

Do not read query parameters, request state, environment variables, files, or
another global. Do not add a CLI parser or change direct-run syntax. Missing or
malformed CLI state must flow to the existing bounded usage failure.

### 6.5 `Phase2RunnableWebFrameworkTest.php`: string-keyed decoded record

In:

```text
tests/BlackBox/Phase2RunnableWebFrameworkTest.php
```

retain JSON decoding, array proof, exact key order, exact result-key inventory,
line count, and every scenario assertion. Before returning each decoded record,
copy it through an explicit string-key proof into a new value whose PHPDoc
return shape is:

```php
array<string, mixed>
```

An authorized helper shape is:

```php
/**
 * @param array<array-key, mixed> $record
 *
 * @return array<string, mixed>
 */
private function stringKeyedRecord(array $record): array
{
    $validated = [];

    foreach ($record as $key => $value) {
        if (!is_string($key)) {
            self::fail('Fixture result keys must be strings.');
        }

        $validated[$key] = $value;
    }

    return $validated;
}
```

The exact helper name and fixed test-only failure text may follow the existing
test vocabulary, but the proof must execute at runtime and return a freshly
constructed `array<string, mixed>`. Apply the existing exact key-order assertion
to the proven value before appending it to the decoded list.

Do not cast keys, coerce values, sort records, weaken the expected key list, or
replace semantic assertions with source-text inspection.

### 6.6 Mechanical allowance

Within only the four paths in Section 2, the executor may make the minimum
formatting, local-variable, private-helper, PHPDoc, and call-site adjustment
strictly required for the five corrections above. This allowance does not
authorize:

- a fifth path;
- another scenario or result field;
- public API expansion;
- suppression or analyzer configuration change;
- altered trusted/generic output;
- changed middleware, lifecycle, request, delivery, cleanup, or isolation
  behavior; or
- production/package-test edits.

Stop and report exact expected/observed evidence if a clean focused PHPStan run
would require anything beyond this boundary.

---

## 7. Focused Gate C resume

Gate B is accepted and must not be rerun. Resume Gate C at the failed focused
PHPStan step after first running syntax checks on the four corrected files.

### 7.1 Syntax and focused PHPStan

Run `php -l` on the four corrected files and on all twelve Phase 2H PHP files.
Require every file to pass.

Run the original focused PHPStan command over the Phase 2H black-box boundary
with the same configuration and memory authority already specified by the
Phase 2H family. Require:

```text
errors 0
```

All six reported findings must be absent. No new finding is accepted. Do not
change `phpstan.neon.dist` beyond its already-authorized `tests/BlackBox` path,
and do not introduce `@phpstan-ignore`, `@phpstan-ignore-next-line`, baseline,
stub, exclusion, `ignoreErrors`, or an artificially narrowed analyzed path.

### 7.2 Focused style and PHPUnit

Run the original Phase 2H focused PHP CS Fixer dry-run against all twelve PHP
files. Require zero fixable files. Formatting may be applied only to the four
corrected paths and only when required for the locked style.

Run the exact black-box PHPUnit suite and require every test and assertion to
pass. Also rerun the complete package/test suite required by Phase 2H.

The test proof must include:

- matched success;
- unmatched 404;
- middleware order;
- generic native failure;
- trusted `KGN-KERNEL-9001` output with both permissions;
- bootstrap-denied generic fallback;
- delivery-denied generic fallback;
- unsupported-native generic fallback;
- request/send/close exact counts;
- bounded body and partial-read behavior;
- exact headers as detached ordered lists;
- sequential health and zero active operations;
- persistent-runtime isolation; and
- malformed runner input and decoded-record validation.

### 7.3 Direct runner and security proof

Run every original direct fixture command for success, unmatched, generic,
trusted, denied, unsupported, multiple sequential scenarios, and invalid CLI
input. Prove exact exit code, stdout JSON-lines protocol, empty/expected stderr,
result-key order, and isolation.

Prove again that trusted output contains no route URI, path parameter, request
value, CLI mode, scenario token, native sentinel, trace, local path, credential,
or secret. Prove generic responses contain no `KGN-KERNEL-9001` or trusted
metadata. Prove the test-only identifier remains absent from production.

If any behavior changes beyond the corrections, stop.

---

## 8. Complete all remaining original gates

After focused Gate C passes, continue from the original Phase 2H.4 sequence:

1. validate exact fourteen-created and twenty-two-modified path inventories;
2. validate ADR 0050, runnable-fixture documentation, and every reconciliation;
3. validate links, current-versus-future language, security claims, encodings,
   line endings, final LFs, ordinary-file status, streams, and modes;
4. validate production/package-test/manifest/lock/dependency freeze;
5. run final pre-staging canonical `composer quality` using the corrected
   temporary 512M overlay and separate Boolean cleanup predicates;
6. permit only the original identical network/cache retry when the literal
   canonical command fails solely because access is blocked;
7. prove permanent PHP state is restored to 128M, `C:\php\php.ini`, no scanned
   files where typed `false` means none, absent `PHP_INI_SCAN_DIR`, and no
   temporary path;
8. run strict dev/no-dev/dev optimized autoload proof;
9. execute the checkpoint in Section 10;
10. run every post-commit focused, canonical, direct-run, documentation,
    security, inventory, hash, mode, history, and preservation proof; and
11. populate only the Phase 2H.5 report after all non-report gates pass.

No accepted Gate B rerun is allowed. A later canonical run is a distinct
mandatory final/post-commit gate, not a Gate B retry.

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

The corrections add no file and remove no file. Before staging require:

```text
tracked modifications  27
untracked paths        173
total status paths     200
staged paths             0
Git-visible paths      531
tracked files          358
```

The increase beyond the original authenticated 505-path snapshot is exactly:

- twelve Phase 2H-family caller records; and
- fourteen original Phase 2H implementation/documentation paths.

Replace every earlier Phase 2H final pre-stage total with
`27/173/200/0`, 531 Git-visible paths, and 358 tracked files.

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
7. `docs/dev_plan/kongono_phase_2H_5_codex_directive_correction.md`; and
8. the fourteen original Phase 2H-created paths.

Exclude exactly:

```text
docs/task reports/Phase_2H_5_correction.md
```

Materialize exactly 199 literal ordinal staging paths. Use the original
pathspec-safe literal array, no wildcard, no directory staging, and no inferred
inventory.

After staging require:

```text
staged paths                  199
staged modified paths          27
staged added paths            172
staged deleted paths            0
unstaged tracked paths          0
untracked paths                 1
index entries                 530
index modes other than 100644   0
```

The sole untracked path is the zero-byte Phase 2H.5 report. All five earlier
Phase 2H reports are staged as exact empty blobs.

### 10.2 Staged proof, tree, and commit

Require:

- exact 199-path staged inventory in ordinal bytewise order;
- all staged blobs equal working bytes;
- exact 27-modified/172-added/zero-deleted split;
- no unstaged tracked path, conflict stage, symlink, reparse point, alternate
  stream, executable mode, submodule, or unexpected file;
- ordinary `100644` modes only;
- `git diff --cached --check` passes without exception;
- protected production/package-test/manifest/lock hashes remain exact; and
- the active report is absent from the index.

The authorized `git write-tree` must contain exactly 530 paths, all `100644`.
Treat semantic staged paths, blobs, modes, and resulting tree as authoritative;
do not stop merely because Git writes cache-tree metadata into `.git/index`.

Use exactly this unchanged subject:

```text
Establish Kongono runnable web framework milestone through Phase 2H
```

Require:

```text
parent          d7846f137a1d53f5306f48d19196b916fde44ac2
parent count    1
commit count    10
tree paths      530
tree modes      all 100644
delta paths     199
modified        27
added           172
deleted         0
```

Use the original effective author and unchanged hook/signing rules. Do not
amend, reset, rewrite, merge, create another branch, create a tag, add a remote,
or stage the active report.

### 10.3 Post-commit state

Before report population require:

```text
tracked files          530
tracked modifications   0
staged paths             0
untracked paths          1
total status paths       1
```

The sole path is the zero-byte Phase 2H.5 report. All five earlier reports must
exist in `HEAD` as exact empty blobs. Run every remaining post-commit gate before
populating the active report.

---

## 11. Acceptance criteria

Every original Phase 2H through Phase 2H.4 criterion remains, with these exact
substitutions and additions:

1. Phase 2H.5 directive identity matched independently supplied values.
2. The `27/173/200/0`, 531-visible entry state and original 505-path manifest
   matched exactly.
3. All five earlier Phase 2H reports remained exact zero-byte caller records.
4. Only the four Section 2 fixture/test files changed for this correction.
5. The stream loop has one EOF decision per active iteration and fails closed
   on a zero-progress read.
6. Response header values are detached into ordered `list<string>` values.
7. Runner raw input PHPDoc is honest and runtime validation constructs the
   typed list used downstream.
8. CLI arguments come only from normalized `$_SERVER['argv']`.
9. Decoded result records receive an executable string-key proof and preserve
   exact key order.
10. All twelve PHP files pass syntax.
11. Focused PHPStan reports zero errors and all six findings are gone.
12. No suppression, baseline, ignore, exclusion, stub, or weakened analysis was
    introduced.
13. Focused style reports zero fixable files.
14. Black-box and complete PHPUnit suites pass with no skipped acceptance proof.
15. Every direct runner scenario, output, exit-code, cleanup, isolation, and
    security proof passes.
16. `KGN-KERNEL-9001` remains test-only and absent from production.
17. Trusted output still requires both permissions; denied and unsupported
    paths remain exact generic output.
18. Production, package tests, manifests, dependencies, lockfile, diagnostic
    catalog, and tooling remain frozen except the already-authorized
    `tests/BlackBox` PHPStan path.
19. The exact fourteen-created and twenty-two-modified inventories remain.
20. Documentation, links, current/future statements, security language,
    encoding, line-ending, stream, ordinary-file, and mode gates pass.
21. Final pre-stage canonical quality passes through the temporary overlay and
    permanent PHP state is restored exactly.
22. Strict dev/no-dev/dev optimized autoload gates pass.
23. Exactly 199 paths are staged: 27 modified, 172 added, zero deleted.
24. Index and tree contain exactly 530 ordinary `100644` files.
25. One commit with the exact subject, parent, delta, author, and history shape
    is created; it is not amended.
26. Every post-commit focused, canonical, direct-run, preservation, and
    repository gate passes.
27. All five prior reports are committed as empty blobs.
28. Only the Phase 2H.5 report remains outside `HEAD`, index, and tracked state.
29. The report is populated only after every non-report criterion passes.
30. Phase 2 is declared closed only if every original and corrected criterion
    passes without unresolved warning or blocker.

Any failure means status cannot be Complete.

---

## 12. Prohibited scope

The complete prohibited scope of Phase 2H through Phase 2H.4 remains. Also do
not:

- modify any production or package-test PHP file;
- modify any manifest, dependency, lockfile, runtime configuration, composer
  script, PHPStan rule, baseline, ignore, or exclusion;
- alter `phpstan.neon.dist` beyond its already-present authorized line;
- remove runtime validation merely because a former PHPDoc made it redundant;
- cast or coerce invalid headers, CLI arguments, or decoded keys into validity;
- accept a zero-progress stream read, loop indefinitely, or truncate output;
- change response bytes, headers, status, result keys, scenario protocol,
  trusted policy, middleware, lifecycle, cleanup, or isolation behavior;
- change `KGN-KERNEL-9001` or assign it to production;
- add or delete a fixture, test, documentation, or report path;
- rerun Gate B;
- populate an earlier report;
- stage the Phase 2H.5 report;
- repair or clean unrelated caller-managed dirty state;
- amend/reset/rewrite history; or
- begin Phase 3.

---

## 13. Stop rules

Stop and preserve exact state if:

- correction identity, caller record, entry state, original manifest, path
  inventory, hash, mode, stream, or accepted partial-state evidence differs;
- any of the six corrections requires a fifth file or production change;
- focused PHPStan has any remaining or new error;
- syntax, style, PHPUnit, direct runner, security, isolation, cleanup,
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
accepted partial implementation and never amend or reset a created commit.

---

## 14. Phase 2H.5 correction completion report

After every original and corrected non-report gate passes, populate only:

```text
docs/task reports/Phase_2H_5_correction.md
```

Use exactly these headings:

```text
# PHASE 2H.5 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. GATE B EVIDENCE
## 6. STATIC ANALYSIS CORRECTIONS
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

Include every original Phase 2H completion-report requirement plus:

- independently supplied Phase 2H.5 identity;
- identities of every Phase 2H-family directive and report;
- exact accepted PHPStan findings and exact four-file correction boundary;
- before/after evidence for each of the six findings;
- explicit no-suppression and no-production-change proof;
- syntax, focused PHPStan/style/PHPUnit, direct runner, security, isolation, and
  cleanup results;
- exact test/assertion and fixer-file counts observed, without inventing them;
- accepted Gate B evidence and distinct later canonical results;
- all five earlier zero-byte reports staged and committed;
- exact `27/173/200/0`, 531-visible pre-stage evidence;
- exact 199-path staging and 530-path tree/commit evidence;
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

The six fixture-only static-analysis findings are corrected through honest raw
types, executable runtime proofs, detached ordered values, bounded stream
progress, and a defined CLI argument source. No suppression and no production
change is used. The runnable framework milestone retains identical success,
failure, trusted, denied, cleanup, persistent-runtime, and security behavior.

Phase 2H then completes every original focused, canonical, documentation,
autoload, checkpoint, and post-commit gate. All five earlier reports remain
zero-byte historical records in `HEAD`; only the populated Phase 2H.5 report
remains outside the commit. Then await architect review before Phase 3 planning.
