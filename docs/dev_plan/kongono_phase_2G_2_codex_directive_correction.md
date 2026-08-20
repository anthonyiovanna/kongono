# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2G.2
## Exact Temporary-Overlay Retry and Environment Restoration

**Directive type:** validation-script correction and one fresh completion attempt

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2G.2

**Depends on:** accepted Phase 2G implementation and the stopped, non-mutating Phase 2G.1 validation attempt

---

## 1. Mission

Correct only the Phase 2G.1 validation procedure and authorize one fresh
temporary-overlay attempt to run the literal unchanged command:

```powershell
composer quality
```

Phase 2G.1 did not execute canonical quality. Its validation wrapper falsely
rejected the quoted human-readable output of `php --ini`, and its cleanup did
not remove an initially absent `PHP_INI_SCAN_DIR` by the exact required
PowerShell operation inside that process. The temporary file and directory were
nevertheless removed, a fresh PHP process returned to `128M`, and the repository
and caller records remained unchanged.

This correction therefore:

1. accepts that stopped Phase 2G.1 attempt as historical evidence only;
2. does not populate or rewrite either earlier zero-byte report;
3. validates PHP configuration through PHP's machine-readable INI APIs rather
   than parsing the quoted `php --ini` display;
4. records whether `PHP_INI_SCAN_DIR` existed before the attempt;
5. uses `Remove-Item Env:PHP_INI_SCAN_DIR` when it was initially absent;
6. authorizes one fresh overlay-backed canonical quality attempt; and
7. permits population only of the new Phase 2G.2 correction report after every
   non-report gate passes.

Do not alter repository implementation, tests, documentation, dependencies,
tool configuration, permanent PHP configuration, Git metadata, or the two
earlier report placeholders.

---

## 2. Architect decision and supersession

The architect authorizes a new attempt because the prior failure occurred in
the correction's validator wrapper before `composer quality` ran. This is not a
retry of a failing quality result.

This directive supersedes Phase 2G.1 only where Phase 2G.1:

- required validation by parsing the presentation-oriented `php --ini` output;
- prohibited a new attempt after the wrapper's false rejection; or
- left the exact initially-absent environment-variable restoration operation
  insufficiently explicit.

All other Phase 2G and Phase 2G.1 boundaries remain locked. In particular:

- the quality command remains literally `composer quality`;
- the effective validation-only PHP memory limit remains `512M`;
- normal PHP must return to `128M` after cleanup;
- no permanent configuration or repository change is permitted; and
- a non-network quality failure is a mandatory stop, not authority to retry or
  repair anything.

Independent architect verification previously established that the same
temporary-overlay mechanism can pass the accepted worktree's complete quality
suite with:

```text
security advisories  0
platform             pass
style                0 of 218 files fixable
PHPStan              zero errors
PHPUnit              464 tests, 8337 assertions
```

These values are verification evidence, not numbers to manufacture.

---

## 3. Independent directive authentication

Before quality, a temporary write, or any repository mutation, compare this
directive with the byte count and SHA-256 supplied independently by the
architect outside this file.

Required repository path:

```text
docs/dev_plan/kongono_phase_2G_2_codex_directive_correction.md
```

Do not derive authority from the local file. If either independent value is
absent or differs, stop before validation and mutation.

---

## 4. Required authorities and caller records

Read completely:

```text
AGENTS.md
docs/dev_plan/kongono_phase_2G_codex_directive.md
docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_2G_2_codex_directive_correction.md
docs/task reports/Phase_2G.md
docs/task reports/Phase_2G_1_correction.md
```

Verify these accepted identities:

```text
docs/dev_plan/kongono_phase_2G_codex_directive.md
bytes      54564
SHA-256    F59EFB941D96C5052C45494D1AFC161F55DDC7EF7900CFBD500BAEC8DCBADAFE

docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
bytes      15687
SHA-256    40EBB8C5C425B3504B517823E56146FEF19F399413DBC98A7ED5F691E1C3E1CA
```

Both historical report placeholders must remain ordinary, untracked,
unstaged, absent from `HEAD`, zero-byte files with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

The paths are:

```text
docs/task reports/Phase_2G.md
docs/task reports/Phase_2G_1_correction.md
```

Do not populate, truncate, replace, delete, rename, or reinterpret either file.
The failed Phase 2G.1 attempt is recorded by the architect conversation and
this corrective authority; its zero-byte placeholder stays immutable.

The caller must additionally provide:

```text
docs/task reports/Phase_2G_2_correction.md
```

At entry it must be an ordinary, untracked, unstaged file absent from `HEAD`,
zero bytes, and have the empty-file SHA-256 above. Keep it zero-byte until every
permitted non-report gate passes.

Stop if any authority or caller record differs. Do not repair it.

---

## 5. Accepted resume state

Immediately after the stopped Phase 2G.1 attempt, before the two new Phase 2G.2
caller paths are placed, the accepted state is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        145
total status paths     172
staged paths           0
Git-visible paths      503
normal PHP memory      128M
PHP_INI_SCAN_DIR       absent in a fresh process environment
```

After the caller places only the authenticated Phase 2G.2 directive and its
zero-byte report placeholder, expected entry and pre-report final state is:

```text
tracked modifications  27
untracked paths        147
total status paths     174
staged paths           0
Git-visible paths      505
tracked files          358
```

The only two added status paths must be:

```text
docs/dev_plan/kongono_phase_2G_2_codex_directive_correction.md
docs/task reports/Phase_2G_2_correction.md
```

Branch, `HEAD`, tree, parent, commit count, index, remotes, tags, and
repository-owned refs must remain unchanged. Environment-managed
`refs/codex/turn-diffs/**` are exempt from count stability but not from
repository-content authority.

The stopped Phase 2G.1 attempt is accepted only with these observed facts:

- the literal `composer quality` never ran;
- no repository content or permanent configuration changed;
- its temporary file and directory were deleted;
- fresh PHP reports `128M`;
- a fresh environment has no `PHP_INI_SCAN_DIR`; and
- status remained exactly `27/145/172/0` before Phase 2G.2 caller paths.

Stop if the resume state differs. Do not clean, restore, stage, or reconstruct
anything.

---

## 6. Frozen boundary

Before final report population, no repository file may change.

Preserve all bytes under or in:

```text
packages/
tests/
docs/
composer.json
composer.lock
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist
```

The only later authorized repository write is population of:

```text
docs/task reports/Phase_2G_2_correction.md
```

and only after every non-report gate succeeds.

Do not modify the original Phase 2G report, the Phase 2G.1 report, any
directive, source, test, documentation, manifest, lockfile, tooling file,
installed `C:\php\php.ini`, user/system environment, profile, registry, Git
index, ref, history, branch, tag, or remote.

No staging or commit is authorized.

---

## 7. Exact one-attempt overlay procedure

### 7.1 Safety prerequisites

Use one new GUID-named directory directly inside the absolute directory
returned by `[System.IO.Path]::GetTempPath()`. Its only file must be named:

```text
99-kongono-phase-2g-memory.ini
```

The file must contain exactly these ASCII-compatible UTF-8 bytes, without BOM,
with one final LF:

```ini
memory_limit=512M
```

Use task-specific variables. Never repurpose `$HOME`, `$home`, or
`$CODEX_HOME`.

Before creating the directory, prove its normalized parent is exactly the
normalized process temp directory, the candidate does not exist, and it is not
the repository, workspace, user home, PHP installation, Composer cache, or
another pre-existing directory. Do not use recursive deletion.

### 7.2 Capture the exact prior scan-variable state

Before setting the overlay, capture both:

```powershell
$phase2g2ScanWasPresent = Test-Path Env:PHP_INI_SCAN_DIR
$phase2g2PreviousScanValue = if ($phase2g2ScanWasPresent) {
    $env:PHP_INI_SCAN_DIR
} else {
    $null
}
```

Do not print the prior value. The completion report may state only whether it
was initially absent or was restored.

For the accepted starting environment, `$phase2g2ScanWasPresent` must be
`$false`. If it is unexpectedly present, stop before creating the overlay and
report only that the prerequisite differed; do not disclose or change its
value.

### 7.3 Temporary overlay and machine-readable proof

Create the already-validated exact directory and file, then set only the
current PowerShell process environment:

```powershell
$env:PHP_INI_SCAN_DIR = $phase2g2IniDirectory
```

Do not parse the formatted or quoted text returned by `php --ini`. It may be
run for human evidence, but it is not an acceptance predicate.

Use these PHP APIs as the acceptance source:

```powershell
php -r "echo ini_get('memory_limit'), PHP_EOL;"
php -r "echo php_ini_loaded_file(), PHP_EOL;"
php -r "echo php_ini_scanned_files(), PHP_EOL;"
```

Require:

1. the first command exits zero and trims exactly to `512M`;
2. the normalized path from `php_ini_loaded_file()` is exactly
   `C:\php\php.ini`;
3. `php_ini_scanned_files()` yields exactly one non-empty normalized path; and
4. that sole normalized path equals the exact temporary INI path.

For item 3, split the API value on commas and line endings, trim empty entries,
normalize each remaining path, and compare the resulting path collection.
Do not search for a quoted display substring and do not infer success from
`php --ini` formatting.

If any predicate fails, enter the exact cleanup in Section 7.5 and then stop.
Do not run quality and do not retry.

### 7.4 Literal canonical quality

After all overlay predicates pass, run exactly:

```powershell
composer quality
```

Do not add a PHP prefix, `-d` option, alternate script, skip flag, memory
argument, worker setting, or environment override beyond the verified scan
directory.

If this first fresh attempt fails only because the managed environment denies
network or Composer cache access, finish the Section 7.5 cleanup, obtain the
required approval, recreate a new GUID-named overlay by the same exact
procedure, and rerun the identical literal command once. This is the only
authorized retry.

Any other failure requires cleanup and an immediate stop. Focused PHPStan,
partial quality, higher memory, reduced parallelism, a baseline, or a changed
script cannot substitute for canonical success.

### 7.5 Mandatory exact cleanup order

Cleanup must be in a `finally` path that runs after every success or failure.
Perform it in this order:

1. restore the process environment variable;
2. delete the exact temporary INI file;
3. delete the exact now-empty generated directory without recursion; and
4. prove the variable state and both path absences.

Because the accepted prior state is absent, restoration must execute this
PowerShell operation before any temporary-path deletion:

```powershell
Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue
```

Then require:

```powershell
Test-Path Env:PHP_INI_SCAN_DIR
```

to be exactly `$false` in that process.

The general restoration rule remains: if a future authenticated starting state
explicitly accepts a present value, assign its exact captured value instead.
For this run, however, the prerequisite is absent and `Remove-Item` is
mandatory. Assigning `$null`, assigning an empty string, or relying on process
exit is not accepted restoration proof.

Delete only the previously normalized exact INI path. Then delete only the
previously normalized exact empty generated directory with a non-recursive
operation. Require both `Test-Path -LiteralPath` checks to be `$false`.

If cleanup proof fails, stop. Do not run another attempt, populate a report, or
make any repository correction.

### 7.6 Fresh-process restoration proof

After successful cleanup, run outside the overlay:

```powershell
php -r "echo ini_get('memory_limit'), PHP_EOL;"
```

Require exactly `128M`. Also require `Test-Path Env:PHP_INI_SCAN_DIR` to remain
`$false` in the controlling PowerShell process.

No temporary file, directory, persistent variable, profile edit, registry
entry, PHP configuration edit, or repository difference may remain.

---

## 8. Required validation and preservation proof

Canonical `composer quality` must pass completely. Record exact results for:

```text
Composer validation
locked advisory audit
platform requirements
PHP CS Fixer
PHPStan
PHPUnit
```

Expected current-worktree evidence is:

```text
security advisories  0
platform             pass
style                0 of 218 files fixable
PHPStan              zero errors
PHPUnit              464 tests, 8337 assertions
```

After exact cleanup, independently verify:

- normal PHP is `128M` and the scan environment variable is absent;
- the temporary INI and directory are absent;
- all repository bytes except later report population remain unchanged from
  the authenticated Phase 2G.2 entry state;
- the accepted Phase 2G boundary remains exactly 17 created and 34 modified
  implementation/documentation paths;
- package/production/test/total PHP inventories remain `13/13/100/118/218`;
- diagnostic IDs remain 113 unique;
- lock totals remain `17/66`;
- all local Markdown links remain unbroken;
- `git diff --check` passes;
- Phase 2G changed/created paths retain strict encoding and file safety;
- status is exactly `27/147/174/0` before report population;
- Git-visible paths are exactly 505 and tracked files exactly 358;
- both earlier Phase 2G report placeholders remain zero-byte, untracked,
  unstaged, absent from `HEAD`, and byte-identical;
- the Phase 2G.2 report remains zero-byte until all preceding gates pass; and
- no Phase 2H work exists.

Only then populate the Phase 2G.2 correction report. After report population,
the status path counts stay `27/147/174/0`; only the report's bytes change.

---

## 9. Prohibited scope

Do not:

- alter Phase 2G implementation, tests, docs, manifests, lockfile, or tooling;
- populate either earlier Phase 2G report;
- edit any directive;
- parse quoted `php --ini` output as a pass/fail predicate;
- leave `PHP_INI_SCAN_DIR` present when it began absent;
- restore absence by assigning `$null` or an empty string instead of the exact
  `Remove-Item Env:PHP_INI_SCAN_DIR` operation;
- retain the temporary file or directory;
- make a permanent environment, PHP, Composer, registry, or profile change;
- modify memory limits in `composer.json`, PHPStan configuration, or scripts;
- use `COMPOSER_MEMORY_LIMIT` as a substitute;
- change PHPStan parallelism, analysis paths, rules, level, or error policy;
- add a baseline, ignore, suppression, exclusion, dependency, cache artifact,
  or generated repository file;
- waive canonical quality based on focused analysis;
- rerun after a non-network failure;
- stage, commit, restore, reset, clean, amend, switch branches, create/delete
  refs, or rewrite history; or
- begin Phase 2H.

---

## 10. Stop rules

Stop, clean only the exact temporary overlay if it exists, and preserve the
repository and caller records if:

- directive authentication fails;
- an authority, caller record, report placeholder, resume identity, status, or
  protected byte differs;
- the scan environment variable is present at entry;
- the generated path cannot be proven new and directly inside the temp root;
- the main INI, scanned-file collection, or `512M` API proof differs;
- canonical quality fails for a non-network/cache reason;
- the sole approved-access retry fails;
- exact environment restoration or path deletion cannot be proven;
- fresh PHP does not return to `128M`;
- any repository byte changes before report population; or
- any final criterion differs.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE REPOSITORY AND CALLER RECORDS
DO NOT CHANGE PERMANENT CONFIGURATION
```

Do not turn a validator, cleanup, quality, identity, or inventory mismatch into
authority for a workaround.

---

## 11. Phase 2G.2 correction completion report

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2G_2_correction.md
```

Use exactly these headings:

```text
# PHASE 2G.2 CORRECTION TASK COMPLETION REPORT
## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. FILES CREATED
## 6. FILES MODIFIED
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. ENVIRONMENT
## 11. COMMANDS RUN
## 12. VALIDATION RESULTS
## 13. DOCUMENTATION REVIEW
## 14. WARNINGS / KNOWN ISSUES
## 15. QUESTIONS / BLOCKERS
## 16. GIT DIFF SUMMARY
## 17. ACCEPTANCE CRITERIA AND NEXT STEP
```

State explicitly:

- independent Phase 2G.2 directive authentication;
- exact identities of Phase 2G and Phase 2G.1 directives;
- all three report entry identities and the two earlier reports' unchanged
  zero-byte state;
- exact resume/final Git identities and counts;
- that Phase 2G.1 stopped before quality due only to wrapper validation;
- initial scan variable absence without printing any value;
- use of PHP's machine-readable INI APIs and their exact successful findings;
- effective `512M` memory during validation;
- the literal canonical command and complete results;
- whether the sole approved network/cache retry occurred;
- exact `Remove-Item Env:PHP_INI_SCAN_DIR` restoration before file cleanup;
- absence of the variable and both temporary paths afterward;
- fresh normal PHP restored to `128M`;
- zero permanent configuration and repository-content changes except this
  report;
- preservation of Phase 2G implementation, inventories, diagnostics, lock,
  links, encoding, history, index, and caller records;
- whether Phase 2G is now fully closed; and
- final report bytes and SHA-256 returned outside the report body.

Its exact final sentence must be:

```text
Await architect review before Phase 2H.
```

The report must be UTF-8 without BOM, LF-only, exactly one final LF, no trailing
whitespace or NUL, ordinary, untracked, unstaged, and absent from `HEAD`.

---

## 12. Strict acceptance criteria

Phase 2G.2 is Complete only if:

1. its directive matched independently supplied byte count and SHA-256;
2. all required authorities and caller records matched exactly;
3. all three report placeholders began with their required identities;
4. Phase 2G and Phase 2G.1 reports remained zero-byte and unchanged;
5. resume Git/status/inventory/protected state matched;
6. the prior Phase 2G.1 wrapper failure was accepted without modifying state or
   treating it as a quality run;
7. `PHP_INI_SCAN_DIR` was proven absent before the new attempt;
8. one new exact temporary directory and INI were created safely outside the
   repository;
9. PHP API proof established main `C:\php\php.ini`, exactly the one scanned
   temporary file, and effective `512M`;
10. no quoted `php --ini` display parsing controlled acceptance;
11. the literal unchanged `composer quality` ran;
12. Composer validation, advisory audit, platform, style, PHPStan, and PHPUnit
    all passed, including 464 tests and 8337 assertions;
13. any retry was identical, approved, and only for managed network/cache
    denial;
14. every non-network failure remained non-retryable;
15. cleanup first executed exact
    `Remove-Item Env:PHP_INI_SCAN_DIR -ErrorAction SilentlyContinue`;
16. the scan variable was then absent in the controlling process;
17. the exact temporary INI and exact empty directory were non-recursively
    deleted and proven absent;
18. fresh ordinary PHP returned exactly `128M`;
19. no permanent configuration, environment, repository, policy, dependency,
    source, test, documentation, tooling, Git, or caller-record change occurred;
20. Phase 2G inventories, diagnostic IDs, lock totals, links, encoding, file
    safety, and `git diff --check` remained correct;
21. final status before and after report population is exactly 27 modified, 147
    untracked, 174 total, zero staged, 505 Git-visible, 358 tracked;
22. Git identity, history, index, and repository-owned refs remained unchanged;
23. no Phase 2H work began;
24. the Phase 2G.2 report was populated only after criteria 1-23 passed;
25. the report has the exact structure, encoding, and final sentence; and
26. its final byte count and SHA-256 are returned independently.

If any criterion fails, status must not be Complete.

---

## 13. Required final outcome

Phase 2G closes with its accepted implementation unchanged and its literal
canonical quality suite passing under a verified, temporary 512 MB PHP overlay.
The overlay is validated through PHP APIs, the initially absent scan variable
is removed exactly before temporary-path cleanup, normal PHP returns to 128 MB,
and neither the repository nor permanent workstation configuration receives a
workaround.

The original Phase 2G and Phase 2G.1 reports remain preserved as zero-byte
historical caller records. Only the completed Phase 2G.2 report records the
successful correction. Then await architect review before Phase 2H.
