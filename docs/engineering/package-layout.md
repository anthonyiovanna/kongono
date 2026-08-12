# Package Layout

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 0F contains exactly three production
packages:

```text
packages/
|-- application-graph/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- dependency-injection/
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
`Api` metadata. `kongono/dependency-injection` owns
`Kongono\DependencyInjection\` and temporarily requires `kongono/kernel: *` only
for the same metadata. These unbounded internal constraints are authorized while
the packages are unreleased and developed atomically; inter-package version
policy remains open. Dependency Injection and Application Graph do not depend on
each other. Package manifests duplicate no root tool dependencies.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps all three package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires all three local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates Application Graph, Dependency Injection, and Kernel in
deterministic lexicographic package-directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than any package.

## Boundaries and stability

The three physical package boundaries are implemented. Further package boundaries,
dependency rules, independent publishing, splitting, versioning, and release
automation remain open and require authorization.

**Stability:** Internal engineering convention implementing the Accepted
monorepo decision.
