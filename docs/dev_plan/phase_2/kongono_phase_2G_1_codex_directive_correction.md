# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 2G.1
## Temporary PHP Memory Overlay for Canonical Quality Completion

**Directive type:** validation-environment correction and completion resume

**Execution role:** executor, not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Date:** 2026-08-20

**Phase:** 2G.1

**Depends on:** completed Phase 2G implementation stopped only by canonical PHPStan worker memory exhaustion

---

## 1. Mission

Resume the completed Phase 2G implementation from its preserved final validation
state and authorize one temporary, process-scoped PHP configuration overlay that
raises `memory_limit` from `128M` to `512M` while running the literal unchanged:

```powershell
composer quality
```

The overlay must be external to the repository, inherited by Composer's child
PHP/PHPStan processes, restored immediately afterward, and deleted. It must not
change `composer.json`, `phpstan.neon.dist`, `php.ini`, Composer scripts, a
baseline, suppression, worker count, repository policy, or any implementation
file.

If canonical quality passes, complete final preservation checks and populate a
new Phase 2G.1 correction report. Preserve the original zero-byte Phase 2G
report as the exact blocked-run caller record.

---

## 2. Architect decision

The architect accepts the agent's recommended option:

> Use a temporary 512 MB PHP CLI memory limit while running the literal
> `composer quality`, without any repository or workstation configuration
> change.

This supersedes only the Phase 2G prohibition on changing process limits for
this one canonical validation run. Every permanent configuration, dependency,
source, test, documentation, tooling, policy, and report-boundary rule remains
locked.

Independent architect verification established that the overlay mechanism
loads `C:\php\php.ini` normally, adds only one temporary scanned `.ini` file,
reports `memory_limit=512M`, and allows the unchanged Phase 2G canonical suite
to pass with:

```text
security advisories  0
style findings       0 of 218
PHPStan errors       0
PHPUnit              464 tests, 8337 assertions
```

These totals are evidence from the accepted current worktree, not targets to be
manufactured.

---

## 3. Independent directive authentication

Before any validation or temporary write, compare this directive with the byte
count and SHA-256 supplied independently by the architect outside this file.

Required repository path:

```text
docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
```

Do not self-authenticate from the local file. If either expected value is absent
or differs, stop without running quality or changing any state.

---

## 4. Required caller records

Read completely before action:

```text
AGENTS.md
docs/dev_plan/kongono_phase_2G_codex_directive.md
docs/task reports/Phase_2G.md
docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
```

The accepted Phase 2G directive must remain:

```text
bytes      54564
SHA-256    F59EFB941D96C5052C45494D1AFC161F55DDC7EF7900CFBD500BAEC8DCBADAFE
```

`docs/task reports/Phase_2G.md` must remain the original blocked-run record:

```text
bytes      0
SHA-256    E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
state      ordinary, untracked, unstaged, absent from HEAD
```

Do not populate, truncate, replace, rename, or delete it.

The caller must also provide:

```text
docs/task reports/Phase_2G_1_correction.md
```

At entry it must be ordinary, untracked, unstaged, absent from `HEAD`, zero
bytes, and have the same empty-file SHA-256 above. Keep it zero-byte until every
non-report gate in this correction passes.

---

## 5. Accepted resume state

Before the two new Phase 2G.1 caller records are placed, the accepted preserved
state is:

```text
branch                 main
HEAD                   d7846f137a1d53f5306f48d19196b916fde44ac2
HEAD tree              bfa1908c619069b699bc9b08668db17ea2555eb3
HEAD parent            8fe793b124384744ac43887037360154c149241a
commit count           9
tracked files          358
tracked modifications  27
untracked paths        143
total status paths     170
staged paths           0
Git-visible paths      501
```

After the caller places only this authenticated correction directive and the
zero-byte correction report, expected entry/final status is:

```text
tracked modifications  27
untracked paths        145
total status paths     172
staged paths           0
Git-visible paths      503
tracked files          358
```

The only two additional paths must be:

```text
docs/dev_plan/kongono_phase_2G_1_codex_directive_correction.md
docs/task reports/Phase_2G_1_correction.md
```

The Phase 2G report stays zero-byte. Branch, history, tree, parent, index,
remotes, tags, and repository-owned refs remain unchanged. Environment-managed
`refs/codex/turn-diffs/**` are exempt and may change count.

Before the stopped canonical run, all Phase 2G gates reached by the executor
passed, including Composer validation/audit/platform/style and focused PHPStan
at 512 MB with zero errors. The sole blocker was the canonical PHPStan worker's
inherited `128M` limit.

Stop if any resume-state fact or caller record differs. Do not reconstruct,
restage, clean, or repair it.

---

## 6. Frozen repository boundary

Before correction-report population, modify no repository path.

In particular, preserve every byte of:

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

except that `docs/task reports/Phase_2G_1_correction.md` may be populated only
after successful canonical quality and all final checks.

Do not modify:

- the original `Phase_2G.md` zero-byte report;
- the Phase 2G or Phase 2G.1 directive;
- root or package Composer scripts;
- PHPStan memory arguments or configuration;
- PHP's installed `C:\php\php.ini`;
- any user/system configuration;
- Composer or PHP environment persistently; or
- Git metadata, index, refs, history, branches, tags, or remotes.

No implementation correction, style change, documentation edit, dependency
update, regenerated lockfile, staging, or commit is authorized.

---

## 7. Exact temporary overlay mechanism

Use one new uniquely named directory under the process temporary directory and
one file inside it named:

```text
99-kongono-phase-2g-memory.ini
```

The file must contain exactly these ASCII bytes with one final LF:

```ini
memory_limit=512M
```

Use a task-specific PowerShell variable such as `$phase2gIniDirectory`; never
repurpose `$HOME`, `$home`, or `$CODEX_HOME`.

Before writing, resolve the generated directory's absolute path and prove it is
inside `[System.IO.Path]::GetTempPath()`, is newly created for this run, and is
not the repository, workspace root, user home, PHP installation, Composer cache,
or another pre-existing directory.

Capture the prior process value of `PHP_INI_SCAN_DIR`, then set a process-only
value that preserves any non-empty prior scan path and adds the new directory.
Do not use `setx`, registry writes, profile edits, machine/user environment
mutation, or a new shell-wide permanent setting.

Before quality, run read-only proof in that same process environment:

```powershell
php -r "echo ini_get('memory_limit'), PHP_EOL;"
php --ini
```

Require:

- `memory_limit` is exactly `512M`;
- loaded main configuration remains `C:\php\php.ini`; and
- the sole added parsed file is the exact temporary INI.

Then run the literal command:

```powershell
composer quality
```

The command itself must not receive a different script name, skip flag,
PHPStan-only substitute, changed worker count, altered Composer script, or
weakened gate.

If the first overlay-backed attempt is blocked only by managed network/cache
access, the identical overlay-backed `composer quality` may be retried once
with approved access. No other retry or variant is authorized.

Use a `finally` path to:

1. restore the exact prior process value of `PHP_INI_SCAN_DIR`, including
   removing the variable when it was initially absent;
2. delete only the exact temporary INI;
3. remove only the now-empty exact generated temporary directory; and
4. verify both temporary paths are absent.

Do not recursively delete an unresolved or unverified path. Do not print
sensitive environment contents; report only whether the prior value was absent
or restored.

After cleanup, a fresh ordinary PHP process must again report the workstation's
normal `128M` limit. The overlay must leave no file, process environment value,
or repository difference.

---

## 8. Required validation and final proof

Canonical quality must pass fully. Record exact output for:

```text
Composer root/package validation
locked advisory audit
platform requirements
PHP CS Fixer
PHPStan
PHPUnit
```

Independently verified expected current-worktree evidence is:

```text
security advisories  0
platform             pass
style                0 of 218 files fixable
PHPStan              zero errors
PHPUnit              464 tests, 8337 assertions
```

After overlay cleanup, verify:

- normal `php -r "echo ini_get('memory_limit'), PHP_EOL;"` returns `128M`;
- no temporary INI/directory remains;
- no repository file except the later correction report changed during this
  correction;
- Phase 2G's exact 17 created and 34 modified implementation/documentation
  boundary remains unchanged;
- inventories remain exactly `13/13/100/118/218`;
- diagnostic IDs remain exactly 113 unique;
- lock totals remain exactly `17/66`;
- local Markdown links remain unbroken;
- `git diff --check` passes;
- all changed/created Phase 2G paths retain strict encoding/file safety;
- status is exactly `27/145/172/0` after caller records;
- Git-visible paths are exactly 503 and tracked files exactly 358;
- original `Phase_2G.md` remains zero-byte;
- the correction report remains zero-byte until every item above passes; and
- no Phase 2H work exists.

Only then populate the correction report.

---

## 9. Prohibited alternatives

Do not:

- add `--memory-limit` to a Composer script;
- modify `composer.json`, `phpstan.neon.dist`, `php.ini`, or any package/tool
  configuration;
- set `COMPOSER_MEMORY_LIMIT` as a substitute for the child PHP limit;
- use `php -d memory_limit=512M` only for the Composer parent without proving
  child inheritance;
- run only focused PHPStan and waive canonical quality;
- reduce PHPStan parallelism, paths, rules, level, or analyzed files;
- add ignores, suppressions, baselines, exclusions, caches, or policy exceptions;
- change production/tests/docs to reduce analysis load;
- install or update dependencies;
- skip audit, platform, style, analysis, or tests;
- persist environment variables or edit user/system profiles;
- retain the temporary INI/directory;
- populate or rewrite the original Phase 2G report;
- stage, commit, restore, reset, clean, amend, switch branches, create refs, or
  rewrite history; or
- begin Phase 2H.

---

## 10. Stop rules

Stop and preserve current state if:

- correction directive authentication fails;
- caller records, zero-byte reports, status, Git identity, or protected Phase
  2G bytes differ;
- the temporary directory cannot be proven new and safely inside the process
  temp root;
- PHP does not load the normal main INI plus only the temporary overlay;
- the effective limit is not exactly `512M`;
- canonical quality fails for any non-network/cache reason;
- the approved-access retry fails;
- the overlay cannot be fully restored/deleted;
- normal PHP does not return to `128M` afterward;
- any repository file changes before report population; or
- any final acceptance criterion differs.

Use:

```text
STOP
REPORT EXPECTED AND OBSERVED VALUES
PRESERVE REPOSITORY AND CALLER RECORDS
DO NOT CHANGE PERMANENT CONFIGURATION
```

---

## 11. Correction completion report

After every non-report gate passes, populate only:

```text
docs/task reports/Phase_2G_1_correction.md
```

Use exactly these headings:

```text
# PHASE 2G.1 CORRECTION TASK COMPLETION REPORT
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

- independent correction-directive authentication;
- original Phase 2G directive/report identities;
- exact resume and final Git state;
- temporary directory/file creation, validation, restoration, and deletion;
- prior/effective/restored memory limits without disclosing unrelated
  environment contents;
- canonical quality output and any approved identical retry;
- zero repository content changes during correction except this report;
- Phase 2G implementation/inventories/protected state remained exact;
- original Phase 2G report remains zero-byte;
- no permanent configuration or policy change;
- whether Phase 2G is now fully closed; and
- report bytes and SHA-256 returned outside the body.

Its exact final sentence must be:

```text
Await architect review before Phase 2H.
```

The report must be UTF-8 without BOM, LF-only, exactly one final LF, no trailing
whitespace/NUL, ordinary, untracked, unstaged, and absent from `HEAD`.

---

## 12. Strict acceptance criteria

Phase 2G.1 is Complete only if:

1. the correction directive matched independently supplied identity;
2. required authorities and caller records matched;
3. both report placeholders began zero-byte and original Phase 2G report stayed
   zero-byte;
4. resume Git/status/inventory/protected state matched;
5. one new verified temporary directory and exact one-line INI were created
   outside the repository;
6. the prior process scan setting was preserved;
7. PHP loaded `C:\php\php.ini` plus only the temporary overlay;
8. effective validation memory was exactly `512M`;
9. the literal unchanged `composer quality` ran;
10. canonical validation passed fully with zero advisories, zero style findings,
    zero PHPStan errors, and all 464 tests/8337 assertions passing;
11. any retry was identical and only for managed network/cache access;
12. process environment was restored exactly;
13. temporary file and directory were deleted and verified absent;
14. normal PHP again reported `128M`;
15. no repository file changed before correction-report population;
16. Phase 2G's implementation, tests, docs, dependencies, lock, diagnostics,
    links, encoding, and inventories remained exact;
17. `git diff --check` passed;
18. final status is exactly 27 modified, 145 untracked, 172 total, zero staged,
    503 Git-visible, and 358 tracked files;
19. Git identity/history/index/repository-owned refs remained unchanged;
20. no permanent memory/configuration/policy change exists;
21. the correction report was populated only after criteria 1-20 passed;
22. it has the exact structure and final sentence;
23. its byte count and SHA-256 are returned independently; and
24. no Phase 2H work began.

If any item fails, status must not be Complete.

---

## 13. Required final outcome

Phase 2G must close with its accepted implementation unchanged and its canonical
quality suite fully passing under a temporary, verified, non-persistent 512 MB
PHP validation overlay. The workstation returns to its original 128 MB default,
the repository receives no configuration workaround, and the original blocked
Phase 2G report remains preserved.

After successful completion, return the Phase 2G.1 correction report and await
architect review before Phase 2H.
