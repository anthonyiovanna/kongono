# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1B
## Immutable Local Checkpoint Through Phase 1A

**Directive status:** Architect-authorized Git checkpoint contract
**Phase type:** Repository preservation only
**Execution role:** Executor, not architect
**Date:** 2026-08-14

---

## 1. Mission

Kongono's last immutable checkpoint is the accepted Phase 0U commit. Phases 0V
through 1A subsequently added and validated the Diagnostic Presentation package,
trusted-developer projection, exact HTML renderer, and fail-closed activation
architecture. Those accepted results currently exist as a large but exact dirty
worktree.

Phase 1B must preserve that already-accepted state in exactly one local Git
commit before any further runtime or adapter implementation begins.

This phase performs no implementation, documentation rewrite, cleanup,
normalization, dependency change, or architectural decision. It may mutate only
the Git index and local `main` reference through the exact staging and commit
operations authorized below, then populate its caller-provided report.

The checkpoint scope is exactly:

```text
32 currently modified tracked paths
42 currently untracked accepted paths
74 accepted paths total
```

The Phase 1B directive and Phase 1B report are caller records for the checkpoint
operation. They must remain outside the commit. After successful commit and
report population, they must be the only two worktree status paths.

The exact commit subject is:

```text
Establish Kongono framework baseline through Phase 1A
```

Do not implement the typed activation decision, configuration, adapter, HTTP,
or any later Phase 1 capability.

---

## 2. Authority and mandatory stop rule

Before any broad validation, staging, index mutation, or commit, read completely:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `SECURITY.md`;
4. `docs/doctrine.md`;
5. `docs/documentation-standard.md`;
6. `docs/adr/0026-actionable-diagnostics-and-error-boundaries.md`;
7. `docs/adr/0034-trusted-developer-diagnostic-projection.md`;
8. `docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md`;
9. `docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md`;
10. `docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md`;
11. `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`;
12. every accepted directive and report listed in Section 3.4;
13. this directive; and
14. the zero-byte `docs/task reports/Phase_1B.md` caller placeholder.

The Phase 1B directive is caller-controlled authority. Its expected byte count
and SHA-256 must be supplied independently by the architect outside this file.
The local file is not self-authenticating. If either expected value is absent or
does not match, stop before quality validation or mutation and report the
observed identity.

Apply the repository policy literally:

```text
STOP
ASK
DO NOT GUESS
```

Any mismatch in a caller record, path inventory, byte identity, Git object,
branch, ref, history, index, worktree status, file mode, stream, file type,
encoding, security scan, quality gate, staged tree, or commit contract is a hard
stop.

On mismatch:

- do not fix the file;
- do not rewrite or normalize content;
- do not stage any path;
- do not unstage or rebuild an index after staging has begun unless a later
  architect correction explicitly authorizes it;
- do not commit, amend, reset, restore, clean, stash, checkout, rebase, merge,
  cherry-pick, tag, push, or change a ref;
- do not create a replacement report or directive; and
- report the exact mismatch and current Git state.

If the first authorized `git add` operation fails only because the managed
environment cannot write repository metadata, rerun the same exact staging
operation with approved repository-metadata access. If `git commit` fails only
for the same environmental reason before creating a commit, rerun the exact
commit command with approved access. Do not change the path set, staging
strategy, commit message, signing choice, or content.

Once the staged index has passed Section 7, it is the accepted checkpoint index.
Do not unstage, restage, or rebuild it. A later failure must preserve that index
for architect review unless this directive expressly authorizes the unchanged
commit retry described above.

---

## 3. Locked starting state

### 3.1 Git identity before caller setup

The accepted repository state is:

```text
branch                  main
local branches          1
HEAD                    7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
HEAD tree               b1d0057925158e0f0f37ca13694d1d0d525ce5f0
HEAD parent             75749a37a1844f65cc4aa6e9bcb2579ea30235f4ce
commit count            6
HEAD subject            Establish Kongono framework baseline through Phase 0U
HEAD tracked files      277
HEAD file modes         277 x 100644
remotes                 0
tags                    0
tracked modifications   32
untracked paths         42
total status paths      74
staged paths            0
tracked diff            32 files, 1118 insertions, 221 deletions
```

The current index is byte-identical to HEAD and has:

```text
index bytes             33842
index SHA-256           EB65F9AB1A678CDC5DED111DD5750F8ADB0376135A8F6ADC15FBE84EAEDF79A1
```

The effective configured Git identity is:

```text
author name             Anthony Iovanna
author email            anthonyiovanna@gmail.com
commit signing          not configured; Phase 1B uses --no-gpg-sign
active hooks            none; only sample hook files exist
```

Do not alter Git configuration.

### 3.2 Caller setup

The user will add exactly:

```text
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/task reports/Phase_1B.md
```

The report must initially be an ordinary zero-byte, untracked, unstaged file,
absent from HEAD, with SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

After caller setup and before staging, the exact status arithmetic is:

```text
tracked modifications   32
untracked paths         44
total status paths      76
staged paths            0
checkpoint paths        74
excluded caller paths   2
```

The two excluded paths are the Phase 1B directive and report only.

### 3.3 Accepted checkpoint-scope digest

The exact 74 checkpoint paths are represented by an ordinal manifest. For each
path, encode:

```text
repository-relative / path
NUL
status token: modified or untracked
NUL
decimal byte length
NUL
uppercase SHA-256 of working-file bytes
LF
```

Sort entries by ordinal repository-relative path, concatenate as UTF-8 without
BOM, then SHA-256 the concatenation.

The locked result before caller setup, and still the required result when the
two Phase 1B caller records are excluded, is:

```text
entries                 74
manifest bytes          9727
SHA-256                 39BE728A68CAFABA4637A3EF7B8E69386914AED294ECCD140CC01CFACC87C2C0
```

The digest locks content, path, and starting status classification. Do not
reconstruct it from assumptions or from the directive itself; calculate it from
the live checkpoint scope and compare.

### 3.4 Accepted caller records entering the checkpoint

All seventeen records below are part of the 74-path commit and must match:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md` | 15434 | `C0DF348516E2A8848278F51A768835A95BA24FDDEAAD49478B4D5F8B3037FBC5` |
| `docs/dev_plan/kongono_phase_0W_codex_directive.md` | 32691 | `F94B1CBA904441B2DE6BCC4B51C34319E85367F783FEAF669479CDC763863FD2` |
| `docs/dev_plan/kongono_phase_0X_1_codex_directive_correction.md` | 13850 | `1EB22C5C116FC010C93A0623AAFB69504DE3D6F1B54C26910219926E7C414679` |
| `docs/dev_plan/kongono_phase_0X_codex_directive.md` | 47300 | `2F21C87ED746FE616469A638C187D911D91023B206373F25594EFBF0BC33481A` |
| `docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md` | 16281 | `6908DE659DA2363CCD83846C626E51A8CC6E487EB416928CC8AAFB86D8F839E4` |
| `docs/dev_plan/kongono_phase_0Y_codex_directive.md` | 49492 | `21E8232B0E305DC03618F3EEA2EC8E02BEE42093B0D1A7355AD92EEA8FFEBD00` |
| `docs/dev_plan/kongono_phase_0Z_codex_directive.md` | 49502 | `C815F40FF45871835D8937DB1FAD4BAF25DE6482CAAC6E03F223F56C6CC7BD0F` |
| `docs/dev_plan/kongono_phase_1A_codex_directive.md` | 43295 | `AD1E865FFFC20D805C34F72D356352F29BF29234DCE25F9BA2639E83C183AC7D` |
| `docs/task reports/Phase_0V.md` | 24353 | `6C5053A469664994C5494598AF0D29E53089F0EACAD08D7ADA744838F404A4A3` |
| `docs/task reports/Phase_0V_1_correction.md` | 10110 | `AB965D2542B243A038B2213106D3C739DBF40F29913D72FED72D275181EF45E3` |
| `docs/task reports/Phase_0W.md` | 17338 | `08E491A635717DA07E7012A593C5173E04EBA6A614E49529632241008DCDE38E` |
| `docs/task reports/Phase_0X.md` | 19614 | `6035EFA808A8C86477CF6AF3E60C7B8E427A8DC0BF2C3DA645DDC2D5CBDF8FC7` |
| `docs/task reports/Phase_0X_1_correction.md` | 10388 | `38523347418FD6DEEB22E86FDD31DA6C579F0493A8EF6DE724F5980A99998D80` |
| `docs/task reports/Phase_0Y.md` | 19190 | `C8DC0AEAB1E2A3D6688FDECD207B98645871F05180965440D2B65DEF0F4A720A` |
| `docs/task reports/Phase_0Y_1_correction.md` | 11438 | `DC29871D5A4F974029120CCA21E67782E70113077199005207559D180F54C1AD` |
| `docs/task reports/Phase_0Z.md` | 18163 | `B3E3CE79A74F73A9A179AE3BD6CD9C2DF7B4DF058F79B3BA2F465C705183E582` |
| `docs/task reports/Phase_1A.md` | 14287 | `049465572EA2106D07D02D82E284966AF9C7D6C1F28F8CABF40ED361B1854C36` |

Every earlier tracked directive/report already in HEAD remains byte-identical
and is not separately staged unless it is in Section 4.1.

### 3.5 Locked implementation and quality state

The accepted protected set remains exactly:

```text
protected paths         140
aggregate SHA-256       82AF50CB86EBADF0AAC87B19CEC44D957D3BDEB3BFA9DC4E8C73D9867E1AD95E
```

The exact implementation counts are:

```text
package directories       6
package manifests         6
all Composer manifests    7
production PHP files      49
test PHP files            80
protected files           140
PHP CS Fixer files        129
API-status declarations   49
PHPUnit tests             241
PHPUnit assertions        4170
```

`composer.lock` remains:

```text
bytes                    180654
SHA-256                  FFBAB343FB34036BD9F71E3824AD14BCD4E445A5379C676A49B1D0A7C69FB387
```

Phase 1B must not change any working-file byte. Staging and committing record
the accepted bytes only.

---

## 4. Exact checkpoint inventory

### 4.1 Stage these exact thirty-two modified paths

```text
.php-cs-fixer.dist.php
ARCHITECTURE.md
README.md
SECURITY.md
composer.json
composer.lock
docs/adr/0003-monorepo-initial-development.md
docs/adr/0004-microkernel-capability-architecture.md
docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
docs/adr/0027-package-local-runtime-diagnostic-metadata.md
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
docs/adr/README.md
docs/architecture/application-graph.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/doctrine.md
docs/documentation-standard.md
docs/engineering/README.md
docs/engineering/api-status.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/engineering/testing.md
docs/glossary.md
packages/diagnostics/README.md
phpstan.neon.dist
phpunit.xml.dist
tests/Architecture/ApiStatusConventionTest.php
```

### 4.2 Stage these exact forty-two untracked accepted paths

```text
docs/adr/0034-trusted-developer-diagnostic-projection.md
docs/adr/0035-trusted-developer-diagnostic-projection-implementation.md
docs/adr/0036-accessible-trusted-developer-html-diagnostic-page.md
docs/adr/0037-trusted-developer-html-diagnostic-renderer-implementation.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/dev_plan/kongono_phase_0V_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0W_codex_directive.md
docs/dev_plan/kongono_phase_0X_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0X_codex_directive.md
docs/dev_plan/kongono_phase_0Y_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Y_codex_directive.md
docs/dev_plan/kongono_phase_0Z_codex_directive.md
docs/dev_plan/kongono_phase_1A_codex_directive.md
docs/task reports/Phase_0V.md
docs/task reports/Phase_0V_1_correction.md
docs/task reports/Phase_0W.md
docs/task reports/Phase_0X.md
docs/task reports/Phase_0X_1_correction.md
docs/task reports/Phase_0Y.md
docs/task reports/Phase_0Y_1_correction.md
docs/task reports/Phase_0Z.md
docs/task reports/Phase_1A.md
packages/diagnostic-presentation/README.md
packages/diagnostic-presentation/composer.json
packages/diagnostic-presentation/src/Developer/DeveloperDiagnosticOwner.php
packages/diagnostic-presentation/src/Developer/Html/TrustedDeveloperHtmlRenderer.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnostic.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticField.php
packages/diagnostic-presentation/src/Developer/TrustedDeveloperDiagnosticProjector.php
packages/diagnostic-presentation/src/Diagnostic/DiagnosticPresentationDiagnostic.php
packages/diagnostic-presentation/src/Exception/DiagnosticPresentationException.php
packages/diagnostic-presentation/src/Exception/ExceptionInterface.php
packages/diagnostic-presentation/src/Exception/InvalidDiagnosticProjectionException.php
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-empty.html
packages/diagnostic-presentation/tests/Fixture/trusted-developer-html-full.html
packages/diagnostic-presentation/tests/Unit/DeveloperDiagnosticOwnerTest.php
packages/diagnostic-presentation/tests/Unit/DiagnosticPresentationDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/ExceptionBoundaryTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticFieldTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticProjectorTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperDiagnosticTest.php
packages/diagnostic-presentation/tests/Unit/TrustedDeveloperHtmlRendererTest.php
```

### 4.3 Exclude exactly these two caller paths

```text
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/task reports/Phase_1B.md
```

Do not stage either one at any time.

### 4.4 No other path is authorized

Do not stage ignored caches, vendor content, `.git` metadata as content, editor
state, global configuration, temporary files, or any path outside Sections 4.1
and 4.2.

The commit delta must contain exactly 74 paths:

```text
modified existing paths  32
new paths                 42
deleted paths              0
renamed paths              0
copied paths               0
mode-only paths            0
```

---

## 5. Prohibited scope

Except for the expressly authorized final population of its caller-provided
report, Phase 1B must not:

- edit, create, delete, rename, move, normalize, or format any implementation or
  documentation file;
- regenerate Composer metadata or run `composer update`;
- modify `.gitignore`, `.gitattributes`, autocrlf, file modes, or attributes;
- add a package, dependency, plugin, suppression, baseline, exclusion, or policy
  exception;
- implement activation, configuration, HTTP, routing, middleware, production
  presentation, logging, telemetry, incidents, runtime integration, CLI,
  extensions, frontend, or agents;
- stage the Phase 1B directive or report;
- use `git add .`, `git add -A`, `git add --all`, wildcard staging, interactive
  staging, or an inferred directory-wide pathspec;
- use `git commit -a`, amend, fixup, squash, merge, rebase, cherry-pick, or a
  second commit;
- reset, restore, checkout, clean, stash, or unstage any path;
- create or change a branch, remote, tag, upstream, worktree, submodule, note,
  replace ref, or stash;
- push or contact a remote;
- sign the commit;
- bypass validation hooks if an unexpected active hook appears; stop instead;
- alter the configured author identity; or
- populate the report before complete post-commit success.

The local commit and its necessary Git object/index/ref writes are the only
authorized repository mutation before report population.

---

## 6. Pre-stage gates

### Gate A - caller authority

Before broad validation:

1. receive the Phase 1B directive byte count and SHA-256 independently from the
   architect;
2. verify the local directive matches both;
3. verify `Phase_1B.md` is the exact zero-byte placeholder;
4. verify both caller records are ordinary, untracked, unstaged, and absent
   from HEAD; and
5. verify all seventeen Section 3.4 records.

Stop immediately on mismatch.

### Gate B - Git and checkpoint scope

Verify every Section 3.1 value, including index bytes/hash. Verify:

- exact status is 32 modified, 44 untracked, 76 total, zero staged after caller
  setup;
- excluding only the two Phase 1B caller records leaves exactly the 74 paths in
  Sections 4.1 and 4.2;
- modified and untracked classifications are exact;
- the checkpoint-scope manifest is 9727 bytes with the exact locked digest;
- all 74 checkpoint files are non-empty ordinary files with exact path case,
  only the default data stream, no reparse point, and no unexpected executable
  mode;
- all HEAD/index entries are mode `100644`;
- there is no submodule, symlink, intent-to-add entry, merge state, cherry-pick,
  rebase, revert, bisect, index lock, or unresolved path;
- branch `main` is the only local branch;
- there are no remotes or tags;
- effective author identity and hook/signing state match Section 3.1; and
- no path outside the repository or caller scope is involved.

### Gate C - security and content safety

Perform read-only checks over the exact 74 checkpoint paths:

- valid UTF-8/LF rules for text files, preserving any already accepted binary-
  safe fixture behavior;
- no BOM, NUL, trailing whitespace, unexpected alternate stream, reparse point,
  or binary Git delta;
- all local Markdown links resolve, excluding `vendor/` and ignored caches;
- no private-key header, credential file, `.env` file, access token, password,
  cookie, authorization header value, or other apparent secret is introduced;
- no dependency artifact, archive, database, log, cache, executable, or oversized
  generated file is entering the checkpoint; and
- `git diff --check` passes.

Do not print suspected secret contents. Stop and report only the path and matched
category if a security check finds something.

### Gate D - canonical quality

Run exactly:

```text
composer quality
```

Expected:

```text
security advisories     0
platform requirements   pass
PHP CS Fixer findings   0 of 129
PHPStan errors          0
PHPUnit tests           241
PHPUnit assertions      4170
```

Accepted warning categories are only missing licenses, authorized local
unbounded development constraints, and the environment's missing native curl
support when Composer reports it.

If the unchanged security audit fails solely because the managed environment
cannot access Composer cache or the advisory network, rerun the identical
`composer quality` command once with approved access. Do not weaken or skip the
audit.

All Gates A through D must pass before staging.

---

## 7. Exact staging and staged-index proof

### 7.1 Authorized staging operation

Construct one explicit PowerShell array containing every path from Sections 4.1
and 4.2, in the directive order, and no other path. Run:

```powershell
git add -- $checkpointPaths
```

Do not use a directory, wildcard, generated repository scan, status output, or
negative pathspec as staging authority. The explicit directive inventory is the
only staging authority.

If this exact operation fails solely because the managed environment cannot
write `.git/index` or `.git/objects`, rerun the unchanged command once with
approved repository-metadata access.

### 7.2 Mandatory staged proof

After staging, do not commit until all of the following pass:

```text
staged paths             74
staged modified          32
staged added             42
staged deleted            0
staged renamed            0
staged copied             0
staged mode-only          0
index files             319
index modes             319 x 100644
unstaged tracked paths    0
untracked paths           2
```

The two untracked paths must be exactly the Phase 1B directive and report.

Verify:

- `git diff --cached --name-only` exactly equals Sections 4.1 plus 4.2;
- `git diff --cached --name-status` contains exactly 32 `M` and 42 `A` rows;
- `git diff --cached --check` passes;
- `git diff --cached --summary` contains no rename, copy, deletion, mode change,
  submodule, or symlink;
- every staged blob equals the corresponding accepted working-file bytes;
- all 17 accepted caller records have the exact Section 3.4 bytes in the index;
- all protected bytes and the protected aggregate remain exact;
- Phase 1B caller records are absent from the index;
- `git diff` is empty after staging;
- `git status --porcelain=v1 -uall` contains exactly 74 staged rows and the two
  untracked Phase 1B caller rows; and
- `git write-tree` succeeds and the resulting tree contains exactly 319
  `100644` files.

Record the staged-tree hash before committing. That hash becomes the required
new commit tree. Do not compute or assume the commit hash in advance because it
includes commit metadata and time.

If any staged proof fails, stop with the index preserved. Do not unstage or
rebuild it.

---

## 8. Exact commit operation

Only after Section 7 passes, run exactly:

```text
git commit --no-gpg-sign -m "Establish Kongono framework baseline through Phase 1A"
```

The command must create exactly one non-merge local commit on `main` with:

```text
parent                  7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f
tree                    exact staged-tree hash from Section 7
subject                 Establish Kongono framework baseline through Phase 1A
author name             Anthony Iovanna
author email            anthonyiovanna@gmail.com
parents                 1
signing                 none
```

Do not add a body, co-author trailer, signature, fixup marker, or other message
text. Do not amend.

If the exact command fails only because repository metadata is sandbox-blocked
and no commit was created, rerun the identical command once with approved
repository-metadata access. Before retry, verify HEAD is still the old parent
and the accepted index is unchanged.

If a commit was created despite a nonzero or confusing wrapper result, do not
retry. Verify live HEAD first.

---

## 9. Post-commit gates

Before populating the report, verify:

### 9.1 Commit identity and delta

- `main` remains the only local branch;
- commit count is exactly 7;
- new HEAD has exactly one parent, the old HEAD;
- new HEAD tree equals the recorded staged tree;
- subject and author identity are exact;
- `HEAD^` is `7d4df1f4e31b9bdb6cd134b44682719bdb17aa6f`;
- the parent-to-HEAD delta contains exactly 74 paths, 32 modified and 42 added;
- there is no deletion, rename, copy, mode change, submodule, or symlink;
- the new tree has exactly 319 files, all mode `100644`;
- all 17 accepted records and every protected path in HEAD have the expected
  bytes;
- all committed working-file bytes equal HEAD bytes; and
- historical commits, trees, subjects, and parents before the new commit remain
  unchanged.

Report the actual new commit hash and tree hash.

### 9.2 Clean checkpoint state

Before report population, exact status is:

```text
modified tracked paths   0
staged paths             0
untracked paths          2
total status paths       2
```

The two paths are exactly:

```text
docs/dev_plan/kongono_phase_1B_codex_directive.md
docs/task reports/Phase_1B.md
```

The report must still be zero bytes at this point.

There must be no remote, tag, upstream, stash, secondary branch, or unexpected
Git operation.

### 9.3 Post-commit quality

Run the identical:

```text
composer quality
```

It must pass with the exact Section 6 counts. The same single environment-only
approved-access retry rule applies.

### 9.4 Final file and documentation validation

Re-run the link, UTF-8/LF, file-safety, secret-category, protected aggregate,
manifest, and implementation-count checks against HEAD and the working tree.
They must remain exact. Do not edit anything in response; stop on mismatch.

---

## 10. Report population and final state

Only after every post-commit gate passes, populate:

```text
docs/task reports/Phase_1B.md
```

Do not stage it.

After population, final status remains exactly two untracked paths and nothing
else:

```text
?? docs/dev_plan/kongono_phase_1B_codex_directive.md
?? docs/task reports/Phase_1B.md
```

Verify the report's structure, UTF-8/LF text properties, ordinary-file state,
default data stream only, and exact final line. Re-read it from disk. Return its
byte count and SHA-256 outside its body.

Do not run quality after report population.

---

## 11. Strict acceptance criteria

Every criterion must pass:

1. All authorities were read completely before mutation.
2. The independently delivered Phase 1B directive identity matched.
3. The Phase 1B report was the exact zero-byte caller placeholder.
4. All seventeen accepted records matched exact bytes and hashes.
5. Branch, HEAD, tree, parent, history, refs, index, author, signing, hooks, and
   counts matched the locked baseline.
6. Pre-caller status was 32 modified plus 42 untracked; post-caller status was
   32 modified plus 44 untracked with zero staged.
7. The exact 74-path checkpoint manifest matched 9727 bytes and
   `39BE728A68CAFABA4637A3EF7B8E69386914AED294ECCD140CC01CFACC87C2C0`.
8. File safety, text, link, secret-category, diff, mode, and ordinary-file
   preflight checks passed.
9. Pre-stage quality passed at 241 tests and 4170 assertions with zero
   advisories, fixer findings, and PHPStan errors.
10. Exactly the 74 authorized paths were staged.
11. Staged delta was exactly 32 modified and 42 added, with no other change
    class.
12. Phase 1B directive and report were never staged.
13. The accepted index had 319 files, all mode `100644`, and no unstaged tracked
    delta.
14. Every staged blob matched accepted working-file bytes and all locked
    identities.
15. `git diff --cached --check` passed and staged summary had no prohibited
    delta.
16. The staged tree was recorded before commit and contained exactly 319 files.
17. Exactly one local non-merge commit was created with the exact subject,
    parent, tree, author, and no signature.
18. Parent-to-HEAD delta is exactly the authorized 74-path checkpoint.
19. Historical commits and trees remain unchanged.
20. New HEAD contains all accepted records and protected bytes exactly.
21. No checkpoint-scope implementation or documentation byte was edited by
    Phase 1B; only the excluded caller report was populated after success.
22. Post-commit quality passed unchanged at 241 tests and 4170 assertions.
23. No remote, tag, push, branch, upstream, stash, rewrite, cleanup, or other
    prohibited Git operation occurred.
24. Before report population, only the nonzero directive and zero-byte report
    remained untracked.
25. After report population, exactly those same two caller paths remained
    untracked, with zero modified and zero staged paths.
26. The report is structurally exact, evidence-backed, and externally
    identified.
27. No Phase 1C implementation or later capability occurred.

Any failed or unverified item makes status `Blocked` or `Partial`, never
`Complete`.

---

## 12. Completion report

Populate only after complete success, using exactly these headings:

```text
# PHASE 1B TASK COMPLETION REPORT

## 1. STATUS
## 2. SUMMARY
## 3. BASELINE VERIFIED
## 4. CALLER RECORDS
## 5. CHECKPOINT INVENTORY
## 6. FILES CREATED OR MODIFIED BY EXECUTOR
## 7. ARCHITECTURAL DECISIONS
## 8. DEVIATIONS FROM DIRECTIVE
## 9. DEPENDENCIES ADDED/REMOVED
## 10. ENVIRONMENT
## 11. COMMANDS RUN
## 12. PRE-STAGE VALIDATION RESULTS
## 13. STAGING EVIDENCE
## 14. COMMIT EVIDENCE
## 15. POST-COMMIT VALIDATION RESULTS
## 16. DOCUMENTATION AND SECURITY REVIEW
## 17. WARNINGS / KNOWN ISSUES
## 18. QUESTIONS / BLOCKERS
## 19. FINAL GIT STATUS
## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must:

- state that the executor populated only the excluded caller report after
  success and created or modified no checkpoint-scope content file;
- enumerate or reference the exact 32/42 checkpoint inventories;
- report all seventeen record identities or confirm them against Section 3.4;
- report environment versions and effective author identity, redacting the
  email in prose if desired while confirming exact commit metadata separately;
- report pre-stage and post-commit quality evidence;
- report pre-stage index identity and checkpoint manifest identity;
- report staged counts, modes, blob proof, `git diff --cached --check`, and
  staged tree hash;
- report new HEAD, tree, parent, subject, author, commit count, and exact delta;
- report the 319-file all-`100644` tree;
- report protected count/digest and Composer lock identity;
- report final link, text, secret-category, and file-safety checks;
- state no remote, tag, push, branch, signing, history rewrite, or later
  capability occurred;
- enumerate PASS/FAIL for all 27 acceptance criteria; and
- end exactly:

```text
Await architect review before any next phase.
```

Do not include the report's own final byte count or SHA-256 inside the body.
Return them separately after final read-back.

---

## 13. Required final outcome

Phase 1B ends with the accepted Phase 0V through Phase 1A work preserved in one
new immutable local checkpoint on `main`.

The checkpoint contains exactly 74 paths: 32 modifications and 42 additions.
Its parent is the Phase 0U checkpoint, its tree is the independently verified
staged tree, and its subject is exactly:

```text
Establish Kongono framework baseline through Phase 1A
```

The working tree then contains only the untracked Phase 1B directive and report.
No accepted content remains dirty, no index entry remains staged, and no remote
or tag exists.

The checkpoint does not implement activation or any new framework behavior. It
creates a safe baseline from which a separately authorized phase may begin.

Do not begin that phase.
