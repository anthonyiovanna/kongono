# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 0V

## Immutable Framework Checkpoint Through Phase 0U

## Mission

Create the sixth immutable Kongono repository checkpoint from the exact accepted
worktree produced by Phases 0O through 0U.1.

This phase is repository operations and verification only. It authorizes no
production, test, documentation, manifest, lock, configuration, or historical
record content change. Preserve the accepted 141-path baseline byte-for-byte,
add only this caller-supplied directive to the checkpoint, keep the zero-byte
Phase 0V completion-report placeholder outside the commit, stage the exact
closed 142-path inventory, prove the staged tree, and create one ordinary commit
with the exact subject:

```text
Establish Kongono framework baseline through Phase 0U
```

After the commit passes every post-commit gate, populate only the Phase 0V
completion report.

Do not begin error presentation, an error page, renderers, HTTP or CLI adapters,
telemetry, logging, or any other later capability.

---

# 1. ROLE, AUTHORITY, AND MANDATORY STOP RULE

You are the executor, not the architect.

Read completely before any mutation:

1. `AGENTS.md`;
2. `ARCHITECTURE.md`;
3. `docs/doctrine.md`;
4. `docs/documentation-standard.md`;
5. `docs/adr/README.md` and ADRs 0001 through 0033;
6. every Phase 0O through Phase 0U.1 directive and populated report;
7. the accepted source, tests, manifests, lock, configuration, and documentation
   represented by the closed inventories in Appendices A and B;
8. this Phase 0V directive; and
9. the zero-byte Phase 0V report placeholder.

Prior completion reports are historical evidence, not executable instructions.
The current directive controls only this checkpoint operation.

If any authority, caller record, file identity, inventory, Git fact, test result,
security result, or file-safety fact differs:

```text
STOP
REPORT
DO NOT GUESS
DO NOT REPAIR
DO NOT STAGE
DO NOT COMMIT A MISMATCH
```

Before staging, leave `docs/task reports/Phase_0V.md` exactly zero bytes. A
pre-stage failure must leave it zero bytes. Do not populate a blocked report
unless this directive expressly reaches the post-commit report step.

---

# 2. LOCKED PRE-CALLER BASELINE

The accepted repository immediately before the caller installs the two Phase 0V
records is exactly:

```text
branch                         main
only local branch              main
HEAD                           75749a37a184996203e569e039bddbc3ec10cdf7
HEAD tree                      5d8afbdbb163c571efb0564c5dd31fc141533839
HEAD parent                    5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD subject                   Establish Kongono framework baseline through Phase 0M
reachable commits              5
tracked files in HEAD          214
tracked modified paths         77
untracked paths                64
total status paths             141
staged paths                   0
tracked diff                   77 files, 2,666 insertions, 293 deletions
binary tracked-diff Git hash   a91233e096ccd37c6f5947426f3334942092918c
remotes                        0
tags                           0
```

The exact 77-path tracked name-status inventory is Appendix A. The exact
64-path untracked inventory is Appendix B.

Raw NUL-delimited inventory hashes are:

```text
git status --porcelain=v1 -z -uall | git hash-object --stdin
  b50e233cd4d57af88dea87eb6c2afcf9ec8458b1

git diff --name-only -z | git hash-object --stdin
  57cec769bc44c5c8fd704e44fa77dd1bc6c8b6b4

git ls-files -o --exclude-standard -z | git hash-object --stdin
  c3cdbf6970122e627b863d9da4efb8b63cc88f54
```

Hash the raw command output exactly as emitted. Do not sort, normalize, or
replace its bytes before hashing.

The accepted 141-record content manifest has:

```text
records       141
UTF-8 bytes   16,933
SHA-256       BE24ED45FAE77D311448045D1FABAFC8C572EA0298104612112C006D263D3CDA
```

Reproduce it with the exact algorithm below after temporarily excluding the two
Phase 0V caller paths from the status records:

```powershell
$phase0VCallerPaths = @(
    'docs/dev_plan/kongono_phase_0V_codex_directive.md',
    'docs/task reports/Phase_0V.md'
)

$records = @(
    git status --porcelain=v1 -uall |
        Where-Object {
            $path = $_.Substring(3).Trim('"').Replace('\', '/')
            $phase0VCallerPaths -notcontains $path
        }
)

$manifest = [System.Collections.Generic.List[string]]::new()
foreach ($record in $records) {
    $code = $record.Substring(0, 2)
    $path = $record.Substring(3).Trim('"').Replace('\', '/')

    if (Test-Path -LiteralPath $path -PathType Leaf) {
        $item = Get-Item -LiteralPath $path
        $sha = (Get-FileHash -LiteralPath $path -Algorithm SHA256).Hash
        $manifest.Add("$code`t$path`t$($item.Length)`t$sha")
    } else {
        $manifest.Add("$code`t$path`tABSENT")
    }
}

$manifestText = ($manifest -join "`n") + "`n"
$manifestBytes = [Text.UTF8Encoding]::new($false).GetBytes($manifestText)
$sha256 = [Security.Cryptography.SHA256]::Create()
$manifestDigest = ([BitConverter]::ToString(
    $sha256.ComputeHash($manifestBytes)
)).Replace('-', '')
```

Require the exact record count, byte count, and digest above. This digest locks
the Git class, path, byte count, and SHA-256 of every pre-caller status record,
including both tracked deletions as `ABSENT`.

The known warning that the managed environment cannot read
`C:\Users\antho\.config\git\ignore` may be recorded only if closed inventories
are independently exact. It does not relax an inventory gate.

---

# 3. CALLER SETUP AND LOCKED START STATE

The caller must copy this artifact byte-for-byte to:

```text
docs/dev_plan/kongono_phase_0V_codex_directive.md
```

and create an ordinary zero-byte file at:

```text
docs/task reports/Phase_0V.md
```

The report placeholder must have SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

Record the copied directive's observed byte count and SHA-256. It must equal the
architect-delivered artifact identity. Do not edit the copied directive.

After caller setup require exactly:

```text
tracked modified paths         77
untracked paths                66
total status paths             143
staged paths                   0
```

The only paths added to the Section 2 baseline must be the Phase 0V directive
and report. The original 141-record manifest must remain exact when those two
paths are excluded.

The Phase 0V directive must be non-empty, strict UTF-8 without BOM, LF-only,
with exactly one final LF. The Phase 0V report must be zero-byte, untracked,
unstaged, absent from `HEAD`, ordinary, and have no alternate data stream.

---

# 4. LOCKED ACCEPTED RECORDS AND CRITICAL IDENTITIES

At minimum verify these accepted identities before staging:

| Path | Bytes | SHA-256 |
| --- | ---: | --- |
| `docs/dev_plan/kongono_phase_0U_codex_directive.md` | 35,487 | `24E16C9398B8A63E8FAE3EB7FA2CBC52E63E299C3238DD215749688D23E01103` |
| `docs/task reports/Phase_0U.md` | 17,731 | `25D9877099DE9230241A4EBD6E42C909468A12EB8AF40C9E9DCC362AEC092CB2` |
| `docs/dev_plan/kongono_phase_0U_1_codex_directive_correction.md` | 10,001 | `A7753147A53857FD10CAD3E0DFFC86B4A8B24E648FB12F7C4C52FA1A584F52E1` |
| `docs/task reports/Phase_0U_1_correction.md` | 8,076 | `ABE9E18A47CA6819B0B397EB3B3DA7E27034CBDC770DE20B75B67E9E126B7DF6` |
| `composer.lock` | 179,468 | `197D310AB802AF3992ED0566E710AABD551A6A06BA5188D3E5D1C360C36374E9` |
| `packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php` | 20,521 | `9FEDF8A98BE9D86C4F35BD1078DD88624384E09CCD79B04E00C8A7CFF9633D48` |
| `packages/dependency-injection/src/Exception/ExceptionInterface.php` | 791 | `CD7EA50F4EEB1D2A6DF3B072B0CCADD950DD9943570D38B20D478A3545FB5395` |
| `packages/dependency-injection/src/Exception/DependencyInjectionException.php` | 1,688 | `15A8FE05EA901201F37E97CA0981797A9E8DD14DF1F8F54A309BAF0B6257680B` |
| `packages/dependency-injection/src/Definition/ServiceDefinition.php` | 14,811 | `4E489A44E3DAB804E173CE76CC21714AE8BADD57FD75DE9CDC93EC6CC5315340` |
| `packages/dependency-injection/src/DependencyResolver.php` | 9,713 | `1F34F7F8662A0C9B2A8D2316AEF1AA82958F2CC1CDC30733A6611389A4B28AE3` |
| `packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php` | 46,742 | `DBDCFF3060E27ED7B46D320773FAE46C73388F9D3B4B3D8BEB586661894CF204` |
| `docs/adr/0033-dependency-injection-diagnostic-context-integration.md` | 7,329 | `EB4193588859712125E0CC80A9720831EBC5F9C25E7E4A380FE327376426BDB5` |

The 141-record manifest is the closed identity gate for every other accepted
status path. Do not replace its coverage with spot checks.

Also verify all five historical commits and trees remain reachable and exact,
including the immutable Phase 0N.4 checkpoint at the Section 2 `HEAD` and tree.

---

# 5. ACCEPTED FRAMEWORK STATE TO PRESERVE

This checkpoint records the accepted foundation; it does not alter it. Verify:

- five package directories and six Composer manifests exist;
- `packages/api-metadata` owns `Kongono\ApiMetadata\Api` and `ApiStatus`;
- Kernel no longer owns the moved API-status types;
- package dependencies remain acyclic and match ADR 0031;
- Diagnostics remains presentation-neutral;
- diagnostic context remains eager, immutable, ordered, bounded, schema-owned,
  scalar-only, and free of ambient state and I/O;
- disclosure remains exactly Safe or Protected, with secrets forbidden;
- Application Graph, Kernel, and DI integrations retain their accepted closed
  schema maps, throw-site behavior, causal chains, and native-failure boundaries;
- the Public DI exception marker has only the intentional pre-alpha
  `diagnosticContext()` addition recorded by Phase 0U.1;
- resolver, ServiceDefinition, Application Graph structure, and Kernel lifecycle
  behavior remain otherwise unchanged;
- the runtime catalog has exactly 53 unique IDs: 6 Kernel, 11 Application
  Graph, 25 DI, and 11 Diagnostics;
- the 42 original package diagnostic cases retain exactly 42 integrated
  context schemas, split 6/11/25;
- no renderer, error page, HTTP/CLI presentation, logging, telemetry, incident,
  retry, cleanup, serializer, collector, or universal exception boundary exists.

Do not treat the checkpoint operation as permission to reconcile or improve any
content.

---

# 6. PRE-STAGE VALIDATION

Before staging, run and record:

1. Git identity, branch, refs, history, tree, parent, subject, status, index,
   diff, modes, remotes, tags, and exact inventories;
2. the Section 2 raw hashes and 141-record manifest proof;
3. every Section 4 identity;
4. strict UTF-8, BOM, LF, final-newline, trailing-whitespace, NUL,
   replacement-character, mojibake, conflict-marker, ordinary-file,
   reparse-point, mode, and alternate-stream inspection;
5. local Markdown-link validation with zero broken targets;
6. source/test/config PHP syntax validation;
7. focused package and architecture suites;
8. PHPStan;
9. PHP CS Fixer dry-run;
10. locked security audit and platform requirements; and
11. canonical `composer quality`.

The accepted toolchain baseline is:

```text
PHP             8.5.9
Composer        2.10.2
Git             2.45.1.windows.1
PHPUnit         13.3.0
PHPStan         2.2.8
PHP CS Fixer    3.95.18
```

Record actual versions. A version drift is a blocker unless the architect
explicitly accepts it.

Canonical quality must pass with at least the accepted result:

```text
PHPUnit         204 tests, 3,377 assertions
PHPStan         zero errors
PHP CS Fixer    0 of 113 files fixable
security audit  zero advisories
platform        pass
```

The exact test/assertion count must not be lower or different. Because this
phase makes no content change, a changed count is a mismatch, not an expected
evolution.

If an unchanged fail-closed Composer security command is blocked solely by the
managed environment's network or cache restriction, request the standard
approved access and rerun the identical command. Do not skip, weaken, exclude,
or reinterpret the security gate.

Accepted Composer warning categories remain limited to missing package licenses,
authorized local unbounded development constraints, and the environment's
missing-curl performance warning. Report every warning. Any new category is a
blocker.

The six historical files below are immutable committed records with one
otherwise-empty terminal line beyond their required final newline:

```text
docs/dev_plan/kongono_phase_0N_codex_directive.md
docs/task reports/Phase_0N.md
docs/dev_plan/kongono_phase_0N_1_codex_directive_correction.md
docs/task reports/Phase_0N_1_correction.md
docs/dev_plan/kongono_phase_0N_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0N_3_codex_directive_correction.md
```

Whole-tree text inspection must report exactly those six inherited exceptions
and no other terminal-blank-line or text-integrity finding. Do not edit them.
Because none belongs to the Phase 0V delta, cached and parent-delta whitespace
checks must be fully clean with no exception.

---

# 7. EXACT STAGING OPERATION

No content edit is authorized. After every pre-stage gate passes, construct the
closed staging array from every path in Appendices A and B plus:

```text
docs/dev_plan/kongono_phase_0V_codex_directive.md
```

The array must contain exactly 142 unique literal paths. It must not contain:

```text
docs/task reports/Phase_0V.md
```

Before staging, compare the sorted array against the exact current status path
set excluding only the Phase 0V report. Require zero missing and zero extra
paths.

Stage each literal path from the closed array with:

```powershell
foreach ($path in $phase0VStagePaths) {
    git add -- $path
    if ($LASTEXITCODE -ne 0) {
        throw "Staging failed: $path"
    }
}
```

The variable must be populated only with the literal paths from Appendices A and
B and the Phase 0V directive. This loop is the only authorized staging form.

Do not use `git add .`, `git add -A`, `git add -u`, a wildcard, a directory,
pathspec magic, interactive staging, patch staging, intent-to-add, or an
unverified generated path set.

After staging require exactly:

```text
staged paths                   142
staged modified paths          75
staged added paths             65
staged deleted paths           2
renamed/copied/type-changed    0
mode changes                   0
index entries                  277
index modes                    277 x 100644
unstaged tracked paths         0
untracked paths                1
```

The only untracked path must be the still-zero-byte Phase 0V report.

Prove every staged blob is byte-identical to its pre-stage worktree file. Prove
the two deletions are exactly:

```text
packages/kernel/src/Api/Api.php
packages/kernel/src/Api/ApiStatus.php
```

Run:

```powershell
git diff --cached --check
```

It must return exit code 0 and no finding. The six inherited Phase 0N exceptions
are outside this delta and authorize no cached finding.

The staged diff from the parent must be exactly:

```text
142 paths
75 modified
65 added
2 deleted
0 renamed, copied, type-changed, or mode-changed
22,165 insertions
293 deletions
```

The 65 additions are the 64 Appendix B paths plus the Phase 0V directive. The
zero-byte Phase 0V report is not staged.

---

# 8. STAGED TREE AND COMMIT

Only after every staged-index and quality gate passes, run:

```powershell
git write-tree
```

Record the returned tree hash. It is an output; never predict or invent it.

If `git write-tree` fails solely because the managed sandbox cannot create
`.git/index.lock` or write the Git object store, request approval through the
standard execution escalation and rerun the identical command once. Do not
change permissions, Git configuration, the index, or command arguments.

Before committing verify:

- effective author is `Anthony Iovanna`;
- an effective email exists and is reported redacted;
- no configured signing or hooks override changes the operation;
- no index lock exists; and
- staged tree identity and all Section 7 counts remain exact.

Missing or changed identity, required signing, a custom hook path, or any other
commit-contract ambiguity is a blocker. Do not change Git configuration or add
a bypass.

Create exactly one ordinary non-merge commit on `main` with:

```powershell
git commit -m "Establish Kongono framework baseline through Phase 0U"
```

The commit must have no body or trailers. Do not use `--no-verify`,
`--no-gpg-sign`, `--allow-empty`, `--amend`, a path-limited commit, plumbing
commit creation, merge, rebase, cherry-pick, reset, restore, checkout, revert,
or history rewrite.

If the exact commit is blocked solely by managed Git metadata permissions,
request approval for the identical non-interactive command and execute it once
through the approved path. Do not request broad reusable shell authority.

---

# 9. LOCKED COMMIT OUTCOME

Require exactly:

```text
branch                         main
only local branch              main
reachable commits              6
new commit parents             1
new commit parent              75749a37a184996203e569e039bddbc3ec10cdf7
new commit subject             Establish Kongono framework baseline through Phase 0U
commit body/trailers            none
committed files                277
committed modes                277 x 100644
parent delta paths             142
parent delta modified          75
parent delta added             65
parent delta deleted           2
renames/copies/type changes    0
mode changes                   0
remotes                        0
tags                           0
push                           prohibited
```

The commit tree must exactly equal the recorded `git write-tree` result. The
commit and tree hashes are outputs. Record them exactly and do not invent them.

---

# 10. POST-COMMIT VALIDATION

Before report population, prove:

1. `main` remains the only local branch and now has exactly six reachable
   commits;
2. `HEAD^` equals the locked Phase 0N.4 commit;
3. `HEAD` has exactly one parent and the exact subject with no body/trailers;
4. `HEAD^{tree}` equals the recorded staged tree;
5. the tree contains exactly 277 ordinary `100644` files and no Gitlink,
   symlink, executable, or other mode;
6. the parent delta is exactly the Section 9 142-path inventory and counts;
7. all five earlier commits and trees remain unchanged;
8. `git diff HEAD^ HEAD --check` returns zero with no finding;
9. index and tracked worktree are clean;
10. only the zero-byte Phase 0V report is untracked;
11. remotes and tags remain zero;
12. repository-wide alternate streams remain zero;
13. the six inherited Phase 0N terminal-blank-line exceptions remain
    byte-identical, committed, and the only whole-tree text exceptions;
14. local Markdown links have zero broken targets;
15. diagnostic IDs, schema maps, package topology, source/test inventories,
    public contracts, and all Section 5 boundaries remain exact; and
16. every focused check and canonical `composer quality` result from Section 6
    passes again from committed `HEAD`.

Use `git show`, `git diff-tree`, `git ls-tree`, and direct file inspection to
prove the committed state. Do not infer it from a successful commit message.

If a post-commit check fails, preserve the commit and report `Blocked`. Do not
reset, amend, revert, repair, or create another commit.

---

# 11. REPORT POPULATION

Only after every post-commit gate passes, populate:

```text
docs/task reports/Phase_0V.md
```

The report must remain untracked, unstaged, and absent from `HEAD`. It must be
strict UTF-8 without BOM, LF-only, contain no NUL/replacement/mojibake/conflict
marker/trailing whitespace, and end with exactly one final LF and no extra
terminal blank line.

Use the standard completion-report content required by `AGENTS.md`, expanded to
these exact 22 headings in this order:

```text
1. STATUS
2. SUMMARY
3. BASELINE VERIFIED
4. CALLER RECORDS
5. FILES CREATED
6. FILES MODIFIED
7. FILES DELETED
8. ARCHITECTURAL DECISIONS
9. DEVIATIONS FROM DIRECTIVE
10. DEPENDENCIES ADDED/REMOVED
11. ENVIRONMENT
12. COMMANDS RUN
13. PRE-STAGE VALIDATION RESULTS
14. STAGING AND TREE EVIDENCE
15. COMMIT EVIDENCE
16. POST-COMMIT VALIDATION RESULTS
17. DOCUMENTATION REVIEW
18. SECURITY AND FILE-SAFETY REVIEW
19. WARNINGS / KNOWN ISSUES
20. QUESTIONS / BLOCKERS
21. ACCEPTANCE CHECKLIST AND FINAL GIT STATUS
22. NEXT STEP
```

For successful completion:

- `STATUS` is `Complete`;
- `FILES CREATED`, `FILES MODIFIED`, and `FILES DELETED` describe the checkpoint
  delta as recorded content, and state explicitly that the executor made no
  content edit;
- `ARCHITECTURAL DECISIONS`, `DEVIATIONS FROM DIRECTIVE`, and
  `QUESTIONS / BLOCKERS` say `None`;
- environment includes all six tool versions;
- commands and validation results are exact, including every failure/retry and
  approved access path;
- staging evidence gives the exact 142-path count/classification, 277-entry
  index, cached-check result, staged tree, and diff stat;
- commit evidence gives commit/tree/parent/subject/count/mode/delta proof;
- documentation and security sections record link, encoding, stream, secret,
  and historical-exception results;
- final Git status proves only the populated report is untracked, unstaged, and
  absent from `HEAD`; and
- Section 22 is exactly:

```text
Await architect review before Phase 0W.
```

After the report's last write, verify and return its final byte count and
SHA-256 outside the report. Inside the report, state that a self-digest cannot be
embedded without changing the digest.

---

# 12. PROHIBITED SCOPE

Do not edit, normalize, format, regenerate, or rewrite any accepted file.

Do not run Composer update/install/dump-autoload or any command that mutates the
lock or vendor state. Canonical read-only quality/audit commands are required;
dependency mutation is prohibited.

Do not change Git configuration, identity, signing, hooks, attributes, ignore
rules, line endings, modes, permissions, branches, remotes, tags, or history.
Do not push.

Do not implement or document a renderer, HTML error page, exception handler,
debug mode, production mode, disclosure policy engine, redactor, serializer,
logger, telemetry exporter, incident system, cleanup/retry orchestration,
HTTP/router/middleware, CLI console, frontend, DI/container expansion,
Application Graph expansion, ORM, agents, extensions, deployment, or any later
capability.

Do not add a dependency, public symbol, exception, diagnostic ID, context field,
schema, test, ADR, guide section, or compatibility promise.

The only authorized mutations are exact staging, one exact commit, and final
population of the untracked Phase 0V report after complete success.

---

# 13. STRICT ACCEPTANCE CRITERIA

Every item must pass:

- [ ] All required authority and accepted content is read completely.
- [ ] Pre-caller Git facts, raw hashes, inventories, tracked diff, and
  141-record content manifest are exact.
- [ ] Caller setup adds only the exact Phase 0V directive and zero-byte report.
- [ ] Start state is exactly 77 tracked, 66 untracked, 143 total, zero staged.
- [ ] Phase 0V directive identity matches the architect artifact and the report
  placeholder is exactly zero bytes.
- [ ] All locked Phase 0U/0U.1 and critical source identities are exact.
- [ ] No accepted content is edited, normalized, regenerated, or cleaned up.
- [ ] Pre-stage quality passes with exactly 204 tests/3,377 assertions,
  PHPStan zero, fixer 0/113, zero advisories, and platform pass.
- [ ] Link, package, catalog, schema, source/test inventory, security, encoding,
  ordinary-file, mode, reparse-point, and alternate-stream gates pass.
- [ ] Whole-tree text findings are exactly the six inherited immutable Phase 0N
  terminal-blank-line exceptions and no others.
- [ ] Closed staging array has exactly 142 unique paths and excludes the report.
- [ ] Final index is exactly 75 modified, 65 added, 2 deleted; 277 entries, all
  mode `100644`; zero unstaged tracked paths; only the report untracked.
- [ ] Every staged blob matches its accepted pre-stage bytes and the two exact
  Kernel API paths are the only deletions.
- [ ] Cached whitespace check passes with zero finding.
- [ ] Staged diff has exactly 142 paths, 22,165 insertions, and 293
  deletions with no rename/copy/type/mode change.
- [ ] Recorded staged tree is used unchanged by one exact commit.
- [ ] Commit is the sixth reachable commit, has one exact parent, exact subject,
  no body/trailers, 277 ordinary `100644` files, and exact parent delta.
- [ ] No bypass, configuration, ref, remote, tag, push, or history mutation
  occurs.
- [ ] Post-commit cached/parent-delta, history, tree, mode, file-safety, link,
  catalog, schema, package, focused-test, and canonical quality gates pass.
- [ ] Index and tracked worktree are clean; only the populated Phase 0V report
  remains untracked, unstaged, and absent from `HEAD`.
- [ ] Report has the exact 22 headings, complete evidence, final integrity, and
  exact NEXT STEP.
- [ ] Final report byte count and SHA-256 are returned outside the report.

Any failed or unreached item makes status `Blocked` or `Partial`, never
`Complete`. Before the commit, stop without staging further or populating the
report. After the commit, preserve the commit and do not repair or rewrite it.

---

# 14. FINAL EXECUTOR INSTRUCTION

Verify the exact accepted 141-path Phase 0U.1 baseline, add only this directive
to the checkpoint, keep the Phase 0V report outside it, and stage the exact
142-path closed inventory. Prove the index and tree, create the sixth commit with
the exact subject, rerun all post-commit gates, and only then populate the sole
untracked report.

Make no content change and begin no Phase 0W work.

---

# APPENDIX A - EXACT 77-PATH TRACKED NAME-STATUS INVENTORY

```text
M .php-cs-fixer.dist.php
M ARCHITECTURE.md
M CONTRIBUTING.md
M README.md
M SECURITY.md
M composer.json
M composer.lock
M docs/adr/0003-monorepo-initial-development.md
M docs/adr/0004-microkernel-capability-architecture.md
M docs/adr/0020-kernel-operation-lifecycle-boundary.md
M docs/adr/0021-machine-checkable-api-status.md
M docs/adr/0022-immutable-application-graph-structural-snapshot.md
M docs/adr/0023-explicit-typed-dependency-resolution.md
M docs/adr/0026-actionable-diagnostics-and-error-boundaries.md
M docs/adr/0027-package-local-runtime-diagnostic-metadata.md
M docs/adr/README.md
M docs/architecture/application-graph.md
M docs/architecture/microkernel.md
M docs/architecture/overview.md
M docs/architecture/runtime-model.md
M docs/doctrine.md
M docs/documentation-standard.md
M docs/engineering/README.md
M docs/engineering/api-status.md
M docs/engineering/application-graph-structure.md
M docs/engineering/dependency-injection.md
M docs/engineering/error-handling.md
M docs/engineering/package-layout.md
M docs/engineering/php-style.md
M docs/engineering/static-analysis.md
M docs/engineering/testing.md
M docs/engineering/toolchain.md
M docs/glossary.md
M packages/application-graph/README.md
M packages/application-graph/composer.json
M packages/application-graph/src/Diagnostic/GraphDiagnostic.php
M packages/application-graph/src/Exception/ExceptionInterface.php
M packages/application-graph/src/Exception/GraphException.php
M packages/application-graph/src/Exception/InvalidGraphException.php
M packages/application-graph/src/Model/ApplicationGraph.php
M packages/application-graph/src/Model/Node.php
M packages/application-graph/src/Model/NodeKey.php
M packages/application-graph/src/Model/NodeKind.php
M packages/application-graph/src/Model/Relation.php
M packages/application-graph/src/Model/RelationKind.php
M packages/application-graph/tests/Unit/ApplicationGraphTest.php
M packages/application-graph/tests/Unit/StructuralValueTest.php
M packages/dependency-injection/README.md
M packages/dependency-injection/composer.json
M packages/dependency-injection/src/Contract/ResolverInterface.php
M packages/dependency-injection/src/Definition/ServiceDefinition.php
M packages/dependency-injection/src/DependencyResolver.php
M packages/dependency-injection/src/Diagnostic/DependencyInjectionDiagnostic.php
M packages/dependency-injection/src/Exception/DependencyInjectionException.php
M packages/dependency-injection/src/Exception/ExceptionInterface.php
M packages/dependency-injection/src/Exception/InvalidDefinitionException.php
M packages/dependency-injection/src/Exception/UnknownServiceException.php
M packages/dependency-injection/tests/Unit/DependencyResolverTest.php
M packages/dependency-injection/tests/Unit/ServiceDefinitionTest.php
M packages/kernel/README.md
M packages/kernel/composer.json
D packages/kernel/src/Api/Api.php
D packages/kernel/src/Api/ApiStatus.php
M packages/kernel/src/Contract/KernelInterface.php
M packages/kernel/src/Contract/KernelLifecycleInterface.php
M packages/kernel/src/Contract/OperationLifecycleInterface.php
M packages/kernel/src/Diagnostic/LifecycleDiagnostic.php
M packages/kernel/src/Exception/ExceptionInterface.php
M packages/kernel/src/Exception/KernelException.php
M packages/kernel/src/Exception/LifecycleException.php
M packages/kernel/src/Kernel.php
M packages/kernel/src/Lifecycle/KernelState.php
M packages/kernel/src/Lifecycle/OperationScope.php
M packages/kernel/tests/Unit/KernelTest.php
M phpstan.neon.dist
M phpunit.xml.dist
M tests/Architecture/ApiStatusConventionTest.php
```

Appendix A contains exactly 75 modifications and 2 deletions.

---

# APPENDIX B - EXACT 64-PATH ACCEPTED UNTRACKED INVENTORY

```text
docs/adr/0028-bounded-diagnostic-context-and-disclosure.md
docs/adr/0029-presentation-neutral-diagnostic-value-foundation.md
docs/adr/0030-application-graph-diagnostic-context-integration.md
docs/adr/0031-neutral-api-metadata-ownership-and-acyclic-package-dependencies.md
docs/adr/0032-kernel-lifecycle-diagnostic-context-integration.md
docs/adr/0033-dependency-injection-diagnostic-context-integration.md
docs/dev_plan/kongono_phase_0O_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0O_codex_directive.md
docs/dev_plan/kongono_phase_0P_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0P_codex_directive.md
docs/dev_plan/kongono_phase_0Q_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_2_codex_directive_correction.md
docs/dev_plan/kongono_phase_0Q_codex_directive.md
docs/dev_plan/kongono_phase_0R_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0R_codex_directive.md
docs/dev_plan/kongono_phase_0S_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0S_codex_directive.md
docs/dev_plan/kongono_phase_0T_codex_directive.md
docs/dev_plan/kongono_phase_0U_1_codex_directive_correction.md
docs/dev_plan/kongono_phase_0U_codex_directive.md
docs/task reports/Phase_0N_4_correction.md
docs/task reports/Phase_0O.md
docs/task reports/Phase_0O_1_correction.md
docs/task reports/Phase_0O_2_correction.md
docs/task reports/Phase_0P.md
docs/task reports/Phase_0P_1_correction.md
docs/task reports/Phase_0Q.md
docs/task reports/Phase_0Q_1_correction.md
docs/task reports/Phase_0Q_2_correction.md
docs/task reports/Phase_0R.md
docs/task reports/Phase_0R_1_correction.md
docs/task reports/Phase_0S.md
docs/task reports/Phase_0S_1_correction.md
docs/task reports/Phase_0T.md
docs/task reports/Phase_0U.md
docs/task reports/Phase_0U_1_correction.md
packages/api-metadata/README.md
packages/api-metadata/composer.json
packages/api-metadata/src/Api.php
packages/api-metadata/src/ApiStatus.php
packages/api-metadata/tests/Unit/ApiMetadataTest.php
packages/application-graph/tests/Unit/GraphDiagnosticContextTest.php
packages/dependency-injection/tests/Unit/DependencyInjectionDiagnosticContextTest.php
packages/diagnostics/README.md
packages/diagnostics/composer.json
packages/diagnostics/src/Context/DiagnosticContext.php
packages/diagnostics/src/Context/DiagnosticContextSchema.php
packages/diagnostics/src/Context/DiagnosticDisclosure.php
packages/diagnostics/src/Context/DiagnosticField.php
packages/diagnostics/src/Context/DiagnosticFieldSchema.php
packages/diagnostics/src/Context/DiagnosticValueKind.php
packages/diagnostics/src/Diagnostic/DiagnosticsDiagnostic.php
packages/diagnostics/src/Exception/DiagnosticsException.php
packages/diagnostics/src/Exception/ExceptionInterface.php
packages/diagnostics/src/Exception/InvalidDiagnosticValueException.php
packages/diagnostics/tests/Unit/DiagnosticContextSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticContextTest.php
packages/diagnostics/tests/Unit/DiagnosticDisclosureTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldSchemaTest.php
packages/diagnostics/tests/Unit/DiagnosticFieldTest.php
packages/diagnostics/tests/Unit/DiagnosticValueKindTest.php
packages/diagnostics/tests/Unit/StructuralValueTest.php
packages/kernel/tests/Unit/KernelDiagnosticContextTest.php
```

Appendix B contains exactly 64 additions already accepted before Phase 0V.

---

# APPENDIX C - EXACT STAGING ARRAY CONSTRUCTION

Populate `$phase0VStagePaths` with every Appendix A path without its leading
status token, followed by every Appendix B path, followed by:

```text
docs/dev_plan/kongono_phase_0V_codex_directive.md
```

Before the first `git add`, require:

```powershell
$phase0VStagePaths.Count -eq 142
($phase0VStagePaths | Sort-Object -Unique).Count -eq 142
$phase0VStagePaths -notcontains 'docs/task reports/Phase_0V.md'
```

The executor may copy the literal path blocks into a PowerShell array. It may
not discover staging scope from a wildcard, directory scan, changed-files
command, or current status. Current status is used only to prove equality with
the architect-closed array.
