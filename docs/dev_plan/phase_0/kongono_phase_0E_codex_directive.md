# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0E

## Accepted Repository Baseline and Initial Commit

## Mission

You are the implementation executor for Kongono Framework.

Phases 0A through 0D are complete and architecturally accepted. Kongono now has:

- repository governance and architectural doctrine;
- accepted ADRs 0001 through 0022;
- a locked PHP 8.5 engineering toolchain and dependency-security policy;
- the accepted `kongono/kernel` package;
- the accepted `kongono/application-graph` structural-core package;
- production, architecture, and tooling tests;
- accepted development directives and task-completion records through Phase 0D.

The repository still has no commit. Its Git index contains three historical staged entries that no longer match the accepted working tree, while nearly all accepted repository files remain untracked.

This task establishes one trustworthy local Git baseline.

Implement only:

1. a complete pre-commit repository and secret audit;
2. preservation of all accepted working-tree content;
3. inclusion of this Phase 0E directive in the repository development-plan history;
4. normalization of the Git index to the accepted working tree;
5. renaming the unborn branch from `master` to `main`;
6. one local initial commit containing the accepted Phase 0A–0D baseline plus this directive;
7. post-commit integrity and quality verification;
8. the required completion report.

This is Phase **0E — Accepted Repository Baseline and Initial Commit**.

Do not implement or modify framework behavior during this task.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are an **executor, not the architect**.

Read before acting:

```text
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
SECURITY.md
.gitignore
.gitattributes
.editorconfig
composer.json
composer.lock
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
docs/adr/README.md
all ADRs 0001 through 0022
all architecture and engineering documents
all Phase 0A–0D implementation directives and correction directives
all Phase 0A–0D task reports and correction reports
both package manifests and READMEs
all production and test source
```

All LOCKED doctrine and Accepted ADRs remain authoritative.

## Mandatory stop rule

Stop without committing if any of the following occurs:

- a secret, credential, private key, access token, or sensitive environment file may enter the commit;
- a non-ignored file cannot be classified as an accepted Kongono artifact;
- an accepted file is missing, empty unexpectedly, unreadable, malformed, or inconsistent with its accepted phase;
- the worktree changes concurrently after the final pre-stage inventory;
- the repository already has a commit;
- a remote already exists;
- Git author identity is unavailable;
- commit signing is required but cannot succeed;
- a Git hook rejects the commit;
- any required quality or integrity check fails for a repository reason;
- completing the task requires editing framework code, tests, configuration, doctrine, ADR decisions, or accepted historical reports;
- the intended staged tree cannot be proven to match the accepted working tree.

Do not bypass a stop condition.

When stopped, report:

1. the exact blocker;
2. affected paths without printing secret values;
3. why committing would be unsafe;
4. the minimal corrective options;
5. a recommendation labeled as a recommendation only.

## No “helpful” corrections

This is a baseline task, not an implementation-correction task.

If review discovers a code, documentation, configuration, ADR, test, dependency, or security defect:

```text
STOP
DO NOT FIX IT
DO NOT COMMIT
REPORT IT FOR A SEPARATE CORRECTIVE DIRECTIVE
```

The only content file authorized for creation is the Phase 0E directive described in Section 6. No other repository content may be created, edited, renamed, or deleted.

---

# 2. LOCKED GIT DECISIONS FOR PHASE 0E

These decisions are authorized and LOCKED for this task:

```text
Primary local branch: main
Commit count after task: exactly 1
Commit parent count: 0
Commit message: Establish Kongono framework baseline through Phase 0D
Remote creation: prohibited
Push: prohibited
Tag creation: prohibited
History rewrite: prohibited
Hook bypass: prohibited
Signing bypass: prohibited
Force operations: prohibited
```

Renaming the current unborn `master` branch to `main` is authorized.

Do not alter global or local Git configuration to make the commit succeed. In particular, do not set or change:

- `user.name`;
- `user.email`;
- `init.defaultBranch`;
- signing keys;
- `commit.gpgsign`;
- safe-directory policy;
- hooks paths;
- line-ending policy;
- file-mode policy;
- credential helpers.

Read and report the effective author identity without modifying it. Do not print authentication credentials or unrelated Git configuration.

Use the exact commit message:

```text
Establish Kongono framework baseline through Phase 0D
```

Do not add a generated body, co-author trailer, sign-off, issue reference, AI attribution, or Conventional Commit prefix.

Do not use:

```text
--amend
--allow-empty
--no-verify
--no-gpg-sign
--author
--date
--reset-author
```

If existing Git configuration signs commits, allow normal signing to occur. If signing fails, stop; do not disable it.

---

# 3. REQUIRED PRECONDITION INSPECTION

Run and record before modifying the branch or index:

```bash
php -v
php --ini
composer --version
git --version
git status --short
git branch --show-current
git remote -v
git config --get user.name
git config --get user.email
git config --get commit.gpgsign
git config --get user.signingkey
```

Treat absent optional signing settings as information, not failure.

## Confirm unborn repository

Verify all of the following:

```text
HEAD does not resolve
There are zero commits
Current branch is master
There are zero remotes
```

Use read-only Git commands that correctly distinguish an unborn branch from an error. Do not allow an expected “no HEAD yet” result to abort the inspection script accidentally.

If HEAD resolves, commit count is nonzero, branch is not `master`, or a remote exists, stop and report the actual state. Do not adapt the plan automatically.

## Confirm author identity

Both effective `user.name` and `user.email` must be non-empty.

Report the name and a redacted email form in the completion report, such as:

```text
a***@example.com
```

Do not copy the email into repository documentation.

---

# 4. ACCEPTED BASELINE SCOPE

The initial commit must contain all and only the accepted non-generated repository artifacts described here.

## Authorized root files

```text
.editorconfig
.gitattributes
.gitignore
.php-cs-fixer.dist.php
AGENTS.md
ARCHITECTURE.md
CONTRIBUTING.md
README.md
SECURITY.md
composer.json
composer.lock
phpstan.neon.dist
phpunit.xml.dist
```

## Authorized documentation tree

```text
docs/adr/
docs/architecture/
docs/dev_plan/
docs/engineering/
docs/memory handoff/
docs/task reports/
docs/doctrine.md
docs/documentation-standard.md
docs/glossary.md
```

The accepted ADR set before this task is exactly:

```text
docs/adr/0001-*.md through docs/adr/0022-*.md
docs/adr/README.md
```

There must be exactly one file for each ADR number 0001 through 0022. Do not renumber, rewrite, add, or remove ADRs.

## Authorized package tree

```text
packages/kernel/
packages/application-graph/
```

No other package directory is authorized.

## Authorized root test tree

```text
tests/Architecture/
tests/Tooling/
```

## Required historical records

The baseline must include the accepted development directives through Phase 0D, including correction directives, and the accepted task reports through Phase 0D, including correction reports.

Historical directives and task reports may contain developer-local paths because they record actual execution evidence. That exception applies only within:

```text
docs/dev_plan/
docs/task reports/
docs/memory handoff/
```

Developer-local absolute paths are not authorized in production source, package manifests, root configuration, ADRs, architecture documents, engineering documents, doctrine, glossary, or public READMEs.

## Expected pre-Phase-0E count

At the accepted Phase 0D state, the union of currently staged and non-ignored untracked work contains **99 unique repository files**.

After adding the Phase 0E directive required by Section 6, and before any external Phase 0E task-report file appears, the intended initial commit contains exactly **100 files**.

Do not use the count alone as proof. Verify every exact path and classification.

If a concurrent external process creates a Phase 0E report placeholder, editor file, or other path during execution:

- do not overwrite it;
- do not stage it;
- do not delete it;
- identify it as concurrent state;
- continue only if it cannot affect the intended 100-file commit or validation;
- otherwise stop.

---

# 5. EXPLICITLY EXCLUDED CONTENT

The following must not enter the initial commit:

```text
.git/
.idea/
.vscode/
vendor/
.cache/
.phpunit.cache/
.php-cs-fixer.cache
.env
.env.* except an explicitly authorized .env.example (none currently exists)
*.log
*.cache
.DS_Store
Thumbs.db
temporary files
editor swap/backup files
coverage output
test result caches
static-analysis caches
downloaded archives
build artifacts
operating-system metadata
credentials
private keys
```

Confirm `.gitignore` excludes the accepted generated/local paths. Do not edit `.gitignore` in this phase. If an exclusion is missing or ineffective, stop and request a corrective directive.

No symbolic link, junction, reparse point, submodule, Gitlink, executable file mode, or nested Git repository is authorized in the initial tree.

All committed entries must be ordinary files with Git mode `100644`.

---

# 6. PHASE 0E DIRECTIVE RECORD

The repository must contain:

```text
docs/dev_plan/kongono_phase_0E_codex_implementation_directive.md
```

Its content must be byte-for-byte identical to the directive supplied for this task, except that a final newline may be normalized according to `.gitattributes`/`.editorconfig` if the supplied file lacks one.

If the supplied directive is already present at that exact path, verify it and do not rewrite it.

If it is not present, create only that file from the supplied directive.

Do not summarize, reformat, edit, or reinterpret the directive when recording it.

This file is the only authorized repository-content creation in Phase 0E.

Do not create `docs/task reports/Phase_0E.md`. Return the completion report to the caller; the caller's environment may preserve it after execution.

---

# 7. CONTENT, SECRET, AND SAFETY AUDIT

Perform the audit before staging.

## 7.1 Exact path inventory

Build a sorted inventory of every non-ignored file that would be staged. Confirm:

- every path is inside an authorized root or tree from Section 4;
- the only package directories are `kernel` and `application-graph`;
- ADR numbering is exactly 0001 through 0022 with no duplicate;
- no accepted directive or task report through Phase 0D is missing;
- no file is zero bytes unless its accepted content is intentionally empty;
- the three historically staged files have non-empty accepted working-tree forms where applicable;
- there are exactly 100 intended files after adding the Phase 0E directive;
- paths differ unambiguously under Windows case-insensitive filesystem semantics;
- no filename contains a control character or trailing dot/space.

The three historical staged entries that require index normalization are expected to include:

```text
docs/dev_plan/kongono_phase_0B_2_codex_directive_correction.md
docs/task reports/Phase_0B_1_Correction.md
docs/task reports/Phase_0C_1_correction.md
```

Do not treat their stale staged blobs as authoritative. The accepted working-tree files are authoritative for the initial baseline.

## 7.2 File-type and size audit

Confirm:

- all intended files are ordinary UTF-8/text project artifacts except `composer.lock`, which is still JSON text;
- no intended file is a binary executable, archive, image, database, or opaque blob;
- no intended file exceeds 1 MiB;
- the line-ending policy is `.gitattributes`: LF in Git;
- all intended text files end with a newline;
- no unresolved merge-conflict marker exists.

Do not mass-format or rewrite files to correct a failure. Stop and report it.

## 7.3 Secret scan

Scan filenames and contents across all intended files for likely:

- private keys and certificates with private material;
- API tokens and bearer tokens;
- GitHub/GitLab tokens;
- OpenAI or other provider secret keys;
- AWS access keys or secret keys;
- database connection URLs with credentials;
- passwords and password assignments;
- OAuth client secrets;
- webhook secrets;
- session secrets;
- `.env` contents;
- authentication cookies;
- personal access tokens;
- high-entropy credential-like strings;
- credential-bearing URLs.

Use local tools only. Do not send repository content to a network service.

Do not print a discovered secret. Report only:

```text
path
line number if safe
secret category
whether the match may be a false positive
```

If any match cannot be confidently classified as harmless documentation/example text, stop before staging.

## 7.4 Machine-local content audit

Search for developer-machine absolute paths and local usernames outside the three historical-record directories authorized in Section 4.

Search at minimum for:

```text
C:\Users\
/Users/
/home/
file://
localhost credentials
```

Do not rewrite findings. Stop if a machine-local reference exists outside the authorized historical-record locations and is not clearly intentional architecture documentation.

## 7.5 Dependency and generated-content audit

Confirm:

- `vendor/` is ignored and absent from intended files;
- Composer caches are absent;
- PHPUnit/PHPStan/PHP CS Fixer caches are absent;
- `composer.lock` contains exactly the two accepted local production packages and the accepted 66 development packages;
- no Composer plugin is present or authorized;
- dependency-security policy remains unchanged;
- no external dependency version changed after Phase 0D acceptance.

---

# 8. PRE-STAGE VALIDATION

Before changing the branch or index, run:

```bash
composer validate
composer validate:composer
composer validate:packages
composer audit
composer audit:locked
composer platform
composer check-platform-reqs
composer install --dry-run --no-interaction
composer cs
composer analyse
composer test
composer quality
composer -vvv quality
```

Confirm:

- all manifests validate with only accepted warnings;
- both package manifests validate in lexicographic order;
- no security advisory exists;
- all platform requirements pass;
- dry-run install reports no changes;
- PHP CS Fixer reports no required changes;
- PHPStan maximum level with strict rules reports zero errors;
- PHPUnit reports 50 tests and 249 assertions unless an environmental/tool version difference is explicitly proven without content change;
- PHPUnit reports zero errors, failures, warnings, risky tests, deprecations, notices, or incomplete tests;
- quality passes in the exact accepted stage order;
- no baseline, suppression, ignore, exclusion, warning allowance, plugin authorization, or policy weakening exists.

If network access is sandbox-blocked, use the normal approval mechanism and rerun the exact command. Do not weaken Composer security or skip the locked audit.

Do not proceed to index normalization unless the complete pre-stage gate is green.

---

# 9. WORKTREE PRESERVATION PROOF

Immediately before changing the branch or index:

1. calculate SHA-256 for every intended file;
2. store the sorted `path + hash` manifest outside the repository in an environment temporary directory;
3. record the manifest's own SHA-256;
4. record the intended path count;
5. record `git status --short` again.

Do not place the manifest inside Kongono and do not commit it.

If status differs from the audited inventory, stop and identify the concurrent change.

After staging and again after committing, recalculate the worktree manifest and prove it is byte-identical to the pre-stage manifest. Git line-ending normalization may affect staged blob bytes, but staging and committing must not change the working-tree files.

Delete the temporary manifest after the final report data has been captured if deletion is safe and confined to the exact temporary file created by this task. If not, leave it and report its exact temporary path. Never use a broad recursive delete.

---

# 10. BRANCH AND INDEX NORMALIZATION

Only after Sections 3–9 pass:

## 10.1 Rename unborn branch

Rename the current unborn branch exactly:

```text
master -> main
```

Verify:

```bash
git branch --show-current
```

Expected:

```text
main
```

Do not create a second branch.

## 10.2 Normalize the index

Stage the complete intended baseline from the repository root so the index matches the accepted working tree.

The operation is explicitly authorized to replace the three stale historical staged blobs with their current accepted working-tree content and to add every accepted untracked file.

Do not use:

```text
git reset
git checkout
git restore
git clean
git rm
git update-index --assume-unchanged
git update-index --skip-worktree
```

Do not stage ignored files or concurrent files outside the intended 100-file inventory.

After staging, verify:

```bash
git status --short
git diff --cached --check
git diff --cached --stat
git diff --cached --name-status
git ls-files --stage
git diff --cached --summary
```

Required staged state:

- exactly 100 files;
- every entry status is Added because this is an unborn repository;
- every mode is `100644`;
- no submodule/Gitlink exists;
- no ignored file is tracked;
- no unstaged difference exists for any intended baseline file;
- staged path inventory exactly equals the intended inventory;
- staged content is non-empty where required;
- cached diff check passes.

Explicitly inspect the staged forms of the three previously stale paths and verify each now matches its accepted working-tree file.

## 10.3 Staged-content validation

Do not assume that a green worktree validates the staged tree.

Export the staged index to a newly created temporary directory using a Git index-safe mechanism. Confirm the resolved export path is within the exact task-specific temporary directory before use.

Within the exported staged tree, run at minimum:

```text
Composer manifest validation for root and both packages
JSON parsing for composer.json and composer.lock
PHP syntax checks for every committed PHP file
Markdown local-link validation
UTF-8 / final-newline / conflict-marker checks
```

Do not install dependencies into the exported tree and do not copy `vendor/` into it. The purpose is to validate the staged content independently of the worktree, not to create another development environment.

Remove only the exact temporary export directory after validation. Resolve and verify its absolute location before recursive removal. If safe removal cannot be established, leave it and report the path.

---

# 11. INITIAL COMMIT

After staged-content validation passes, create exactly one commit using the exact message:

```text
Establish Kongono framework baseline through Phase 0D
```

Allow configured hooks and signing policy to run normally.

Do not retry by bypassing a failing hook or signature. If the commit fails, preserve the staged tree, report the failure, and stop.

After success, record:

```bash
git rev-parse HEAD
git rev-parse HEAD^{tree}
git log -1 --format=fuller
git log -1 --format=%P
git rev-list --count HEAD
git show --stat --oneline --decorate --summary HEAD
git status --short
git branch --show-current
git remote -v
```

Required results:

```text
branch: main
commit count: 1
parent list: empty
subject: Establish Kongono framework baseline through Phase 0D
committed files: 100
remote count: 0
tag count: 0
```

The author and committer must come from existing Git/environment configuration. Do not rewrite them.

---

# 12. POST-COMMIT VERIFICATION

## 12.1 Tree verification

Verify the committed tree, not merely the worktree:

- commit contains exactly the intended 100 paths;
- all committed modes are `100644`;
- ignored/generated paths are absent;
- both package production and test source are present;
- the root architecture test is present;
- the obsolete Kernel-local architecture-test path is absent;
- ADRs 0001–0022 and their index are present;
- all accepted directives through Phase 0E are present;
- all accepted reports through Phase 0D are present;
- both package entries and all accepted external versions remain in `composer.lock`;
- the three previously stale staged paths contain their accepted worktree content.

Use commit-object/tree reads where possible. Do not rely only on filesystem presence.

## 12.2 Worktree preservation

Recalculate the intended-file SHA-256 manifest and compare it with the pre-stage manifest.

Required:

```text
path set unchanged
every worktree file hash unchanged
```

The only content creation was the Phase 0E directive, and it was present before the preservation manifest.

## 12.3 Post-commit quality

Run again:

```bash
composer install --dry-run --no-interaction
composer audit:locked
composer cs
composer analyse
composer test
composer quality
```

All results must remain green with the same accepted warnings and test count.

## 12.4 Final state

Final `git status --short` should be empty.

The only permitted exception is a concurrent externally created Phase 0E task-report placeholder that appeared after the commit. If one exists:

- preserve it untracked;
- do not add it to the initial commit;
- report its path, size, creation/last-write time, and whether it is empty;
- do not claim the worktree is clean;
- confirm every committed baseline file remains clean.

No other staged, modified, deleted, or untracked file is permitted.

---

# 13. EXPLICITLY PROHIBITED ACTIONS

Do not:

- change production or test code;
- change Composer manifests or lockfile;
- change tool configuration;
- change `.gitignore`, `.gitattributes`, or `.editorconfig`;
- change doctrine, architecture, engineering, glossary, ADR, README, security, or contribution content;
- rewrite accepted directives or task reports;
- add an ADR;
- add a changelog, release note, version, license, code of conduct, funding file, or community file;
- add GitHub/GitLab configuration;
- add CI;
- add a remote;
- contact GitHub or any hosting service;
- push;
- fetch, pull, clone, or merge;
- create a tag;
- create more than one commit;
- amend or rebase;
- squash existing history—there is no existing history;
- create another branch;
- use force options;
- bypass hooks or signing;
- alter Git identity or configuration;
- stage ignored/generated files;
- delete caches merely to obtain a clean status;
- delete, overwrite, restore, or reformat user files;
- implement Phase 0F;
- implement DI, HTTP, stable Graph IDs, schemas, serialization, discovery, compilers, ORM, agents, extensions, CLI, frontend, telemetry, or other framework capability.

Do not create a remote repository. Do not publish Kongono.

---

# 14. STRICT ACCEPTANCE CRITERIA

Phase 0E is complete only if every criterion passes.

## Preconditions

- [ ] Required authority and accepted artifacts were read.
- [ ] Repository was confirmed to have no resolvable HEAD and zero commits.
- [ ] Initial branch was exactly `master`.
- [ ] No remote existed.
- [ ] Existing Git author name and email were available without configuration changes.
- [ ] Signing and hook policy were inspected and not bypassed.

## Baseline audit

- [ ] Every intended path was classified under the authorized baseline scope.
- [ ] The pre-Phase-0E union contained 99 accepted files.
- [ ] The Phase 0E directive was added byte-for-byte at the required path.
- [ ] Intended commit inventory contained exactly 100 files.
- [ ] ADR set was exactly 0001–0022 with no gap or duplicate.
- [ ] Accepted directives through Phase 0E were present.
- [ ] Accepted task reports through Phase 0D were present.
- [ ] Only `kernel` and `application-graph` package directories existed.
- [ ] No unexpected empty, binary, oversized, ambiguous, symlinked, nested-repository, or executable-mode artifact existed.
- [ ] Secret scan found no unapproved sensitive material.
- [ ] Machine-local paths outside authorized historical records were absent.
- [ ] Generated, vendor, cache, environment, and editor content was excluded.

## Repository correctness before staging

- [ ] Root and both package manifests validated.
- [ ] Locked and native audits found no advisories.
- [ ] Platform requirements passed.
- [ ] Dry-run install reported no change.
- [ ] PHP CS Fixer was clean.
- [ ] PHPStan max/strict reported zero errors.
- [ ] PHPUnit passed 50 tests and 249 assertions with zero problematic outcomes.
- [ ] Full quality and verbose dispatch passed in accepted order.
- [ ] No policy weakening, baseline, suppression, ignore, or plugin authorization existed.

## Preservation and index

- [ ] Pre-stage SHA-256 manifest covered exactly the intended 100 files.
- [ ] Unborn branch was renamed exactly `master` to `main`.
- [ ] No other branch was created.
- [ ] Index contained exactly the intended 100 paths.
- [ ] All staged entries were Added with mode `100644`.
- [ ] Three historically stale staged files were replaced by their accepted working-tree content.
- [ ] No ignored, generated, concurrent, or unexpected file was staged.
- [ ] Cached diff check passed.
- [ ] Staged tree independently passed manifest, JSON, PHP syntax, Markdown-link, UTF-8, newline, and conflict-marker checks.
- [ ] Staging did not change worktree hashes.

## Commit

- [ ] Exactly one commit was created.
- [ ] Commit has zero parents.
- [ ] Commit is on `main`.
- [ ] Commit subject is exact.
- [ ] Commit contains exactly 100 files.
- [ ] Commit modes are all `100644`.
- [ ] No hook or signing policy was bypassed.
- [ ] Existing configured author/committer identity was used.
- [ ] No tag or remote was created.
- [ ] Nothing was pushed.

## Post-commit verification

- [ ] Committed tree exactly matches intended inventory.
- [ ] Required accepted paths are present and obsolete architecture-test path is absent.
- [ ] Worktree file hashes remain identical to the pre-stage manifest.
- [ ] Post-commit dry-run install, locked audit, style, analysis, tests, and quality pass.
- [ ] Final branch is `main`.
- [ ] Commit count remains one.
- [ ] Remote and tag counts remain zero.
- [ ] Final status is clean or contains only a reported concurrent external Phase 0E report placeholder.
- [ ] No content other than the Phase 0E directive was created or modified by this task.

If any criterion fails, report Partial or Blocked—not Complete.

---

# 15. REQUIRED EXECUTOR COMPLETION REPORT

Return exactly this structure:

```text
PHASE 0E TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
Concise description of the baseline operation.

3. INITIAL REPOSITORY STATE
- HEAD/unborn result
- initial branch
- commit count
- remote count
- staged entries
- unstaged entries
- non-ignored untracked count
- known stale-index mismatches

4. ENVIRONMENT AND GIT IDENTITY
- PHP, Composer, and Git versions
- author name
- redacted author email
- signing configuration state
- hook outcome
- confirmation no Git configuration changed

5. AUTHORIZED FILE CREATED
- exact Phase 0E directive path
- byte-equivalence verification
- confirmation no other content file was created

6. BASELINE INVENTORY AUDIT
- intended file count
- root file count
- documentation counts by directory
- package counts by package
- root test count
- ADR sequence result
- directives/reports sequence result
- zero-byte/binary/size/path/mode/symlink/nested-repository results

7. SECRET AND MACHINE-LOCAL AUDIT
- local tools/method used
- secret-category results without values
- machine-local path results
- historical-record exceptions
- blockers or false positives reviewed

8. GENERATED AND DEPENDENCY AUDIT
- ignored/generated exclusions
- vendor/cache/editor/environment result
- Composer package counts
- local package entries
- external-version confirmation
- plugin/security-policy confirmation

9. PRE-STAGE QUALITY RESULTS
- manifest validation
- audits
- platform
- dry-run install
- style
- PHPStan
- PHPUnit tests/assertions/outcomes
- quality
- verbose stage order

10. WORKTREE PRESERVATION MANIFEST
- intended path count
- manifest SHA-256
- temporary manifest disposition
- pre-stage versus post-stage comparison
- pre-stage versus post-commit comparison

11. BRANCH RESULT
- old branch
- new branch
- confirmation no second branch

12. INDEX NORMALIZATION RESULT
- staged path count
- status and mode summary
- cached diff check
- stale-path verification
- ignored/concurrent exclusion
- staged/worktree equality result

13. STAGED-TREE INDEPENDENT VALIDATION
- temporary export path/disposition
- manifest validation
- JSON parsing
- PHP syntax
- Markdown links
- UTF-8/newline/conflict-marker results

14. INITIAL COMMIT
- full commit hash
- tree hash
- exact subject
- author/committer source
- parent count
- committed path count
- mode summary
- signing result
- hook result

15. COMMITTED-TREE AUDIT
- exact inventory match
- required paths
- prohibited/obsolete paths
- ADR/directive/report presence
- package and lock presence
- stale historical-path content confirmation

16. POST-COMMIT QUALITY RESULTS
- dry-run install
- locked audit
- style
- PHPStan
- PHPUnit
- full quality

17. FINAL GIT STATE
- branch
- commit count
- status
- remote count
- tag count
- concurrent Phase 0E report placeholder, if any
- confirmation no push occurred

18. DEVIATIONS
None, or exact deviation and approval status.

19. QUESTIONS / BLOCKERS
None, or exact architect/user action required.

20. ACCEPTANCE CHECKLIST
Pass/Fail for every criterion in Section 14.

21. NEXT STEP
Await architect review before Phase 0F.
```

For item 21, state only:

> Await architect review before Phase 0F.

Do not begin Phase 0F.

---

# 16. FINAL EXECUTION INSTRUCTION

This phase establishes provenance, not features.

The successful outcome is exactly:

```text
Accepted Phase 0A–0D working tree audited
No secret or generated material committed
Phase 0E directive recorded
Unborn branch renamed master -> main
Stale index replaced with accepted working-tree content
Exactly 100 ordinary files staged
Staged tree independently validated
Exactly one zero-parent local commit created
No remote, tag, or push
Post-commit quality green
Accepted worktree content unchanged
No Phase 0F implementation
```

When complete, stop and return the required report.
