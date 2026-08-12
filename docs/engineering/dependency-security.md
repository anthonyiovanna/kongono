# Dependency Security

## Purpose and trust boundary

Dependencies and Composer plugins can execute third-party code in contributor and automation environments. Kongono therefore treats dependency resolution, audit policy, the root lock file, and plugin authorization as supply-chain controls rather than installation convenience.

Composer resolves the root development environment as PHP 8.5.0 even when a contributor runs a newer 8.5 patch. This prevents tool updates from silently raising the minimum runtime. `composer platform` separately checks the actual runtime and extensions.

## Fail-closed policy

Composer blocks packages affected by active security advisories, packages flagged as malware, and abandoned packages. Audits fail for all three categories. Malware blocking applies to install and update operations, and unreachable policy or security sources are not silently ignored. No advisory, severity, malware-source, package, or abandoned-package ignore is configured.

Audit the locked dependency graph with:

```bash
composer audit:locked
```

This repository command executes `composer audit --locked`. Composer's built-in `composer audit` command remains unchanged and audits installed dependencies; `composer quality` uses `composer audit:locked` so its security result is deterministic from the lock file.

If a policy source is unavailable, the safe result is failure, not reduced protection. If an authorized direct dependency resolves through a blocked abandoned or unsafe transitive package, stop and report the dependency chain. Do not weaken policy or select an alternative package family without approval.

## Lock and plugin policy

The committed-intent root `composer.lock` makes contributor tools reproducible. Composer alone generates or updates it; manual edits are forbidden. The root lock governs this monorepo's development environment and does not dictate how future separately published Kongono packages resolve for consumers.

No Composer plugin is authorized. The empty `allow-plugins` map is an explicit deny-by-default boundary. A future plugin requires architect and security approval because it executes inside Composer's process.

Use secure HTTPS and distribution archives from normal Composer repositories. Do not add credentials, custom repositories, mirrors, policy exceptions, `--no-audit`, or platform-ignore flags to repository workflows.
