# Package Layout

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 0D contains exactly two production
packages:

```text
packages/
|-- application-graph/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
`-- kernel/
    |-- composer.json
    |-- README.md
    |-- src/
    `-- tests/
```

Empty or speculative package directories remain prohibited. A package may later
be published independently, but this layout selects no splitting, publishing,
release, or version-coordination mechanism.

## Composer integration

Each package manifest defines a PHP 8.5+ library, owns its production namespace
from `src/`, and maps its development test namespace to `tests/`.
`kongono/application-graph` owns `Kongono\ApplicationGraph\` and temporarily
requires `kongono/kernel: *` solely because production types use the accepted
`Api` metadata. That unbounded internal constraint is authorized only while both
packages are unreleased and developed atomically; inter-package version policy
remains open. Package manifests duplicate no root tool dependencies.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps both package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires both local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates `packages/application-graph` before `packages/kernel` in
deterministic lexicographic directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than either package.

## Boundaries and stability

The two physical package boundaries are implemented. Further package boundaries,
dependency rules, independent publishing, splitting, versioning, and release
automation remain open and require authorization.

**Stability:** Internal engineering convention implementing the Accepted
monorepo decision.
