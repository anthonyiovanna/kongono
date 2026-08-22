# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1I

## Immutable Local Checkpoint Through Phase 1H

**Directive type:** exact repository checkpoint; no framework implementation

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Create one immutable local Git checkpoint containing exactly the accepted Kongono work through Phase 1H.

This phase converts the accepted 36-path worktree boundary left after Phase 1H into the ninth local commit. It changes no accepted content byte before staging and makes no new architectural decision.

The exact commit subject is:

```text
Establish Kongono trusted diagnostic handling through Phase 1H
```

The checkpoint contains exactly:

- 22 currently modified tracked paths; and
- 14 currently untracked accepted paths.

The Phase 1I directive and Phase 1I completion report are caller records. They remain outside the commit.

### 1.1 Required outcome

After successful completion:

- `main` has exactly nine commits;
- the new commit has exactly one parent, the accepted Phase 1F checkpoint;
- the new commit changes exactly 36 paths: 22 modified and 14 added;
- the commit delta is exactly 6,167 insertions and 104 deletions;
- the new tree contains exactly 358 ordinary `100644` files;
- all accepted Phase 1H bytes are preserved exactly;
- canonical quality passes with 254 tests and 4,279 assertions;
- the working tree contains only the untracked Phase 1I directive and populated Phase 1I report;
- the index contains no staged change; and
- no remote, tag, extra branch, signing requirement, or Phase 1J work is introduced.

---

## 2. Governing authority and mandatory stop rule

### 2.1 Authority order

Read completely, in this order, before any staging or repository mutation:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. every Accepted ADR in `docs/adr/`
6. the Phase 1F, Phase 1G, Phase 1G.1, and Phase 1H directives named in Section 4.3
7. their corresponding completion reports named in Section 4.3
8. this Phase 1I directive
9. the zero-byte `docs/task reports/Phase_1I.md` placeholder

Earlier documents remain historical evidence. This directive controls only the checkpoint procedure for Phase 1I. It reopens no architectural decision and does not authorize implementation.

### 2.2 Independent directive identity

The architect will supply the byte count and uppercase SHA-256 of this directive outside this file. Compare those independently supplied values with:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
```

Do not derive the expected identity from the local file. The local file is not self-authenticating.

The report placeholder must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_1I.md
```

Its initial SHA-256 must be:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.3 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, changed byte, failed gate, unauthorized warning, or prohibited side effect:

1. stop immediately;
2. preserve the repository and index in the state required by the failed gate;
3. do not repair, normalize, truncate, restage, unstage, reset, amend, retry, or widen scope unless this directive expressly authorizes that exact action;
4. leave the Phase 1I report zero bytes unless Section 13 authorizes its population; and
5. report the exact observation, expected value, viable options, and a recommendation.

Do not guess. Do not silently improve adjacent work. Do not treat a locally observed value as architect approval.

### 2.4 Environment-only retry rule

One unchanged retry is allowed only when a required command is blocked solely by the managed environment, including:

- Composer advisory, network, or cache access;
- write access to `.git/index.lock` for the exact authorized staging command;
- write access required by `git write-tree`; or
- write access required by the exact authorized commit command.

The retry must use the identical semantic command, closed path set, and commit message. It may request approved host access. It must not alter configuration, dependencies, source files, the staging inventory, or history.

A functional failure is not an environment-only failure and must not be retried under this rule.

---

## 3. Locked starting repository state

Before the caller adds the two Phase 1I records, the accepted repository state is exactly:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `8fe793b124384744ac43887037360154c149241a` |
| `HEAD^{tree}` | `9de9388e85e463639f8d7fbbc90ae085f947a1b0` |
| `HEAD^` | `837863d11feda282e4959ef7ec98a810c184531e` |
| Commit count | `8` |
| Remotes | `0` |
| Tags | `0` |
| Tracked files | `344` |
| Stage-zero index entries | `344` |
| Higher-stage index entries | `0` |
| Tracked file modes | all `100644` |
| Modified tracked paths | `22` |
| Untracked paths with `-uall` | `14` |
| Total status paths with `-uall` | `36` |
| Staged paths | `0` |
| Unstaged tracked paths | `22` |
| Tracked diff | `22 files, 467 insertions, 104 deletions` |
| Package directories | `8` |
| Package manifests | `8` |
| Composer manifests including root | `9` |
| Production PHP files | `53` |
| Test PHP files | `85` |
| PHP CS Fixer-scoped files | `138` |
| Production declarations | `53` |
| Explicit `Api` attributes | `51` |

Use `git status --porcelain=v1 -uall` or an equivalent exact file inventory. Do not rely on the collapsed directory form of ordinary `git status --short` when counting untracked files.

The accepted `composer.lock` identity is:

```text
bytes: 182894
SHA-256: 804B276750164A634D25ED97A613D9B51FC1E59F9D170A9D345C733299B3498B
```

### 3.1 Expected state after caller setup

After the caller copies this directive to `docs/dev_plan/` and creates the required zero-byte report placeholder, but before staging, the repository must be exactly:

| Property | Required value |
|---|---:|
| Modified tracked paths | `22` |
| Untracked paths with `-uall` | `16` |
| Total status paths with `-uall` | `38` |
| Staged paths | `0` |
| Unstaged tracked paths | `22` |

The two additional untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
```

No other starting-state difference is authorized.

---

## 4. Exact checkpoint inventory

The checkpoint inventory is closed. Stage no path outside this section and omit no path inside it. All paths are repository-relative and use `/` separators for comparison.

### 4.1 Modified tracked paths - exactly 22

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostic-activation/README.md
packages/diagnostic-presentation/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

### 4.2 Added paths - exactly 14

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/dev_plan/kongono_phase_1H_codex_directive.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
docs/task reports/Phase_1G_1_correction.md
docs/task reports/Phase_1H.md
packages/diagnostic-handler/README.md
packages/diagnostic-handler/composer.json
packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php
packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php
packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php
```

### 4.3 Immutable accepted caller records - exactly 8

These records are part of the 14 added paths. Verify every byte before staging.

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1F_codex_directive.md` | 32834 | `433EE2ECCD65B24C9CDCE8C3616302695F46359FA59958DA857F716177C4A0ED` |
| `docs/task reports/Phase_1F.md` | 15619 | `A5D0AE6355276FD7E53077ED2048E2BB6A84CAF56D21B6A82B77DC6493C82F01` |
| `docs/dev_plan/kongono_phase_1G_codex_directive.md` | 35789 | `58E9408FDA8734507AC49564D8AEAAFC65EDDA9F99A211468523C4BA2CA2838A` |
| `docs/task reports/Phase_1G.md` | 16449 | `813433CE270B1963E95112D44CB098CE55A039F8F22828157451F1FC930AD21A` |
| `docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md` | 16441 | `6ADCDBD2DF4CC207ACE34214A89E4D19CACA224D9E03B6083326F55E3AA3E7A9` |
| `docs/task reports/Phase_1G_1_correction.md` | 15444 | `4C3320F22D603EF84AAAFDD1C7FCF23F161314894590FAF735CADF68F29F54CF` |
| `docs/dev_plan/kongono_phase_1H_codex_directive.md` | 39506 | `7E0C7ACDE57DFB4E503286F97B64A54A49C5FC2B52AB45696D1C6884AE4509CB` |
| `docs/task reports/Phase_1H.md` | 21971 | `DA8223B65DE81249E5A6AA8B2EE3D6E0720F70F0103615F3EF88500A0EC89F18` |

### 4.4 Immutable accepted implementation additions - exactly 6

These are the non-caller-record added paths. Verify every byte before staging.

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md` | 13629 | `6ED06DDB3788B7345640CF1B5BF962C9F75CBCEEC3FB7B7B15EBF98188F08975` |
| `packages/diagnostic-handler/README.md` | 5170 | `A4E8A75C02D3477A76C2174718608B8D74817DD7BB054AAD6231B862C0547D59` |
| `packages/diagnostic-handler/composer.json` | 622 | `5C5FFAF9682019ED95DEDDDE770459CE5B7055ED10B7673A9CBF8EF8DA6997F5` |
| `packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php` | 1608 | `BAE619B2B5278707186569257C5A717B8A44887B732775236FB25570859C3C5C` |
| `packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php` | 848 | `EBD57E2250FAD4208B6AA5232D6A605FC5E31A5406646F87F26645C45C9994BE` |
| `packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php` | 9214 | `FEA5B804CB0CDE889CB51BA88A94B521758F2669E1AC4E5C10E59D15C489A8EA` |

---

## 5. Deterministic checkpoint manifest

Before staging, construct the manifest over all 36 Section 4 paths using this exact algorithm:

1. normalize each repository-relative path to `/` separators;
2. sort paths using ordinal bytewise order;
3. read the primary file bytes without text normalization;
4. calculate uppercase SHA-256 over the primary bytes;
5. append one UTF-8, no-BOM record per path:

```text
<path><NUL><decimal byte length><NUL><uppercase SHA-256><LF>
```

6. concatenate the 36 records without an extra record or BOM; and
7. hash that manifest byte sequence with SHA-256.

Required result:

```text
entries: 36
manifest bytes: 4012
manifest SHA-256: 2E9CE853DFEB80D5F2658ABD80D8AF7CD9A92AFB34D5838D71900FD1C0DCDDCB
```

Do not create a repository file for this manifest. It is validation evidence only. It proves current primary-file bytes but does not replace Git status, index, mode, stream, or quality proofs.

---

## 6. Prohibited scope

Phase 1I must not:

- edit, reformat, normalize, repair, truncate, append to, or regenerate any checkpoint path;
- edit or stage the Phase 1I directive;
- populate the Phase 1I report before all non-report post-commit gates pass;
- stage any path outside Section 4;
- use `git add .`, `git add -A`, a wildcard, a directory-wide add, or an array inferred from live status;
- run a second staging command after the exact Section 9 command succeeds;
- unstage, reset, restore, checkout, clean, stash, amend, rebase, cherry-pick, merge, squash, filter, or rewrite history;
- change `.gitignore`, attributes, line endings, modes, file timestamps intentionally, or alternate data streams;
- add a remote, tag, branch, hook, signing configuration, or Git configuration;
- create a signed commit;
- add, remove, update, or reinstall any Composer dependency;
- regenerate `composer.lock` or run `composer update`;
- implement an outer adapter, evidence evaluator, bootstrap loader, authorization mechanism, HTTP, routing, middleware, request/response type, transport delivery, CLI, ORM, database access, agents, extensions, frontend, telemetry, serialization, Application Graph expansion, production diagnostic model, logging, incidents, localization, or any later capability;
- alter public, Internal, or Experimental API status;
- weaken security, diagnostic disclosure, lifecycle, persistent-runtime, or no-assumption doctrine;
- create a release, version, package publication, pull request, or push; or
- treat raw `.git/index` byte length, raw index SHA-256, or cache-tree metadata as an acceptance contract.

This phase authorizes only:

1. read-only validation;
2. exact staging of the 36 paths;
3. semantic staged-index and tree proof;
4. one exact local commit;
5. post-commit validation; and
6. population of the untracked Phase 1I report after success.

---

## 7. Gate A - authority, identity, and baseline

Before broad validation or mutation, prove all of the following:

1. all Section 2.1 authorities were read completely;
2. the independently supplied directive byte count and SHA-256 match the local Phase 1I directive;
3. the Phase 1I report is the required zero-byte ordinary file;
4. every Section 4.3 caller record matches its locked bytes and SHA-256;
5. every Section 4.4 implementation addition matches its locked bytes and SHA-256;
6. all Section 3 Git identities, counts, and exact `-uall` inventories match after accounting for the two Phase 1I caller records;
7. the index contains exactly 344 stage-zero entries, no higher-stage entry, and no staged change;
8. the 22 modified tracked paths and 14 pre-existing untracked paths exactly match Section 4;
9. the two Phase 1I caller records are the only additional untracked paths;
10. all 38 current status paths are ordinary files, with no reparse point or non-default alternate data stream;
11. tracked modes remain `100644`;
12. `composer.lock` matches its locked identity;
13. the 36-entry manifest matches Section 5; and
14. no accepted byte has changed since the Phase 1H report was reviewed.

If any item fails, stop before broad quality validation and before staging.

---

## 8. Gate B - pre-stage quality and safety

With the index still empty and every worktree byte unchanged, run:

```powershell
composer quality
```

The required result is:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Accepted Composer warning categories remain only the repository's established missing-license and unbounded local development-constraint warnings, plus the optional missing-curl performance warning if actually emitted. Report exact warnings. Any unexpected warning category is a stop condition.

Also verify before staging:

- all local Markdown links resolve under the repository's accepted link rules;
- UTF-8/LF, exactly one final LF for non-empty text, trailing-whitespace, NUL, ordinary-file, reparse-point, and alternate-stream gates pass for the checkpoint scope;
- no secret, credential, token, private key, signing material, or new machine-specific absolute path exists in accepted repository content;
- the manifest, caller-record identities, implementation-addition identities, Git history, status inventories, and lockfile remain unchanged after quality validation;
- `git diff --check` passes for the 22 modified tracked paths; and
- the complete intended checkpoint delta remains exactly 36 paths, 6,167 insertions, and 104 deletions.

Do not alter content to make a gate pass.

---

## 9. Gate C - exact staging operation

Stage exactly the following literal array. Do not build this array from status output, a glob, a directory enumeration, or an untrusted file.

```powershell
$checkpointPaths = @(
    '.php-cs-fixer.dist.php'
    'ARCHITECTURE.md'
    'README.md'
    'SECURITY.md'
    'composer.json'
    'composer.lock'
    'docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md'
    'docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md'
    'docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md'
    'docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md'
    'docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md'
    'docs/adr/README.md'
    'docs/architecture/overview.md'
    'docs/architecture/runtime-model.md'
    'docs/dev_plan/kongono_phase_1F_codex_directive.md'
    'docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md'
    'docs/dev_plan/kongono_phase_1G_codex_directive.md'
    'docs/dev_plan/kongono_phase_1H_codex_directive.md'
    'docs/engineering/api-status.md'
    'docs/engineering/error-handling.md'
    'docs/engineering/package-layout.md'
    'docs/glossary.md'
    'docs/task reports/Phase_1F.md'
    'docs/task reports/Phase_1G.md'
    'docs/task reports/Phase_1G_1_correction.md'
    'docs/task reports/Phase_1H.md'
    'packages/diagnostic-activation/README.md'
    'packages/diagnostic-handler/README.md'
    'packages/diagnostic-handler/composer.json'
    'packages/diagnostic-handler/src/TrustedDeveloperDiagnosticHtmlPresenter.php'
    'packages/diagnostic-handler/tests/Fixture/ExplodingKernelDiagnosticException.php'
    'packages/diagnostic-handler/tests/Unit/TrustedDeveloperDiagnosticHtmlPresenterTest.php'
    'packages/diagnostic-presentation/README.md'
    'phpstan.neon.dist'
    'phpunit.xml.dist'
    'tests/Architecture/ApiStatusConventionTest.php'
)

git -c safe.directory='C:/Users/antho/Dev/kongono' add -- $checkpointPaths
```

Before executing `git add`, prove the literal array contains exactly 36 unique paths and its set exactly equals Section 4.

If the exact add is blocked solely by repository metadata permissions, Section 2.4 permits one unchanged approved-access retry.

After a successful add, do not run another staging command.

---

## 10. Gate D - semantic staged-index proof

After staging and before `git write-tree` or commit, prove:

| Property | Required value |
|---|---:|
| Staged paths | `36` |
| Staged modified paths | `22` |
| Staged added paths | `14` |
| Staged deleted paths | `0` |
| Staged renames/copies | `0` |
| Staged insertions | `6167` |
| Staged deletions | `104` |
| Unstaged tracked paths | `0` |
| Untracked paths with `-uall` | `2` |
| Stage-zero index entries | `358` |
| Higher-stage index entries | `0` |
| Index modes | all `100644` |

The two untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
```

For every staged path, prove:

- the staged blob bytes equal the current worktree primary bytes;
- the staged path and status class match Section 4;
- the staged mode is `100644`;
- no symlink, submodule, reparse point, or alternate stream is involved; and
- the combined staged primary bytes reproduce the Section 5 manifest.

Run:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' diff --cached --check
```

It must exit successfully with no finding.

Then run:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' write-tree
```

Record the returned tree object ID. Do not predict it in advance.

`git write-tree` may update internal cache-tree metadata. Raw `.git/index` byte length and SHA-256 are deliberately not acceptance criteria. Do not stop solely because raw index metadata changes. After `write-tree`, re-prove the exact semantic entries, stages, blob object IDs, modes, statuses, and worktree equality.

If a semantic index entry changes, stop and preserve the index. If only uncontracted cache-tree metadata changes while semantic entries remain exact, continue.

---

## 11. Gate E - exact local commit

Only after Gates A-D pass, create exactly one commit:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' commit --no-gpg-sign -m "Establish Kongono trusted diagnostic handling through Phase 1H"
```

Do not amend. Do not add a body. Do not change author or committer configuration. Do not sign. Do not stage anything else. Do not create a second commit.

Before committing, verify effective author configuration exists without printing a full private email address in the report. Record the author name and redact the email address.

If the exact commit is blocked solely by repository metadata permissions, Section 2.4 permits one unchanged approved-access retry after re-proving all Gate D semantic conditions.

If a hook unexpectedly runs, signing is unexpectedly requested, the message differs, or the created commit does not satisfy Section 12, stop. Do not amend or reset.

---

## 12. Gate F - post-commit proof

Before populating the report, prove all of the following.

### 12.1 Commit identity and history

- current branch is `main`;
- `main` is the only local branch;
- new `HEAD` differs from the locked starting `HEAD`;
- `HEAD^` is exactly `8fe793b124384744ac43887037360154c149241a`;
- `HEAD` has exactly one parent;
- the repository has exactly nine commits;
- the commit subject is exactly `Establish Kongono trusted diagnostic handling through Phase 1H`;
- the commit is unsigned;
- `HEAD^{tree}` equals the tree returned by the authorized `git write-tree`;
- there are no remotes and no tags; and
- all prior commits and trees are unchanged.

Record the new commit ID and tree ID as observed results. Neither is pre-authorized by a predicted value.

### 12.2 Exact commit delta

Relative to its parent, the new commit must contain exactly:

```text
36 changed paths
22 modified paths
14 added paths
0 deleted paths
0 renamed paths
0 copied paths
6167 insertions
104 deletions
```

The path/status set must exactly equal Section 4. The commit must not contain either Phase 1I caller record.

### 12.3 Exact tree

The new tree must contain:

```text
358 files
358 stage-zero semantic entries in the current worktree index
all file modes 100644
0 symlinks
0 submodules
```

Every committed checkpoint path must equal the current worktree primary bytes. The committed forms of all Section 4.3 and 4.4 paths must match their locked identities. The 36 committed paths must reproduce the Section 5 manifest.

### 12.4 Repository status before report population

Before the report is populated:

```text
modified tracked paths: 0
staged paths: 0
untracked paths with -uall: 2
total status paths with -uall: 2
```

The two paths must be the Phase 1I directive and the still-zero-byte Phase 1I report.

### 12.5 Post-commit quality

Run:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass on PHP 8.5.9
PHP CS Fixer: 0 of 138 files fixable
PHPStan: 0 errors
PHPUnit: 254 tests, 4279 assertions
```

Recheck local links, UTF-8/LF, trailing whitespace, NUL bytes, alternate streams, reparse points, ordinary-file status, secrets, modes, manifest identity, lockfile identity, caller-record identities, implementation-addition identities, API-status counts, and dependency topology.

Expected repository counts are:

```text
package directories: 8
package manifests: 8
Composer manifests including root: 9
production PHP files: 53
test PHP files: 85
PHP CS Fixer-scoped files: 138
production declarations: 53
explicit Api attributes: 51
```

The package graph must remain acyclic. Diagnostic Handler remains the only package depending on both Diagnostic Activation and Diagnostic Presentation, has no direct Diagnostics dependency, and remains an unconsumed leaf package.

If any post-commit gate fails, do not amend, reset, or create a repair commit. Leave the Phase 1I report zero bytes and report the blocker outside it.

---

## 13. Gate G - completion report and final state

Populate `docs/task reports/Phase_1I.md` only after every non-report criterion in Sections 7-12 passes.

The report remains:

- untracked;
- unstaged;
- absent from `HEAD`;
- an ordinary UTF-8/LF Markdown file;
- free of trailing whitespace and NUL bytes;
- terminated by exactly one final LF; and
- outside the checkpoint commit.

After report population, validate the report and return its final byte count and uppercase SHA-256 outside the report body.

Final repository status must be exactly:

```text
modified tracked paths: 0
staged paths: 0
untracked paths with -uall: 2
total status paths with -uall: 2
```

The two untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1I_codex_directive.md
docs/task reports/Phase_1I.md
```

Do not stage or commit the report after population.

---

## 14. Acceptance criteria

Phase 1I is accepted only if every item passes:

1. all governing authorities were read completely;
2. the externally supplied directive identity matched;
3. the starting Git identity and history matched Section 3;
4. post-caller status was exactly 22 modified, 16 untracked, 38 total, and zero staged with `-uall`;
5. all eight locked caller records matched;
6. all six accepted implementation additions matched;
7. `composer.lock` matched its locked identity;
8. the checkpoint inventory was exactly 36 paths;
9. the deterministic manifest was exactly 4,012 bytes with the locked SHA-256;
10. pre-stage quality passed with 254 tests and 4,279 assertions;
11. no checkpoint path was edited during Phase 1I;
12. exactly 36 paths were staged using the literal closed list;
13. staged status was exactly 22 modified and 14 added;
14. the staged delta was exactly 6,167 insertions and 104 deletions;
15. no unstaged tracked path remained;
16. only the two Phase 1I caller records remained untracked;
17. all staged blobs equaled their worktree bytes;
18. all 358 semantic index entries were stage zero and mode `100644`;
19. `git diff --cached --check` passed;
20. `git write-tree` succeeded and semantic index proof still passed afterward;
21. no raw `.git/index` byte identity was treated as a gate;
22. exactly one unsigned commit was created with the exact subject;
23. the commit parent was the locked Phase 1F checkpoint;
24. the commit tree equaled the staged tree;
25. the commit delta exactly matched Section 12.2;
26. the repository then contained exactly nine commits and 358 tracked files;
27. no remote, tag, extra branch, history rewrite, or prohibited operation occurred;
28. post-commit quality passed with 254 tests and 4,279 assertions;
29. documentation, security, encoding, stream, reparse, link, lockfile, API-status, count, identity, manifest, and topology gates passed;
30. the report was populated only after every other gate passed;
31. the report remained outside the commit and was validated; and
32. final status contains only the untracked Phase 1I directive and populated report.

No partial result is a pass.

---

## 15. Standard executor completion report

Use exactly these headings, in this order:

```markdown
# PHASE 1I TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. CHECKPOINT INVENTORY

## 6. FILES CREATED OR MODIFIED BY EXECUTOR

## 7. ARCHITECTURAL DECISIONS

## 8. DEVIATIONS FROM DIRECTIVE

## 9. DEPENDENCIES ADDED OR REMOVED

## 10. ENVIRONMENT

## 11. COMMANDS RUN

## 12. PRE-STAGE VALIDATION RESULTS

## 13. STAGING EVIDENCE

## 14. COMMIT EVIDENCE

## 15. POST-COMMIT VALIDATION RESULTS

## 16. DOCUMENTATION, SECURITY, AND FILE-SAFETY REVIEW

## 17. WARNINGS AND KNOWN ISSUES

## 18. QUESTIONS OR BLOCKERS

## 19. FINAL GIT STATUS

## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include:

- `Complete` only if every acceptance criterion passed; otherwise the precise blocked status;
- the independently verified directive identity;
- starting and final Git identities and counts;
- exact 36-path inventory and manifest proof;
- caller-record and implementation-addition identity results;
- pre-stage and post-commit quality evidence;
- semantic staged-index evidence before and after `write-tree`;
- staged tree ID, new commit ID, parent, tree, subject, redacted author summary, and exact commit delta;
- a statement that raw index bytes were not an acceptance gate;
- exact warnings and any approved environment-only retry;
- confirmation that no accepted content byte was changed during Phase 1I;
- final status and the two remaining untracked paths; and
- the acceptance checklist with explicit pass or fail results.

Do not include a private email address in full. Do not claim a command passed unless its output was observed. Do not call a blocked execution complete.

The report's final nonblank line must be exactly:

```text
Await architect review before Phase 1J.
```

Return the report's final byte count and uppercase SHA-256 separately after the file is complete. Do not place that self-identity inside the report.

---

## 16. Required final architectural outcome

Phase 1I creates no new architecture. It preserves the accepted Phase 1H result as an immutable local checkpoint:

- Kongono has eight first-party packages and no Symfony or other third-party production framework dependency;
- Diagnostic Activation owns two typed permission decisions and their pure combined eligibility;
- Diagnostic Presentation owns the detached trusted projection and HTML renderer;
- Diagnostic Handler owns only stateless eligibility-first orchestration and remains an unconsumed leaf;
- denial occurs before diagnostic source access;
- successful trusted rendering remains transport-neutral;
- direct low-level calls and non-null HTML are not authorization proof;
- no outer adapter, raw configuration loader, delivery-evidence evaluator, authentication mechanism, HTTP request or response, routing, middleware, emitter, production diagnostic model, logging, telemetry, persistence, or other later capability exists;
- all prior Kernel, Application Graph, Dependency Injection, API Metadata, Diagnostics, security, disclosure, and persistent-runtime doctrine remains locked; and
- later work begins only after independent architect review of this checkpoint and its report.

Do not begin Phase 1J.
