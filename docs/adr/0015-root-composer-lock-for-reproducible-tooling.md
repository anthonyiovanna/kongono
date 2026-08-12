# ADR 0015: Root Composer Lock for Reproducible Tooling

## Status

Accepted

## Date

2026-08-12

## Context

Unpinned development-tool resolution could change formatting, analysis, and test behavior between contributors even when source code is unchanged.

## Decision

Generate and retain `composer.lock` as an intended root monorepo file. Composer must generate and update it; manual edits are forbidden. The lock captures the reproducible root development environment and does not dictate downstream dependency resolution for future individually published Kongono packages.

## Rationale

Tooling should change through an explicit dependency update that can be reviewed, audited, and validated, not implicitly on each install.

## Consequences

Contributors install the locked graph with `composer install`. Dependency updates can change the lock only through Composer under the repository security policy. The file must not be ignored.

## Rejected alternatives

Omitting the root lock, manually pinning transitive packages, or editing lock metadata directly are rejected because they reduce reproducibility or bypass Composer's integrity model.

## What remains open

Future package publication, versioning, release coordination, repository splitting, and downstream lock-file guidance remain separate decisions.
