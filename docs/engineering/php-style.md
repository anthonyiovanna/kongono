# PHP Style

## Purpose and scope

Kongono uses the current `@PER-CS` ruleset so first-party PHP has a consistent community-based baseline instead of a project-specific style dialect. The formatter scans exactly 113 PHP files across root tests and the source and test trees for API Metadata, Application Graph, Dependency Injection, Diagnostics, and Kernel.

Every first-party PHP source, test, and PHP-based repository configuration file uses `declare(strict_types=1);` where PHP syntax permits it. Native types should express contracts whenever possible; PHPDoc adds information that PHP cannot express and must not hide a weak native API. `mixed` remains available for genuine boundaries but is not a substitute for modeling.

## Additional enforced rules

Beyond `@PER-CS`, the configuration enforces strict-types declarations, removes unused imports, orders imports alphabetically, and requires trailing commas in supported multiline constructs. These rules reduce ambiguous coercion and noisy diffs without selecting speculative formatting policy.

Check style without changing files:

```bash
composer cs
```

Apply the configured rules explicitly:

```bash
composer cs:fix
```

Review formatter output as code. Do not exclude a first-party location or disable a rule merely to obtain a pass; a legitimate exception requires architect approval.
