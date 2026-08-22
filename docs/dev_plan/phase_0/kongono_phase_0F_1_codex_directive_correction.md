# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0F.1

## Completion-report conformance correction only

## Mission

Correct the Phase 0F executor completion report. Do not change the Phase 0F implementation.

The Phase 0F production work has independently passed its quality gate and appears technically conformant. This correction exists because the returned report did not satisfy the exact reporting contract required by Phase 0F.

The correction is limited to:

- re-inspecting the current repository without changing implementation state;
- rerunning the required non-mutating verification;
- returning a truthful, UTF-8, correctly structured Phase 0F.1 correction report;
- explicitly recording the historical directive-copy discrepancy and the defects in the original report.

Do not begin Phase 0G.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the correction executor, not the architect.

Read completely before acting:

1. `AGENTS.md`;
2. the accepted Kongono doctrine and repository guidance required by the Phase 0F directive;
3. `docs/dev_plan/kongono_phase_0F_codex_directive.md`;
4. `docs/task reports/Phase_0F.md`;
5. this Phase 0F.1 correction directive;
6. every current Phase 0F implementation file needed to verify the claims you report.

This directive authorizes no implementation, documentation, dependency, tooling, lockfile, test, Git-history, or architecture change.

If verification reveals an implementation defect, unexpected repository mutation, or conflict with the Phase 0F authority:

```text
STOP
REPORT
DO NOT FIX
```

Return `Blocked` or `Partial`, identify the exact issue, and await architect direction. Do not infer authority to repair it.

---

# 2. REVIEW FINDING TO CORRECT

The original archived report at `docs/task reports/Phase_0F.md` is not an acceptable Phase 0F completion report even though its implementation evidence is largely substantive.

The correction must acknowledge all of these defects:

1. none of the mandatory report sections is numbered `1` through `22`;
2. the mandatory section `22. NEXT STEP` is absent;
3. the exact required next-step sentence is absent;
4. several section labels are fused to their first content line, including `SUMMARY`, `BASELINE OBSERVED`, `FILES MODIFIED`, and other labels;
5. the fixture inventory gives bare filenames rather than every exact repository-relative fixture path and single purpose;
6. the archived text contains the three-character mojibake sequence represented by Unicode code points `U+00E2 U+20AC U+201D` instead of clean punctuation;
7. its final checklist says the report contains all required numbered sections even though it contains zero numbered section headers;
8. it therefore cannot truthfully claim complete reporting conformance.

Do not edit or replace the archived `Phase_0F.md` file. It is a preserved caller-managed record of the original return.

---

# 3. CURRENT ACCEPTED IMPLEMENTATION EVIDENCE

At architect review time, independent checks observed:

```text
Branch: main
Only branch: main
HEAD: 136822352a0caac199506cbddb4c9a539001c592
HEAD tree: 88d0f199e95e6891739df5c4a4d7cc139ab12cae
Commit count: 1
Parent count: 0
Remotes: 0
Tags: 0
Staged paths: 0
Authorized tracked modifications: 27
Authorized Phase 0F created implementation paths: 68
Dependency Injection fixture files: 55
Dependency Injection production source files: 7
Full quality result: PASS
PHPUnit: 111 tests, 472 assertions
PHPStan: zero errors
PHP CS Fixer: 89 files clean
Locked Composer audit: no security vulnerability advisories
```

These observations are review evidence, not permission to copy claims without rerunning the relevant checks. Distinguish your own verification from this supplied baseline.

The implementation boundary remains exactly Phase 0F:

- one `kongono/dependency-injection` package;
- seven production types;
- explicit canonical interface contracts;
- explicit implementations and ordered constructor dependencies;
- validation without autowiring;
- deterministic complete-graph validation;
- transient construction on every edge;
- no resolved-instance cache;
- the exact package exception boundary and API statuses;
- no PSR-11, shared/scoped lifetime system, Application Graph integration, Kernel-owned resolver behavior, or later capability.

---

# 4. CALLER-MANAGED RECORDS

Before this correction directive is copied into the repository, the current historical caller records are:

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
```

The archived repository copy of the Phase 0F directive is five bytes longer than the architect-delivered artifact because it has the standalone text `Read` plus a newline appended after the directive's final sentence. The architect-delivered artifact was:

```text
71,065 bytes
SHA-256 E9A855123C663E65E1CBCCE827C309BAE3729F59FA2703AC373E3E924C0CAB4F
```

Treat the 71,070-byte repository file as the historical caller-supplied input that was actually present during Phase 0F execution. Preserve it byte-for-byte. State plainly that the trailing token changed no authorized scope and that Phase 0F implementation followed the preceding complete directive contract. Do not silently claim the repository copy is byte-identical to the architect-delivered artifact.

The caller will provide these two new correction records before execution:

```text
docs/dev_plan/kongono_phase_0F_1_codex_directive_correction.md
    non-empty caller-supplied copy of this directive

docs/task reports/Phase_0F_1_correction.md
    zero-byte caller-managed report placeholder
```

Read the correction directive completely. Preserve all five caller-managed records. Do not edit, stage, commit, rename, remove, or populate any of them. Return the report in your response; the caller will archive it afterward.

If the new correction report placeholder is not zero bytes before execution, stop and report its size and SHA-256.

---

# 5. PROHIBITED MUTATIONS

This is a report-only correction.

Do not:

- edit any PHP source, test, fixture, manifest, lockfile, tooling configuration, ADR, documentation, directive, or task report;
- add, remove, rename, or move any implementation path;
- run a Composer update;
- run a formatter in write mode;
- alter dependencies, autoloading, security policy, test configuration, static-analysis configuration, or API metadata;
- stage or unstage;
- commit, amend, reset, restore, checkout, clean, stash, rebase, branch, tag, add a remote, or push;
- change Git configuration;
- create Phase 0G work;
- attempt to improve implementation while performing this correction.

Read-only test tooling may create ignored local caches through its normal operation. Do not add them to status, and report any unexpected artifact.

---

# 6. REQUIRED VERIFICATION

Before verification, record:

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
git remote
git tag
git status --short --untracked-files=all
git diff --cached --exit-code
```

Create a task-specific pre-verification SHA-256 inventory outside the repository for:

- all current tracked files;
- all current untracked paths;
- the five caller-managed records after the caller supplies the two correction files.

Then verify without mutation:

1. current status has the exact 27 authorized tracked modifications, 68 authorized Phase 0F created implementation paths, and five caller-managed records;
2. no staged path exists;
3. HEAD, tree, branch set, commit count, parent count, subject, remotes, and tags still match Section 3;
4. the three historical caller-record sizes and hashes in Section 4 match;
5. the new correction directive is non-empty and the new report placeholder is zero bytes;
6. the package contains exactly seven production source declarations and 55 fixture files;
7. every fixture path is under `packages/dependency-injection/tests/Fixture/`, uses the required test namespace, contains one named class-like declaration, and matches its filename;
8. the created and modified inventories match the original Phase 0F directive;
9. no dependency or production import exists between Dependency Injection and Application Graph;
10. production Kernel imports remain limited to `Api` and `ApiStatus` metadata;
11. the exact 27-production-type and API-status inventory remains correct;
12. the exact public-method, final, readonly, abstract, and exception relationships remain correct;
13. all original Phase 0F prohibited-scope categories remain absent;
14. all local Markdown links resolve;
15. all implementation text files pass UTF-8, final-newline, trailing-whitespace, conflict-marker, and zero-byte checks;
16. no secret, unexpected machine-local path, binary, symlink, junction, executable mode, nested repository, generated package artifact, or unexpected cache is present;
17. `git diff --check` passes;
18. `composer quality` passes, including the real locked security audit;
19. focused Dependency Injection PHPUnit still passes;
20. the final repository state is byte-identical to the pre-verification state, apart from ignored tool caches that do not enter status.

If the restricted environment blocks the network-backed locked audit, use the authorized access mechanism and rerun the unchanged command. Do not skip or weaken it.

At completion rerun and report:

```text
git diff --check
git diff --cached --check
git diff --cached --exit-code
git status --short --untracked-files=all
git branch --show-current
git for-each-ref --format="%(refname:short)" refs/heads
git rev-parse HEAD
git rev-parse HEAD^{tree}
git rev-list --count HEAD
git rev-list --parents -n 1 HEAD
git log -1 --format=%s
git remote
git tag
```

Compare the pre-verification and final SHA-256 inventories. No non-cache file may change.

---

# 7. REPORT ENCODING AND FORMAT RULES

Return plain UTF-8 text with a final newline.

Use ASCII hyphen-minus (`-`) for list markers and separators. Do not use a character that can become mojibake when copied. Do not emit the code-point sequence `U+00E2 U+20AC U+201D`, replacement characters, NUL bytes, tabs, trailing whitespace, or fused headings.

Every numbered section heading must:

- begin at the start of its own line;
- use the exact number and title below;
- be followed by a newline before its content.

Include all 22 sections exactly once and in order. Do not omit, merge, rename, or renumber a section.

List every one of the 55 fixture files as its full repository-relative path and give one concise purpose for each. Do not group multiple fixture files on one line.

Do not state that a report-format criterion passes unless the returned report itself visibly satisfies it.

---

# 8. REQUIRED PHASE 0F.1 CORRECTION REPORT

Return exactly this structure with complete verified content:

```text
PHASE 0F.1 CORRECTION TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
- report-only correction performed
- implementation mutation status
- Phase 0F technical verification result

3. BASELINE OBSERVED
- environment and tool versions
- branch, HEAD, tree, parent count, commit count, and subject
- tracked files and modes
- index and worktree classification
- remotes and tags
- all five caller-managed records, sizes, hashes, and roles
- historical Phase 0F directive-copy discrepancy

4. FILES CREATED
- no executor-created file, or exact unexpected path
- complete full-path list of the existing 55 Phase 0F fixtures and one purpose per fixture

5. FILES MODIFIED
- no executor modification
- exact existing 27 Phase 0F tracked modifications verified

6. FILES MOVED / REMOVED
None, or exact unexpected paths.

7. PACKAGE, DEPENDENCY, AND AUTOLOAD CONTRACT
- verified Phase 0F package and root integration
- lock and external-package state
- Kernel metadata and Graph separation
- development/no-dev evidence available from the original run and current checks actually rerun

8. PRODUCTION SYMBOL AND API INVENTORY
- seven new FQCNs and exact statuses
- exact total production count
- exact shape and public-method results

9. SERVICE-DEFINITION CONTRACT
- verified identity, implementation, dependency, constructor, immutability, reflection, and autoload boundaries

10. RESOLUTION AND LIFECYCLE INVARIANTS
- verified deterministic validation, resolution order, transient identity, failure propagation, retained state, and integration boundaries

11. EXCEPTION CONTRACT
- hierarchy, throw categories, marker separation, and unchanged external failure behavior

12. TEST RESULTS
- focused and full commands
- tests, assertions, outcomes, and verification distinction

13. COMPOSER AND SECURITY RESULTS
- manifest, lock, platform, audit, dependency, plugin, warning, and policy results actually verified

14. STATIC ANALYSIS, STYLE, AND QUALITY RESULTS
- exact versions, level, strict rules, file count, result counts, dispatch order, and policy-preservation checks

15. DOCUMENTATION AND ADR RESULTS
- implementation documentation and ADR verification
- local-link and implemented-versus-planned review

16. FILE-SAFETY AND PROHIBITED-SCOPE AUDIT
- encoding, whitespace, links, secrets, modes, generated artifacts, and every prohibited-scope category
- explicit acknowledgement of the original report's mojibake and this report's clean encoding

17. COMMANDS RUN
- exact commands in execution order
- access or environment distinctions

18. ARCHITECTURAL DECISIONS AND DEVIATIONS
- no new decision or implementation deviation
- exact reporting corrections and historical directive-copy disclosure

19. QUESTIONS / BLOCKERS
None, or exact architect action required.

20. GIT STATUS SUMMARY
- exact final status classification
- pre/post inventory comparison
- HEAD/tree/index/branch/remotes/tags confirmation
- no mutation/stage/commit/push/configuration change

21. ACCEPTANCE CHECKLIST
- PASS / FAIL for every Phase 0F Section 18 criterion
- PASS / FAIL for every Phase 0F.1 correction criterion
- report structure self-check confirming headings 1 through 22 are visibly present once and in order

22. NEXT STEP
Await architect review before Phase 0G.
```

For item 22, state only:

> Await architect review before Phase 0G.

Do not append content after item 22.

---

# 9. CORRECTION ACCEPTANCE CRITERIA

Phase 0F.1 is complete only if:

- [ ] no implementation or caller-managed file was edited;
- [ ] no file was staged, committed, moved, removed, or added by the executor;
- [ ] the exact repository and implementation inventories were verified;
- [ ] the independent full quality gate passed with the locked audit;
- [ ] focused Dependency Injection tests passed;
- [ ] all Phase 0F architectural and prohibited-scope boundaries remain intact;
- [ ] the original report defects are explicitly acknowledged;
- [ ] the historical directive-copy discrepancy is accurately disclosed without rewriting the record;
- [ ] the returned report is clean UTF-8 with no mojibake or fused headings;
- [ ] every fixture appears as one full repository-relative path with one purpose;
- [ ] all 22 exact numbered sections appear once, in order;
- [ ] section 21 truthfully self-checks the returned report;
- [ ] section 22 contains only the exact required sentence;
- [ ] no Phase 0G work began.

If any item fails, use `Partial` or `Blocked`.

---

# 10. FINAL INSTRUCTION

Perform a non-mutating Phase 0F verification and return the conforming Phase 0F.1 correction report.

Do not repair, refactor, format, or otherwise change the implementation. Do not edit the archived Phase 0F directive or report. Do not begin Phase 0G.
