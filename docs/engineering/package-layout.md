# Package Layout

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 0U retains exactly five production
packages:

```text
packages/
|-- api-metadata/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
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
|-- diagnostics/
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
`kongono/api-metadata` owns `Kongono\ApiMetadata\`, requires PHP only, and
contains exactly `Api` and `ApiStatus`. `kongono/application-graph` owns
`Kongono\ApplicationGraph\` and requires `kongono/api-metadata: *` for
compatibility metadata plus `kongono/diagnostics: *` for exact Graph-owned
bounded context.
`kongono/dependency-injection` owns
`Kongono\DependencyInjection\` and requires `kongono/api-metadata: *` only for
the same metadata. `kongono/diagnostics` owns `Kongono\Diagnostics\` and
requires `kongono/api-metadata: *`; Diagnostics has no Kernel or reverse Graph
dependency. Kernel requires API Metadata and Diagnostics; Dependency Injection
does not depend on Diagnostics. These unbounded
internal constraints are authorized while the packages are unreleased and
developed atomically; inter-package version policy remains open. Dependency
Injection and Application Graph do not depend on each other. The independent
Graph-to-Diagnostics and Kernel-to-Diagnostics edges create no cross-package
lifecycle integration or universal exception boundary. Package manifests
duplicate no root tool dependencies.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps all five package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires all five local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates API Metadata, Application Graph, Dependency Injection, Diagnostics, and Kernel in
deterministic lexicographic package-directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than any package.

## Boundaries and stability

The five physical package boundaries and the Phase 0T dependency graph are
implemented. Further package boundaries and dependency rules remain open. Independent publishing,
splitting, versioning, and release automation also remain open and require
authorization.

**Stability:** Internal engineering convention implementing the Accepted
monorepo decision.

## Implemented Phase 0T dependency graph

Phase 0S created this fifth package directory:

```text
packages/api-metadata/
|-- composer.json
|-- README.md
|-- src/
`-- tests/
```

The current dependency graph is:

```text
api-metadata -> php
kernel -> api-metadata
kernel -> diagnostics
diagnostics -> api-metadata
application-graph -> api-metadata
application-graph -> diagnostics
dependency-injection -> api-metadata
dependency-injection -> diagnostics
framework root -> all five packages
```

The four API Metadata consuming package-manifest constraints are
`"kongono/api-metadata": "*"`; the root constraint is
`"kongono/api-metadata": "@dev"`. Cycles, overlapping production namespaces,
self-dependencies, and undeclared package dependencies remain prohibited.
The former `Kongono\Kernel\Api\*` namespace is absent and no compatibility shim
exists. Kernel, Graph, and DI independently require Diagnostics; none requires
another semantic consumer. Current Composer/autoload behavior implements the five-package graph
documented above.
