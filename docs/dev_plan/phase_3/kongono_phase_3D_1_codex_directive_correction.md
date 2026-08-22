# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 3D.1

## HEAD Completion and Deterministic PHPStan Closure

**Repository:** `C:\Users\antho\Dev\kongono`
**Execution role:** correction executor, not architect
**Authority:** this authenticated directive only
**Starting point:** preserved Phase 3D worktree after architect review
**Outcome:** close Phase 3D without beginning Phase 3E

---

## 1. Mission

Phase 3D implemented the bounded PHP CGI response-staging and emission boundary,
but architect review found two closure defects:

1. `CgiResponseEmission::emitStaged()` returns early for `HEAD` after emitting
   status, application headers, and computed `Content-Length`. That correctly
   suppresses body writes, but it bypasses the common local
   `completed = true` transition required by the authenticated Phase 3D
   sequence.
2. The first exact focused PHPStan command exhausted the normal 128 MiB process
   limit. Phase 3D required an immediate stop on that condition, but execution
   continued through a 512 MiB diagnostic/cache-warming run and a later 128 MiB
   retry. The Phase 3D report then recorded no deviation and described the
   cache-warmed retry as an accepted pass.

Correct only those closure defects:

- replace the `HEAD` early return with a negative body-write guard so both
  `HEAD` and non-`HEAD` success reach the common completion transition;
- validate the focused scope through one explicit, supported, serial 512 MiB
  PHPStan process;
- validate the canonical repository through the already-accepted root
  `composer quality` script, whose `analyse` child already receives 512 MiB;
- record the prior gate/deviation inaccuracies truthfully in the new Phase
  3D.1 correction report; and
- leave the original Phase 3D report unchanged as historical evidence.

Do not redesign the emitter, expose completion state, add a new API, change a
tooling file, or begin Phase 3E.

---

## 2. Mandatory stop rules

Stop immediately, preserve the exact reached state, report expected and
observed values, and ask for the smallest authenticated correction if any of
the following occurs:

- the independently delivered identity for this directive is absent or does
  not match;
- a required authority or caller record is absent, non-ordinary, staged,
  unexpectedly tracked, or has the wrong identity;
- the locked Git identity, semantic index, status counts, path inventories, or
  preservation manifest differs;
- the exact source starting identity differs;
- the exact old source block occurs other than once;
- the exact correction produces bytes other than the locked target identity;
- any path other than the one authorized production source and the active
  correction report changes;
- focused PHPStan fails under the exact authorized 512 MiB serial command;
- focused style, package tests, architecture tests, or canonical quality fails;
- normal PHP no longer reports `memory_limit=128M` before or after validation;
- `PHP_INI_SCAN_DIR` is introduced or any permanent PHP, Composer, PHPStan,
  PHPUnit, or PHP CS Fixer configuration changes;
- staging, commit, branch, tag, remote, ref, history, index, dependency,
  lockfile, or Phase 3E work is attempted; or
- a requirement is ambiguous.

On a stop:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE WORKTREE, INDEX, HISTORY, AND CALLER RECORDS
DO NOT GUESS, RETRY A NON-NETWORK FAILURE, OR EXPAND SCOPE
```

The only retry authorized by this directive is one literal, unchanged
`composer quality` retry with approved network/cache access when the first
attempt fails solely because the managed environment cannot reach the locked
advisory service or its normal cache. No PHPStan memory/process failure is a
network retry.

---

## 3. Independent directive authentication

The architect must supply the expected byte count and SHA-256 for this file
outside the file body:

```text
docs/dev_plan/kongono_phase_3D_1_codex_directive_correction.md
```

The executor must calculate the local identity and compare it to those
independently delivered values before reading beyond authentication or running
quality, editing, staging, or committing.

The local file is not self-authenticating. Do not adopt locally observed values
as authority.

The caller must also provide this ordinary zero-byte placeholder:

```text
docs/task reports/Phase_3D_1_correction.md
```

It must be untracked, unstaged, absent from `HEAD`, exactly zero bytes, and have
SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Keep it zero bytes until every non-report gate passes.

---

## 4. Required authorities

After authentication and before mutation, read each file completely:

```text
AGENTS.md
ARCHITECTURE.md
SECURITY.md
docs/doctrine.md
docs/engineering/toolchain.md
docs/adr/0054-bounded-php-cgi-response-emission.md
docs/dev_plan/kongono_phase_3D_codex_directive.md
docs/task reports/Phase_3D.md
packages/php-cgi/README.md
packages/php-cgi/src/Internal/CgiResponseEmission.php
packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
composer.json
phpstan.neon.dist
```

The Phase 3D directive remains the architectural authority except where this
correction explicitly supersedes its focused PHPStan invocation and its
completion handling for `HEAD`.

The Phase 3D report is historical execution evidence. Do not edit it. Its
incorrect gate/deviation claims must be identified and superseded in the Phase
3D.1 report.

---

## 5. Locked starting state

These values describe the repository after the caller copies this directive
and creates the zero-byte correction-report placeholder.

### 5.1 Git identity

```text
branch:                  main
local branches:          1
HEAD:                    647053d4bddc4c76090bca9bf3d693faa5880c52
tree:                    3ad60da1dc535fc0b7a166ac8522f954d4c67fac
parent:                  d7846f137a1d53f5306f48d19196b916fde44ac2
commit count:            10
remotes:                 0
tags:                    0
modified tracked paths:  21
untracked paths:         42
total status paths:      63
staged paths:             0
Git-visible files:      578
tracked files:          536
```

Exactly one local branch ref, `refs/heads/main`, is permitted. Application-
managed `refs/codex/**` refs are exempt from the local-branch invariant, but the
executor must not create, delete, or modify any ref. No other unexpected ref is
permitted.

The 21 modified tracked paths must be exactly the 21 paths locked by Section
5.1 of the Phase 3D directive.

The 42 untracked paths must be exactly:

- the 30 starting untracked paths locked by Section 5.1 of the Phase 3D
  directive;
- the ten Phase 3D-created paths listed in Section 6.1 of that directive; and
- these two caller records:

```text
docs/dev_plan/kongono_phase_3D_1_codex_directive_correction.md
docs/task reports/Phase_3D_1_correction.md
```

Use `git status --short --untracked-files=all`. Do not rely on collapsed
directory output.

### 5.2 Locked caller and target identities

```text
docs/dev_plan/kongono_phase_3D_codex_directive.md
bytes:    39679
SHA-256: 33EA26695BAB62B1B1C6B9513FBAADD0E9333BBC1238A2F0FA335FE72EFC5647

docs/task reports/Phase_3D.md
bytes:    16434
SHA-256: D786136B0BCE72994FFDEC3D1AD02DB9BB64B704ED4A220BFC648F22D2D90285

packages/php-cgi/src/Internal/CgiResponseEmission.php
bytes:    11864
SHA-256: A3982C8C5751FF0D2D27B6389A648AFEFA7A2656E54BDD27BF5ED367B5788A19

packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
bytes:    31293
SHA-256: 71ECC34AF4F6483E1EE2ED4D01E06549A3B0843815FEFC13BFC7BAA738376F90

composer.json
bytes:    4468
SHA-256: B389C1E40B01A081B775B2288F8D750159133F6FB02894C320067FB430F7CD83

phpstan.neon.dist
bytes:    1311
SHA-256: 973B13A5859DBFB6C7809C60F5C3DFBBD69F9ACA6C5DAE38EF64451E40A6BB45
```

The original Phase 3D report must remain byte-identical throughout this
correction.

### 5.3 Closed preservation manifest

Build a UTF-8-without-BOM manifest from:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' ls-files --cached --others --exclude-standard
```

Normalize every returned path to `/`, exclude exactly:

```text
packages/php-cgi/src/Internal/CgiResponseEmission.php
docs/dev_plan/kongono_phase_3D_1_codex_directive_correction.md
docs/task reports/Phase_3D_1_correction.md
```

Sort with .NET `StringComparer.Ordinal`. For each path, append exactly:

```text
path<TAB>byte-count<TAB>uppercase-SHA-256<LF>
```

Do not add a BOM. The authenticated starting result is:

```text
records:               575
manifest bytes:       70718
manifest SHA-256:     FFFD348DACAF8C844572705A1F08A5E1678C463CC3C02104AF3F3AF7E99F4A3C
aggregate file bytes: 6260831
first path:           .editorconfig
last path:            tests/Tooling/EnvironmentTest.php
```

Reconstruct this manifest after the source correction and again after report
population. Every value must remain exact. This proves that no other accepted
worktree byte changed.

Do not include ignored caches, `.git`, the two correction caller records, or
the authorized source file in this manifest.

---

## 6. Exact authorized file boundary

### 6.1 Modify exactly one production file

```text
packages/php-cgi/src/Internal/CgiResponseEmission.php
```

### 6.2 Populate exactly one caller report

Only after every non-report gate passes:

```text
docs/task reports/Phase_3D_1_correction.md
```

### 6.3 Everything else is frozen

Do not modify, create, rename, move, delete, stage, commit, restore, format, or
regenerate any other path. In particular, do not modify:

```text
docs/dev_plan/kongono_phase_3D_codex_directive.md
docs/task reports/Phase_3D.md
docs/adr/0054-bounded-php-cgi-response-emission.md
packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
.php-cs-fixer.dist.php
```

No Composer command that rewrites manifests, the lockfile, or autoload files is
authorized. No cache-clearing or cache-warming command is authorized.

---

## 7. Exact source correction

In `CgiResponseEmission::emitStaged()`, replace exactly this one block:

```php
        if ('HEAD' === $requestMethod) {
            return;
        }

        $remaining = $staged->length;
        while ($remaining > 0) {
            try {
                $chunk = $staged->body->read(min(self::CHUNK_BYTES, $remaining));
                if ('' === $chunk) {
                    throw self::emissionFailure('staged_body_read_failed', commitAttempted: $commitAttempted);
                }
            } catch (CgiResponseEmissionException $failure) {
                throw $failure;
            } catch (Throwable $failure) {
                throw self::emissionFailure('staged_body_read_failed', $failure, $commitAttempted);
            }

            try {
                $written = $target->writeBody($chunk);
            } catch (Throwable $failure) {
                throw self::emissionFailure('output_write_failed', $failure, $commitAttempted);
            }
            if (strlen($chunk) !== $written) {
                throw self::emissionFailure('output_write_failed', commitAttempted: $commitAttempted);
            }
            $remaining -= strlen($chunk);
        }

        $completed = true;
```

with exactly:

```php
        if ('HEAD' !== $requestMethod) {
            $remaining = $staged->length;
            while ($remaining > 0) {
                try {
                    $chunk = $staged->body->read(min(self::CHUNK_BYTES, $remaining));
                    if ('' === $chunk) {
                        throw self::emissionFailure('staged_body_read_failed', commitAttempted: $commitAttempted);
                    }
                } catch (CgiResponseEmissionException $failure) {
                    throw $failure;
                } catch (Throwable $failure) {
                    throw self::emissionFailure('staged_body_read_failed', $failure, $commitAttempted);
                }

                try {
                    $written = $target->writeBody($chunk);
                } catch (Throwable $failure) {
                    throw self::emissionFailure('output_write_failed', $failure, $commitAttempted);
                }
                if (strlen($chunk) !== $written) {
                    throw self::emissionFailure('output_write_failed', commitAttempted: $commitAttempted);
                }
                $remaining -= strlen($chunk);
            }
        }

        $completed = true;
```

The old block must occur exactly once. Make no other textual or semantic
change.

The corrected complete file must be exactly:

```text
bytes:    11931
SHA-256: 8105196CBAEC82CD66EA074A0D92FE6740338CFC41733794322A00E5C58C43BD
```

This correction means:

- `HEAD` still performs full response access, validation, body staging,
  length calculation, the second headers-sent proof, status emission,
  application-header emission, and computed `Content-Length` emission;
- `HEAD` performs zero body-write calls;
- non-`HEAD` behavior and every failure boundary remain unchanged;
- both successful paths reach the one common `completed = true` assignment;
- no successful path returns before that assignment; and
- completion remains local Internal sequencing state, not a public result,
  property, event, callback, or delivery signal.

Do not rename, expose, remove, read, return, log, or otherwise repurpose the
local completion variable.

---

## 8. Deterministic PHPStan strategy

The accepted Kongono tooling decision already gives the root Composer
`analyse` child a 512 MiB process limit:

```json
"analyse": "@php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress"
```

Do not edit that script or any tooling file.

For this correction, the only focused PHPStan command is:

```powershell
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/php-cgi tests/Architecture
```

`--debug` deliberately selects a serial, directly inspectable focused run and
avoids depending on a parallel-worker cache-warming sequence. The explicit
`-d memory_limit=512M` is process-local. It does not change normal PHP or any
repository configuration.

The canonical gate remains literal:

```powershell
composer quality
```

That command exercises the unchanged root `analyse` script at its accepted
512 MiB child limit.

Never:

- run the focused PHPStan scope first at 128 MiB;
- warm, clear, or manipulate the PHPStan result cache;
- use an INI overlay or set `PHP_INI_SCAN_DIR`;
- change a permanent PHP configuration;
- add a baseline, suppression, ignore, exclusion, or weakened rule;
- substitute focused analysis for canonical quality; or
- retry a PHPStan memory/process failure.

Before the focused gate and after canonical quality, prove from a fresh normal
PHP process:

```text
memory_limit:       128M
PHP_INI_SCAN_DIR:   absent
```

If the environment does not match, stop without repairing it.

---

## 9. Existing behavioral proof

Do not change tests. The accepted Phase 3D suite already proves:

- `HEAD` stages and counts the full body;
- `HEAD` emits the computed `Content-Length`;
- `HEAD` emits no body chunk;
- non-`HEAD` methods write the full staged body;
- status/header/body order and failure semantics are exact; and
- repeated operations retain no state.

In addition to rerunning those behavioral tests, directly inspect the corrected
private method and prove:

1. the old `if ('HEAD' === $requestMethod) { return; }` block is absent;
2. `if ('HEAD' !== $requestMethod)` occurs exactly once in `emitStaged()`;
3. the complete staged-body loop is inside that guard;
4. `$completed = true;` occurs exactly once after the guard;
5. no `return` occurs between the computed `Content-Length` emission and the
   common completion assignment; and
6. the rest of the file differs only by the exact Section 7 replacement.

Do not invent reflection, source-scanning PHPUnit, a new fixture, or a public
completion surface merely to observe a local sequencing marker.

---

## 10. Validation workflow

### Gate A - authenticate and freeze

1. Authenticate this directive against the independently supplied identity.
2. Read every Section 4 authority completely.
3. Verify the zero-byte correction report.
4. Verify Section 5 Git identity, caller identities, exact status unions, file
   safety, and the closed preservation manifest.
5. Verify the source has the exact starting identity and the old block occurs
   exactly once.

Do not run pre-change canonical quality. The accepted Phase 3D implementation
evidence already exists, and this correction must avoid redundant gates before
the exact one-block repair.

### Gate B - apply the one-block correction

Apply only Section 7. Then prove:

```text
source bytes:       11931
source SHA-256:     8105196CBAEC82CD66EA074A0D92FE6740338CFC41733794322A00E5C58C43BD
modified tracked:   21
untracked:          42
total status:       63
staged:              0
Git-visible:       578
tracked:           536
```

Reconstruct the Section 5.3 preservation manifest and require every value to
remain exact.

### Gate C - focused proof

Run exactly, in this order:

```powershell
php -l packages/php-cgi/src/Internal/CgiResponseEmission.php
php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress --debug packages/php-cgi tests/Architecture
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff packages/php-cgi/src/Internal/CgiResponseEmission.php packages/php-cgi/tests/Unit/Emission/CgiResponseEmitterTest.php tests/Architecture/ApiStatusConventionTest.php
php vendor/bin/phpunit --configuration=phpunit.xml.dist packages/php-cgi/tests
php vendor/bin/phpunit --configuration=phpunit.xml.dist tests/Architecture/ApiStatusConventionTest.php
```

Require:

- syntax pass;
- PHPStan zero errors;
- style zero fixable files;
- both PHPUnit commands pass with no failure, error, warning, risky, skipped,
  incomplete, notice, or deprecation result; and
- package and architecture test/assertion counts are recorded rather than
  predeclared.

Then perform every direct inspection in Section 9.

### Gate D - canonical proof

Run literal:

```powershell
composer quality
```

If and only if it fails solely because the managed environment cannot reach
the advisory service or its normal cache, repeat the identical command once
with approved network/cache access. Do not change arguments, scripts,
configuration, dependency state, or policy.

Require:

- Composer validation succeeds under the repository's accepted warning
  categories;
- locked audit reports zero advisories;
- platform requirements pass;
- PHP CS Fixer reports zero fixable files;
- PHPStan reports zero errors through the unchanged 512 MiB `analyse` child;
- the complete PHPUnit suite passes; and
- exact final test/assertion and style-covered-file counts are recorded.

### Gate E - preservation and report

Before report population, prove:

- normal PHP is still 128M;
- `PHP_INI_SCAN_DIR` is absent;
- target source identity is exact;
- all Section 5.2 frozen identities remain exact;
- the Section 5.3 preservation manifest remains exact;
- all Git identity, status, index, branch, ref, history, mode, ordinary-file,
  default-stream, UTF-8, LF, final-LF, NUL, and whitespace checks pass;
- `git diff --check` passes; and
- no path is staged or committed.

Only then populate `docs/task reports/Phase_3D_1_correction.md`.

After report population, rerun report-integrity, source-identity,
preservation-manifest, Git-state, no-staging, and no-history-change proofs.

No stage or commit is authorized.

---

## 11. Historical report reconciliation

Do not edit `docs/task reports/Phase_3D.md`. The Phase 3D.1 correction report
must explicitly and unambiguously state all of the following:

1. The original report's Section 13 claim of `None` under deviations was
   inaccurate.
2. The first exact focused PHPStan command at the normal 128 MiB limit failed
   because the PHPStan worker exhausted its process memory.
3. The Phase 3D directive required an immediate stop and prohibited a
   non-network retry or substitute gate after that failure.
4. Execution nevertheless ran a 512 MiB diagnostic/cache-warming command,
   retried the exact 128 MiB command, continued through later gates, and
   populated the report.
5. The original report's statements that the cache-warming sequence was
   directive-consistent, that the exact focused gate passed without deviation,
   and that all Phase 3D acceptance criteria passed are withdrawn.
6. Architect review determined this was validation-process noncompliance and
   tooling instability, not evidence of a production semantic failure.
7. Phase 3D.1 replaces the unreliable cache-dependent sequence with one
   explicit serial 512 MiB focused command, consistent with the already-
   accepted root 512 MiB analysis policy.
8. The corrected source makes successful `HEAD` and non-`HEAD` emission reach
   the common local completion transition while preserving every public and
   failure behavior.
9. Phase 3D is accepted only when the historical Phase 3D report is read
   together with the completed Phase 3D.1 correction report.

Do not falsely claim that the original Phase 3D execution had no deviation.
Do not describe the original 128 MiB rerun as an accepted gate. Do not rewrite
the historical report to conceal what occurred.

---

## 12. Completion report contract

Use exactly these headings:

```text
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. EXACT SOURCE CORRECTION
## 8. PHPSTAN STRATEGY
## 9. HISTORICAL REPORT RECONCILIATION
## 10. ARCHITECTURAL DECISIONS
## 11. DEVIATIONS FROM DIRECTIVE
## 12. DEPENDENCIES ADDED/REMOVED
## 13. ENVIRONMENT
## 14. COMMANDS RUN
## 15. VALIDATION RESULTS
## 16. DOCUMENTATION REVIEW
## 17. SECURITY AND FILE-SAFETY REVIEW
## 18. WARNINGS / KNOWN ISSUES
## 19. QUESTIONS / BLOCKERS
## 20. FINAL GIT STATUS
## 21. ACCEPTANCE CRITERIA
## 22. NEXT STEP
```

`FILES CREATED` must say none by the executor; the active report is a
caller-provided placeholder populated only after all other gates pass.

`FILES MODIFIED` must name only:

```text
packages/php-cgi/src/Internal/CgiResponseEmission.php
docs/task reports/Phase_3D_1_correction.md
```

The final next step must be exactly:

```text
Await architect review before Phase 3E.
```

After the report is complete, return its byte count and SHA-256 outside the
report body. Do not put self-referential identity values inside the report.

---

## 13. Strict acceptance criteria

Every criterion must pass:

1. The correction directive identity was independently supplied and matched.
2. Every authority was read completely.
3. The zero-byte correction-report caller record matched before mutation.
4. Git identity, exact status unions, semantic index, caller identities, and
   the 575-record preservation manifest matched.
5. The source starting identity and exact old block matched.
6. Only the exact Section 7 replacement was made.
7. The corrected source is exactly 11,931 bytes with the locked target SHA-256.
8. Successful `HEAD` performs no body write and reaches the common completion
   assignment.
9. Successful non-`HEAD` behavior reaches the same assignment unchanged.
10. No public API, diagnostic, exception, target seam, property, dependency,
    manifest, lockfile, tooling, or documentation changed.
11. Existing behavioral tests remained byte-identical and passed.
12. Direct completion-flow inspection passed.
13. Focused syntax passed.
14. The exact serial 512 MiB focused PHPStan command passed with zero errors.
15. Focused style passed with zero fixable files.
16. Package and architecture PHPUnit passed without non-pass results.
17. Literal canonical quality passed, including zero advisories, platform pass,
    zero style findings, zero PHPStan errors, and complete PHPUnit success.
18. Normal PHP remained 128M and `PHP_INI_SCAN_DIR` remained absent.
19. No cache-warming sequence, non-network retry, baseline, suppression,
    exclusion, policy weakening, or permanent configuration change occurred.
20. The original Phase 3D report remained byte-identical.
21. The correction report truthfully superseded every inaccurate historical
    gate/deviation claim listed in Section 11.
22. The final preservation manifest, Git identity, `21/42/63/0` status,
    578-visible/536-tracked inventory, semantic index, branch, refs, history,
    modes, streams, encoding, whitespace, and file-safety checks passed.
23. Nothing was staged or committed.
24. No Phase 3E or later capability was designed or implemented.
25. The report contract and external report identity handoff passed.

Any failure means Phase 3D remains open. Preserve the exact reached state,
leave the active report zero bytes unless every non-report gate passed, and
request the smallest authenticated correction.

---

## 14. Prohibited scope

Do not implement, modify, or select:

- PHP CGI delivery composition;
- operator/bootstrap/trust activation;
- ambient request capture;
- a production entrypoint;
- a FastCGI listener, parser, client, process manager, or deployment profile;
- output buffering, rollback, retry, fallback response, or flush behavior;
- streaming responses or a larger body profile;
- HTTP routing, middleware, message, server, Web Runtime, or Web Application
  behavior;
- DI/container behavior or compile-time wiring;
- database, ORM, schema, migration, query, transaction, or persistence work;
- templating, frontend, CLI, agents, extensions, telemetry, or other later
  capability;
- PHP 8.6 or PHP 9 compatibility changes; or
- cleanup, formatting, documentation reconciliation, or refactoring outside
  the one exact source replacement.

Phase 3E remains the only possible next phase, and only after architect review
accepts this correction.

---

## 15. Final instruction

Act as the executor of this correction, not its architect.

Authenticate first. Preserve every accepted byte outside the exact target.
Apply the one literal source replacement. Use the one supported 512 MiB serial
focused PHPStan command and the unchanged canonical quality gate. Record the
historical validation deviation honestly. Do not stage, commit, retry a
non-network failure, or begin Phase 3E.

Stop after returning the populated correction report's independently computed
byte count and SHA-256.
