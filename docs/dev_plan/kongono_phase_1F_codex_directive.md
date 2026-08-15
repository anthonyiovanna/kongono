# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1F

## Immutable Local Checkpoint Through Phase 1E

**Directive type:** exact repository checkpoint; no framework implementation

**Executor role:** executor, verifier, and reporter; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Create one immutable local Git checkpoint containing exactly the accepted Kongono work through Phase 1E.

This phase exists to convert the currently accepted 49-path worktree boundary into the eighth local commit. It must not change any framework, test, manifest, lockfile, tooling, documentation, directive, or historical-report byte before staging. It must not make a new architectural decision.

The exact commit subject is:

```text
Establish Kongono diagnostic activation foundation through Phase 1E
```

The checkpoint must contain exactly:

- 24 currently modified tracked paths; and
- 25 currently untracked accepted paths.

The Phase 1F directive and Phase 1F completion report are caller records. They remain outside the commit.

### 1.1 Required outcome

After successful completion:

- `main` has exactly eight commits;
- the new commit has exactly one parent, the accepted Phase 1B checkpoint;
- the new commit changes exactly 49 paths: 24 modified and 25 added;
- the new tree contains exactly 344 ordinary `100644` files;
- all accepted Phase 1E bytes are preserved exactly;
- canonical quality still passes with 247 tests and 4,233 assertions;
- the working tree contains only the untracked Phase 1F directive and populated Phase 1F report;
- the index contains no staged change; and
- no remote, tag, extra branch, signing requirement, or later-phase work is introduced.

---

## 2. Governing authority and mandatory stop rule

### 2.1 Authority order

Read completely, in this order, before any staging or repository mutation:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. every Accepted ADR in `docs/adr/`
6. every Phase 1B through Phase 1E directive and correction directive named in Section 4.3
7. every corresponding completion report named in Section 4.3
8. this Phase 1F directive
9. the zero-byte `docs/task reports/Phase_1F.md` placeholder

Earlier documents remain historical evidence. Where their operational checkpoint procedures differ from this directive, this directive controls Phase 1F only. No earlier decision is reopened.

### 2.2 Independent directive identity

The architect will supply the byte count and SHA-256 of this directive outside this file. Compare those independently supplied values with:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
```

Do not derive the expected identity from the local file. The local file is not self-authenticating.

The report placeholder must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_1F.md
```

Its initial SHA-256 must be:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.3 Stop rather than assume

At the first mismatch, ambiguity, unexpected path, changed byte, failed gate, unauthorized warning, or prohibited side effect:

1. stop immediately;
2. preserve the repository and index in the state required by the gate that failed;
3. do not repair, normalize, truncate, restage, unstage, reset, amend, retry, or widen scope unless this directive expressly authorizes that exact action;
4. leave the Phase 1F report zero bytes unless Section 13 authorizes its population; and
5. report the exact observation, expected value, viable options, and a recommendation.

Do not guess. Do not silently improve adjacent work. Do not treat a locally observed value as architect approval.

### 2.4 Environment-only retry rule

One unchanged retry is allowed only when a required command is blocked solely by the managed environment, such as:

- Composer advisory/network/cache access;
- write access to `.git/index.lock` for the exact authorized staging command;
- write access required by `git write-tree`; or
- write access required by the exact authorized commit command.

The retry must use the identical semantic command and the same closed path set. It may request approved host access. It must not alter configuration, dependencies, source files, the staging inventory, or the commit message.

A functional failure is not an environment-only failure and must not be retried under this rule.

---

## 3. Locked starting repository state

Before the caller adds the two Phase 1F records, the accepted repository state is exactly:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `837863d11feda282e4959ef7ec98a810c184531e` |
| `HEAD^{tree}` | `24a82a296b9075a2d7640f015a06230b12ed5fa2` |
| `HEAD^` | `7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f` |
| Commit count | `7` |
| Remotes | `0` |
| Tags | `0` |
| Tracked files | `319` |
| Stage-zero index entries | `319` |
| Tracked file modes | all `100644` |
| Modified tracked paths | `24` |
| Untracked paths | `25` |
| Total status paths | `49` |
| Staged paths | `0` |
| Unstaged tracked paths | `24` |
| Package directories | `7` |
| Package manifests | `7` |
| Composer manifests including root | `8` |
| Production PHP files | `52` |
| Test PHP files | `83` |
| PHP CS Fixer-scoped files | `135` |

The accepted `composer.lock` identity is:

```text
bytes: 181650
SHA-256: 6EB8CF5E003C6A17D41C8127057BD4A05C8B984A909BB9B7F07845A712689A8E
```

The accepted protected-set proof remains:

```text
files: 147
aggregate SHA-256: 4D6E7C4F11F86C56065AFBC8999E55BB2132741B80AAF515C482C9219FB19D9E
```

Use the same protected-set definition and deterministic aggregation algorithm established by the accepted Phase 1E directive. The set must be byte-identical before staging and after commit.

### 3.1 Expected state after caller setup

After the caller copies this directive to `docs/dev_plan/` and creates the required zero-byte report placeholder, but before staging, the repository must be exactly:

| Property | Required value |
|---|---:|
| Modified tracked paths | `24` |
| Untracked paths | `27` |
| Total status paths | `51` |
| Staged paths | `0` |
| Unstaged tracked paths | `24` |

The two additional untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/task reports/Phase_1F.md
```

No other starting-state difference is authorized.

---

## 4. Exact checkpoint inventory

The checkpoint inventory is closed. Stage no path outside this section and omit no path inside it.

All paths are repository-relative and use `/` separators for comparison.

### 4.1 Modified tracked paths - exactly 24

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
packages/diagnostic-presentation/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

### 4.2 Added paths - exactly 25

```text
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md
docs/dev_plan/kongono_phase_1C_codex_directive.md
docs/dev_plan/kongono_phase_1D_codex_directive.md
docs/dev_plan/kongono_phase_1E_codex_directive.md
docs/task reports/Phase_1B.md
docs/task reports/Phase_1B_1_correction.md
docs/task reports/Phase_1B_2_correction.md
docs/task reports/Phase_1B_3_correction.md
docs/task reports/Phase_1C.md
docs/task reports/Phase_1D.md
docs/task reports/Phase_1E.md
packages/diagnostic-activation/README.md
packages/diagnostic-activation/composer.json
packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php
packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php
packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php
packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php
packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php
packages/diagnostic-activation/tests/Unit/TrustedDeveloperPresentationEligibilityTest.php
```

### 4.3 Immutable accepted caller records - exactly 14

These records are part of the 25 added paths. Verify every byte before staging.

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1B_codex_directive.md` | 30579 | `0A7FBF14E61CF6971C673A90006F3A4F913638679A879685A9715A4097BB8B31` |
| `docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md` | 20048 | `E94B5217CDC8F07A3BE7FFEEF4E80C26BD7C12561024E9611B5A8DC2E2175D5F` |
| `docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md` | 21661 | `50D795817F18753797EFF867AC271EB08AEFEF1B74B1FACD1A726EC0C6BCA493` |
| `docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md` | 19220 | `3CD09281EF71E5EE3F8DC183D83DB5CB2734C4B56D3FEDAC0095F1C0F4C75EC6` |
| `docs/task reports/Phase_1B.md` | 3001 | `256FD7B335163B9029E341E673DD457C46AA5532F95A01D442E339F263A8C6C8` |
| `docs/task reports/Phase_1B_1_correction.md` | 4078 | `62F61AA859FAEAB35ABCA865603D312DED3D1ED75DBC3035F996B711A346E017` |
| `docs/task reports/Phase_1B_2_correction.md` | 2699 | `B6C3E0C156BCFC75894E8F5E5A98392FF182A1DEEA71162448B4B9BE177B719B` |
| `docs/task reports/Phase_1B_3_correction.md` | 18805 | `56F1938083B9D58F8BE39C0567FF0A63A4A2EC38FDB00D91C8D5CE2BDC93CCE4` |
| `docs/dev_plan/kongono_phase_1C_codex_directive.md` | 35459 | `0E0027BA05FE305DCA92CADA56843EDF42D91C664A2C507C1C90FF983A7FD7C3` |
| `docs/task reports/Phase_1C.md` | 20551 | `28864B5D4B1B3D4EE9D7564E83F23FFC5E9D4D6CC4DD55C251FB13A5E393931B` |
| `docs/dev_plan/kongono_phase_1D_codex_directive.md` | 39847 | `9A98F82B00B46256647C1F5A29B0D9147E5EB087194BEE6CE72ABA302E635115` |
| `docs/task reports/Phase_1D.md` | 22623 | `45EB98654C68F87E07A6A874FE3C3AEB566D7DC12154E69415734173A62F4DFB` |
| `docs/dev_plan/kongono_phase_1E_codex_directive.md` | 41092 | `1B1B799C26FC3D6750D58BD6C66EFF019DE992B7E8B243E54B1A386CD88E9910` |
| `docs/task reports/Phase_1E.md` | 16445 | `33983CAFD5418C3230F4B9D1E1A106A09544869A106D10AAB66B465933142B29` |

### 4.4 Immutable accepted implementation additions - exactly 11

These are the non-caller-record added paths. Verify every byte before staging.

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md` | 8811 | `99FD9B917F7A00CCEF053F463F033F6BA48D9A6EB4867CAEC90E9554CF000ECC` |
| `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md` | 10616 | `8996D3AA8534201DE169CFFEA856CD96DF33DCC8D4D1C9B9BA252CD879D1BE8E` |
| `docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md` | 4630 | `FF5C3BA4F825DB7F2377229C16D05BAA1953F62D4C85FC80819DC62F50C5C03D` |
| `packages/diagnostic-activation/README.md` | 5854 | `E70D59C99D23A91B7078ECA54DFDE964076A834BE2726E2E798A1062F7C70783` |
| `packages/diagnostic-activation/composer.json` | 428 | `C66A7BE2DF90476CD1B516BC448F3BF41737E89F798BD379C69D70EA9DC0E771` |
| `packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php` | 690 | `938D1BCEE66F647045C5EF9D45426482E662992D3FF2E994687CEE7A0BE83732` |
| `packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php` | 707 | `0B83C99CA936CEB4264CCD6433367C976B5912FD952287F9F5F068226E4B00DC` |
| `packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php` | 910 | `DE10F378C77BC44997D415EA3F02F904FF1A7F98087EC04AA9656F39BA37F7D6` |
| `packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php` | 1989 | `114D97F3B0EAA2116C051B84259D8154899992786677DF57AC62F9ED7F7ABDDE` |
| `packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php` | 1989 | `E5511B3773405CB366AF341B8BDBC98EFB4F7C53B4318A9BA17BBACB8B8F0423` |
| `packages/diagnostic-activation/tests/Unit/TrustedDeveloperPresentationEligibilityTest.php` | 3817 | `44B677915824A3C3BBE0D3B93992F79A3E606C5D338720704CA61970CBA282CB` |

---

## 5. Deterministic checkpoint manifest

Before staging, construct the manifest over all 49 Section 4 paths using this exact algorithm:

1. normalize each repository-relative path to `/` separators;
2. sort paths using ordinal bytewise order;
3. read the primary file bytes without text normalization;
4. calculate uppercase SHA-256 over the primary bytes;
5. append one UTF-8, no-BOM record per path:

```text
<path><NUL><decimal byte length><NUL><uppercase SHA-256><LF>
```

6. concatenate the 49 records without an extra record or BOM; and
7. hash that manifest byte sequence with SHA-256.

Required result:

```text
entries: 49
manifest bytes: 5716
manifest SHA-256: AACD61453CB6E143F96F8C38B5F894BE6D03093498B66D3AF3686EA542A1B10B
```

Do not create a repository file for this manifest. It is validation evidence only.

The manifest proves current primary-file bytes. It does not replace the required Git status, index, mode, stream, or quality proofs.

---

## 6. Prohibited scope

Phase 1F must not:

- edit, reformat, normalize, repair, truncate, append to, or regenerate any of the 49 checkpoint paths;
- edit or stage the Phase 1F directive;
- populate the Phase 1F report before all non-report post-commit gates pass;
- stage any path outside Section 4;
- use `git add .`, `git add -A`, a wildcard, a directory-wide add, or an inferred status list;
- unstage, reset, restore, checkout, clean, stash, amend, rebase, cherry-pick, merge, squash, filter, or rewrite history;
- change `.gitignore`, attributes, line endings, modes, file timestamps intentionally, or alternate data streams;
- add a remote, tag, branch, hook, signing configuration, or Git configuration;
- create a signed commit;
- add, remove, or update a Composer dependency;
- regenerate `composer.lock`;
- implement DI/container behavior, HTTP, routing, middleware, request/response adapters, exception rendering delivery, CLI, ORM, agents, extensions, frontend, telemetry, serialization, Application Graph expansion, or any later capability;
- alter public, internal, or experimental API status;
- weaken security, diagnostics disclosure, lifecycle, persistent-runtime, or no-assumption doctrine;
- create a release, version, package publication, pull request, or push; or
- treat raw `.git/index` byte identity as an acceptance contract.

This phase authorizes only:

1. read-only validation;
2. exact staging of the 49 paths;
3. semantic staged-index and tree proof;
4. the one exact local commit;
5. post-commit validation; and
6. population of the untracked Phase 1F report after success.

---

## 7. Gate A - authority, identity, and baseline

Before broad validation or mutation, prove all of the following:

1. all Section 2.1 authorities were read completely;
2. the independently supplied directive byte count and SHA-256 match the local Phase 1F directive;
3. the Phase 1F report is the required zero-byte ordinary file;
4. every Section 4.3 caller record matches its locked bytes and SHA-256;
5. every Section 4.4 implementation addition matches its locked bytes and SHA-256;
6. all Section 3 Git identities, counts, and inventories match after accounting for the two Phase 1F caller records;
7. the index contains exactly 319 stage-zero entries, no higher-stage entry, and no staged change;
8. the 24 modified tracked paths and 25 pre-existing untracked paths match Section 4 exactly;
9. the two Phase 1F caller records are the only additional untracked paths;
10. all repository files in scope are ordinary files, with no reparse point or non-default alternate data stream;
11. tracked modes remain `100644`;
12. `composer.lock` matches its locked identity;
13. the 147-file protected set matches its locked digest; and
14. the 49-entry manifest matches Section 5.

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
platform requirements: pass
PHP CS Fixer: 0 of 135 files fixable
PHPStan: 0 errors
PHPUnit: 247 tests, 4233 assertions
```

Accepted Composer warning categories remain only those already authorized by the accepted repository doctrine and manifests: missing package licenses, unbounded local development constraints, and missing optional curl performance support where actually emitted. Report the exact warnings seen. An unexpected category is a stop condition.

Also verify before staging:

- all local Markdown links resolve under the repository's accepted link rules;
- UTF-8/LF, final-LF, trailing-whitespace, NUL, ordinary-file, and alternate-stream gates pass for the checkpoint scope;
- no secret, credential, token, private key, signing material, or machine-specific absolute path was introduced into the accepted content;
- the manifest, protected-set digest, caller-record identities, implementation identities, Git history, status inventories, and lockfile remain unchanged after quality validation; and
- `git diff --check` passes for the 24 modified tracked paths.

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
    'docs/adr/0026-actionable-diagnostics-and-error-boundaries.md'
    'docs/adr/0034-trusted-developer-diagnostic-projection.md'
    'docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md'
    'docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md'
    'docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md'
    'docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md'
    'docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md'
    'docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md'
    'docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md'
    'docs/adr/README.md'
    'docs/architecture/overview.md'
    'docs/architecture/runtime-model.md'
    'docs/dev_plan/kongono_phase_1B_codex_directive.md'
    'docs/dev_plan/kongono_phase_1B_1_codex_directive_correction.md'
    'docs/dev_plan/kongono_phase_1B_2_codex_directive_correction.md'
    'docs/dev_plan/kongono_phase_1B_3_codex_directive_correction.md'
    'docs/dev_plan/kongono_phase_1C_codex_directive.md'
    'docs/dev_plan/kongono_phase_1D_codex_directive.md'
    'docs/dev_plan/kongono_phase_1E_codex_directive.md'
    'docs/doctrine.md'
    'docs/engineering/api-status.md'
    'docs/engineering/error-handling.md'
    'docs/engineering/package-layout.md'
    'docs/glossary.md'
    'docs/task reports/Phase_1B.md'
    'docs/task reports/Phase_1B_1_correction.md'
    'docs/task reports/Phase_1B_2_correction.md'
    'docs/task reports/Phase_1B_3_correction.md'
    'docs/task reports/Phase_1C.md'
    'docs/task reports/Phase_1D.md'
    'docs/task reports/Phase_1E.md'
    'packages/diagnostic-activation/README.md'
    'packages/diagnostic-activation/composer.json'
    'packages/diagnostic-activation/src/AdapterControlledDeliveryPermission.php'
    'packages/diagnostic-activation/src/TrustedDeveloperBootstrapPermission.php'
    'packages/diagnostic-activation/src/TrustedDeveloperPresentationEligibility.php'
    'packages/diagnostic-activation/tests/Unit/AdapterControlledDeliveryPermissionTest.php'
    'packages/diagnostic-activation/tests/Unit/TrustedDeveloperBootstrapPermissionTest.php'
    'packages/diagnostic-activation/tests/Unit/TrustedDeveloperPresentationEligibilityTest.php'
    'packages/diagnostic-presentation/README.md'
    'phpstan.neon.dist'
    'phpunit.xml.dist'
    'tests/Architecture/ApiStatusConventionTest.php'
)

git -c safe.directory='C:/Users/antho/Dev/kongono' add -- $checkpointPaths
```

Before executing `git add`, prove that the literal array contains exactly 49 unique paths and its set exactly equals Section 4.

If the exact add is blocked solely by repository metadata permissions, Section 2.4 permits one unchanged approved-access retry.

After a successful add, do not run another staging command.

---

## 10. Gate D - semantic staged-index proof

After staging and before `git write-tree` or commit, prove:

| Property | Required value |
|---|---:|
| Staged paths | `49` |
| Staged modified paths | `24` |
| Staged added paths | `25` |
| Staged deleted paths | `0` |
| Staged renames/copies | `0` |
| Unstaged tracked paths | `0` |
| Untracked paths | `2` |
| Stage-zero index entries | `344` |
| Higher-stage index entries | `0` |
| Index modes | all `100644` |

The two untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/task reports/Phase_1F.md
```

For every staged path, prove:

- the staged blob bytes equal the current worktree primary bytes;
- the staged path and status class match Section 4;
- the staged mode is `100644`;
- no symlink, submodule, reparse point, or alternate stream is involved; and
- the combined staged primary bytes still reproduce the Section 5 manifest.

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

`git write-tree` is allowed to update internal Git cache-tree metadata. Raw `.git/index` byte length and SHA-256 are deliberately not acceptance criteria. Do not stop merely because those raw metadata bytes change. After `write-tree`, re-prove the semantic requirements in this section: exact paths, stage numbers, blob object IDs, modes, statuses, and worktree equality.

If a semantic index entry changes, stop and preserve the index. If only uncontracted cache-tree metadata changes while the semantic entries remain exact, continue.

---

## 11. Gate E - exact local commit

Only after Gates A-D pass, create exactly one commit with:

```powershell
git -c safe.directory='C:/Users/antho/Dev/kongono' commit --no-gpg-sign -m "Establish Kongono diagnostic activation foundation through Phase 1E"
```

Do not amend. Do not add a body. Do not change author or committer configuration. Do not sign. Do not stage anything else. Do not create a second commit.

Before the commit, verify that effective author configuration is present without printing a full private email address into the report. Record the author name and redact the email address.

If the exact commit is blocked solely by repository metadata permissions, Section 2.4 permits one unchanged approved-access retry after re-proving all Gate D semantic index conditions.

If a hook unexpectedly runs, signing is unexpectedly requested, the commit message differs, or the created commit does not satisfy Section 12, stop. Do not amend or reset.

---

## 12. Gate F - post-commit proof

Before populating the report, prove all of the following.

### 12.1 Commit identity and history

- current branch is `main`;
- `main` is the only local branch;
- the new `HEAD` differs from the locked starting `HEAD`;
- `HEAD^` is exactly `837863d11feda282e4959ef7ec98a810c184531e`;
- `HEAD` has exactly one parent;
- the repository has exactly eight commits;
- the commit subject is exactly `Establish Kongono diagnostic activation foundation through Phase 1E`;
- the commit is not signed;
- `HEAD^{tree}` equals the tree returned by the authorized `git write-tree`;
- there are no remotes and no tags; and
- historical commits and their trees are unchanged.

Record the new commit ID and tree ID as observed results. Neither is pre-authorized by a predicted value.

### 12.2 Exact commit delta

Relative to its parent, the new commit must contain exactly:

```text
49 changed paths
24 modified paths
25 added paths
0 deleted paths
0 renamed paths
0 copied paths
```

The path/status set must exactly equal Section 4. The commit must not include either Phase 1F caller record.

### 12.3 Exact tree

The new tree must contain:

```text
344 files
344 stage-zero semantic entries when checked out in the current worktree
all file modes 100644
0 symlinks
0 submodules
```

Every committed path must equal the current worktree primary bytes. The committed forms of all Section 4.3 and 4.4 paths must match their locked identities. The 49 committed paths must reproduce the Section 5 manifest.

The protected set must remain exactly:

```text
files: 147
aggregate SHA-256: 4D6E7C4F11F86C56065AFBC8999E55BB2132741B80AAF515C482C9219FB19D9E
```

### 12.4 Repository status before report population

Before the report is populated:

```text
modified tracked paths: 0
staged paths: 0
untracked paths: 2
total status paths: 2
```

The two untracked paths must be exactly the Phase 1F directive and the still-zero-byte Phase 1F report.

### 12.5 Post-commit quality

Run the unchanged canonical gate:

```powershell
composer quality
```

Required result:

```text
security advisories: 0
platform requirements: pass
PHP CS Fixer: 0 of 135 files fixable
PHPStan: 0 errors
PHPUnit: 247 tests, 4233 assertions
```

Recheck local links, UTF-8/LF, trailing whitespace, NUL bytes, alternate streams, ordinary-file status, secrets, modes, package/manifests counts, production/test counts, API status convention, lockfile identity, caller-record identities, implementation identities, and the protected-set digest.

Expected repository counts remain:

```text
package directories: 7
package manifests: 7
Composer manifests including root: 8
production PHP files: 52
test PHP files: 83
PHP CS Fixer-scoped files: 135
API declarations: 52
API attributes: 50
```

If any post-commit gate fails, do not amend, reset, or create a repair commit. Leave the Phase 1F report zero bytes and report the blocker outside it.

---

## 13. Gate G - completion report and final state

Populate `docs/task reports/Phase_1F.md` only after every non-report criterion in Sections 7-12 passes.

The report remains:

- untracked;
- unstaged;
- absent from `HEAD`;
- an ordinary UTF-8/LF Markdown file;
- free of trailing whitespace and NUL bytes;
- terminated by exactly one final LF; and
- outside the checkpoint commit.

After report population, validate the report itself and return its final byte count and uppercase SHA-256 outside the report body.

Final repository status must be exactly:

```text
modified tracked paths: 0
staged paths: 0
untracked paths: 2
total status paths: 2
```

The two untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/task reports/Phase_1F.md
```

Do not stage or commit the report after population.

---

## 14. Acceptance criteria

Phase 1F is accepted only if every item passes:

1. all governing authorities were read completely;
2. the externally supplied directive identity matched;
3. the starting Git identity and history matched Section 3;
4. the post-caller status was exactly 24 modified, 27 untracked, 51 total, zero staged;
5. all 14 locked caller records matched;
6. all 11 accepted implementation additions matched;
7. `composer.lock` and the protected set matched;
8. the checkpoint inventory was exactly 49 paths;
9. the deterministic manifest was exactly 5,716 bytes with the locked SHA-256;
10. pre-stage canonical quality passed with 247 tests and 4,233 assertions;
11. no checkpoint path was edited during Phase 1F;
12. exactly 49 paths were staged using the literal closed list;
13. staged status was exactly 24 modified and 25 added;
14. no unstaged tracked path remained;
15. only the two Phase 1F caller records remained untracked;
16. all staged blobs equaled their worktree bytes;
17. all 344 semantic index entries were stage zero and mode `100644`;
18. `git diff --cached --check` passed;
19. `git write-tree` succeeded and semantic index proof still passed afterward;
20. no raw `.git/index` byte identity was treated as a gate;
21. exactly one unsigned commit was created with the exact subject;
22. the commit parent was the locked Phase 1B checkpoint;
23. the commit tree equaled the staged tree;
24. the commit delta was exactly 49 paths: 24 modified and 25 added;
25. the repository now has exactly eight commits and 344 tracked files;
26. no remote, tag, extra branch, history rewrite, or prohibited operation occurred;
27. post-commit canonical quality passed with 247 tests and 4,233 assertions;
28. all documentation, security, encoding, stream, link, lockfile, API-status, count, identity, and protected-set gates passed;
29. the report was populated only after every other gate passed;
30. the report remained outside the commit and was validated; and
31. final status contains only the untracked Phase 1F directive and populated report.

No partial result is a pass.

---

## 15. Standard executor completion report

Use exactly these headings, in this order:

```markdown
# PHASE 1F TASK COMPLETION REPORT

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
- the starting and final Git identities;
- all baseline and final counts;
- the exact 49-path inventory result and manifest proof;
- caller-record and accepted-addition identity results;
- pre-stage and post-commit quality evidence;
- semantic staged-index evidence before and after `write-tree`;
- staged tree ID, new commit ID, parent, tree, subject, author summary, and commit delta;
- a statement that raw index bytes were not an acceptance gate;
- exact warnings and any approved environment-only retry;
- confirmation that no content bytes were changed during Phase 1F;
- final status and the two remaining untracked paths; and
- the acceptance checklist with explicit pass/fail results.

Do not include a private email address in full. Do not claim a command passed unless its output was observed. Do not call a blocked execution complete.

The report's final nonblank line must be exactly:

```text
Await architect review before any next phase.
```

Return the report's final byte count and uppercase SHA-256 separately after the file is complete. Do not place that self-identity inside the report.

---

## 16. Required final architectural outcome

Phase 1F creates no new architecture. It preserves the accepted Phase 1E result as an immutable local checkpoint:

- diagnostic activation remains a small typed Experimental foundation;
- bootstrap permission and adapter-controlled delivery permission remain separate explicit inputs;
- trusted presentation eligibility remains a pure two-permission decision;
- no request, response, middleware, routing, HTTP delivery, CLI delivery, global mode, mutable state, environment lookup, container lookup, telemetry, or future runtime behavior is added;
- all prior Kernel, Application Graph, dependency-injection, API-metadata, diagnostics, presentation, security, and persistent-runtime doctrine remains locked; and
- future work must begin only after independent architect review of this checkpoint and its report.

Do not begin the next phase.
