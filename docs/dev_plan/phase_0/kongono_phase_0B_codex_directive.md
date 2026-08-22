# CODEX IMPLEMENTATION DIRECTIVE — KONGONO PHASE 0B

## Engineering Toolchain & Quality Foundation

### Mission

You are the **implementation executor** for **Kongono Framework**.

Phase 0A has been architecturally accepted.

The repository currently contains governance, architectural doctrine, documentation standards, glossary material, ADRs, and a minimal Composer manifest. It intentionally contains **no framework implementation**.

The development environment has now been verified as:

```text
PHP:      8.5.9 CLI
Composer: 2.10.2
Git:      2.45.1.windows.1
```

This satisfies the runtime prerequisite for beginning executable Kongono development.

Your responsibility in **Phase 0B** is to establish Kongono's permanent PHP engineering toolchain, quality gates, dependency-security policy, testing foundation, static-analysis policy, coding-style policy, and contributor commands.

You are **not authorized to implement Kongono framework functionality yet**.

This phase creates the engineering system that future Kongono source code will be required to pass.

---

# 1. ROLE AND AUTHORITY — LOCKED

You remain:

> **Executor, not architect.**

All rules from the existing `AGENTS.md` remain in force.

If this directive conflicts with an existing Phase 0A document:

**STOP. DO NOT RECONCILE THE CONFLICT YOURSELF.**

Report the conflicting statements and request architect direction.

## Mandatory stop rule

If completing this task requires making an architectural, security, public-API, package-boundary, dependency, protocol, runtime, compatibility, licensing, or data-model decision not explicitly covered here:

**STOP. DO NOT GUESS.**

Report:

1. The exact decision required.
2. Why the task cannot safely proceed without it.
3. The viable options.
4. Tradeoffs of each option.
5. Your recommendation, clearly labeled as a recommendation only.

Do not implement the recommendation until approval is returned.

---

# 2. PHASE 0B GOAL

At the end of this phase, the following command must represent Kongono's local PHP quality gate:

```bash
composer quality
```

It must deterministically check, in an appropriate order:

```text
Composer manifest validity
Dependency security
Platform requirements
Coding style
Static analysis
Tests
```

The repository must remain free of actual Kongono framework implementation.

---

# 3. TOOLCHAIN DECISIONS — LOCKED

## 3.1 Static analysis

Use:

```text
phpstan/phpstan ^2.2
phpstan/phpstan-strict-rules ^2.0
```

Kongono static analysis must run at:

```text
level: max
```

The analysis target PHP version must represent Kongono's **minimum supported PHP version**, not merely the currently installed patch version:

```text
PHP 8.5.0
```

Kongono starts with **zero PHPStan baseline**.

Do not create baselines, blanket exclusions, `ignoreErrors`, or suppressions merely to make analysis pass.

If PHPStan reports a genuine issue, fix it.

If PHPStan reports what appears to be a false positive that cannot be solved without degrading code:

**STOP and report it.**

Do not suppress it without architect approval.

---

## 3.2 Test runner

Use:

```text
phpunit/phpunit ^13.2
```

PHPUnit is the **official test runner for Kongono's own framework repository**.

This decision applies to Kongono core development.

It does **not** decide which test frameworks applications built with Kongono may use in the future.

Do not add Pest during Phase 0B.

Do not add alternative test frameworks.

---

## 3.3 Coding-style tool

Use:

```text
friendsofphp/php-cs-fixer ^3.95
```

The baseline coding standard is:

```text
@PER-CS
```

Do not target a deprecated version-specific alias such as `@PER-CS3.0`.

Use the current stable `@PER-CS` ruleset.

Only the additional rules explicitly authorized in this directive may be added during Phase 0B.

---

## 3.4 Tools explicitly deferred

Do **not** add:

```text
Rector
Infection / mutation testing
Psalm
PHP_CodeSniffer
Pest
Deptrac
architecture-test packages
benchmark frameworks
coverage services
code-quality SaaS SDKs
Git hooks
pre-commit frameworks
Node-based linters
Rust tooling
CI/CD
GitHub Actions
```

These require later review.

---

# 4. COMPOSER SECURITY POLICY — LOCKED

Use Composer 2.10+'s modern:

```text
config.policy
```

configuration.

Do not use deprecated legacy `config.audit` policy keys for these controls.

Configure:

## Security advisories

```text
block: true
audit: fail
```

## Malware

```text
block: true
block-scope: all
audit: fail
```

## Abandoned packages

```text
block: true
audit: fail
```

Kongono is a new framework and must not begin with knowingly abandoned packages.

If an approved dependency unexpectedly requires an abandoned transitive package and Composer blocks resolution:

**STOP.**

Do not weaken the policy.

Report the dependency chain.

## Unreachable policy sources

Configure dependency-policy handling so unavailable policy/security sources are **not silently ignored**.

Security verification must fail closed rather than silently reduce protection.

## No policy ignores

Do not add advisory, malware, abandoned-package, severity, or package exceptions during this phase.

Any future exception requires explicit architectural/security approval with a documented reason.

---

# 5. COMPOSER PLUGIN POLICY — LOCKED

Third-party Composer plugins execute code in Composer's process and represent a supply-chain boundary.

Kongono must start with **no Composer plugins authorized**.

Configure:

```json
"allow-plugins": {}
```

or the Composer-equivalent configuration that explicitly allows none.

Do not install an auto-extension-installer or any other Composer plugin.

If an approved Phase 0B dependency unexpectedly requires a Composer plugin:

**STOP and report it.**

Do not authorize the plugin yourself.

---

# 6. COMPOSER PLATFORM RESOLUTION — LOCKED

The development environment runs PHP 8.5.9.

Dependency resolution for the root environment must remain compatible with Kongono's minimum:

```text
PHP 8.5.0
```

Configure Composer's platform PHP value to:

```text
8.5.0
```

This prevents development tooling from silently raising Kongono's minimum supported runtime.

Actual runtime compatibility must additionally be checked with:

```bash
composer check-platform-reqs
```

Do not raise Kongono's PHP minimum.

---

# 7. COMPOSER INSTALLATION DEFAULTS — LOCKED

Configure:

```text
sort-packages: true
secure-http: true
preferred-install: dist
```

Do not configure:

```text
classmap-authoritative
discard-changes
source-fallback
custom repositories
custom Packagist mirrors
```

Do not add credentials or tokens to repository configuration.

---

# 8. ROOT COMPOSER LOCK — LOCKED

Generate:

```text
composer.lock
```

Treat it as an intended repository file for Kongono's **root monorepo development environment**.

Rationale:

- contributor tooling must be reproducible;
- static-analysis/test/style behavior must not change unpredictably between developers;
- the root lock does not dictate downstream dependency resolution for individually published Kongono packages.

Do not manually edit `composer.lock`.

Generate it through Composer.

Do not commit it in this phase because commits remain unauthorized.

Document this decision in an ADR.

---

# 9. DEV DEPENDENCIES

Modify root `composer.json` and add only:

```text
friendsofphp/php-cs-fixer ^3.95
phpstan/phpstan ^2.2
phpstan/phpstan-strict-rules ^2.0
phpunit/phpunit ^13.2
```

Do not add any other direct dependency.

Transitive dependencies resolved by Composer are expected.

If Composer cannot resolve these four direct dependencies under the PHP 8.5.0 platform and security policies:

**STOP and report the exact failure.**

Do not lower security settings or change package families without approval.

---

# 10. AUTOLOADING — LOCKED FOR PHASE 0B

Do not create a production/root framework PSR-4 mapping yet.

Framework package boundaries and first production namespaces will be introduced when the first implementation package is authorized.

For Phase 0B add only:

```json
"autoload-dev": {
    "psr-4": {
        "Kongono\\Tests\\": "tests/"
    }
}
```

Do not create implementation package directories.

---

# 11. PHP LANGUAGE RULES — LOCKED

All first-party Kongono PHP code targets PHP >=8.5.

Every first-party PHP source file, test file, and PHP-based repository configuration file must use:

```php
declare(strict_types=1);
```

where syntactically applicable.

Prefer native PHP types over PHPDoc-only types whenever PHP can express the contract.

PHPDoc should add information PHP cannot express clearly.

Do not duplicate obvious native types in PHPDoc.

Do not use PHPDoc to conceal a weak native API.

Do not globally ban `mixed`, but treat it as an explicit boundary type rather than a substitute for modeling.

Do not introduce dynamic-property or legacy-PHP compatibility patterns.

---

# 12. PHP CS FIXER CONFIGURATION

Create:

```text
.php-cs-fixer.dist.php
```

It must:

1. use `declare(strict_types=1);`;
2. use `@PER-CS`;
3. enforce `declare(strict_types=1)` for first-party PHP files;
4. remove unused imports;
5. deterministically order imports alphabetically;
6. require trailing commas in supported multiline constructs where safe;
7. avoid speculative formatting rules beyond those authorized here;
8. avoid header/license rules;
9. scan only first-party PHP locations that currently exist.

For Phase 0B the primary PHP location is:

```text
tests/
```

Do not create future directories merely to simplify Finder configuration.

---

# 13. PHPSTAN CONFIGURATION

Create:

```text
phpstan.neon.dist
```

Requirements:

```text
level: max
PHP target: 8.5.0
strict-rules included
tests/ analyzed
no baseline
no ignored errors
```

Use the normal configuration include supplied by `phpstan/phpstan-strict-rules`.

Do not enable PHPStan bleeding-edge behavior during this phase.

Create an ignored local cache/temp location if PHPStan needs one.

---

# 14. PHPUNIT CONFIGURATION

Create:

```text
phpunit.xml.dist
```

Use PHPUnit 13-compatible configuration.

Discover:

```text
tests/
```

Configure explicit strict failure behavior for at least:

```text
warnings
risky tests
deprecations triggered by first-party code
PHPUnit deprecations
notices
incomplete tests
an empty configured test suite
unexpected output during tests
```

Do not use a future-expanding global "fail on every future issue type" switch merely for convenience.

Prefer explicit settings.

Do not require coverage metadata yet.

Do not establish a coverage percentage yet.

---

# 15. TOOLCHAIN SANITY TEST

Create exactly:

```text
tests/Tooling/EnvironmentTest.php
```

Purpose: verify executable Kongono development runs on at least PHP 8.5.0.

Requirements:

- `declare(strict_types=1);`
- namespace `Kongono\Tests\Tooling`
- normal PHPUnit `TestCase`
- `final` class
- clear test method name
- no vague comments
- no framework implementation
- no production class created for this test

If PHPUnit 13 has a stable public attribute indicating intentional coverage of no production code, it may be used without adding dependencies.

Do not create additional tests merely to inflate assertion count.

---

# 16. COMPOSER SCRIPTS — LOCKED

Add portable Composer scripts:

```text
composer test
composer analyse
composer cs
composer cs:fix
composer audit
composer platform
composer validate
composer quality
```

Semantics:

## `composer test`

Run PHPUnit.

## `composer analyse`

Run PHPStan using repository configuration.

## `composer cs`

Check formatting without modifying files and show useful differences.

## `composer cs:fix`

Apply the approved formatting rules.

## `composer audit`

Audit the **locked** dependency graph.

## `composer platform`

Run:

```bash
composer check-platform-reqs
```

against the actual runtime.

## `composer validate`

Validate the root Composer manifest without pretending Kongono is currently publication-ready.

The intentionally missing license remains intentional.

## `composer quality`

Run this non-mutating sequence:

```text
validate
audit
platform
cs
analyse
test
```

`composer quality` must never auto-fix code.

---

# 17. DEPENDENCY UPDATE BEHAVIOR

After modifying `composer.json`, use Composer to resolve/install approved dependencies and generate `composer.lock`.

Use normal stable dependency resolution.

Do not manually select transitive versions unless the authorized constraints cannot resolve.

Do not bypass project configuration using:

```text
--ignore-platform-reqs
--no-audit
```

Do not bypass Composer's security controls.

---

# 18. GITIGNORE UPDATES

Update `.gitignore` only as required for Phase 0B tooling.

Expected ignored generated paths may include:

```text
/vendor/
.php-cs-fixer.cache
.phpunit.cache/
.cache/
```

Use actual selected cache paths.

Do **not** ignore:

```text
composer.lock
phpstan.neon.dist
phpunit.xml.dist
.php-cs-fixer.dist.php
```

---

# 19. ENGINEERING DOCUMENTATION TO CREATE

Create:

```text
docs/
└── engineering/
    ├── README.md
    ├── toolchain.md
    ├── php-style.md
    ├── static-analysis.md
    ├── testing.md
    └── dependency-security.md
```

Documentation must follow Kongono's existing documentation doctrine and explain:

```text
what each tool does
why Kongono selected it
what problem it solves
how developers use it
what the quality gate means
what developers must not bypass
security implications
when exceptions require architect approval
```

Do not merely list commands.

Explain context.

---

# 20. ADRS TO CREATE

Continue existing numbering with:

```text
0014-php-engineering-toolchain.md
0015-root-composer-lock-for-reproducible-tooling.md
0016-strict-types-and-per-cs-style.md
0017-phpunit-as-core-test-runner.md
0018-phpstan-max-without-baseline.md
0019-composer-dependency-security-policy.md
```

Use the existing ADR format.

Do not renumber existing ADRs.

Do not rewrite existing accepted decisions unless a genuine contradiction is found, in which case STOP.

---

# 21. UPDATE EXISTING DOCUMENTATION

Update only where needed:

```text
AGENTS.md
CONTRIBUTING.md
README.md
ARCHITECTURE.md if genuinely necessary
docs/adr/README.md
```

## `AGENTS.md`

Add permanent requirements that AI executors:

- run relevant quality checks after PHP changes;
- run `composer quality` before declaring a PHP implementation task complete unless explicitly exempted;
- never create a PHPStan baseline without architect approval;
- never suppress static-analysis findings merely to get a green build;
- never weaken Composer dependency-security policies;
- never authorize Composer plugins;
- never add dependencies without explicit directive authorization;
- never manually modify `composer.lock`;
- report all future suppressions/exceptions explicitly.

Preserve all Phase 0A no-assumption rules.

## `CONTRIBUTING.md`

Document:

```bash
composer install
composer quality
```

and the individual quality commands.

Require PHP 8.5+.

## `README.md`

Keep concise.

It may mention that a development-quality toolchain now exists.

Do not imply framework functionality exists.

---

# 22. DOCUMENTATION STANDARD FOR TOOL CONFIGURATION

Configuration files are code.

Non-obvious decisions must be documented by **reason**, not by restating the key.

Example:

```text
Composer resolves dependencies as PHP 8.5.0 even when contributors run
newer 8.5 patches so development tooling cannot silently raise Kongono's
minimum supported runtime.
```

Avoid useless comments such as:

```text
# Set PHP version
```

---

# 23. NO QUALITY BYPASS POLICY — LOCKED

Forbidden unless a future architect directive explicitly authorizes them:

```text
PHPStan baseline
blanket PHPStan ignores
@phpstan-ignore
@phpstan-ignore-line
@phpstan-ignore-next-line
PHPStan errorIdentifier suppression
--ignore-platform-reqs
disabling Composer advisory blocking
disabling Composer malware blocking
disabling abandoned-package blocking
Composer policy ignore entries
automatic authorization of Composer plugins
skipping tests to hide failure
marking tests incomplete to hide failure
removing strict_types to satisfy a dependency
lowering PHPStan's level
removing strict rules to obtain a green build
```

If a legitimate exceptional case appears:

**STOP and request review.**

The purpose of the quality system is to protect Kongono, not produce a green indicator.

---

# 24. QUALITY GATE EXPECTATION

After installation and formatting, all must pass:

```bash
composer validate
composer audit
composer platform
composer cs
composer analyse
composer test
composer quality
```

`composer quality` must pass without modifying files.

If `composer cs` finds formatting issues, use:

```bash
composer cs:fix
```

then rerun non-mutating checks.

Do not hide formatting errors through Finder exclusions.

---

# 25. ENVIRONMENT VERIFICATION

At the beginning run:

```bash
php -v
composer --version
git --version
composer diagnose
```

Expected:

```text
PHP >= 8.5.0
Composer >= 2.10.2
Git available
```

If PHP or Composer is below minimum:

**STOP.**

Do not install/update system software.

Report material `composer diagnose` warnings.

Do not change global Composer configuration.

---

# 26. FILES EXPECTED TO BE CREATED

At minimum:

```text
composer.lock
.php-cs-fixer.dist.php
phpstan.neon.dist
phpunit.xml.dist

tests/
└── Tooling/
    └── EnvironmentTest.php

docs/
└── engineering/
    ├── README.md
    ├── toolchain.md
    ├── php-style.md
    ├── static-analysis.md
    ├── testing.md
    └── dependency-security.md

docs/
└── adr/
    ├── 0014-php-engineering-toolchain.md
    ├── 0015-root-composer-lock-for-reproducible-tooling.md
    ├── 0016-strict-types-and-per-cs-style.md
    ├── 0017-phpunit-as-core-test-runner.md
    ├── 0018-phpstan-max-without-baseline.md
    └── 0019-composer-dependency-security-policy.md
```

Other changes must stay within authorized existing files.

---

# 27. FILES / AREAS NOT TO CREATE

Do not create:

```text
src/
packages/
bin/
kongo executable
app/
config/
routes/
database/
resources/
public/
runtime/
compiler/
graph/
container/
http/
agents/
extensions/
frontend/
node_modules/
package.json
Dockerfile
docker-compose.yml
.github/
LICENSE
```

No actual framework package begins in Phase 0B.

---

# 28. PROHIBITED IMPLEMENTATION

Do not implement:

```text
Application Graph
Graph IR
Graph IDs
Graph serialization
dependency injection
container
kernel lifecycle
runtime adapter
router
request/response model
middleware
actions
ORM
entities
database abstraction
migrations
events
queues
workflows
authorization
authentication
Principal implementation
Agent implementation
Delegation implementation
Extension Host
capability runtime
MCP
A2A
OpenAPI
OpenTelemetry integration
Vite integration
asset platform
kongo CLI
AI provider integration
```

---

# 29. GIT RULES

Do not:

```text
commit
push
create a remote
create a branch
rewrite Git history
```

Run final:

```bash
git status --short
```

Report all changes.

---

# 30. REQUIRED VALIDATION AUDIT

Before reporting completion, verify:

## Composer

```text
Only 4 direct dev dependencies added.
No production dependency added.
No Composer plugin authorized.
Security policy is fail-closed.
composer.lock exists.
PHP platform resolution is 8.5.0.
```

## PHPStan

```text
level max
strict-rules included
no baseline
no ignoreErrors
no suppressions
tests analyzed
```

## PHPUnit

```text
PHPUnit 13 installed
tooling test passes
strict explicit failure settings configured
no framework source exists
```

## PHP CS Fixer

```text
@PER-CS
strict_types rule
unused imports
deterministic import ordering
multiline trailing commas
dry-run check works
```

## Documentation

```text
Engineering docs explain intent/context.
ADRs match only authorized decisions.
Phase 0A doctrine remains intact.
No planned feature is described as implemented.
```

---

# 31. REQUIRED COMPLETION REPORT

Return exactly this structure:

```text
TASK COMPLETION REPORT

1. STATUS
Complete / Partial / Blocked

2. SUMMARY
What Phase 0B established.

3. ENVIRONMENT
Exact:
- PHP version
- Composer version
- Git version
- material composer diagnose findings

4. FILES CREATED
Exact paths.

5. FILES MODIFIED
Exact paths and reason for each.

6. DEPENDENCIES ADDED
For each direct dependency:
- package
- requested constraint
- resolved version
- purpose

7. TRANSITIVE DEPENDENCY / SECURITY SUMMARY
- number of locked packages if easily determinable
- Composer audit result
- abandoned packages
- security advisories
- malware findings
- Composer plugins present/authorized

8. COMPOSER POLICY REVIEW
Confirm:
- advisory blocking
- malware blocking
- abandoned-package blocking
- fail-closed policy source behavior
- PHP 8.5.0 platform resolution
- plugin deny-by-default

9. TOOLCHAIN CONFIGURATION
Summarize:
- PHPStan
- PHPUnit
- PHP CS Fixer
- Composer scripts

10. COMMANDS RUN
Exact important commands, including dependency resolution and quality checks.

11. VALIDATION RESULTS
Exact result for:
- composer validate
- composer audit
- composer platform
- composer cs
- composer analyse
- composer test
- composer quality

12. TEST RESULTS
Tests:
Assertions:
Failures:
Errors:
Warnings:
Risky:
Deprecations:
Notices:
Skipped:
Incomplete:

13. STATIC ANALYSIS RESULTS
Files analyzed if reported:
Errors:
Baseline:
Suppressions:

14. CODING STYLE RESULTS
Files checked if reported:
Violations:
Whether cs:fix was required:

15. ARCHITECTURAL DECISIONS
Only those explicitly authorized.
List unresolved decisions separately.

16. DEVIATIONS FROM DIRECTIVE
None, or exact deviation and reason.

17. DOCUMENTATION REVIEW
Confirm:
- engineering documentation is contextual rather than vague
- security implications are explained
- no framework functionality is claimed
- no conflicts with accepted Phase 0A doctrine were found

18. WARNINGS / KNOWN ISSUES
Anything unresolved.

19. QUESTIONS / BLOCKERS
Anything requiring architect input.

20. GIT DIFF / STATUS SUMMARY
Concise summary.
Do not commit.

21. ACCEPTANCE CRITERIA
Report PASS / FAIL for every criterion in Section 32.

22. NEXT STEP
State only:

"Await architect review before Phase 0C."
```

---

# 32. ACCEPTANCE CRITERIA

Every criterion must PASS.

```text
[ ] Agent independently verified PHP >=8.5.0.
[ ] Agent independently verified Composer >=2.10.2.
[ ] composer diagnose was run and material warnings reported.
[ ] Exactly four authorized direct dev dependencies were added.
[ ] No production dependency was added.
[ ] Composer resolves the project against PHP 8.5.0.
[ ] Composer security advisories are blocking and audit-failing.
[ ] Composer malware findings are blocking and audit-failing.
[ ] Abandoned dependencies are blocking and audit-failing.
[ ] Security policy sources are not silently ignored when unreachable.
[ ] No dependency-policy ignore entries exist.
[ ] No Composer plugin is authorized.
[ ] composer.lock was generated by Composer.
[ ] composer.lock is not ignored.
[ ] No manual composer.lock edits were made.
[ ] PHPUnit 13 is installed.
[ ] phpunit.xml.dist exists.
[ ] PHPUnit is configured with strict explicit failure behavior.
[ ] tests/Tooling/EnvironmentTest.php exists.
[ ] EnvironmentTest verifies PHP >=8.5.0.
[ ] EnvironmentTest contains no framework implementation.
[ ] PHPStan 2.2 family is installed.
[ ] phpstan-strict-rules is installed.
[ ] PHPStan runs at level max.
[ ] PHPStan targets PHP 8.5.0.
[ ] No PHPStan baseline exists.
[ ] No PHPStan errors are suppressed.
[ ] PHP CS Fixer is installed.
[ ] PHP CS Fixer uses @PER-CS.
[ ] strict_types is enforced.
[ ] unused imports are removed.
[ ] imports are deterministically ordered.
[ ] supported multiline trailing commas are enforced.
[ ] composer test works.
[ ] composer analyse works.
[ ] composer cs works without modifying files.
[ ] composer cs:fix exists as the explicit mutating formatter command.
[ ] composer audit works against the lock file.
[ ] composer platform works.
[ ] composer validate works.
[ ] composer quality runs the complete non-mutating quality gate.
[ ] composer quality passes.
[ ] Engineering documentation was created.
[ ] Six authorized ADRs were added using the existing ADR format.
[ ] AGENTS.md contains permanent no-bypass quality requirements.
[ ] CONTRIBUTING.md documents contributor quality commands.
[ ] README does not claim framework functionality exists.
[ ] No framework source directory was created.
[ ] No framework implementation class was created.
[ ] No kongo executable was created.
[ ] No frontend/Node tooling was created.
[ ] No CI/CD was created.
[ ] No license was selected or created.
[ ] No commit was created.
[ ] No push was performed.
[ ] Existing Phase 0A architecture remains consistent.
```

---

# 33. FINAL OPERATING RULE

The quality system itself is part of Kongono's architecture.

Do not optimize for:

> "making the checks green."

Optimize for:

> **making the repository correct enough that the checks are legitimately green.**

Never bypass a quality or security control to finish this task.

If an approved tool conflicts with another approved requirement:

**STOP and ask.**

A blocked Phase 0B with a precise technical conflict is preferable to a completed Phase 0B containing an unauthorized compromise.

Begin Kongono Phase 0B now.
