# CODEX CORRECTION DIRECTIVE - KONGONO PHASE 0M.1

## Dual-Resolver Fixture Boundary Correction and Phase 0M Resumption

## Mission

Correct one omission in the Phase 0M file boundary, resolve the already observed type-analysis findings, and resume the original Phase 0M implementation to completion.

The blocker is accepted as legitimate. `DualResolverService` is a deliberately invalid consumer fixture used to prove that a service implementation exposing Kongono's resolver contract is rejected. Its `resolve()` body is not a Dependency Injection package failure boundary and must not fabricate a reserved Kongono diagnostic. Replace its fixture-only `InvalidDefinitionException` with native `RuntimeException` while preserving the message input.

This correction supersedes Phase 0M Section 5 only by authorizing one additional test fixture. Every other Phase 0M contract remains in force.

---

# 1. ROLE, AUTHORITY, AND STOP RULE

You are the Phase 0M.1 correction executor, not the architect.

Read completely:

1. `AGENTS.md` and all repository authority;
2. the original Phase 0M directive;
3. the blocked Phase 0M report;
4. the live partial Phase 0M diff;
5. this correction directive.

Do not discard correct partial work, restart Phase 0M, weaken the diagnostic constructor, invent a fixture diagnostic, add a suppression, or change the analysis configuration.

Stop if the live partial state contains a path outside the original Phase 0M boundary, differs materially from the blocked report, or cannot be reconciled without another architectural choice.

---

# 2. ACCEPTED BLOCKER AND DECISION

The original Phase 0M contract changed `InvalidDefinitionException` construction from:

```php
new InvalidDefinitionException(string $message)
```

to the exact owning-catalog form inherited from `DependencyInjectionException`:

```php
new InvalidDefinitionException(
    DependencyInjectionDiagnostic $diagnostic,
    string $message,
    ?Throwable $previous = null,
)
```

The previously prohibited fixture still contains:

```php
throw new InvalidDefinitionException($contract);
```

Do not assign a reserved DI identifier to it. None of ADR 0026's 25 DI reasons describes execution of this intentionally invalid fixture method.

The locked correction is:

```php
use RuntimeException;

// ...

throw new RuntimeException($contract);
```

This preserves the fixture's supplied message, keeps the body non-returning, and reinforces the accepted rule that consumer-code failures are native and do not implement Kongono's package marker or diagnostic metadata contract.

Rejected alternatives:

- adding any `DependencyInjectionDiagnostic` case or reusing an unrelated identifier;
- relaxing the exact production exception constructor;
- keeping the stale exception call through variadics, defaults, unions, or overload-like behavior;
- suppression, baseline, exclusion, ignored error, or PHPStan configuration change;
- changing the fixture's interfaces or its purpose;
- converting another consumer fixture to a Kongono exception.

---

# 3. LOCKED REPOSITORY AND PARTIAL-WORK BASELINE

Require:

```text
Branch: main
HEAD: 5027939d3de2dd508c74ae6007b9dc5bb524e3a8
HEAD tree: 432a0121a87b225de44e9daa301602be79ebd94c
Commit count: 4
Staged paths: 0
Remotes: 0
Tags: 0
```

Before Phase 0M.1 caller setup, the accepted partial state is:

```text
Tracked modifications: 32
Untracked paths: 10
Total status paths: 42
Tracked diff: 32 files changed, 1,186 insertions, 200 deletions
```

The three authorized diagnostic enum files exist. ADR 0027 and the Phase 0M documentation reconciliation do not yet exist. The original Phase 0M report is populated with the legitimate Blocked report.

Exact caller evidence:

```text
docs/dev_plan/kongono_phase_0M_codex_directive.md
    42,507 bytes
    SHA-256 F67D90AA79F3849555DE06CABF6EC25C8C58C47C999402B2B9404BA98F546ED5

docs/task reports/Phase_0M.md
    2,611 bytes
    SHA-256 6AF1B0B6CA9B742A467AADE7B0CF4A16C1FA0B412CA1BC2F4F08EAB8161D2A1F
```

Preserve both byte-for-byte. The blocked Phase 0M report is historical evidence and must not be overwritten with the correction result.

Independently inventory and hash all partial files before editing. Confirm every changed path is within the original Phase 0M boundary and that the implemented production/catalog behavior matches the original directive before resuming.

---

# 4. PHASE 0M.1 CALLER RECORDS

The caller will add:

```text
docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md
docs/task reports/Phase_0M_1_correction.md
```

The correction directive must be a non-empty, byte-identical copy of this artifact. Record size and SHA-256 and never edit it.

The correction report must initially be exactly zero bytes. Populate it only after the resumed Phase 0M implementation and all validation pass. Keep it untracked and unstaged.

After caller setup, expect:

```text
Tracked modifications: 32
Untracked paths: 12
Total status paths: 44
Staged paths: 0
```

No other pre-resumption path is authorized.

---

# 5. EXACT FILE-BOUNDARY CORRECTION

Add exactly one file to the original Phase 0M modification boundary:

```text
packages/dependency-injection/tests/Fixture/DualResolverService.php
```

Modify it only to:

1. remove `use Kongono\DependencyInjection\Exception\InvalidDefinitionException;`;
2. add `use RuntimeException;`;
3. replace `throw new InvalidDefinitionException($contract);` with `throw new RuntimeException($contract);`.

No other line, type, interface, template annotation, method signature, or message expression in that fixture may change.

The original Phase 0M boundary remains exact for all other production, test, and documentation work. This correction authorizes no second fixture and no new production file, exception, diagnostic, dependency, or API.

After this correction, Phase 0M has exactly 39 authorized content paths: its original 38 plus this fixture.

---

# 6. REQUIRED RESOLUTION OF OBSERVED PHPSTAN FINDINGS

The following findings were independently reproduced. Resolve them only inside the original six authorized Phase 0M test files, except for the newly authorized fixture.

## `StructuralValueTest.php`

For `sortedMethodNames()`, retain `array` as the native return type and add:

```php
/**
 * @param list<\ReflectionMethod> $methods
 *
 * @return list<string>
 */
```

Do not widen the result or suppress `missingType.iterableValue`.

## `KernelTest.php`

For `sortedMethodNames()`, add the same exact `@return list<string>` contract.

Replace the six arrow functions passed to `assertLifecycleDiagnostic()` with block closures that explicitly return `void`, invoke the same operation exactly once, and capture only the required variables. Change the helper PHPDoc to `@param callable(): void $operation`.

Do not alter the tested lifecycle state, diagnostic mapping, message, or assertions.

## `DependencyResolverTest.php`

The requested-autoloader failure is already the exact known native `RuntimeException`. Retain identity proof and replace the impossible `method_exists($caught, 'diagnosticId')` assertion with package-boundary proof:

```php
self::assertNotInstanceOf(ExceptionInterface::class, $caught);
```

Do not add a diagnostic-capability interface or wrap the native failure.

## `ApiStatusConventionTest.php`

`BackedEnum::$value` is already known to be a string for the three catalogs. Remove only the useless `(string)` cast and return `$diagnostic->value` directly.

## Newly authorized fixture

Apply Section 5 exactly. It resolves both the constructor-count and argument-type findings without assigning a false diagnostic.

No suppressions, baselines, inline type overrides, configuration changes, excluded paths, meaningless casts, or weakened return types are permitted.

---

# 7. RESUME THE ORIGINAL PHASE 0M

After applying Sections 5 and 6:

1. rerun focused DI tests and PHPStan immediately;
2. if they pass, continue from the partial state rather than recreating completed work;
3. complete ADR 0027 and all original Phase 0M documentation reconciliation;
4. re-audit every production enum, exception base, marker, and throw mapping against the original directive;
5. complete every original focused, static, documentation, security, and canonical-quality gate;
6. write the result only to the Phase 0M.1 correction report.

All original Phase 0M requirements remain binding, including:

- exact three Internal catalogs and 42 mappings;
- exact three Public marker accessors;
- exact abstract-base constructor and immutable metadata contract;
- unchanged deliberate messages and native cause behavior;
- package-marker separation;
- no broad wrapping;
- production count 30, test-file count 62, syntax inventory 93, expected style inventory 92;
- at least 130 meaningful tests and more than 567 assertions;
- ADR 0027 and exact documentation reconciliation;
- no renderer, HTTP/CLI mapping, logging, telemetry, retry, cleanup, incident, localization, context bag, dependency, Composer, tooling, or Git-history work.

---

# 8. EXPECTED FINAL STATUS

Before populating the Phase 0M.1 report, expect exactly:

```text
Tracked modifications: 35
Untracked paths: 13
Total status paths: 48
Staged paths: 0
```

The 35 tracked modifications are the original final Phase 0M union of 34 plus:

```text
packages/dependency-injection/tests/Fixture/DualResolverService.php
```

The 13 untracked paths are the original final 11 plus:

```text
docs/dev_plan/kongono_phase_0M_1_codex_directive_correction.md
docs/task reports/Phase_0M_1_correction.md
```

After report population, counts remain the same and the correction report becomes nonzero. The original blocked Phase 0M report remains unchanged.

Any additional path is a blocker.

---

# 9. VALIDATION AND ACCEPTANCE

Run and report:

```text
all 93 first-party PHP/configuration syntax checks
KernelTest.php
all Application Graph unit tests
all Dependency Injection unit tests
ApiStatusConventionTest.php
PHPStan with zero errors
PHP CS Fixer dry-run with expected 92-file inventory
root and package Composer validation
platform requirements
locked security audit
canonical composer quality
all original Phase 0M catalog, throw-site, reflection, message, cause, link, encoding, and prohibited-scope audits
```

Explicitly prove:

- `DualResolverService.php` throws native `RuntimeException($contract)`;
- it no longer imports or constructs a Kongono exception;
- its class/interface/template/method shape otherwise remains unchanged;
- no reserved diagnostic was added or reused for the fixture;
- consumer-constructor and registered-autoloader failures still propagate unchanged and without DI metadata;
- all 11 reproduced PHPStan findings are gone;
- no suppression, baseline, exclusion, configuration, dependency, or tooling change exists;
- all original Phase 0M acceptance criteria pass;
- HEAD, tree, commit count, branch, index, remotes, tags, and history remain unchanged;
- the original Phase 0M directive and blocked report retain their exact hashes.

Phase 0M.1 is accepted only if the resumed Phase 0M is fully complete. A clean fixture alone is not completion.

---

# 10. PROHIBITED SCOPE

Do not:

- assign a Kongono diagnostic to `DualResolverService::resolve()`;
- change the production constructor or catalogs to accommodate a test fixture;
- change `DualResolverServiceInterface` or any other fixture;
- add a suppression, baseline, cast workaround, ignored error, or configuration exception;
- discard or rewrite correct partial work;
- overwrite the blocked Phase 0M report;
- modify the original Phase 0M or Phase 0M.1 directives;
- expand any production, documentation, package, dependency, tooling, or runtime boundary;
- stage, commit, branch, tag, add a remote, push, reset, restore, or rewrite history;
- begin Phase 0N.

---

# 11. CORRECTION COMPLETION REPORT

Write the final correction report only to:

```text
docs/task reports/Phase_0M_1_correction.md
```

Put the entire report inside one outer `text` fence with no text outside it. Use exactly the repository-standard 16 sections:

```text
1. STATUS
2. SUMMARY
3. FILES CREATED
4. FILES MODIFIED
5. ARCHITECTURAL DECISIONS
6. DEVIATIONS FROM DIRECTIVE
7. DEPENDENCIES ADDED/REMOVED
8. ENVIRONMENT
9. COMMANDS RUN
10. VALIDATION RESULTS
11. DOCUMENTATION REVIEW
12. WARNINGS / KNOWN ISSUES
13. QUESTIONS / BLOCKERS
14. GIT DIFF SUMMARY
15. ACCEPTANCE CRITERIA
16. NEXT STEP
```

Mandatory evidence:

- exact baseline, partial-state, and final path inventories;
- original and correction directive/report sizes and hashes;
- exact one-file boundary expansion and three-line semantic fixture change;
- all 11 PHPStan findings and their exact non-suppressive resolutions;
- all original Phase 0M created/modified files and runtime contracts;
- exact 42-case catalog, throw mapping, reflection, API-status, message, cause, propagation, and security evidence;
- exact test/assertion and tool results;
- proof of no dependency, manifest, lockfile, tooling, suppression, or Git mutation;
- final correction-report size and SHA-256 in the accompanying response after its last write;
- Section 16 exactly: `Await architect review before Phase 0N.`

Keep the correction report untracked and unstaged. Preserve the original blocked report.

---

# 12. FINAL EXECUTOR INSTRUCTION

Apply the exact native fixture correction, resolve the known analysis findings within their already authorized test files, and resume Phase 0M to full completion.

Do not misclassify consumer fixture behavior as a Kongono diagnostic. Do not weaken the production contract. Do not expand scope beyond this exact one-file correction and the original Phase 0M work.
