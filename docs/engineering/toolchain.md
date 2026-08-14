# PHP Engineering Toolchain

## Purpose

The toolchain turns Kongono's contributor expectations into deterministic commands. The root `composer.lock` fixes the development-tool versions, while Composer scripts provide the same entry points across supported contributor environments.

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
2. `composer validate:packages` validates the API Metadata, Application Graph, Dependency Injection, Diagnostics, and Kernel manifests in lexicographic package-directory order without publication-readiness checks.
3. `composer audit:locked` executes `composer audit --locked` and checks the lock-file dependency graph against Composer's advisory, malware, and abandoned-package policies. Composer's built-in `composer audit` command remains available for auditing installed dependencies.
4. `composer platform` verifies that installed packages can run on the actual PHP runtime and extensions, complementing dependency resolution against PHP 8.5.0.
5. `composer cs` checks approved formatting without modifying files and prints a useful diff.
6. `composer analyse` analyzes first-party PHP at PHPStan's maximum level with strict rules.
7. `composer test` runs the PHPUnit suite under explicit failure settings.

Use an individual command to reproduce a focused failure. `composer cs:fix` is the only mutating quality command; run it deliberately, review its changes, and rerun `composer quality`.

## Failure and security contract

A green result only describes the configured checks at the time they ran. It does not replace review or prove future compatibility. Never use platform-ignore flags, policy exceptions, skipped tests, formatter exclusions, static-analysis suppressions, or weakened configuration to conceal a failure. Exceptions require architect approval and must be documented and reported.
