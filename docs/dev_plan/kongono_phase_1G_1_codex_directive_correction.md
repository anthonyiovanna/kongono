# CODEX IMPLEMENTATION DIRECTIVE - KONGONO PHASE 1G.1 CORRECTION

## Restore Exact Namespace Separators in the Planned Handler Contract

**Directive type:** exact documentation correction

**Executor role:** executor and verifier; not architect

**Repository:** `C:\Users\antho\Dev\kongono`

**Required branch:** `main`

**Date:** 2026-08-15

---

## 1. Mission

Correct exactly four malformed PHP import-example lines across two Phase 1G architecture documents.

Phase 1G selected these exact source-marker imports:

```php
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
```

The executor instead recorded:

```php
use KongonoApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use KongonoDependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
```

in both:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/engineering/error-handling.md
```

Those are invalid FQCNs and contradict the exact alias imports required by the Phase 1G directive. Phase 1G.1 must add only the missing namespace separator after `Kongono` in each of those four lines.

No architectural decision changes. The package, class, method, dependency topology, execution order, return meaning, failure behavior, security ownership, lifecycle rule, current-versus-planned state, and prohibited scope remain exactly as Phase 1G selected them.

---

## 2. Authority and stop rule

### 2.1 Read completely before acting

Read in this order:

1. `AGENTS.md`
2. `ARCHITECTURE.md`
3. `docs/doctrine.md`
4. `docs/documentation-standard.md`
5. `docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md`
6. `docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md`
7. `docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md`
8. `docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md`
9. `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md`
10. `docs/engineering/error-handling.md`
11. `docs/dev_plan/kongono_phase_1G_codex_directive.md`
12. `docs/task reports/Phase_1G.md`
13. this Phase 1G.1 correction directive
14. the zero-byte `docs/task reports/Phase_1G_1_correction.md` placeholder

The Phase 1G directive remains the architectural authority. This correction authorizes only the literal replacements in Section 5 and the final correction report.

### 2.2 Independent correction-directive identity

The architect will provide this correction directive's expected byte count and uppercase SHA-256 outside the file. Verify those independently supplied values before broad validation or mutation.

Do not treat the local file as self-authenticating.

The correction report must initially be an ordinary, untracked, unstaged, zero-byte file absent from `HEAD`:

```text
docs/task reports/Phase_1G_1_correction.md
```

Required initial SHA-256:

```text
E3B0C44298FC1C149AFBF4C8996FB92427AE41E4649B934CA495991B7852B855
```

### 2.3 Mandatory stop rule

At the first mismatch:

1. stop immediately;
2. make no correction;
3. do not stage, commit, reset, restore, clean, stash, or repair anything;
4. keep the Phase 1G.1 report zero bytes; and
5. report the exact expected and observed values with a recommendation.

Do not guess. Do not normalize files. Do not correct adjacent prose. Do not change the original Phase 1G report.

### 2.4 Environment-only retry

One unchanged approved-access retry is allowed only when `composer quality` is blocked solely by managed Composer cache or advisory-network restrictions. No other retry or repository mutation is authorized.

---

## 3. Locked starting state

### 3.1 Git state before caller setup

Before the caller adds the two Phase 1G.1 records:

| Property | Required value |
|---|---:|
| Branch | `main` |
| Local branches | `1` |
| `HEAD` | `8fe793b124384744ac43887037360154c149241a` |
| `HEAD^{tree}` | `9de9388e85e463639f8d7fbbc90ae085f947a1b0` |
| `HEAD^` | `837863d11feda282e4959ef7ec98a810c184531e` |
| Commit count | `8` |
| Index entries | `344`, all stage zero and `100644` |
| Modified tracked paths | `13` |
| Untracked paths | `5` |
| Total status paths | `18` |
| Staged paths | `0` |
| Remotes | `0` |
| Tags | `0` |
| Package directories | `7` |

The exact 13 modified tracked paths are:

```text
ARCHITECTURE.md
README.md
SECURITY.md
docs/adr/0038-fail-closed-trusted-developer-diagnostic-activation.md
docs/adr/0039-typed-trusted-developer-bootstrap-permission-foundation.md
docs/adr/0040-typed-adapter-controlled-delivery-permission-foundation.md
docs/adr/0041-pure-two-permission-trusted-presentation-eligibility.md
docs/adr/README.md
docs/architecture/overview.md
docs/architecture/runtime-model.md
docs/engineering/error-handling.md
docs/engineering/package-layout.md
docs/glossary.md
```

The exact five untracked paths are:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
```

`packages/diagnostic-handler/` must remain absent.

### 3.2 Locked caller records

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/dev_plan/kongono_phase_1F_codex_directive.md` | 32834 | `433EE2ECCD65B24C9CDCE8C3616302695F46359FA59958DA857F716177C4A0ED` |
| `docs/task reports/Phase_1F.md` | 15619 | `A5D0AE6355276FD7E53077ED2048E2BB6A84CAF56D21B6A82B77DC6493C82F01` |
| `docs/dev_plan/kongono_phase_1G_codex_directive.md` | 35789 | `58E9408FDA8734507AC49564D8AEAAFC65EDDA9F99A211468523C4BA2CA2838A` |
| `docs/task reports/Phase_1G.md` | 16449 | `813433CE270B1963E95112D44CB098CE55A039F8F22828157451F1FC930AD21A` |

All four remain immutable. The Phase 1G report is historical execution evidence; Phase 1G.1 records the correction separately.

### 3.3 Locked protected implementation

The 147-file protected implementation/tooling set remains:

```text
aggregate SHA-256: 4D6E7C4F11F86C56065AFBC8999E55BB2132741B80AAF515C482C9219FB19D9E
```

`composer.lock` remains:

```text
bytes: 181650
SHA-256: 6EB8CF5E003C6A17D41C8127057BD4A05C8B984A909BB9B7F07845A712689A8E
```

Canonical quality remains:

```text
PHP CS Fixer: 0 of 135 files fixable
PHPStan: 0 errors
PHPUnit: 247 tests, 4233 assertions
security advisories: 0
platform: pass
```

### 3.4 Expected state after caller setup

After the caller adds this correction directive and zero-byte correction report:

```text
modified tracked paths: 13
untracked paths: 7
total status paths: 20
staged paths: 0
```

The two additional untracked paths must be exactly:

```text
docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md
docs/task reports/Phase_1G_1_correction.md
```

---

## 4. Exact target identities

Before correction:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md` | 13819 | `6626F02E980948CC8FE918E6245E71A346EC1859238CC2F2BEC7F249FA9339C7` |
| `docs/engineering/error-handling.md` | 70937 | `F4BAEC559136C12A949F1C52F3ACC986AF77BB860F6624FD17C535EB0CAF0753` |

After exactly the authorized replacements:

| Path | Bytes | SHA-256 |
|---|---:|---|
| `docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md` | 13821 | `91FE4B01CCC136AB85282AC3D84AC74A06C83A70D92EF6B555B94BF40F26AD80` |
| `docs/engineering/error-handling.md` | 70939 | `049F7FCB978D845ED743DB42CFC5E62B91D62B03A5B920A71B45BE376F764000` |

Each file grows by exactly two bytes: one missing `\` on each of two import lines.

---

## 5. Exact authorized replacements

In each of the two target files, replace this exact line once:

```text
use KongonoApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
```

with:

```text
use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
```

And replace this exact line once:

```text
use KongonoDependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
```

with:

```text
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
```

Required totals across the two files:

```text
old Graph import before: 2
old DI import before: 2
new Graph import before: 0
new DI import before: 0

old Graph import after: 0
old DI import after: 0
new Graph import after: 2
new DI import after: 2
```

Use one narrow `apply_patch` operation. Do not rewrite either file wholesale. Preserve every byte outside these four line replacements.

### 5.1 Reverse-reconstruction proof

After correction, create no repository artifact but prove in memory that replacing the two corrected imports with their malformed predecessors reproduces both complete pre-correction file byte sequences and their Section 4 hashes exactly.

This proof is mandatory. A line-count comparison alone is insufficient.

---

## 6. Prohibited scope

Do not:

- edit any path other than the two Section 4 targets and the final correction report;
- edit the original Phase 1G completion report;
- alter any other word, line wrap, heading, fence, link, blank line, or final newline;
- change the selected package, class, method, source union, parameter order, return type, dependencies, or architecture;
- create `packages/diagnostic-handler/` or any PHP/test file;
- modify a Composer manifest, lockfile, autoload mapping, or tooling configuration;
- run an autoformatter that writes changes;
- implement a handler, adapter, transport, configuration mechanism, evidence evaluator, non-trusted response, or later capability;
- stage, commit, reset, restore, clean, stash, push, branch, tag, rebase, or amend;
- alter a prior directive or report; or
- begin Phase 1H.

---

## 7. Validation gates

### Gate A - authority and baseline

Before broad validation or correction, prove:

- all Section 2.1 authorities were read completely;
- the externally supplied correction-directive identity matches;
- the correction report is the required zero-byte placeholder;
- all four locked caller records match;
- Git state and exact path inventories match Section 3 after caller setup;
- both targets match their pre-correction identities;
- each malformed import occurs exactly once in each target;
- neither corrected import occurs in either target;
- the protected aggregate and lockfile match;
- no Diagnostic Handler package exists; and
- no non-default stream, reparse point, mode change, or staged path exists.

Stop before quality or correction on failure.

### Gate B - pre-correction quality

Run:

```powershell
composer quality
```

It must pass with 247 tests and 4,233 assertions, zero PHPStan errors, zero fixer findings across 135 files, platform success, and zero advisories. Only the already accepted Composer warning categories may appear.

Re-prove Gate A afterward.

### Gate C - exact correction

Apply only Section 5. Then prove:

- both post-correction identities match Section 4;
- all four old-import occurrences are absent;
- each corrected import occurs exactly once in each target;
- the reverse-reconstruction proof passes;
- every byte outside the four exact lines is unchanged;
- no unauthorized path changed;
- both files remain UTF-8 without BOM, LF-only, exactly one final LF, no trailing whitespace, no NUL, ordinary, default-stream-only, non-reparse; and
- `git diff --check` passes.

### Gate D - final quality and architecture proof

Run unchanged:

```powershell
composer quality
```

It must pass with the same exact results as Gate B.

Then prove:

- the four imports are valid and exactly match the Phase 1G directive;
- ADR 0042 and the engineering guide now agree;
- the source union and aliases are semantically coherent;
- all other Phase 1G architecture remains unchanged;
- current-versus-planned language remains correct;
- the protected aggregate, lockfile, caller records, Git identity, index, topology, package count, and absence of implementation remain exact; and
- final status matches Section 8 before report population except that the correction report is still zero bytes.

### Gate E - report

Only after Gates A-D pass, populate `docs/task reports/Phase_1G_1_correction.md` using Section 10. Validate it afterward and return its final byte count and uppercase SHA-256 outside the report body.

---

## 8. Exact final repository state

After report population:

```text
branch: main
HEAD: 8fe793b124384744ac43887037360154c149241a
HEAD tree: 9de9388e85e463639f8d7fbbc90ae085f947a1b0
commit count: 8
modified tracked paths: 13
untracked paths: 7
total status paths: 20
staged paths: 0
package directories: 7
remotes: 0
tags: 0
```

The modified tracked inventory remains the 13 Phase 1G paths in Section 3.1.

The exact untracked inventory is:

```text
docs/adr/0042-transport-neutral-trusted-diagnostic-handler.md
docs/dev_plan/kongono_phase_1F_codex_directive.md
docs/dev_plan/kongono_phase_1G_codex_directive.md
docs/dev_plan/kongono_phase_1G_1_codex_directive_correction.md
docs/task reports/Phase_1F.md
docs/task reports/Phase_1G.md
docs/task reports/Phase_1G_1_correction.md
```

---

## 9. Acceptance criteria

Phase 1G.1 passes only if:

1. all required authorities were read completely;
2. the external correction-directive identity matched;
3. the initial report placeholder and all caller records matched;
4. the exact Git and file baseline matched;
5. pre-correction quality passed with 247 tests and 4,233 assertions;
6. both targets matched their locked starting identities;
7. exactly four literal line replacements were made;
8. the two corrected file identities match Section 4;
9. all malformed import occurrences are absent;
10. both correct imports occur once per target;
11. reverse reconstruction reproduces both starting byte sequences;
12. every non-target byte and every other repository path is unchanged except the final report;
13. ADR 0042 and the engineering guide now match the exact Phase 1G imports;
14. no architecture, implementation, dependency, package, test, runtime, or later capability changed;
15. encoding, endings, whitespace, NUL, ordinary-file, stream, reparse, link, and security gates pass;
16. the protected aggregate and lockfile remain exact;
17. final canonical quality passes with 247 tests and 4,233 assertions;
18. Git history, index, branch, modes, remotes, and tags remain unchanged;
19. final status is exactly 13 modified, seven untracked, 20 total, and zero staged;
20. the original Phase 1G report remains byte-identical historical evidence;
21. the correction report is populated only after every other gate passes and remains outside `HEAD`; and
22. no prohibited operation occurred.

No partial pass is acceptable.

---

## 10. Standard correction completion report

Use exactly these headings in order:

```markdown
# PHASE 1G.1 CORRECTION TASK COMPLETION REPORT

## 1. STATUS

## 2. SUMMARY

## 3. BASELINE VERIFIED

## 4. CALLER RECORDS

## 5. FILES CREATED

## 6. FILES MODIFIED

## 7. EXACT CORRECTION

## 8. ARCHITECTURAL DECISIONS

## 9. DEVIATIONS FROM DIRECTIVE

## 10. DEPENDENCIES ADDED OR REMOVED

## 11. ENVIRONMENT

## 12. COMMANDS RUN

## 13. VALIDATION RESULTS

## 14. REVERSE-RECONSTRUCTION EVIDENCE

## 15. DOCUMENTATION REVIEW

## 16. SECURITY AND FILE-SAFETY REVIEW

## 17. WARNINGS AND KNOWN ISSUES

## 18. QUESTIONS OR BLOCKERS

## 19. FINAL GIT STATUS

## 20. ACCEPTANCE CRITERIA AND NEXT STEP
```

The report must include exact before/after identities, occurrence counts, reverse-reconstruction proof, quality results, protected-set proof, caller-record proof, final Git inventory, and a criterion-by-criterion result.

The final nonblank line must be exactly:

```text
Await architect review before Phase 1H.
```

Return the report's final byte count and uppercase SHA-256 separately after completion.

---

## 11. Required final outcome

Phase 1G.1 restores the two valid source-package namespace imports in both authoritative examples and changes nothing else.

After correction, the planned handler contract consistently names:

```php
Kongono\ApplicationGraph\Exception\ExceptionInterface
Kongono\DependencyInjection\Exception\ExceptionInterface
Kongono\Kernel\Exception\ExceptionInterface
```

The repository still has seven implemented packages, no Diagnostic Handler code, and no runtime change. Phase 1H remains blocked until architect acceptance of the correction report.
