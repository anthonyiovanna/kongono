# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0N

## Accepted Phase 0M Repository Checkpoint

## Mission

Create one immutable local Git checkpoint containing the fully accepted Kongono repository through Phase 0M, including the Phase 0L doctrine, Phase 0M runtime diagnostic metadata, the blocked Phase 0M historical report, and the completed Phase 0M.1 correction record.

Phase 0N is a repository-history operation only. It authorizes no production, test, documentation, configuration, dependency, or tooling content change. Verify the exact accepted worktree, stage only the closed path inventory, create one ordinary commit on `main`, and leave only the Phase 0N completion report untracked.

The exact commit subject is:

```text
Establish Kongono framework baseline through Phase 0M
```

Do not begin structured diagnostic context, redaction machinery, a development error page, a renderer, HTTP or CLI presentation, logging, telemetry, incident handling, retry, cleanup orchestration, or any other later capability.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0N checkpoint executor, not the architect.

Read completely before acting:

1. `AGENTS.md`;
2. all tracked root doctrine, architecture, security, contribution, and quality authorities;
3. accepted ADRs 0001 through 0025 from `HEAD`;
4. every accepted Phase 0L and Phase 0M worktree path listed in Sections 3 and 4;
5. `docs/dev_plan/kongono_phase_0L_codex_directive.md`;
6. `docs/dev_plan/kongono_phase_0M_codex_directive.md`;
7. `docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md`;
8. `docs/task reports/Phase_0K.md`;
9. `docs/task reports/Phase_0L.md`;
10. `docs/task reports/Phase_0M.md`;
11. `docs/task reports/Phase_0M_1_correction.md`; and
12. this directive.

Phase 0N accepts the completed Phase 0M.1 correction. The blocked Phase 0M report remains an immutable historical record; it is not an active instruction and must not be edited or replaced.

## Mandatory stop rule

Stop before staging if any required fact differs. Stop after staging but before commit if the index is not exact. Stop after commit without attempting rollback if a post-commit check uncovers a mismatch.

```text
STOP
REPORT
DO NOT GUESS
DO NOT FIX
DO NOT COMMIT A MISMATCH
```

Stop for any of the following:

- wrong branch, additional branch, remote, tag, commit, parent, tree, subject, mode, count, status path, size, or digest;
- missing, populated, staged, tracked, renamed, or non-ordinary Phase 0N report placeholder;
- a Phase 0N directive that is not byte-identical to the caller-supplied artifact;
- any unexpected staged, unstaged, untracked, ignored-sensitive, generated, or concurrent change;
- any quality, syntax, test, analysis, style, platform, audit, encoding, link, secret, or file-safety failure;
- an unexpected Composer warning category;
- a need to edit content, change configuration, add a dependency, weaken a gate, bypass a hook, alter signing, or repair accepted work; or
- inability to prove the intended index and commit exactly.

Do not turn a verification finding into an opportunistic correction. A defect requires a separate architect-authorized correction directive.

---

# 2. LOCKED GIT OUTCOME

The only authorized final history outcome is:

```text
Branch: main
Only local branch: main
Commit count: 5
New commit parent: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
New commit parent count: 1
Commit subject: Establish Kongono framework baseline through Phase 0M
Tracked files: 206
Tracked modes: 206 x 100644
Commit delta paths: 49
Commit delta: 35 modified, 14 added, 0 deleted
Remotes: 0
Tags: 0
Push: prohibited
```

Use exactly:

```text
Establish Kongono framework baseline through Phase 0M
```

Do not add a body, prefix, issue reference, sign-off, co-author trailer, or AI attribution.

Do not use `--amend`, `--allow-empty`, `--no-verify`, `--no-gpg-sign`, `--author`, `--date`, or `--reset-author`.

Do not create, rename, switch, or delete a branch. Do not create a tag or remote. Do not push, merge, rebase, reset, cherry-pick, squash, or rewrite history.

Do not change local or global Git configuration, including identity, signing, hooks, safe-directory, file mode, line endings, ignore rules, or credentials. Report the effective author name and a redacted email. Missing identity is a blocker. If existing configuration requires signing, allow normal configured signing; if it fails, stop rather than bypassing it.

The new commit and tree hashes are outputs. Record them after Git creates them; do not invent them in advance.

---

# 3. ACCEPTED PRE-PHASE-0N BASELINE

Before the caller adds the two Phase 0N records, require exactly:

```text
Branch: main
Only local branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
HEAD parent: 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
HEAD parent count: 1
Commit count: 4
HEAD subject: Establish Kongono framework baseline through Phase 0J
Tracked files in HEAD: 192
Tracked modes in HEAD: 192 x 100644
Remotes: 0
Tags: 0
Staged paths: 0
Tracked worktree modifications: 35
Untracked paths: 13
Total status paths: 48
```

The tracked worktree diff is exactly:

```text
35 files changed
1265 insertions
214 deletions
```

There is no tracked deletion, rename, copy, type change, mode change, or staged path.

## Exact 35 accepted tracked modifications

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/testing.md
docs/glossary.md
packages/application-graph/README.md
packages/application-graph/src/Exception/ExceptionInterface.php
packages/application-graph/src/Exception/GraphException.php
packages/application-graph/src/Model/ApplicationGraph.php
packages/application-graph/src/Model/NodeKey.php
packages/application-graph/src/Model/NodeKind.php
packages/application-graph/src/Model/RelationKind.php
packages/application-graph/tests/Unit/ApplicationGraphTest.php
packages/application-graph/tests/Unit/StructuralValueTest.php
packages/dependency-injection/README.md
packages/dependency-injection/src/Definition/ServiceDefinition.php
packages/dependency-injection/src/DependencyResolver.php
packages/dependency-injection/src/Exception/DependencyInjectionException.php
packages/dependency-injection/src/Exception/ExceptionInterface.php
packages/dependency-injection/tests/Fixture/DualResolverService.php
packages/dependency-injection/tests/Unit/DependencyResolverTest.php
packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
packages/kernel/README.md
packages/kernel/src/Exception/ExceptionInterface.php
packages/kernel/src/Exception/KernelException.php
packages/kernel/src/Kernel.php
packages/kernel/tests/Unit/KernelTest.php
tests/Architecture/ApiStatusConventionTest.php
```

Compare the live name-status and numstat inventories to this closed list and to the exact totals above. A same-count substitution is still a mismatch.

---

# 4. EXACT 13 ACCEPTED UNTRACKED PATHS

These paths are accepted Phase 0L, Phase 0M, Phase 0M.1, or prior checkpoint records. Their bytes are immutable:

```text
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
  21,183 bytes
  SHA-256 B7AE4AF899E2402825CD24004CA72D617A580DEEFED8AB16208F306263CCF9AA

docs/adr/0027-package-local-runtime-diagnostic-metadata.md
  13,097 bytes
  SHA-256 692420EBE25E6F24AFEEB0C9FB50BD0C66CD8D12E34B08AFF95F3928177D57F7

docs/dev_plan/kongono_phase_0L_codex_directive.md
  39,127 bytes
  SHA-256 C7A52B4B08DD56595BC5F4A3893C18ADEEFB13F1AEF0F61D595AB23FC729555F

docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md
  13,670 bytes
  SHA-256 35CD4C1E870AAFC6031435B88562E015B8E9713C5C4D36A4A9CAFD70BC5D8BE2

docs/dev_plan/kongono_phase_0M_codex_directive.md
  42,507 bytes
  SHA-256 F67D90AA79F3849555DE06CABF6EC25C8C58C47C999402B2B9404BA98F546ED5

docs/engineering/error-handling.md
  23,311 bytes
  SHA-256 4BCE81A6184A58123C5C2CCEDBF218721D8DBB0FB244AEC0832D197302762E65

docs/task reports/Phase_0K.md
  18,034 bytes
  SHA-256 F6EA293C490872B598C9E9F1E0D7AA6B18D1F69A4C68E1434813FE55E8871F6F

docs/task reports/Phase_0L.md
  14,817 bytes
  SHA-256 01AD92F26D9FA5EBBF5F31F715E4D8B0AEEBCFAB39B5067CE09007747788B494

docs/task reports/Phase_0M.md
  2,611 bytes
  SHA-256 6AF1B0B6CA9B742A467AADE7B0CF4A16C1FA0B412CA1BC2F4F08EAB8161D2A1F

docs/task reports/Phase_0M_1_correction.md
  24,853 bytes
  SHA-256 9EC8B69D7B276A9818B12E879330AF21C12388E8F497315A7CAFE41FF06E4455

packages/application-graph/src/Diagnostic/GraphDiagnostic.php
  3,024 bytes
  SHA-256 98A4285EF897D24CD695CF2116CC59EEA279A5DE8A04C51C6AB698C5003CDB55

packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
  7,449 bytes
  SHA-256 F28C196A245BC8A3CE16A3467298128DD7F108193B2AAF6B28FB08B3A11294F3

packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
  2,226 bytes
  SHA-256 3E675D465FB5E1620B0E9252FBD2A8326B4E4211BC6A657AAA00FF279CCD0FD5
```

All 13 must be ordinary files, strict UTF-8 where textual, and remain byte-identical through staging and commit. `Phase_0M.md` remains the truthful blocked historical report. `Phase_0M_1_correction.md` remains the accepted completion report. Neither may be merged, rewritten, summarized, renamed, or removed.

---

# 5. PHASE 0N CALLER RECORDS

The caller will add exactly:

```text
docs/dev_plan/kongono_phase_0N_codex_directive.md
docs/task reports/Phase_0N.md
```

The directive must be a non-empty, byte-identical caller-supplied copy of this artifact. Read it completely, record its exact size and SHA-256, and never edit it. It is the only Phase 0N record authorized to enter the commit.

The report must be an exactly zero-byte, ordinary, caller-managed placeholder through all preflight, staging, commit creation, and post-commit validation. Do not treat it as authority, stage it, commit it, rename it, delete it, or write to it early. Only after every post-commit gate passes may the executor populate it with Section 14's completion report. It must remain untracked and outside `HEAD`.

If either path is absent, misnamed, non-ordinary, or has the wrong initial content state, stop.

After caller setup, require exactly:

```text
Tracked modifications: 35
Untracked paths: 15
Total status paths: 50
Staged paths: 0
Physical versioned-candidate paths: 207
Intended committed files: 206
Intended commit delta paths: 49
```

The 15 untracked paths are the 13 paths in Section 4, this directive, and the zero-byte Phase 0N report. No other path is authorized.

---

# 6. CONTENT IMMUTABILITY

Phase 0N authorizes only:

1. Git index writes needed to assemble the exact checkpoint;
2. one ordinary Git commit with the exact subject;
3. Git metadata created naturally by that commit; and
4. population of the Phase 0N report only after all post-commit gates pass.

Do not edit, create, delete, move, rename, normalize, format, regenerate, or otherwise rewrite repository content. The caller-supplied Phase 0N directive and zero-byte report are the only new worktree files.

Do not run Composer update/install, a formatter in write mode, code generation, documentation generation, an autofixer, or any dependency/configuration mutation. Read-only tools may update ignored caches; those caches must remain ignored, non-sensitive, and absent from status.

Do not repair even an obvious typo. Stop and request a correction directive.

---

# 7. REQUIRED PRE-STAGE VERIFICATION

Run and record the commands and outputs needed to establish:

```powershell
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
git log --reverse --format="%H|%T|%P|%s"
git ls-tree -r HEAD
git remote
git tag
git status --short --untracked-files=all
git status --porcelain=v1 --untracked-files=all
git diff --check
git diff --name-status
git diff --stat
git diff --numstat
git diff --cached --exit-code
git diff --cached --name-status
git ls-files
git ls-files --stage
git config --get user.name
git config --get user.email
```

Redact the email in the report while confirming it is configured. Do not change identity or configuration.

## Historical commit chain

Prove these existing objects and relationships remain exact:

```text
136822352a0caac199506cbddb4c9a539001c592
  tree 88d0f199e95e6891739df5c4a4d7cc139ab12cae
  zero parents
  Establish Kongono framework baseline through Phase 0D

29235c6a943dd9f3e96bae7b85ff4bea6a26535c
  tree 58623c654c8febad5e299f7c5cf8c30ffe4ee95f
  parent 136822352a0caac199506cbddb4c9a539001c592
  Establish Kongono framework baseline through Phase 0F

7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
  tree 9d3326fae7f7e7b8c75f3309af0cb5b5a6f81e8d
  parent 29235c6a943dd9f3e96bae7b85ff4bea6a26535c
  Establish Kongono framework baseline through Phase 0H

5027939d3de2dd508c74ae6007b9dc5bb524e3a8
  tree 432a0121a87b225de44e9daa301602be79ebd94c
  parent 7607e7b1844f65cc4aa6e9bcb2579ea30235f4ce
  Establish Kongono framework baseline through Phase 0J
```

## Content and quality proof

Before staging:

1. verify exact size and SHA-256 of all 13 Section 4 paths and the caller Phase 0N directive;
2. verify the Phase 0N report is exactly zero bytes;
3. verify the 35-path tracked diff and its exact totals;
4. syntax-check exactly 93 first-party PHP/configuration files;
5. run the focused suites:
   - Kernel: 17 tests, 84 assertions;
   - Application Graph: 48 tests, 273 assertions;
   - Dependency Injection: 62 tests, 473 assertions;
   - API-status architecture: 6 tests, 267 assertions;
6. run `composer analyse` and require zero errors;
7. run `composer quality` unchanged and require the accepted `134 tests, 1099 assertions`, with no reduced coverage or missing test;
8. require PHP CS Fixer to find zero fixable files across its 92-file inventory;
9. require zero locked dependency advisories and successful platform checks;
10. accept only existing missing-license and authorized local `@dev` or `@dev/*` Composer warning categories;
11. verify all current local Markdown links resolve under the established historical-record exception policy;
12. verify every status path is strict UTF-8 where textual, without BOM, NUL, replacement characters, mojibake, conflict markers, trailing whitespace, CRLF, or missing final newline; and
13. rerun status and hashes after validation to prove tools changed no versioned candidate.

If dependency audit fails only because the sandbox cannot reach its configured advisory source, rerun the identical fail-closed command with authorized network access. Do not change policy, ignore unreachable advisories, substitute stale evidence, or claim a pass from a network failure.

---

# 8. SECRET AND FILE-SAFETY GATE

Before staging and again against the exact index:

- enumerate every intended file and verify it is ordinary;
- reject symbolic links, junctions, Gitlinks, submodules, devices, sockets, executables, alternate data streams, and non-`100644` intended modes;
- reject conflict artifacts, editor backups, caches, temporary files, archives, binaries, generated reports outside authorized records, and OS metadata;
- inspect names and content for credentials, tokens, private keys, cookies, authorization values, connection strings, secrets, environment dumps, and other sensitive material;
- verify `.env`, vendor/cache content, credentials, and local configuration are absent from the intended index; and
- distinguish examples and placeholders from live values without printing suspected secrets into the report.

Any uncertain sensitive value is a blocker. Do not redact or edit content during this checkpoint.

The intended committed tree must contain exactly 206 ordinary `100644` files.

---

# 9. EXACT STAGING CONTRACT

Do not use `git add .`, `git add -A`, a wildcard, interactive staging, or any command capable of absorbing an unexpected path.

First stage exactly the 35 tracked modifications:

```powershell
git add -u -- .
```

Immediately verify this staged exactly the Section 3 list, with no deletion or other path. Then add exactly these 14 untracked files using literal paths:

```powershell
git add -- "docs/adr/0026-actionable-diagnostics-and-error-boundaries.md"
git add -- "docs/adr/0027-package-local-runtime-diagnostic-metadata.md"
git add -- "docs/dev_plan/kongono_phase_0L_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_codex_directive.md"
git add -- "docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md"
git add -- "docs/dev_plan/kongono_phase_0N_codex_directive.md"
git add -- "docs/engineering/error-handling.md"
git add -- "docs/task reports/Phase_0K.md"
git add -- "docs/task reports/Phase_0L.md"
git add -- "docs/task reports/Phase_0M.md"
git add -- "docs/task reports/Phase_0M_1_correction.md"
git add -- "packages/application-graph/src/Diagnostic/GraphDiagnostic.php"
git add -- "packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php"
git add -- "packages/kernel/src/Diagnostic/LifecycleDiagnostic.php"
```

Never stage, populate, modify, delete, rename, or move:

```text
docs/task reports/Phase_0N.md
```

After staging, require exactly:

```text
49 staged paths
  35 modified
  14 added
   0 deleted
   0 renamed, copied, or type-changed

206 index entries
206 ordinary 100644 files

0 unstaged tracked paths
1 untracked path
  docs/task reports/Phase_0N.md
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

The cached name-status inventory must match the closed 49-path set exactly. Compare staged blob hashes and content SHA-256 values with pre-stage evidence; staging must not transform any byte. Record the staged tree hash.

Any other untracked, unstaged, staged, ignored-sensitive, or unexpected path is a blocker.

---

# 10. FINAL PRE-COMMIT GATE

With the exact index assembled and before committing:

1. rerun `composer quality` unchanged;
2. rerun all four focused suites from Section 7;
3. repeat the 93-file PHP syntax validation;
4. repeat secret, file-safety, text-integrity, and local-link gates against staged content;
5. repeat every staged-state check from Section 9;
6. verify the staged tree contains exactly 206 ordinary `100644` entries;
7. verify the staged Phase 0N directive is byte-identical to the caller copy;
8. verify the Phase 0N report is still unstaged, untracked, ordinary, and zero-byte;
9. reverify branch, HEAD, tree, parent, subject, commit count, branch count, remote count, and tag count against Section 3;
10. verify the 42 diagnostic identifiers reconcile exactly as 6 Kernel, 11 Graph, and 25 DI identifiers across the directive, ADRs, catalogs, tests, and engineering guide; and
11. verify no hook, formatter, test, cache, or concurrent process changed an intended byte.

The final pass must not contain fewer tests or assertions, reduced inventories, weakened policies, new suppressions, baselines, exclusions, or unexpected warning categories.

Do not commit if any gate is not exact.

---

# 11. EXACT COMMIT OPERATION

Create one ordinary, non-merge commit from the verified index using exactly:

```text
Establish Kongono framework baseline through Phase 0M
```

Use a non-interactive commit command that supplies only that subject. Do not amend, squash, rebase, reset, cherry-pick, merge, tag, bypass hooks, disable signing policy, or add a message body.

The new commit must:

```text
be on main
be the fifth commit reachable from main
have exactly one parent
have parent 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
contain exactly 206 files
contain only ordinary 100644 entries
change exactly 49 paths from its parent
record 35 modifications and 14 additions
record no deletion, rename, copy, type change, Gitlink, or executable mode
use the exact subject Establish Kongono framework baseline through Phase 0M
```

If identity, signing, hooks, concurrency, or any condition prevents this exact operation, stop and report `Blocked`. Do not change configuration or improvise another history operation.

---

# 12. REQUIRED POST-COMMIT VERIFICATION

Immediately after the commit and before populating the report, prove:

```text
branch main
only local branch main
commit count 5
HEAD subject exactly Establish Kongono framework baseline through Phase 0M
HEAD has exactly one parent
HEAD parent exactly 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree equals the pre-commit staged tree
HEAD contains exactly 206 ordinary 100644 files
parent delta is exactly 49 paths: 35 modified and 14 added
index is clean
tracked worktree is clean
only docs/task reports/Phase_0N.md is untracked
Phase_0N.md is still exactly zero bytes
remotes remain 0
tags remain 0
all four earlier commit and tree objects remain unchanged
```

Run and record at least:

```powershell
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-parse HEAD^
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log --reverse --format="%H|%T|%P|%s"
git show --format=fuller --stat --summary HEAD
git diff-tree --root --no-commit-id --name-status -r HEAD
git diff-tree --root --no-commit-id --numstat -r HEAD
git ls-tree -r HEAD
git status --short --untracked-files=all
git diff --exit-code
git diff --cached --exit-code
git remote
git tag
```

Then rerun the 93-file syntax gate, all four focused suites, `composer analyse`, `composer quality`, local Markdown-link resolution, committed text-integrity validation, committed secret/file-safety inspection, and the 42-identifier reconciliation.

All must pass from committed `HEAD`. Do not populate the report from a self-asserted commit.

If a post-commit check fails, preserve the commit and evidence. Do not reset, amend, revert, or make a corrective commit. Populate a truthful `Blocked` report describing the exact state and required architect decision.

Only after every post-commit check passes may you populate `docs/task reports/Phase_0N.md`. After its final write, verify it is strict UTF-8 without BOM, LF-only, final-newline terminated, nonzero, ordinary, untracked, unstaged, and absent from `HEAD`. Record its final byte count and SHA-256 in the accompanying executor response; the report itself must explain that embedding its own final digest would change that digest.

---

# 13. PROHIBITED SCOPE

Phase 0N prohibits all content and architecture changes, including:

- production, test, fixture, documentation, ADR, manifest, lockfile, tool, quality-policy, ignore, or repository-policy edits;
- a shared diagnostics package or universal exception/diagnostic interface;
- structured diagnostic context, redaction types, context bags, source readers, registries, factories, or attributes;
- a development error page, HTML/JSON/Markdown renderer, production renderer, or presentation API;
- HTTP routing, problem mapping, request/response handling, middleware, CLI output, or exit-code mapping;
- logging, telemetry, OpenTelemetry, tracing, metrics, incident storage, exporters, or remote calls;
- retry, cancellation, cleanup orchestration, recovery, worker supervision, or runtime adapters;
- DI/container expansion, service discovery, factories, scopes, caching, autowiring, service-location, or Kernel integration;
- Application Graph schema, stable Graph IDs, serialization, persistence, traversal, query DSL, indexing, compilation, mutation, or discovery;
- ORM, database, events, workflows, authorization, agents, extensions, frontend, Vite, localization, packaging, releases, licensing, CI, or deployment;
- dependencies, Composer plugins, suppressions, baselines, ignored findings, exclusions, warning allowances, or weakened gates; and
- pushes, remotes, tags, branches, merges, rebases, resets, amendments, or history rewrites.

This checkpoint does not decide the next error-experience mechanism. It only preserves the accepted foundation from which that decision can be made.

---

# 14. STANDARD EXECUTOR COMPLETION REPORT

Populate `docs/task reports/Phase_0N.md` only after Section 12 completes. The report must contain exactly one outer `text` fence, no text outside it, and exactly these 22 numbered top-level sections in order:

```text
1. STATUS
2. SUMMARY
3. BASELINE VERIFIED
4. CALLER RECORDS
5. FILES CREATED
6. FILES MODIFIED
7. ARCHITECTURAL DECISIONS
8. DEVIATIONS FROM DIRECTIVE
9. DEPENDENCIES ADDED/REMOVED
10. ENVIRONMENT
11. COMMANDS RUN
12. PRE-STAGE VALIDATION RESULTS
13. STAGING EVIDENCE
14. COMMIT EVIDENCE
15. POST-COMMIT VALIDATION RESULTS
16. DOCUMENTATION REVIEW
17. SECURITY AND FILE-SAFETY REVIEW
18. WARNINGS / KNOWN ISSUES
19. QUESTIONS / BLOCKERS
20. FINAL GIT STATUS
21. ACCEPTANCE CHECKLIST
22. NEXT STEP
```

For a completed run:

- Section 1 says `Complete`.
- Section 2 states that Phase 0N made no content edit and created only the authorized Git commit, followed later by report population.
- Sections 5 and 6 distinguish caller-created files from executor content edits; the executor created or modified no committed content file.
- Sections 7, 8, 9, and 19 say `None` when true.
- Section 18 lists only truthful accepted warnings or residual risks.
- Section 22 contains exactly:

```text
Await architect review before Phase 0O.
```

Mandatory evidence includes:

- all environment tool versions;
- exact baseline branch, refs, commit/tree/parent/subject, counts, modes, status, diff, and caller-record facts;
- exact sizes and SHA-256 for the 13 accepted untracked records and Phase 0N directive;
- pre-stage, staged, committed, and final path inventories;
- exact staged tree, new commit, new tree, parent, subject, and commit count;
- proof of 206 committed `100644` files and a 49-path delta of 35 modifications and 14 additions;
- proof all four prior commits and trees are unchanged;
- exact syntax, focused-suite, PHPStan, style, audit, platform, and canonical quality results, including test/assertion counts and warning text;
- link, encoding, whitespace, catalog, secret, and file-safety results;
- confirmation no production or documentation content was edited during Phase 0N;
- confirmation the only executor-created repository history object was the authorized commit;
- exact final status showing only the populated Phase 0N report untracked;
- proof the report is not staged, tracked, or in `HEAD`;
- every failed command and its exact safe resolution;
- a line-by-line PASS/FAIL reproduction of every Section 15 criterion; and
- the final report byte count and SHA-256 in the accompanying response after its last write, with the report noting the self-digest limitation.

If the run blocks before commit, preserve the original worktree/index and report the observed state. If it blocks after commit, preserve the commit and report the observed state without destructive rollback.

Before returning, self-check the report for its one-fence structure, exact 22-section order, strict UTF-8, LF endings, final newline, no BOM, NUL, replacement character, mojibake, conflict marker, or trailing whitespace.

---

# 15. STRICT ACCEPTANCE CRITERIA

Every item must pass:

## Repository identity and safety

- [ ] Preflight matches the exact Section 3 branch, commit, tree, parent, subject, counts, modes, remotes, tags, and history.
- [ ] The exact 35 tracked modifications and 13 accepted untracked paths match.
- [ ] Every Section 4 size and SHA-256 matches.
- [ ] The Phase 0N directive is the exact caller artifact and the report begins as an ordinary zero-byte placeholder.
- [ ] No unexpected path, sensitive file, link defect, text defect, mode, or content change exists.

## Accepted implementation quality

- [ ] All 93 first-party PHP/configuration syntax checks pass.
- [ ] Kernel passes with 17 tests and 84 assertions.
- [ ] Application Graph passes with 48 tests and 273 assertions.
- [ ] Dependency Injection passes with 62 tests and 473 assertions.
- [ ] API-status architecture passes with 6 tests and 267 assertions.
- [ ] PHPStan passes with zero errors.
- [ ] PHP CS Fixer reports zero fixable files across 92 files.
- [ ] Canonical quality passes with at least 134 tests and 1099 assertions.
- [ ] Locked audit and platform checks pass without policy weakening.
- [ ] Composer warnings remain limited to authorized categories.
- [ ] All 42 package-owned identifiers and mappings remain exact and reconciled.

## Index and commit

- [ ] Exactly 49 paths are staged: 35 modified and 14 added.
- [ ] No deletion, rename, copy, type change, mode change, Gitlink, executable, or unstaged tracked path exists.
- [ ] The index contains exactly 206 ordinary `100644` files.
- [ ] Only the zero-byte Phase 0N report remains untracked before commit.
- [ ] The final pre-commit gate passes and the staged tree hash is recorded.
- [ ] Exactly one ordinary commit is created on `main` with the exact subject.
- [ ] The commit has the exact parent and the fifth reachable-commit position.
- [ ] The commit tree equals the staged tree and contains exactly 206 files.
- [ ] The parent delta is exactly 49 paths: 35 modified and 14 added.
- [ ] Earlier commits and trees remain unchanged; no branch, remote, tag, push, rewrite, bypass, or configuration change occurs.

## Final evidence

- [ ] All committed-state validation passes.
- [ ] The only final status path is the populated, untracked, unstaged Phase 0N report, which is absent from `HEAD`.
- [ ] The report has exactly the required structure and text integrity.
- [ ] The accompanying response records the report's final size and SHA-256.
- [ ] Section 22 is exactly `Await architect review before Phase 0O.`

Any failure makes status `Blocked` or `Partial`, never `Complete`.

---

# 16. FINAL EXECUTOR INSTRUCTION

Act only as the Phase 0N checkpoint executor. Verify the accepted Phase 0M.1 repository, stage the exact authorized 49-path delta, create exactly one local commit with the locked subject, validate it completely, and leave only the populated Phase 0N report untracked.

Do not change framework content. Do not begin Phase 0O. Do not push, tag, branch, rewrite history, bypass policy, or absorb the report into the commit.

If any path, byte, hash, count, mode, quality result, warning category, security result, caller record, or Git fact differs from this directive, stop and return a truthful blocked report rather than guessing or fixing it.

