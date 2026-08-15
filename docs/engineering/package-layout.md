# Package Layout

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 0X contains exactly six production
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
|-- diagnostic-presentation/
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
`Kongono\DependencyInjection\` and requires `kongono/api-metadata: *` for
compatibility metadata plus `kongono/diagnostics: *` for exact DI-owned bounded
context. `kongono/diagnostics` owns `Kongono\Diagnostics\` and requires
`kongono/api-metadata: *`; Diagnostics has no Kernel or reverse Graph dependency.
Kernel requires API Metadata and Diagnostics. `kongono/diagnostic-presentation`
owns `Kongono\DiagnosticPresentation\` and directly requires all five other
packages as an acyclic leaf. These unbounded internal constraints
are authorized while the packages are unreleased and developed atomically;
inter-package version policy remains open. Dependency Injection and Application
Graph do not depend on each other. The independent Graph-, Kernel-, and
DI-to-Diagnostics edges create no cross-package lifecycle integration or
universal exception boundary. Package manifests duplicate no root tool
dependencies.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps all six package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires all six local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates API Metadata, Application Graph, Dependency Injection, Diagnostic Presentation, Diagnostics, and Kernel in
deterministic lexicographic package-directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than any package.

## Boundaries and stability

The six physical package boundaries and the Phase 0X dependency graph are
implemented. Further package boundaries and dependency rules remain open. Independent publishing,
splitting, versioning, and release automation also remain open and require
authorization.

**Stability:** Internal engineering convention implementing the Accepted
monorepo decision.

## Implemented Phase 0X dependency graph

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
diagnostic-presentation -> api-metadata
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
framework root -> all six packages
```

The five API Metadata consuming package-manifest constraints are
`"kongono/api-metadata": "*"`; the root constraint is
`"kongono/api-metadata": "@dev"`. Cycles, overlapping production namespaces,
self-dependencies, and undeclared package dependencies remain prohibited.
The former `Kongono\Kernel\Api\*` namespace is absent and no compatibility shim
exists. Kernel, Graph, and DI independently require Diagnostics; none requires
another semantic consumer. Current Composer/autoload behavior implements the six-package graph
documented above.

## Implemented Diagnostic Presentation leaf

Phase 0X implements this package boundary:

```text
packages/diagnostic-presentation/
```

Its Composer name is `kongono/diagnostic-presentation` and its production
namespace is `Kongono\DiagnosticPresentation\`. The directory and manifest are
part of the current six-package tree and seven-manifest count.

The leaf depends directly on:

```text
diagnostic-presentation -> api-metadata
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
```

No existing package may depend on Diagnostic Presentation. There is no reverse
edge, service registration, Kernel binding, DI definition, Graph node, or
ambient discovery, so the graph remains acyclic. The root consumes the leaf for
monorepo testing. Phase 0X adds its manifest, autoload/tooling integration,
eight production declarations, six package tests, README, and pure projection
behavior. Phase 0Z adds the ninth production declaration, one focused ten-test
PHP file, and two test-only golden HTML fixtures. The renderer creates no new
package edge or runtime binding; no trusted selector or production model exists.
