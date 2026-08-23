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

## Hantei process boundary

Phase 4E adds no third-party runtime dependency. Hantei uses only identified
local PHP, Composer, Git, the fixed Composer scripts, and the existing locked
advisory audit. It cannot authorize plugins, update dependencies, choose an
alternate audit, or reclassify policy/security findings as transport failure.
The committed-intent root `composer.lock` makes contributor tools reproducible. Composer alone generates or updates it; manual edits are forbidden. The root lock governs this monorepo's development environment and does not dictate how future separately published Kongono packages resolve for consumers.

No Composer plugin is authorized. The empty `allow-plugins` map is an explicit deny-by-default boundary. A future plugin requires architect and security approval because it executes inside Composer's process.

Use secure HTTPS and distribution archives from normal Composer repositories. Do not add credentials, custom repositories, mirrors, policy exceptions, `--no-audit`, or platform-ignore flags to repository workflows.

Phase 4C adds only the first-party local path package `kongono/knowledge`. It
requires PHP, `ext-hash`, `ext-json`, API Metadata, and Diagnostics; no new
third-party package, Composer plugin, policy exception, repository, network
client, serializer, CLI framework, model SDK, or Application Graph dependency
is introduced. No existing package depends on Knowledge. Deterministic artifact
generation performs no dependency installation or network access.

Phase 4D adds only the first-party local path package `kongono/chie`. It
requires PHP, `ext-json`, API Metadata, Diagnostics, and Knowledge. No existing
production package depends on Chie. No third-party package, plugin, policy
exception, repository, HTTP client, serializer, CLI framework, search library,
or model SDK is added. Chie consumes an explicit artifact at runtime but cannot
run Composer, alter dependencies, use a network, or discover configuration.

Phase 4F adds no third-party runtime dependency. `kongono/oshie` depends only on PHP, `ext-hash`, `ext-json`, API Metadata, Diagnostics, and Knowledge. It has no Chie or Hantei dependency, and no other production package depends on Oshie. It cannot run Composer, alter dependencies, use a network, or discover configuration.
## Phase 4G dependency boundary

`kongono/chie` declares the PHP `ext-hash` platform capability used for exact
proposal identities. Phase 4G adds no package or third-party dependency. The
proposal path performs no network, provider, model, process, Git, Hantei,
publication, or deployment operation.

Phase 4H adds one root Composer script and test-owned external harness only. It
adds no requirement, repository, plugin, package-manifest, lockfile, platform,
audit-policy, provider, model, SDK, network, account, or production dependency.
Network-denied Knowledge/Chie/Oshie proof remains local; locked-advisory
unavailability makes Hantei visibly `BLOCKED` rather than guessed, while the
checkpoint separately requires real current-repository PASS. Phase 5 was not
begun.
