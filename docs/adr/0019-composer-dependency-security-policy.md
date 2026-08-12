# ADR 0019: Composer Dependency-Security Policy

## Status

Accepted

## Date

2026-08-12

## Context

Dependency resolution and Composer plugins cross a supply-chain trust boundary. A new framework can start fail-closed instead of inheriting unsafe exceptions.

## Decision

Use Composer 2.10+'s `config.policy` to block and audit-fail active security advisories, malware, and abandoned packages. Malware blocking applies to all relevant operations, and unreachable policy or security sources are not silently ignored. Configure no policy ignores.

Resolve the root environment as PHP 8.5.0, require secure HTTP, prefer distribution installs, sort direct packages, and authorize no Composer plugins through an empty `allow-plugins` map. Keep normal Composer repositories and add no credentials or mirrors.

Keep Composer's built-in `composer audit` command unchanged. Kongono's deterministic lock-file audit command is `composer audit:locked`, which executes `composer audit --locked`; the complete quality gate uses this locked form.

## Rationale

Fail-closed resolution prevents availability problems or convenience from silently reducing dependency protection. Denying plugins by default avoids unreviewed code execution inside Composer.

## Consequences

An advisory, malware finding, abandoned package, unreachable policy source, or unexpected plugin requirement can block installation. The executor must report the package chain or source failure and may not weaken policy. Actual runtime compatibility remains separately checked.

## Rejected alternatives

Legacy `config.audit` controls, policy ignore entries, severity exceptions, plugin auto-authorization, custom repositories, insecure HTTP, `--no-audit`, and platform-ignore flags are rejected.

## What remains open

Any future dependency-policy exception, plugin authorization, repository change, or security-source change requires explicit architectural and security approval with a documented reason.
