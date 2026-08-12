# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0G

## Accepted Phase 0F Repository Checkpoint

## Mission

Create the second immutable local Git checkpoint for Kongono.

Phase 0F and its Phase 0F.1 report correction are architecturally accepted. Implement only:

1. complete read-only verification of the accepted Phase 0F state;
2. secret, file-safety, scope, and intended-tree audits;
3. inclusion of this Phase 0G directive in repository history;
4. exact staging of the accepted Phase 0F checkpoint and this directive;
5. one local commit on `main`;
6. post-commit integrity and quality verification;
7. the required completion report.

This phase creates no framework behavior and authorizes no content edit. Do not begin Phase 0H.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the checkpoint executor, not the architect.

Read completely before acting:

1. `AGENTS.md` and all locked doctrine and repository guidance;
2. ADRs 0001 through 0023;
3. all Phase 0A through Phase 0F directives and correction directives;
4. all Phase 0A through Phase 0F reports and correction reports;
5. all current manifests, tooling configuration, production source, tests, package READMEs, and Phase 0F documentation;
6. this Phase 0G directive.

The Phase 0F.1 report is the accepted conformance record. The earlier `Phase_0F.md` is preserved historical evidence of a malformed original return.

## Mandatory stop rule

Stop before staging or committing if:

- the baseline differs from this directive;
- any accepted file is missing, unexpectedly empty, malformed, or changed concurrently;
- a secret, credential, sensitive environment file, generated artifact, or unclassified path may enter the commit;
- a required quality, security, file-safety, or integrity check fails for a repository reason;
- the index is not initially empty;
- an unexpected branch, commit, remote, tag, submodule, nested repository, executable mode, symlink, junction, or reparse point exists;
- Git author identity is unavailable;
- signing or a hook cannot complete normally;
- the intended staged tree cannot be proven exactly;
- any content edit or architectural decision would be required.

If a stop condition appears:

```text
STOP
DO NOT FIX
DO NOT COMMIT
REPORT THE EXACT BLOCKER
```

Do not bypass a hook, disable signing, weaken validation, repair implementation, or improvise another checkpoint.

---

# 2. LOCKED GIT CONTRACT

The exact outcome is:

```text
Branch: main
Branch count: 1
Commit count: 2
New commit parent: 136822352a0caac199506cbddb4c9a539001c592
New commit parent count: 1
Commit subject: Establish Kongono framework baseline through Phase 0F
Tracked files: 178
Tracked modes: 178 x 100644
Remotes: 0
Tags: 0
Push: prohibited
```

Use the exact commit message:

```text
Establish Kongono framework baseline through Phase 0F
```

Do not add a body, prefix, issue reference, sign-off, co-author trailer, or AI attribution.

Do not use `--amend`, `--allow-empty`, `--no-verify`, `--no-gpg-sign`, `--author`, `--date`, or `--reset-author`.

Do not create, rename, switch, or delete a branch. Do not create a tag or remote. Do not push or rewrite history.

Do not change local or global Git configuration, including identity, signing, hooks, safe-directory, file-mode, line-ending, ignore, or credential settings.

Report the effective author name and a redacted email. If either is absent, stop. If configuration requires signing, allow it; if signing fails, stop.

---

# 3. ACCEPTED PRE-PHASE-0G BASELINE

Before the caller adds Phase 0G records, the exact state is:

```text
Branch: main
Only branch: main
HEAD: 136822352a0caac199506cbddb4c9a539001c592
HEAD tree: 88d0f199e95e6891739df5c4a4d7cc139ab12cae
Commit count: 1
HEAD parent count: 0
HEAD subject: Establish Kongono framework baseline through Phase 0D
Tracked files in HEAD: 104
Tracked modes in HEAD: 104 x 100644
Remotes: 0
Tags: 0
Staged paths: 0
Tracked worktree modifications: 27
Untracked paths: 73
Total status paths: 100
```

The 100 status paths are exactly 27 accepted tracked modifications, 68 accepted Phase 0F created paths, and five accepted historical caller records. No deletion or other status category exists.

## Exact 27 tracked modifications

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0020-kernel-operation-lifecycle-boundary.md
docs/adr/README.md
docs/architecture/microkernel.md
docs/architecture/overview.md
docs/architecture/progressive-architecture.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/package-layout.md
docs/engineering/php-style.md
docs/engineering/static-analysis.md
docs/engineering/testing.md
docs/engineering/toolchain.md
docs/glossary.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

## Exact 68 Phase 0F created paths

They are the 13 required created paths in Phase 0F Section 15 plus the 55 fixture paths listed individually in the accepted Phase 0F.1 report:

```text
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/engineering/dependency-injection.md
packages/dependency-injection/README.md
packages/dependency-injection/composer.json
packages/dependency-injection/src/Contract/ResolverInterface.php
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/src/Exception/InvalidDefinitionException.php
packages/dependency-injection/src/Exception/UnknownServiceException.php
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
packages/dependency-injection/tests/Fixture/*.php - exactly the 55 accepted paths
```

Do not accept the wildcard as proof. Enumerate and compare all fixture paths with Phase 0F.1.

## Exact five historical caller records

```text
docs/task reports/Phase_0E_2_correction.md
    11,603 bytes
    SHA-256 D6DE57DE2A2C2087156784EB5DBF35E48B565A43C6FB4E85763BB52A50DCF6E4

docs/dev_plan/kongono_phase_0F_codex_directive.md
    71,070 bytes
    SHA-256 1E28B6C822DAAD78A0675C5747BAFDA9C75D7F5A01DCA2A63D01606012C09797

docs/task reports/Phase_0F.md
    24,044 bytes
    SHA-256 418243423B07445DBD396A2D20EA0D6EA65F8C7A5825EDC3F6DB77B15997EB52

docs/dev_plan/kongono_phase_0F_1_codex_directive_correction.md
    16,118 bytes
    SHA-256 C6660BC5A414FDD85828ED2DBFFDF808CB2D4B3B6BFD37E04663C482FCA64BF0

docs/task reports/Phase_0F_1_correction.md
    37,039 bytes
    SHA-256 B4BAF4CA45F811FB343E1D431EC54F8EF951D323FF9C9A32BFBF27E1CDA52185
```

The Phase 0F directive repository copy historically has a trailing standalone `Read` token. Phase 0F.1 established that it changed no authority. Preserve it exactly.

Preserve both Phase 0F reports. The accepted Phase 0F.1 report must have one title, headings 1 through 22 once and in order, 55 full fixture paths, clean UTF-8, and a final newline.

---

# 4. PHASE 0G CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0G_codex_directive.md
docs/task reports/Phase_0G.md
```

The directive must be a non-empty caller-supplied copy of this file and begin with its exact title. Read it fully and record its size and SHA-256. It is the only Phase 0G record authorized to enter the commit. Do not edit it.

The report placeholder must be exactly zero bytes. Do not populate, edit, stage, commit, rename, or delete it. Return the report in the response; the caller will archive it afterward.

If the report placeholder is non-empty before execution, stop.

After both files exist, the exact pre-stage state is:

```text
Tracked modifications: 27
Untracked paths: 75
Total status paths: 102
Staged paths: 0
```

The 75 untracked paths are the 68 Phase 0F paths, five historical records, this directive, and the zero-byte report placeholder. No other path is authorized.

---

# 5. CONTENT IMMUTABILITY

Phase 0G authorizes Git index and commit metadata changes only.

Do not edit, create, delete, move, rename, format, regenerate, or normalize repository content. The caller-supplied Phase 0G directive and zero-byte placeholder are the only new files.

Do not run Composer update/install, a formatter in write mode, code generation, documentation generation, dependency mutation, or any source/test/configuration edit.

Read-only quality tooling may update ignored caches. They must remain ignored and absent from status.

If a check reveals a defect, stop, do not fix it, and request a separate correction.

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
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -1 --format=%s
git ls-tree -r HEAD
git remote
git tag
git status --short --untracked-files=all
git diff --cached --exit-code
git diff --check
git config --get user.name
git config --get user.email
git config --get commit.gpgsign
git config --get user.signingkey
git config --get core.hooksPath
```

Absent optional signing/hook settings are informational. Missing author identity is a blocker.

Create a task-specific directory outside the repository and record every tracked/untracked path, mode where applicable, size, and SHA-256; the exact status inventories; all seven caller-record hashes; the locked Composer inventory; and secret-scan results without secret values.

The worktree inventory is exactly 179 paths: 104 existing tracked plus 75 untracked. Exactly 178 are intended for commit. The sole excluded path is `docs/task reports/Phase_0G.md`.

Before staging, verify:

1. all historical hashes and Phase 0F.1 structure;
2. 27 production declarations repository-wide and seven Dependency Injection declarations;
3. exactly 55 accepted fixtures with one matching declaration per file;
4. the exact Phase 0F created/modified inventory;
5. no Dependency Injection/Application Graph production dependency;
6. Kernel imports limited to `Api` and `ApiStatus` metadata;
7. exact API statuses, method inventories, type shapes, and exception-marker separation;
8. no prohibited Phase 0F capability or external production dependency;
9. all local Markdown links;
10. strict UTF-8, final newlines, no trailing whitespace/NUL/conflict markers/unexpected zero-byte file;
11. no secret, unexpected machine path, binary, executable mode, link, reparse point, nested repository, submodule, generated package artifact, or oversized unexpected file;
12. focused Dependency Injection PHPUnit;
13. `composer quality`, including the real locked audit.

Use authorized access if the restricted environment blocks the network audit. Do not skip or weaken it.

Recompute the intended 178-file inventory after checks. Any content change or concurrency difference is a blocker.

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

All 178 intended entries must be ordinary `100644` files.

---

# 8. EXACT STAGING CONTRACT

Only after all pre-stage checks pass, stage the exact checkpoint.

Do not use `git add -A`, `git add .`, `git add --all`, or an unrestricted wildcard.

Stage tracked modifications only:

```text
git add -u -- .
```

Then stage only these accepted untracked groups and exact files:

```text
docs/adr/0023-explicit-typed-dependency-resolution.md
docs/dev_plan/kongono_phase_0F_codex_directive.md
docs/dev_plan/kongono_phase_0F_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0G_codex_directive.md
docs/engineering/dependency-injection.md
docs/task reports/Phase_0E_2_correction.md
docs/task reports/Phase_0F.md
docs/task reports/Phase_0F_1_correction.md
packages/dependency-injection/
```

Quote paths containing spaces. Staging the package directory is authorized only after proving it contains exactly 66 accepted paths:

```text
7 production files
2 unit-test files
55 fixture files
README.md
composer.json
```

Never stage `docs/task reports/Phase_0G.md`.

## Required staged-state proof

After staging, require:

```text
Staged paths: 101
Staged modifications: 27
Staged additions: 74
Staged deletions: 0
Staged renames: 0
Unstaged tracked paths: 0
Untracked paths: 1
Untracked path: docs/task reports/Phase_0G.md
Index entries: 178
Index modes: 178 x 100644
```

The 74 additions are 68 Phase 0F paths, five historical caller records, and this Phase 0G directive.

Run and inspect:

```text
git status --short --untracked-files=all
git diff --exit-code
git diff --cached --check
git diff --cached --name-status --no-renames
git diff --cached --stat
git ls-files --stage
git write-tree
```

Record the tree object from `git write-tree`. Compare every index path and blob with the approved 178-file inventory. Counts alone are insufficient.

If the report placeholder or any unexpected path is staged, stop before committing.

---

# 9. FINAL PRE-COMMIT GATE

With the exact index staged and only the report placeholder untracked:

1. rerun `composer quality`;
2. rerun focused Dependency Injection PHPUnit;
3. rerun the staged diff and mode checks;
4. require `git diff --exit-code`;
5. recompute all 178 intended worktree hashes;
6. verify the placeholder remains zero-byte and untracked;
7. verify branch, HEAD, remotes, tags, author identity, signing, and hooks remain acceptable.

Every result and the staged tree must equal the previously approved result. Otherwise stop for concurrent mutation.

---

# 10. AUTHORIZED COMMIT

Create exactly one normal local commit with:

```text
Establish Kongono framework baseline through Phase 0F
```

Use normal Git behavior. Do not bypass hooks/signing or supply author/date metadata.

The commit must:

- be the second commit on `main`;
- have only parent `136822352a0caac199506cbddb4c9a539001c592`;
- use the recorded staged tree;
- contain 178 ordinary `100644` files;
- contain the exact 101-path delta;
- exclude only the report placeholder from the 179-path worktree inventory;
- create no branch, tag, remote, or push.

If commit fails, report the failure and current state. Do not amend, bypass, reset, or rewrite.

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
git log -2 --format="%H %P %s"
git show --no-ext-diff --format=fuller --stat --summary HEAD
git diff HEAD^ HEAD --name-status --no-renames
git ls-tree -r HEAD
git diff --exit-code
git diff --cached --exit-code
git remote
git tag
```

Require:

```text
Branch: main, and only main
Commit count: 2
HEAD parent: 136822352a0caac199506cbddb4c9a539001c592
HEAD parent count: 1
HEAD subject: Establish Kongono framework baseline through Phase 0F
HEAD tree: recorded pre-commit tree
HEAD files: 178
HEAD modes: 178 x 100644
Parent delta: 101 paths; 27 modified; 74 added; 0 deleted; 0 renamed
Unstaged tracked changes: 0
Staged changes: 0
Untracked: only docs/task reports/Phase_0G.md
Report placeholder: 0 bytes
Remotes: 0
Tags: 0
```

Verify the first commit object, tree, parent count, and subject are unchanged. Verify all committed bytes against the approved inventory, including the Phase 0G directive and five historical Phase 0E.2/0F/0F.1 records.

Rerun `composer quality`, focused Dependency Injection PHPUnit, and `git diff --check`. The real locked audit must pass after commit.

Do not stage or commit the report placeholder.

---

# 12. EXPLICITLY PROHIBITED SCOPE

Do not:

- change source, tests, fixtures, manifests, lockfile, tooling, documentation, ADRs, doctrine, directives, or reports;
- correct historical Phase 0F records;
- change ignore rules, dependencies, versions, license, or release policy;
- add PSR-11, autowiring, service lifetimes, Graph/Kernel integration, HTTP, ORM, agents, extensions, CLI, frontend, telemetry, or later capability;
- add CI/CD, publishing, distribution, changelog, tag, or remote behavior;
- create Phase 0H files;
- push or communicate externally;
- clean, reset, restore, checkout, stash, rebase, cherry-pick, merge, amend, or rewrite history;
- alter Git configuration, hooks, signing, credentials, or ignores;
- populate or commit `docs/task reports/Phase_0G.md`.

This is a checkpoint only.

---

# 13. STRICT ACCEPTANCE CRITERIA

Phase 0G is complete only if every item passes.

## Authority and baseline

- [ ] All authority and implementation records were read fully.
- [ ] The exact Phase 0F/0F.1 baseline matched.
- [ ] The initial index was empty.
- [ ] Exact 27 modified and 75 untracked paths were classified.
- [ ] All seven caller records were classified and preserved.
- [ ] The report placeholder began and remained zero bytes.
- [ ] No decision was guessed.

## Content and security

- [ ] No repository content was edited by the executor.
- [ ] Pre-stage and final pre-commit inventories matched.
- [ ] All 178 intended bytes were proven.
- [ ] Secret and file-safety audits passed.
- [ ] All intended entries were ordinary `100644` files.
- [ ] Phase 0F boundaries and prohibited-scope checks passed.
- [ ] Focused tests and full quality passed.

## Staging

- [ ] Only authorized staging operations were used.
- [ ] Exactly 101 paths were staged: 27 modifications and 74 additions.
- [ ] No deletion or rename was staged.
- [ ] The index had 178 `100644` entries.
- [ ] No unstaged tracked change remained.
- [ ] Only the report placeholder remained untracked.
- [ ] The staged tree matched the content inventory.
- [ ] The final pre-commit gate passed.

## Commit

- [ ] Exactly one new commit was created.
- [ ] It is commit two on sole branch `main`.
- [ ] It has the accepted first commit as its only parent.
- [ ] Subject is exact with no unauthorized body/trailer.
- [ ] Its tree equals the staged tree and has 178 `100644` files.
- [ ] No hook/signing policy was bypassed.
- [ ] No branch, remote, tag, push, or rewrite occurred.

## Post-commit

- [ ] HEAD, parent, tree, count, subject, and delta were verified.
- [ ] The first commit remained immutable.
- [ ] Committed bytes matched the approved inventory.
- [ ] Index and tracked worktree were clean.
- [ ] Only the zero-byte report placeholder remained untracked.
- [ ] Post-commit focused tests and quality passed.
- [ ] No implementation or later-phase work occurred.
- [ ] The completion report contains every required section.

If any item fails, report `Partial` or `Blocked`, not `Complete`.

---

# 14. REQUIRED EXECUTOR COMPLETION REPORT

Return the entire report inside one fenced `text` code block so literal numbering and line breaks survive copying.

Inside that block, use exactly:

```text
PHASE 0G TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
- checkpoint outcome
- confirmation no content implementation occurred

3. PRE-STAGE BASELINE
- environment and tool versions
- branch, HEAD, tree, parent, count, and subject
- tracked count/modes and status counts
- remotes/tags
- author identity with redacted email
- signing/hook observations

4. CALLER RECORDS
- all seven paths, roles, sizes, and SHA-256 values
- Phase 0F historical directive discrepancy
- original versus accepted Phase 0F report classification

5. CONTENT AND INVENTORY AUDIT
- 179-path worktree inventory
- 178-path intended commit inventory
- modified/created/package/fixture counts
- pre-stage/final pre-commit comparison
- modes and file types

6. PHASE 0F ACCEPTANCE REVERIFICATION
- symbols and statuses
- package/dependency/autoload boundary
- definition/resolver invariants
- exceptions/marker separation
- documentation/ADR state
- prohibited-scope results

7. SECRET AND FILE-SAFETY AUDIT
- every Section 7 category
- historical machine-path classification
- confirmation no secret value printed

8. PRE-STAGE TEST AND QUALITY RESULTS
- focused command and counts
- full quality and dispatch order
- PHPUnit, PHPStan, style, platform, and locked audit
- access distinctions

9. STAGING COMMANDS
- exact commands in order
- proof no unrestricted add was used

10. STAGED STATE
- 101 staged paths
- 27 modified, 74 added, 0 deleted/renamed
- 178 index entries/modes
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
- branch set and two-commit log
- HEAD/tree/parent/subject
- 178 files/modes
- parent delta counts
- index/worktree/untracked placeholder
- remotes/tags

14. POST-COMMIT CONTENT INTEGRITY
- committed-versus-approved inventory
- Phase 0G directive hash
- historical-record hashes
- first-commit immutability

15. POST-COMMIT TEST AND QUALITY RESULTS
- focused/full commands and counts
- analysis, style, platform, and security results

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
Await architect review before Phase 0H.
```

For Section 22, state only:

> Await architect review before Phase 0H.

Do not append content after Section 22. Do not omit, merge, rename, reorder, or renumber sections. Do not claim conformance unless headings 1 through 22 visibly appear once and in order inside the code block.

---

# 15. FINAL INSTRUCTION

Establish the accepted Phase 0F checkpoint as exactly one second local commit.

Do not change content. Do not commit the report placeholder. Do not create a remote or tag. Do not push. Do not begin Phase 0H.

When verification is complete, stop and return the exact fenced report.
