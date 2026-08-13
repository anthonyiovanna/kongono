# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0I

## Accepted Phase 0H Repository Checkpoint

## Mission

Create the third immutable local Git checkpoint for Kongono.

Phase 0H is architecturally accepted. Implement only:

1. complete read-only verification of the accepted Phase 0H state;
2. secret, file-safety, scope, and intended-tree audits;
3. inclusion of this Phase 0I directive in repository history;
4. exact staging of the accepted Phase 0H checkpoint and this directive;
5. one normal local commit on `main`;
6. post-commit content, Git, security, and quality verification;
7. the required executor completion report.

This phase creates no framework behavior and authorizes no repository-content edit by the executor. Do not begin Phase 0J.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the checkpoint executor, not the architect.

Read completely before acting:

1. `AGENTS.md` and all locked governance, doctrine, architecture, engineering, and security guidance;
2. ADRs 0001 through 0024 and the ADR index;
3. all Phase 0A through Phase 0H directives, correction directives, reports, and correction reports;
4. all current manifests, tooling configuration, production source, tests, and package READMEs;
5. the accepted Phase 0H report;
6. this Phase 0I directive.

The populated `docs/task reports/Phase_0H.md` is the accepted Phase 0H conformance record. Do not reinterpret, edit, regenerate, or normalize it.

## Mandatory stop rule

Stop before staging or committing if:

- the baseline differs from this directive;
- any accepted file is missing, unexpectedly empty, malformed, or concurrently changed;
- the Phase 0I directive does not match the caller-supplied artifact or the Phase 0I report placeholder is not exactly zero bytes;
- a secret, credential, sensitive environment file, generated artifact, or unclassified path may enter the commit;
- a required quality, security, file-safety, or integrity check fails for a repository reason;
- the index is not initially empty;
- an unexpected branch, commit, remote, tag, submodule, nested repository, executable mode, symlink, junction, or reparse point exists;
- Git author identity is unavailable;
- signing or a hook cannot complete normally;
- the intended staged tree cannot be proven exactly;
- any content edit, correction, or architectural decision would be required.

If a stop condition appears:

```text
STOP
DO NOT FIX
DO NOT COMMIT
REPORT THE EXACT BLOCKER
```

Do not bypass a hook, disable signing, weaken validation, repair Phase 0H, or improvise another checkpoint.

---

# 2. LOCKED GIT OUTCOME

The exact outcome is:

```text
Branch: main
Only local branch: main
Commit count: 3
New commit parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
New commit parent count: 1
Commit subject: Establish Kongono framework baseline through Phase 0H
Tracked files: 183
Tracked modes: 183 x 100644
Remotes: 0
Tags: 0
Push: prohibited
```

Use the exact commit message:

```text
Establish Kongono framework baseline through Phase 0H
```

Do not add a body, prefix, issue reference, sign-off, co-author trailer, or AI attribution.

Do not use `--amend`, `--allow-empty`, `--no-verify`, `--no-gpg-sign`, `--author`, `--date`, or `--reset-author`.

Do not create, rename, switch, or delete a branch. Do not create a tag or remote. Do not push or rewrite history.

Do not change local or global Git configuration, including identity, signing, hooks, safe-directory, file-mode, line-ending, ignore, or credential settings.

Report the effective author name and a redacted email. If either is absent, stop. If existing configuration requires signing, allow normal signing; if it fails, stop.

---

# 3. ACCEPTED PRE-PHASE-0I BASELINE

Before the caller adds Phase 0I records, the exact state is:

```text
Branch: main
Only local branch: main
HEAD: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD tree: 58623c654c8febad5e299f7c5cf8c30ffe4ee95f
HEAD parent: 136822352a0caac199506cbddb4c9a539001c592
HEAD parent count: 1
Commit count: 2
HEAD subject: Establish Kongono framework baseline through Phase 0F
Tracked files in HEAD: 178
Tracked modes in HEAD: 178 x 100644
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
323 insertions
22 deletions
```

## Exact 11 accepted tracked modifications

```text
ARCHITECTURE.md
README.md
docs/adr/0022-immutable-application-graph-structural-snapshot.md
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
docs/adr/0024-exact-direct-relation-selection.md
    4,446 bytes
    SHA-256 9E13C2B0D7E08F21A1BA727E7A4799A07E30C3E839C8B79823B6D42172590C66

docs/dev_plan/kongono_phase_0H_codex_directive.md
    32,191 bytes
    SHA-256 9070F0E7C72D98578275DFA8C20900CB4B50140B334E8F649D2092B8E515F25A

docs/task reports/Phase_0G.md
    22,011 bytes
    SHA-256 46D045825169CB35BDC4080704B8D05896B601473AD18CAFFE512D997D43DD06

docs/task reports/Phase_0H.md
    21,110 bytes
    SHA-256 361ED188B7857D355A5EF15EC05F1E294D60327B1C4C88F41C7A907A6EA0215C
```

All four are accepted Phase 0G/0H records or implementation. Preserve them byte-for-byte.

The Phase 0H report contains exactly 22 numbered top-level sections in the required order and ends with the Phase 0I architect-review handoff. Its archived Markdown copy need not contain the response transport's outer code fence. Do not treat that archive representation as a defect.

---

# 4. PHASE 0I CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0I_codex_directive.md
docs/task reports/Phase_0I.md
```

The directive must be a non-empty caller-supplied copy of this file and begin with its exact title. Read it fully, record its size and SHA-256, and do not edit it. It is the only Phase 0I record authorized to enter the commit.

The report must be a zero-byte caller-managed placeholder. Do not populate, edit, stage, commit, rename, or delete it. Return the report in the executor response; the caller will archive it afterward.

If the placeholder is non-empty before execution, stop.

After both files exist, the exact pre-stage state is:

```text
Tracked modifications: 11
Untracked paths: 6
Total status paths: 17
Staged paths: 0
Physical worktree paths: 184
Intended commit paths: 183
```

The six untracked paths are the four accepted paths from Section 3, this directive, and the zero-byte Phase 0I report. No other path is authorized.

---

# 5. CONTENT IMMUTABILITY

Phase 0I authorizes Git index and commit metadata changes only.

Do not edit, create, delete, move, rename, format, regenerate, or normalize repository content. The caller-supplied Phase 0I directive and zero-byte report placeholder are the only new files.

Do not run Composer update/install, a formatter in write mode, code generation, documentation generation, dependency mutation, or any source/test/configuration edit.

Read-only quality tooling may update ignored caches. They must remain ignored and absent from status.

If a check reveals a defect, stop, do not fix it, and request a separate correction phase.

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
git log -2 --format="%H %P %s"
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

Create a task-specific evidence directory outside the repository. Record every tracked/untracked path, mode where applicable, size, and SHA-256; the exact status inventory; all six caller/accepted-record hashes; the locked Composer inventory; and secret-scan results without printing secret values.

The worktree inventory is exactly 184 paths: 178 existing tracked files plus six untracked files. Exactly 183 are intended for commit. The sole excluded path is `docs/task reports/Phase_0I.md`.

Before staging, verify:

1. every baseline fact and accepted record hash in Sections 3 and 4;
2. the exact 11-path tracked diff and exact four accepted untracked Phase 0H paths;
3. the Phase 0H directive and report structure;
4. exactly 27 production class-like declarations repository-wide with unchanged API statuses;
5. `ApplicationGraph` remains final and readonly with exactly private `nodes` and `relations` state;
6. its declaration-owned public method inventory is exactly `__construct`, `hasNode`, `incomingRelations`, `node`, `nodes`, `outgoingRelations`, and `relations`;
7. both selectors use exact endpoint-key value equality, direct `O(R)` scanning, canonical-order preservation, same stored immutable relation objects, detached arrays, and no retained index/cache;
8. empty, missing, isolated, self-relation, order, identity, and value-collision behavior;
9. ADR 0024 is Accepted, dated 2026-08-12, indexed, and consistent with the narrow ADR 0022 later-decision note;
10. no Application Graph/Dependency Injection integration and no new Kernel lifecycle integration beyond existing API-status metadata dependencies;
11. no dependency, manifest, lockfile, autoload, plugin, tooling, security-policy, version, license, or release change;
12. no prohibited Phase 0H or later capability;
13. all local Markdown links;
14. strict UTF-8, LF endings, final newlines, no trailing whitespace, NUL, replacement character, mojibake, conflict marker, or unexpected zero-byte file;
15. no secret, unexpected machine path, binary, executable mode, link, reparse point, nested repository, submodule, generated package artifact, or oversized unexpected file;
16. focused Application Graph PHPUnit and root API-status architecture tests;
17. `composer quality`, including the real locked audit.

The accepted pre-stage quality baseline is:

```text
PHPUnit: 119 tests, 513 assertions
PHPStan: 0 errors
PHP CS Fixer: 0 of 89 files require a fix
Security advisories: 0
Platform requirements: pass on PHP 8.5.9
```

Composer validation warnings must remain limited to the intentionally missing license and existing unreleased local `@dev` or `*` constraints. A new warning category is a blocker.

If the restricted environment blocks only the real advisory lookup or existing ignored tool-cache access, rerun the unchanged read-only check with authorized access. Do not skip or weaken it.

Recompute the intended 183-file inventory after all checks. Any content change or concurrency difference is a blocker.

---

# 7. SECRET AND FILE-SAFETY GATE

Inspect every intended committed path for:

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

Historical directives, reports, and memory handoffs may contain truthful developer-local paths. That exception does not authorize secrets or machine-local paths in source, manifests, current architecture/engineering documentation, public READMEs, tests, or tooling.

Do not print a suspected secret. Identify only its path and category, stop, and do not stage.

All 183 intended entries must be ordinary `100644` files.

---

# 8. EXACT STAGING CONTRACT

Only after all pre-stage checks pass, stage the exact checkpoint.

Do not use `git add -A`, `git add .`, `git add --all`, or an unrestricted wildcard.

Stage the 11 tracked modifications only:

```text
git add -u -- .
```

Then stage only these exact accepted untracked files:

```text
git add -- \
  docs/adr/0024-exact-direct-relation-selection.md \
  docs/dev_plan/kongono_phase_0H_codex_directive.md \
  docs/dev_plan/kongono_phase_0I_codex_directive.md \
  "docs/task reports/Phase_0G.md" \
  "docs/task reports/Phase_0H.md"
```

The multiline form is illustrative; use shell-appropriate quoting without changing the exact path set.

Never stage `docs/task reports/Phase_0I.md`.

## Required staged-state proof

After staging, require:

```text
Staged paths: 16
Staged modifications: 11
Staged additions: 5
Staged deletions: 0
Staged renames: 0
Unstaged tracked paths: 0
Untracked paths: 1
Untracked path: docs/task reports/Phase_0I.md
Index entries: 183
Index modes: 183 x 100644
```

Run and inspect:

```text
git status --short --untracked-files=all
git diff --exit-code
git diff --cached --check
git diff --cached --name-status --no-renames
git diff --cached --stat
git diff --cached --numstat
git ls-files --stage
git write-tree
```

Record the tree object from `git write-tree`. Compare every index path, mode, and blob with the approved 183-file inventory. Counts alone are insufficient.

If the report placeholder or any unexpected path is staged, stop before committing.

---

# 9. FINAL PRE-COMMIT GATE

With the exact index staged and only the Phase 0I report placeholder untracked:

1. rerun `composer quality`;
2. rerun focused `ApplicationGraphTest.php` and `ApiStatusConventionTest.php`;
3. rerun staged diff, path, mode, and secret checks;
4. require `git diff --exit-code` and `git diff --cached --check`;
5. recompute all 183 intended worktree hashes;
6. verify the placeholder remains exactly zero bytes and untracked;
7. verify branch, HEAD, remotes, tags, author identity, signing, and hooks remain acceptable;
8. require the staged tree to equal the previously recorded approved tree.

Every result must match the approved pre-stage state. Otherwise stop for concurrent mutation.

---

# 10. AUTHORIZED COMMIT

Create exactly one normal local commit with:

```text
Establish Kongono framework baseline through Phase 0H
```

Use normal Git behavior. Do not bypass hooks/signing or supply author/date metadata.

The commit must:

- be the third commit on `main`;
- have only parent `29235c6a943dd9f3e96bae7b85ff4bea6a26535c`;
- use the recorded staged tree;
- contain exactly 183 ordinary `100644` files;
- contain the exact 16-path delta: 11 modified and five added;
- exclude only the Phase 0I report placeholder from the 184-path worktree inventory;
- create no branch, tag, remote, or push.

If commit fails, report the failure and exact current state. Do not amend, bypass, reset, or rewrite.

---

# 11. REQUIRED POST-COMMIT VERIFICATION

Run and report:

```text
git status --short --untracked-files=all
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-parse HEAD^
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -3 --format="%H %P %s"
git show --no-ext-diff --format=fuller --stat --summary HEAD
git diff HEAD^ HEAD --name-status --no-renames
git diff HEAD^ HEAD --stat
git ls-tree -r HEAD
git diff --exit-code
git diff --cached --exit-code
git remote
git tag
```

Require:

```text
Branch: main, and only main
Commit count: 3
HEAD parent: 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
HEAD parent count: 1
HEAD subject: Establish Kongono framework baseline through Phase 0H
HEAD tree: recorded pre-commit tree
HEAD files: 183
HEAD modes: 183 x 100644
Parent delta: 16 paths; 11 modified; 5 added; 0 deleted; 0 renamed
Unstaged tracked changes: 0
Staged changes: 0
Untracked: only docs/task reports/Phase_0I.md
Report placeholder: 0 bytes
Remotes: 0
Tags: 0
```

Verify both earlier commit objects, trees, parents, and subjects remain unchanged. Verify every committed byte against the approved inventory, including all five newly tracked files.

Rerun `composer quality`, focused Application Graph and API-status tests, and `git diff --check`. The real locked audit must pass after commit.

Do not stage or commit the report placeholder.

---

# 12. EXPLICITLY PROHIBITED SCOPE

Do not:

- change source, tests, manifests, lockfile, tooling, documentation, ADRs, doctrine, directives, or reports;
- correct or rewrite any historical record;
- change ignore rules, dependencies, versions, license, security policy, or release policy;
- add Graph queries, traversal, filters, neighbors, schema, identity, serialization, persistence, mutation, discovery, compilation, or indexes;
- add PSR-11, autowiring, service lifetimes, cleanup, context, Kernel/DI/Graph integration, HTTP, routing, ORM, security, events, queues, workflows, agents, extensions, CLI, frontend, telemetry, or another later capability;
- add CI/CD, publishing, distribution, changelog, tag, or remote behavior;
- create Phase 0J files;
- push or communicate externally;
- clean, reset, restore, checkout, stash, rebase, cherry-pick, merge, amend, or rewrite history;
- alter Git configuration, hooks, signing, credentials, or ignores;
- populate or commit `docs/task reports/Phase_0I.md`.

This is a checkpoint only.

---

# 13. STRICT ACCEPTANCE CRITERIA

Phase 0I is complete only if every item passes.

## Authority and baseline

- [ ] All required authority, implementation, directive, and report records were read fully.
- [ ] The exact accepted Phase 0H baseline matched Sections 3 and 4.
- [ ] The initial index was empty.
- [ ] Exactly 11 tracked modifications and six untracked paths were classified after caller setup.
- [ ] All six untracked paths and their roles were classified and preserved.
- [ ] The Phase 0I report placeholder began and remained zero bytes.
- [ ] No decision was guessed.

## Content, architecture, and security

- [ ] No repository content was edited by the executor.
- [ ] Pre-stage and final pre-commit inventories matched byte-for-byte.
- [ ] All 183 intended paths, modes, and bytes were proven.
- [ ] Secret and file-safety audits passed.
- [ ] All intended entries were ordinary `100644` files.
- [ ] Phase 0H API, behavior, test, documentation, and ADR boundaries passed independent reverification.
- [ ] Production inventory remained 27 declarations with unchanged API statuses.
- [ ] No prohibited capability, dependency, policy, tool, or content change occurred.
- [ ] Focused tests and full quality passed with the accepted warning policy.

## Staging

- [ ] Only authorized staging operations were used.
- [ ] Exactly 16 paths were staged: 11 modifications and five additions.
- [ ] No deletion, rename, or mode change was staged.
- [ ] The index had exactly 183 `100644` entries.
- [ ] No unstaged tracked change remained.
- [ ] Only the Phase 0I report placeholder remained untracked.
- [ ] The staged tree matched the approved content inventory.
- [ ] The final pre-commit gate passed.

## Commit

- [ ] Exactly one new commit was created.
- [ ] It is commit three on sole branch `main`.
- [ ] It has the accepted Phase 0F checkpoint as its only parent.
- [ ] Its subject is exact with no unauthorized body or trailer.
- [ ] Its tree equals the staged tree and contains 183 `100644` files.
- [ ] Its parent delta is exactly 11 modified and five added paths.
- [ ] No hook or signing policy was bypassed.
- [ ] No branch, remote, tag, push, or rewrite occurred.

## Post-commit

- [ ] HEAD, parent, tree, count, subject, and delta were verified.
- [ ] Both earlier commits remained immutable.
- [ ] Committed bytes matched the approved inventory.
- [ ] Index and tracked worktree were clean.
- [ ] Only the zero-byte Phase 0I report placeholder remained untracked.
- [ ] Post-commit focused tests and quality passed.
- [ ] No implementation or Phase 0J work occurred.
- [ ] The completion report contains every required section.

If any item fails, report `Partial` or `Blocked`, not `Complete`.

---

# 14. REQUIRED EXECUTOR COMPLETION REPORT

Return the entire report inside one fenced `text` block so literal numbering and line breaks survive copying.

Inside that block, use exactly these 22 numbered top-level sections in order:

```text
PHASE 0I TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
- checkpoint outcome
- confirmation no content implementation occurred

3. PRE-STAGE BASELINE
- environment and tool versions
- branch, HEAD, tree, parent, count, and subject
- tracked count/modes and exact status counts
- remotes/tags/index
- author identity with redacted email
- signing/hook observations

4. CALLER AND ACCEPTED RECORDS
- all six untracked paths before staging, roles, sizes, and SHA-256 values
- Phase 0G/0H report classification
- Phase 0I directive/placeholder treatment

5. CONTENT AND INVENTORY AUDIT
- 184-path worktree inventory
- 183-path intended commit inventory
- exact 11 modified and five intended-added paths
- pre-stage/final pre-commit comparison
- modes and file types

6. PHASE 0H ACCEPTANCE REVERIFICATION
- exact selector APIs, semantics, ordering, ownership, and performance
- class/property/method shape and 27-type API-status inventory
- tests and direct/reflection behavior
- package/dependency boundaries
- documentation and ADR state
- prohibited-scope results

7. SECRET AND FILE-SAFETY AUDIT
- every Section 7 category
- historical machine-path classification
- confirmation no secret value printed

8. PRE-STAGE TEST AND QUALITY RESULTS
- focused commands and counts
- full quality and dispatch order
- PHPUnit, PHPStan, style, platform, Composer validation, and locked audit
- access distinctions

9. STAGING COMMANDS
- exact commands in order
- proof no unrestricted add was used

10. STAGED STATE
- 16 staged paths
- 11 modified, five added, zero deleted/renamed
- 183 index entries/modes
- unstaged and untracked state
- diff checks
- git write-tree value
- staged blob comparison

11. FINAL PRE-COMMIT GATE
- rerun results
- inventory comparison
- branch/HEAD/placeholder confirmation

12. COMMIT RESULT
- exact command
- new commit hash
- parent/count
- subject/body/trailer confirmation
- author/committer and signing/hook result

13. POST-COMMIT REPOSITORY STATE
- branch set and three-commit log
- HEAD/tree/parent/subject
- 183 files/modes
- parent delta counts
- index/worktree/untracked placeholder
- remotes/tags

14. POST-COMMIT CONTENT INTEGRITY
- committed-versus-approved inventory
- hashes of all five newly tracked files
- both earlier commits' immutability

15. POST-COMMIT TEST AND QUALITY RESULTS
- focused/full commands and counts
- analysis, style, platform, Composer validation, and security results

16. FILES CREATED / MODIFIED / MOVED / REMOVED BY EXECUTOR
- no repository content change
- Git metadata only
- caller-supplied directive/placeholder treatment

17. COMMANDS RUN
- exact execution order
- access distinctions and failed non-mutating diagnostics

18. ARCHITECTURAL DECISIONS AND DEVIATIONS
None, or exact blocker/deviation and approval status.

19. QUESTIONS / BLOCKERS
None, or exact architect action required.

20. ACCEPTANCE CHECKLIST
- PASS / FAIL for every Section 13 criterion
- literal report-structure self-check

21. GIT STATUS SUMMARY
- exact final status
- no remote/tag/push/configuration/rewrite confirmation

22. NEXT STEP
Await architect review before Phase 0J.
```

For Section 22, state only:

> Await architect review before Phase 0J.

Do not append content after Section 22. Do not omit, merge, rename, reorder, or renumber sections. Do not claim conformance unless headings 1 through 22 visibly appear once and in order inside the code block.

Do not write to the zero-byte Phase 0I report placeholder. Return the report in the executor response for the caller to copy.

---

# 15. FINAL INSTRUCTION

Establish the accepted Phase 0H checkpoint as exactly one third local commit.

Do not change content. Do not commit the Phase 0I report placeholder. Do not create a remote or tag. Do not push. Do not begin Phase 0J.

When verification is complete, stop and return the exact fenced report.
