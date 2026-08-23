# PHP Engineering Toolchain

## Purpose

The toolchain turns Kongono's contributor expectations into deterministic commands. The root `composer.lock` fixes the development-tool versions, while Composer scripts provide the same entry points across supported contributor environments.

Phase 2 is complete at checkpoint
`647053d4bddc4c76090bca9bf3d693faa5880c52` with exactly fourteen production
packages and a root-owned, test-owned runnable fixture. The fixture is not a
production application or deployment profile. Phase 3C implements the bounded
`kongono/php-cgi` inbound converter, and Phase 3D implements its bounded response
emitter. Phase 3E and ADR 0055 implement its immutable operator bootstrap and independent
current-delivery permission evaluator. Phase 3F implements the concrete delivery
and thin entry. The existing root autoload, package validation, style, analysis,
and PHPUnit inventories cover all four boundaries. Phase 3G uses the unchanged
toolchain for its lifecycle and documentation boundary. Phase 3H adds a
root-owned external harness and Composer acceptance command; no production
package depends on that code, and the harness is not a production or supported
server/client. The memory command below remains contributor tooling only.

Install the locked development environment with:

```bash
composer install
```

Run the complete gate with:

```bash
composer quality
```

## Gate order and meaning

The gate stops on the first failure and runs in this order:

1. `composer validate:composer` executes `composer validate --no-check-publish` and checks that the root manifest is structurally valid. Composer's built-in `composer validate` remains unchanged. Publication-readiness checks are intentionally excluded from the repository script because Kongono has not selected a license.
2. `composer validate:packages` validates all fourteen package manifests in lexicographic package-directory order without publication-readiness checks.
3. `composer audit:locked` executes `composer audit --locked` and checks the lock-file dependency graph against Composer's advisory, malware, and abandoned-package policies. Composer's built-in `composer audit` command remains available for auditing installed dependencies.
4. `composer platform` verifies that installed packages can run on the actual PHP runtime and extensions, complementing dependency resolution against PHP 8.5.0.
5. `composer cs` checks approved formatting without modifying files and prints a useful diff.
6. `composer analyse` starts PHPStan through Composer's cross-platform `@php` executable with `memory_limit=512M`, then analyzes first-party PHP at PHPStan's maximum level with strict rules.
7. `composer test` runs the PHPUnit suite under explicit failure settings.

Use an individual command to reproduce a focused failure. `composer cs:fix` is the only mutating quality command; run it deliberately, review its changes, and rerun `composer quality`.

## Package manifest validation

`composer validate:packages` runs these exact commands and validates each package according to its implemented role:

| Command | Package role |
| --- | --- |
| `composer --working-dir=packages/api-metadata validate --no-check-publish` | Neutral API compatibility metadata |
| `composer --working-dir=packages/application-graph validate --no-check-publish` | Immutable Application Graph structural core |
| `composer --working-dir=packages/dependency-injection validate --no-check-publish` | Explicit typed dependency resolution |
| `composer --working-dir=packages/diagnostic-activation validate --no-check-publish` | Typed trusted-diagnostic permission values and eligibility |
| `composer --working-dir=packages/diagnostic-handler validate --no-check-publish` | Eligibility-first trusted-diagnostic sequencing |
| `composer --working-dir=packages/diagnostic-presentation validate --no-check-publish` | Trusted-developer diagnostic projection and HTML rendering |
| `composer --working-dir=packages/diagnostics validate --no-check-publish` | Presentation-neutral bounded diagnostic values |
| `composer --working-dir=packages/http-message validate --no-check-publish` | First-party server-side PSR-7 messages and PSR-17 factories |
| `composer --working-dir=packages/http-server validate --no-check-publish` | Explicit PSR-15 middleware and terminal routing |
| `composer --working-dir=packages/kernel validate --no-check-publish` | Runtime-neutral lifecycle and operation scopes |
| `composer --working-dir=packages/routing validate --no-check-publish` | Bounded deterministic route declaration and matching |
| `composer --working-dir=packages/web-application validate --no-check-publish` | Web composition and Kernel operation ownership |
| `composer --working-dir=packages/web-runtime validate --no-check-publish` | Outer web delivery, safe failure selection, and cleanup orchestration |

## Contributor analysis memory

The root analysis script is:

```text
@php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan.neon.dist --no-progress
```

The 512 MB value is an upper limit for the contributor PHPStan child process only. It applies through both `composer analyse` and `composer quality`; it is not a Kongono application or runtime memory policy, framework configuration, PHP INI scan-directory overlay, or permission to raise another process limit. Ordinary contributor commands need no temporary INI file or `PHP_INI_SCAN_DIR` manipulation, and the limit is not unlimited.

## Failure and security contract

A green result only describes the configured checks at the time they ran. It does not replace review or prove future compatibility. Never use a PHPStan baseline, platform-ignore flag, policy exception, skipped test, formatter or analysis exclusion, static-analysis suppression, or weakened configuration to conceal a failure. Exceptions require architect approval and must be documented and reported.

If the locked advisory gate fails solely because managed network or cache access is denied, allow the failed command to finish, prove repository state unchanged, and retry the identical command once with approved access. The retry does not authorize changing the command, skipping the advisory gate, weakening policy, or accepting an unverified result.

Phase 3F extends the configured production inventory to 124 PHP declarations,
the PHP CGI source inventory to 24 files, and the style-covered inventory to 263
files. Phase 3G adds two production PHP files and one test PHP file, bringing
the repository to 126 production declarations, 26 PHP CGI source files, 140
test PHP files, and 266 style-covered PHP files. Its focused gate covers PHP CGI
plus the architecture convention test; the canonical `composer quality` gate
remains mandatory and unchanged. No manifest, lockfile, PHP constraint, policy,
plugin, or tool configuration changes. The existing direct local
`php-cgi -> web-runtime` edge remains sufficient, with no Web Application edge.
The selected synchronous PHP-CGI FastCGI RESPONDER profile now has external,
test-owned proof. Production trusted diagnostics remain denied and operational
ownership remains external. Phase 3 is complete, is not Stable API, 1.0, the
full framework, universal SAPI support, deployment automation, or a
production-hardening certification, and starts no next phase automatically.

## Phase 4 policy relationship and current inventory

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. The current repository has fourteen
package manifests plus the root manifest, 126 production PHP files, 151 test
PHP files, and 277 PHP files covered by the existing PHP CS Fixer configuration.
These current global counts supersede the preceding historical Phase 3G
inventory only as present-state evidence; the historical figures remain valid
for that subphase.

The literal `composer quality` order and configuration stay unchanged. A future
PHP compatibility gate uses the locked audit, platform requirements, strict
optimized autoload proof, style, analysis, complete test and acceptance suites,
persistent-runtime cleanup, supported-host checks, runtime-change review, and
measured performance/memory evidence. Phase 4A changes no command, manifest,
constraint, lockfile, dependency, plugin, baseline, suppression, or CI lane.

Experimental Kongono Hantei independently selects and interprets the closed
eight-check framework-quality profile, but cannot replace or weaken the checks
and cannot repair the subject it judges. `PASS`, `FAIL`, or `BLOCKED` follows
inspectable evidence. See the [PHP support policy](php-support-policy.md),
[developer-intelligence architecture](../architecture/developer-intelligence.md),
and [Phase 4 roadmap](../architecture/phase-4-roadmap.md).

## Knowledge artifact command

`composer knowledge:check` runs after platform validation and before style in
the canonical quality sequence. It invokes the package bin entry with the
explicit repository root, generates and validates the exact 31-source artifact
in memory, emits one compact JSON result, and writes no artifact. PHPUnit,
PHPStan, PHP CS Fixer, package Composer validation, optimized strict autoload,
and the root architecture test cover the new package. The command performs no
network, model, retrieval, workspace scan, source mutation, cache, Git action,
or production integration.

## Read-only Chie command

## Read-only Hantei command

`composer hantei:verify` invokes `kongono-hantei verify .`. The command always
runs the eight fixed profile scripts and emits one canonical semantic report.
It is intentionally outside `quality` to prevent recursion. Exits are `0` PASS,
`1` FAIL, `2` BLOCKED, `64` usage, and `70` unexpected command failure.
`packages/chie/bin/kongono-chie` is included explicitly in Composer autoload,
PHPStan, PHPUnit source coverage, and PHP CS Fixer. It accepts only `explain`
or `diagnose` with an explicit framework root, explicit content-addressed
artifact path, and one admitted token. Canonical quality does not invoke it or
create an artifact implicitly. Focused validation runs PHPStan and PHP CS Fixer
over source, tests, and the extensionless bin and runs exactly 36 package tests.
Deterministic command proof generates an artifact only in a fresh external
temporary directory.

Phase 4F adds `packages/oshie` to package validation, PHP CS Fixer, PHPStan, PHPUnit, and root autoloading. `composer oshie` invokes only the package executable and is not part of recursive canonical quality. Hantei's fixed profile mirrors the eighteenth package validation entry without changing its eight-check order.
## Phase 4G proposal command

`kongono-chie propose <framework-root> <artifact-path> <proposal-spec-path>` is
the only proposal grammar. It is non-interactive and deterministic. It reads one
explicit bounded spec plus one explicit current artifact, produces canonical
JSON, and does not call Git, Hantei, a shell, a model, a provider, or a network.

`composer acceptance:phase4` is the only Phase 4H script addition. It invokes
the real local role commands with argument vectors, bounded independent streams
and timeouts, exact exit capture, and temporary-only files. A successful run
emits one canonical 28-scenario object after cleanup. The harness calls literal
`composer hantei:verify` for real current evidence and separately labels its
temporary evaluator fixtures synthetic. It changes no canonical quality gate,
dependency, lockfile, package manifest, platform range, or security policy.
