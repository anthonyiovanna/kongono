# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0K

## Accepted Phase 0J Repository Checkpoint

## Mission

Create the fourth immutable local Git checkpoint for Kongono.

Phase 0J is architecturally accepted. Perform only:

1. complete read-only reverification of the accepted Phase 0J state;
2. exact content, scope, secret, file-safety, and intended-tree audits;
3. inclusion of this Phase 0K directive in repository history;
4. exact staging of the accepted Phase 0J checkpoint and this directive;
5. one normal local commit on `main`;
6. post-commit Git, content, quality, and security verification;
7. the required completion report.

This phase creates no framework behavior and authorizes no repository-content edit by the executor. Do not begin Phase 0L.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the checkpoint executor, not the architect.

Read completely before acting:

1. `AGENTS.md` and all locked governance, doctrine, architecture, engineering, and security guidance;
2. ADRs 0001 through 0025 and the ADR index;
3. all directives, correction directives, reports, and correction reports through Phase 0J;
4. all current manifests, tooling configuration, production source, tests, and package READMEs;
5. the accepted Phase 0J report;
6. this Phase 0K directive.

The populated `docs/task reports/Phase_0J.md` is the accepted Phase 0J conformance record. Preserve it byte-for-byte.

## Mandatory stop rule

Stop before staging or committing if:

- the baseline differs from this directive;
- any accepted file is missing, unexpectedly empty, malformed, or concurrently changed;
- the Phase 0K directive is absent, differs from the caller-supplied artifact, or the Phase 0K report is not exactly zero bytes;
- an unclassified path, secret, credential, environment file, generated artifact, or unsafe entry may enter the commit;
- a required quality, security, file-safety, architecture, or integrity check fails for a repository reason;
- the index is not initially empty;
- an unexpected branch, commit, remote, tag, submodule, nested repository, executable mode, symlink, junction, or reparse point exists;
- Git author identity is unavailable;
- normal signing or a hook cannot complete;
- the intended staged tree cannot be proven exactly;
- any content edit, correction, or architectural decision would be required.

If a stop condition appears:

```text
STOP
DO NOT FIX
DO NOT STAGE
DO NOT COMMIT
REPORT THE EXACT BLOCKER
```

Do not bypass a hook, disable signing, weaken validation, repair Phase 0J, or improvise another checkpoint.

---

# 2. LOCKED GIT OUTCOME

The exact outcome is:

```text
Branch: main
Only local branch: main
Commit count: 4
New commit parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
New commit parent count: 1
Commit subject: Establish Kongono framework baseline through Phase 0J
Tracked files: 192
Tracked modes: 192 x 100644
Remotes: 0
Tags: 0
Push: prohibited
```

Use exactly:

```text
Establish Kongono framework baseline through Phase 0J
```

Do not add a body, prefix, issue reference, sign-off, co-author trailer, or AI attribution.

Do not use `--amend`, `--allow-empty`, `--no-verify`, `--no-gpg-sign`, `--author`, `--date`, or `--reset-author`.

Do not create, rename, switch, or delete a branch. Do not create a tag or remote. Do not push or rewrite history.

Do not change local or global Git configuration, including identity, signing, hooks, safe-directory, file-mode, line-ending, ignore, or credential settings.

Report effective author name and a redacted email. Missing identity is a blocker. If existing configuration requires signing, permit normal signing; if it fails, stop.

---

# 3. ACCEPTED PRE-PHASE-0K BASELINE

Before caller Phase 0K records are added, require:

```text
Branch: main
Only local branch: main
HEAD: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD tree: 9d3326fae7f7e7b8c75f3309af0cb5b5a6f81e8d
HEAD parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD parent count: 1
Commit count: 3
HEAD subject: Establish Kongono framework baseline through Phase 0H
Tracked files in HEAD: 187
Tracked modes in HEAD: 187 x 100644
Remotes: 0
Tags: 0
Staged paths: 0
Tracked worktree modifications: 11
Untracked paths: 4
Total status paths: 15
```

The tracked diff is exactly:

```text
11 files changed
274 insertions
28 deletions
```

## Exact 11 accepted tracked modifications

```text
ARCHITECTURE.md
README.md
docs/adr/0024-exact-direct-relation-selection.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/doctrine.md
docs/engineering/application-graph-structure.md
packages/application-graph/README.md
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/tests/Unit/ApplicationGraphTest.php
```

There is no tracked deletion, rename, mode change, or staged path.

## Exact four accepted untracked paths

```text
docs/adr/0025-exact-whole-snapshot-kind-selection.md
    5,181 bytes
    SHA-256 E509658BC0E1230E87753A4CC018389E9BFC07D7A019114EBCB30791143B9A78

docs/dev_plan/kongono_phase_0J_codex_directive.md
    32,534 bytes
    SHA-256 F94D361FFA375B0AA200BC2054CF7D003483BDD59BCD4192AFD0A73F226F7975

docs/task reports/Phase_0I_2_correction.md
    20,584 bytes
    SHA-256 E78EADAA4FEDC30D3867E00AB636AAC7DF3FD46E3C873F0158150C137FD8D886

docs/task reports/Phase_0J.md
    24,312 bytes
    SHA-256 A2CFA44561BEAF9E4AF9E76263BFB96B51FA7A266C14B992998C2C08BA95AF51
```

All four are accepted implementation or caller records. Preserve them byte-for-byte.

The Phase 0J report contains exactly 22 required top-level sections in order, strict UTF-8 without BOM, LF endings, and a final newline. Its archived Markdown copy need not retain the executor response's transport fence.

---

# 4. PHASE 0K CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0K_codex_directive.md
docs/task reports/Phase_0K.md
```

The directive must be a non-empty exact caller-supplied copy of this artifact. Read it fully, record size and SHA-256, and do not edit it. It is the only Phase 0K record authorized to enter the commit.

The report must be an exactly zero-byte caller-managed placeholder through staging, commit creation, and all post-commit validation. Do not read it as a directive, stage it, commit it, rename it, or delete it. Only after the checkpoint and its post-commit gates pass may you populate it with the completion report required by Section 14. It must remain untracked and outside HEAD.

If the placeholder is non-empty, stop.

After caller setup, require:

```text
Tracked modifications: 11
Untracked paths: 6
Total status paths: 17
Staged paths: 0
Physical worktree paths: 193
Intended commit paths: 192
```

The six untracked paths are the four accepted paths from Section 3, this directive, and the zero-byte Phase 0K report. No other path is authorized.

---

# 5. CONTENT IMMUTABILITY

Phase 0K authorizes Git index and commit metadata changes only.

Do not edit, create, delete, move, rename, format, regenerate, or normalize repository content. The caller-supplied Phase 0K directive and zero-byte report placeholder are the only new files. The sole later write is population of that report after every post-commit gate passes, exactly as Section 14 requires.

Do not run Composer update/install, a formatter in write mode, code generation, documentation generation, dependency mutation, or any source/test/configuration edit.

Read-only quality tooling may update ignored caches. They must remain ignored and absent from status.

If verification reveals a defect, stop without fixing it and request a separate correction directive.

---

# 6. REQUIRED PRE-STAGE VERIFICATION

Run and record:

```text
php -v
php --ini
composer --version
git --version
vendor/bin/phpunit --version
vendor/bin/phpstan --version
vendor/bin/php-cs-fixer --version
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-parse HEAD^
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -3 --format="%H %P %s"
git ls-tree -r HEAD
git remote
git tag
git status --short --untracked-files=all
git diff --name-status --no-renames
git diff --stat
git diff --numstat
git diff --cached --exit-code
git diff --check
git config --get user.name
git config --get user.email
git config --get commit.gpgsign
git config --get user.signingkey
git config --get core.hooksPath
```

Absent optional signing/hook settings are informational. Missing author identity is a blocker.

Create a task-specific evidence directory outside the repository. Record every tracked/untracked path, mode where applicable, size, and SHA-256; exact status inventories; all six untracked record hashes; locked Composer inventory; and secret-scan results without secret values.

The worktree inventory must contain exactly 193 physical paths: 187 tracked plus six untracked. Exactly 192 are intended for commit. The sole excluded path is `docs/task reports/Phase_0K.md`.

Before staging, verify:

1. every baseline fact and hash in Sections 3 and 4;
2. exact 11-path tracked diff and exact six-path untracked inventory;
3. Phase 0J directive and report structure;
4. exactly 27 production class-like declarations with unchanged API statuses;
5. `ApplicationGraph` remains final and readonly with exactly private `nodes` and `relations`;
6. its declaration-owned public methods are exactly `__construct`, `hasNode`, `incomingRelations`, `node`, `nodes`, `nodesOfKind`, `outgoingRelations`, `relations`, and `relationsOfKind`;
7. exact endpoint selectors and whole-snapshot kind selectors retain their accepted semantics;
8. kind domains remain separate open labels with no vocabulary/schema;
9. direct scans, canonical order, detached lists, same object identity, and absence of indexes/caches;
10. ADR 0025 is Accepted, dated 2026-08-13, indexed, and consistent with ADR 0024's narrow later-decision note;
11. no Graph/DI relationship or new Kernel lifecycle integration;
12. no dependency, manifest, lockfile, autoload, plugin, tooling, security-policy, version, license, or release change;
13. no prohibited Phase 0J or later capability;
14. all local Markdown links;
15. strict UTF-8, LF, final newlines, no trailing whitespace, NUL, replacement character, mojibake, conflict marker, or unexpected zero-byte file;
16. no secret, unsafe environment file, binary, executable mode, link, reparse point, nested repository, submodule, generated artifact, or unexpected oversized file;
17. all 90 first-party PHP files pass syntax checking, including `.php-cs-fixer.dist.php`;
18. focused `ApplicationGraphTest.php` and `ApiStatusConventionTest.php` pass;
19. canonical `composer quality` passes, including the real locked audit.

Accepted quality baseline:

```text
PHPUnit: 127 tests, 567 assertions
Focused ApplicationGraph: 36 tests, 132 assertions
API-status architecture: 5 tests, 154 assertions
PHPStan: 0 errors
PHP CS Fixer: 0 of 89 files require a fix
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Composer warnings must remain limited to the missing license and existing unreleased local `@dev` or `*` constraints.

If restricted execution blocks only the unchanged advisory source or normal ignored tool caches, rerun the same read-only check with authorized access. Never weaken or omit validation.

Recompute the intended 192-file inventory after checks. Any content or concurrency difference is a blocker.

---

# 7. SECRET AND FILE-SAFETY GATE

Inspect all 192 intended committed paths for:

```text
environment files
private keys and certificates
tokens and credentials
cloud, GitHub, OpenAI, registry, database, mail, and webhook secrets
credential-bearing URLs
caches, logs, coverage, and test output
editor and operating-system metadata
archives and generated builds
unexpected vendor directories
nested .git directories
symlinks, junctions, reparse points, Gitlinks, and submodules
executable modes
unexpected absolute machine paths
```

Historical directives, reports, and memory handoffs may contain truthful developer-local paths. That does not authorize secrets or machine paths in source, manifests, current architecture/engineering docs, public READMEs, tests, or tooling.

Never print a suspected secret. Identify only path and category, stop, and do not stage.

All 192 intended entries must be ordinary `100644` files.

---

# 8. EXACT STAGING CONTRACT

Stage only the accepted Phase 0J delta and this Phase 0K directive. Do not use `git add .`, `git add -A`, a wildcard, an interactive staging command, or any command that can absorb an unexpected path.

First stage the exact tracked modifications:

```powershell
git add -u -- .
```

This must stage exactly the 11 modified tracked paths from Section 3 and no deletion.

Then add exactly these five untracked records, using literal paths:

```powershell
git add -- "docs/adr/0025-exact-whole-snapshot-kind-selection.md"
git add -- "docs/dev_plan/kongono_phase_0J_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0K_codex_directive.md"
git add -- "docs/task reports/Phase_0I_2_correction.md"
git add -- "docs/task reports/Phase_0J.md"
```

Before and during the commit, never stage, populate, modify, delete, rename, or move:

```text
docs/task reports/Phase_0K.md
```

After staging, the exact state must be:

```text
16 staged paths
  11 modified
   5 added
   0 deleted
   0 renamed or copied

192 index entries
192 ordinary 100644 files

1 untracked path
  docs/task reports/Phase_0K.md
```

Run and preserve evidence from:

```powershell
git status --short --untracked-files=all
git status --porcelain=v1 --untracked-files=all
git diff --exit-code
git diff --cached --check
git diff --cached --name-status
git diff --cached --stat
git diff --cached --numstat
git ls-files --stage
git write-tree
```

The unstaged tracked diff must be empty. The cached name-status inventory must match the closed 16-path list exactly. Compare staged blob IDs and staged content hashes with the pre-stage evidence; staging must not transform any byte.

The Phase 0K report placeholder is not an error, but it is never part of the index or commit. Any other untracked, unstaged, staged, ignored-sensitive, or unexpected path is a blocker.

---

# 9. FINAL PRE-COMMIT GATE

With the exact index assembled and before creating the commit:

1. rerun `composer quality` without changing its command or configuration;
2. rerun the focused Application Graph and API-status test files;
3. confirm all 90 first-party PHP files still pass syntax checking;
4. repeat the secret and file-safety gate against the staged blobs;
5. repeat all staged-state checks from Section 8;
6. verify the staged tree contains exactly 192 `100644` files;
7. verify the staged Phase 0K directive is byte-identical to the caller-provided directive;
8. verify the Phase 0K report remains an unstaged, zero-byte, ordinary file;
9. verify `main`, its current HEAD, tree, parent, subject, commit count, branch count, remote count, and tag count still equal Section 3;
10. verify no hook, formatter, test, or concurrent process changed repository content.

The final pre-commit quality result must equal or improve the accepted pass counts without reducing coverage, deleting tests, weakening assertions, changing tooling, or concealing warnings. An unexpected count or state difference requires investigation and a stop, not an assumption.

Do not commit if any gate is not exact.

---

# 10. EXACT COMMIT OPERATION

Create one ordinary non-merge commit from the verified index with this exact subject:

```text
Establish Kongono framework baseline through Phase 0J
```

Use a non-interactive commit command that supplies that subject exactly. Do not amend, squash, rebase, reset, cherry-pick, merge, tag, sign unless the repository's already-configured policy requires signing, or add a generated message body.

The new commit must:

```text
be on main
be the fourth commit reachable from main
have exactly one parent
have parent 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
contain exactly 192 files
contain only ordinary 100644 entries
change exactly 16 paths from its parent
record 11 modifications and 5 additions
record no deletion, rename, copy, Gitlink, or executable mode
use the exact subject above
```

The resulting commit and tree hashes are outputs, not values to invent in advance. Record them exactly.

If Git identity, signing, hook behavior, concurrency, or any other condition prevents this exact commit, stop and report Blocked. Do not change repository-local or global Git configuration, bypass hooks, disable signing policy, or improvise a substitute history operation.

---

# 11. REQUIRED POST-COMMIT VERIFICATION

Immediately after the commit, capture and verify:

```powershell
git branch --show-current
git for-each-ref --format="%(refname)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-parse HEAD^
git rev-list --count HEAD
git log -1 --format=%s
git show --format=fuller --stat --summary HEAD
git diff-tree --root --no-commit-id --name-status -r HEAD
git diff-tree --no-commit-id --name-status -r HEAD^ HEAD
git ls-tree -r HEAD
git status --short --untracked-files=all
git status --porcelain=v1 --untracked-files=all
git diff --exit-code
git diff --cached --exit-code
git remote -v
git tag --list
```

Required state:

```text
branch: main
branches: exactly refs/heads/main
commit count: 4
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD subject: Establish Kongono framework baseline through Phase 0J
HEAD files: 192
HEAD modes: all 100644
parent delta: exactly the authorized 16 paths
tracked worktree diff: empty
index diff: empty
untracked: only docs/task reports/Phase_0K.md
Phase_0K.md size: exactly 0 bytes
remotes: 0
tags: 0
```

Prove that the three earlier commit objects and their tree hashes remain unchanged. No earlier commit may be rewritten.

Rerun after the commit:

```text
composer quality
focused ApplicationGraphTest.php
focused ApiStatusConventionTest.php
all first-party PHP syntax checks
```

The committed checkout must reproduce the accepted Phase 0J quality baseline. Recompute SHA-256 and sizes for the 192 committed files and compare them with the verified pre-stage inventory. The only expected repository-state change is the new commit and the tracking of the exact five additions.

Do not stage or commit the newly written completion report after the checkpoint. It must remain the sole untracked caller-owned record.

---

# 12. PROHIBITED SCOPE

Phase 0K authorizes repository checkpointing only. It does not authorize:

```text
production, test, documentation, ADR, manifest, lockfile, or tooling edits
formatting, cleanup, normalization, generated-file refresh, or warning suppression
dependency or Composer changes
new packages or namespace changes
API additions, removals, renames, visibility changes, or status changes
Application Graph behavior changes, indexes, caches, traversal, schema, IDs, or serialization
DI/container or resolver changes
Kernel lifecycle changes
HTTP, routing, middleware, request, response, session, cookie, or server work
ORM, database, migration, query, persistence, or transaction work
agents, extensions, plugins, hooks, or discovery systems
CLI, console, scheduler, queue, worker, frontend, templating, assets, or telemetry
release, version, license, remote, tag, branch, push, pull, fetch, merge, rebase, or history-rewrite work
Phase 0L implementation or speculative architecture
deleting or absorbing caller records
```

Do not fix an adjacent issue even if it appears obvious. Record it as an observation only when relevant to this checkpoint. Under the no-assumption rule, ambiguity, drift, or mismatch means stop.

---

# 13. ACCEPTANCE CRITERIA

Phase 0K is accepted only when every item below is proven:

- the accepted Phase 0J implementation was independently reverified before staging;
- all content is byte-identical to the authorized pre-stage state;
- the exact 11 tracked modifications and exact five additions were staged;
- the Phase 0K report placeholder was never staged or committed;
- staged content passed syntax, focused, canonical quality, secret, encoding, link, and file-safety gates;
- the new commit is the fourth commit on `main`;
- it has exactly one parent, the locked pre-Phase-0K HEAD;
- it has the exact required subject;
- it contains exactly 192 ordinary `100644` files;
- its parent delta is exactly 16 paths: 11 modified and five added;
- no prior commit was changed;
- no branch, remote, tag, submodule, Gitlink, symlink, executable, binary, cache, build artifact, secret, or unrelated path was introduced;
- post-commit quality and focused verification pass at the accepted Phase 0J baseline;
- the index and tracked worktree are clean afterward;
- the only remaining untracked path is the zero-byte Phase 0K report while tests run;
- the final report is later written only into that caller-owned path and remains untracked;
- no prohibited scope was implemented;
- the completion report follows Section 14 exactly.

A commit existing is not sufficient. The exact content, history, test, and residual-worktree evidence is required.

---

# 14. STANDARD EXECUTOR COMPLETION REPORT

Write the final report only to:

```text
docs/task reports/Phase_0K.md
```

The report must be truthful, inspectable, and self-contained. It must remain untracked and must not be amended into the checkpoint.

The entire report must be inside one outer plain-text fence so nested commands and output cannot break its structure. Put no text outside that fence. Use exactly these 22 numbered sections in this order:

```text
1. STATUS
2. SUMMARY
3. PRE-STAGE BASELINE
4. CALLER AND ACCEPTED RECORDS
5. CONTENT AND INVENTORY AUDIT
6. PHASE 0J ACCEPTANCE REVERIFICATION
7. SECRET AND FILE SAFETY
8. PRE-STAGE TEST AND QUALITY RESULTS
9. STAGING COMMANDS
10. STAGED STATE
11. FINAL PRE-COMMIT GATE
12. COMMIT RESULT
13. POST-COMMIT REPOSITORY STATE
14. CONTENT INTEGRITY
15. POST-COMMIT TEST AND QUALITY RESULTS
16. FILES CREATED, MODIFIED, MOVED, OR REMOVED BY EXECUTOR
17. COMMANDS RUN
18. ARCHITECTURAL DECISIONS AND DEVIATIONS
19. QUESTIONS, BLOCKERS, AND RESIDUAL RISKS
20. ACCEPTANCE CHECKLIST
21. FINAL GIT STATUS AND REPORT SELF-CHECK
22. NEXT STEP
```

Mandatory report evidence includes:

- `Complete` or `Blocked` status;
- exact environment and tool versions;
- all locked pre-stage Git hashes, counts, and inventories;
- exact caller-record sizes and SHA-256 values;
- before/staged/committed/post-commit path inventories and file counts;
- exact staged tree hash, new commit hash, new tree hash, parent hash, subject, and commit count;
- parent-delta name-status, mode, and count evidence;
- proof that prior commits and trees were unchanged;
- secret/file-safety, encoding, link, syntax, focused-test, and full-quality results;
- exact PHPUnit test/assertion counts, PHPStan result, style result, audit result, and accepted warnings;
- explicit confirmation that no production or documentation content was edited by the executor;
- exact statement that the only executor-created repository history object was the authorized commit;
- exact final status, including the untracked report itself and its nonzero final byte count;
- confirmation that the final report path is not staged, not tracked, and not contained in HEAD;
- any failed command and its exact safe resolution;
- deviations, questions, blockers, and residual risks, or `None`;
- a line-by-line acceptance checklist;
- Section 22 exactly: `Await architect review before Phase 0L.`

For a completed run, Section 16 must say that the executor made no content-file change and created only the authorized Git commit; the report population is caller-record evidence outside the commit. If the run blocks before commit, report the observed state and do not claim completion. If it blocks after commit, do not attempt destructive rollback; preserve evidence and report the exact state for architect review.

Before returning, self-check the populated report for strict UTF-8, LF, final newline, no BOM, NUL, replacement character, mojibake, conflict marker, trailing whitespace, or text outside its one outer fence. Re-run Git status after writing the report and make the resulting nonzero report size and SHA-256 the final recorded evidence.

---

# 15. FINAL EXECUTOR INSTRUCTION

Act only as the Phase 0K checkpoint executor. Verify the accepted Phase 0J repository, stage the exact authorized 16-path delta, create exactly one commit with the locked subject, reverify the committed state, and leave only the populated Phase 0K report untracked.

Do not modify framework content. Do not advance Phase 0L. Do not push, tag, branch, rewrite history, or absorb the report.

If any path, hash, count, mode, test, warning category, security result, caller record, or Git fact differs from this directive, stop and report Blocked rather than guessing.
