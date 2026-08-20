# Package Layout

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 2E contains exactly eleven production
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
|-- diagnostic-activation/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- diagnostic-handler/
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
|-- http-message/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- http-server/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- kernel/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- routing/
    |-- composer.json
    |-- README.md
    |-- src/
    `-- tests/
`-- web-application/
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
owns `Kongono\DiagnosticPresentation\` and directly requires all five
originating packages as an acyclic leaf. `kongono/diagnostic-activation` owns
`Kongono\DiagnosticActivation\` and requires only API Metadata. Diagnostic
Handler owns `Kongono\DiagnosticHandler\` and requires API Metadata, Activation,
Presentation, Kernel, Application Graph, and Dependency Injection, but not
Diagnostics. Handler is the only current package depending on Activation and
Presentation; neither lower layer depends on Handler or on the other. These
unbounded internal constraints
are authorized while the packages are unreleased and developed atomically;
inter-package version policy remains open. Dependency Injection and Application
Graph do not depend on each other. The independent Graph-, Kernel-, and
DI-to-Diagnostics edges create no cross-package lifecycle integration or
universal exception boundary. Package manifests duplicate no root tool
dependencies.

`kongono/http-message` owns the selected PSR-7/PSR-17 concrete boundary.
`kongono/routing` separately owns `Kongono\Routing\` and requires only API
Metadata, Diagnostics, PHP 8.5, and `psr/http-message`. It does not depend on
HTTP Message, Kernel, Graph, DI, Diagnostic Handler, or PSR-15. Its immutable
bounded route declarations and matcher perform exact encoded method/path
selection without handler execution, authorization, I/O, or retained request
state.

`kongono/http-server` owns `Kongono\HttpServer\` and requires only API Metadata,
Diagnostics, Routing, PHP 8.5, and the PSR-15 handler and middleware interface
packages. It does not depend on HTTP Message, Kernel, Graph, DI, Diagnostic
Activation, Diagnostic Presentation, or Diagnostic Handler. It owns explicit
bounded immutable middleware sequencing and complete canonical route-name
terminal mapping, without transport or ambient request state.

`kongono/web-application` owns `Kongono\Web\Application\` and requires API
Metadata, Diagnostics, HTTP Server, Kernel, Routing, PHP 8.5, PSR-7 messages,
and the PSR-15 handler/middleware interfaces. It is the leaf explicit composition
boundary. No existing package depends on it, and it has no concrete HTTP
Message, Graph, DI, Activation, Presentation, Handler, adapter, or transport edge.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps all thirteen package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires all thirteen local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates API Metadata, Application Graph, Dependency Injection,
Diagnostic Activation, Diagnostic Handler, Diagnostic Presentation, Diagnostics,
HTTP Message, HTTP Server, Kernel, Routing, and Web Application in
deterministic lexicographic package-directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than any package.

## Boundaries and stability

The eleven physical package boundaries and the Phase 2E dependency graph are
implemented. Other package boundaries and dependency
rules remain open. Independent publishing, splitting, versioning, and release
automation also remain open and require authorization.

**Stability:** Internal engineering convention implementing the Accepted
monorepo decision.

## Phase 2 HTTP message package boundary

Phase 2A created no ninth package, namespace, Composer manifest, dependency,
autoload rule, or lockfile change. Phase 2C now implements the ninth package.

Phase 2B accepted one planned ninth first-party package; Phase 2C creates its
paths:

```text
directory              packages/http-message
Composer package       kongono/http-message
production namespace   Kongono\Http\Message\
test namespace         Kongono\Http\Message\Tests\
```

Its direct requirements are exactly:

```text
php: ^8.5
kongono/api-metadata: *
kongono/diagnostics: *
psr/http-message: ^2.0
psr/http-factory: ^1.1
```

The root requires `kongono/http-message: @dev`. There is no reverse
API Metadata or Diagnostics edge. HTTP Message must not depend on Kernel,
Dependency Injection, Application Graph, Diagnostic Activation, Diagnostic
Presentation, Diagnostic Handler, PSR-15, routing, adapters, runtimes, or a
third-party PSR-7 implementation. The Kernel remains transport-neutral and
PSR-free. Phase 2C creates only this package under its separately authenticated
directive; Phase 2D now creates the separate routing boundary.

## Implemented Phase 1H dependency graph

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
diagnostic-activation -> api-metadata
diagnostic-handler -> api-metadata
diagnostic-handler -> application-graph
diagnostic-handler -> dependency-injection
diagnostic-handler -> diagnostic-activation
diagnostic-handler -> diagnostic-presentation
diagnostic-handler -> kernel
diagnostic-presentation -> api-metadata
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
http-message -> api-metadata
http-message -> diagnostics
routing -> api-metadata
routing -> diagnostics
routing -> psr/http-message
http-server -> api-metadata
http-server -> diagnostics
http-server -> routing
http-server -> psr/http-server-handler
http-server -> psr/http-server-middleware
web-application -> api-metadata
web-application -> diagnostics
web-application -> http-server
web-application -> kernel
web-application -> routing
web-application -> psr/http-message
web-application -> psr/http-server-handler
web-application -> psr/http-server-middleware
framework root -> all thirteen packages
```

The eleven API Metadata consuming package-manifest constraints are
`"kongono/api-metadata": "*"`; the root constraint is
`"kongono/api-metadata": "@dev"`. Cycles, overlapping production namespaces,
self-dependencies, and undeclared package dependencies remain prohibited.
The former `Kongono\Kernel\Api\*` namespace is absent and no compatibility shim
exists. Kernel, Graph, and DI independently require Diagnostics; none requires
another semantic consumer. Current Composer/autoload behavior implements the twelve-package graph
documented above.

## Implemented Diagnostic Activation foundation

Phase 1C implements `packages/diagnostic-activation/` with Composer name
`kongono/diagnostic-activation` and namespace
`Kongono\DiagnosticActivation\`. It requires only API Metadata and PHP. Phase
1C added only the Experimental unbacked
`TrustedDeveloperBootstrapPermission` enum. Phase 1D adds only the Experimental
unbacked `AdapterControlledDeliveryPermission` enum for a future adapter's
already-determined decision for one current delivery.

Phase 1E adds the Experimental final readonly
`TrustedDeveloperPresentationEligibility` in the same package. It stores the
two typed decisions and computes their pure AND truth table for one current
delivery without changing the package graph or manifest.

Only Diagnostic Handler depends on Diagnostic Activation. Diagnostic Presentation
remains an independent lower layer, and the root consumes all three for monorepo
validation. Diagnostic Activation owns the two neutral permission types and
their pure combined eligibility value, not raw configuration, delivery evidence
or evaluation, enforcement, an adapter,
transport, exception, diagnostic identifier, or presentation behavior.

## Implemented Diagnostic Presentation leaf

Phase 0X implements this package boundary:

```text
packages/diagnostic-presentation/
```

Its Composer name is `kongono/diagnostic-presentation` and its production
namespace is `Kongono\DiagnosticPresentation\`. The directory and manifest are
part of the current twelve-package tree and thirteen-manifest count including the root.

The leaf depends directly on:

```text
diagnostic-presentation -> api-metadata
diagnostic-presentation -> diagnostics
diagnostic-presentation -> kernel
diagnostic-presentation -> application-graph
diagnostic-presentation -> dependency-injection
```

Only Diagnostic Handler depends on Diagnostic Presentation. There is no reverse
Handler edge, service registration, Kernel binding, DI definition, Graph node, or
ambient discovery, so the graph remains acyclic. The root consumes the leaf for
monorepo testing. Phase 0X adds its manifest, autoload/tooling integration,
eight production declarations, six package tests, README, and pure projection
behavior. Phase 0Z adds the ninth production declaration, one focused ten-test
PHP file, and two test-only golden HTML fixtures. The renderer creates no new
package edge or runtime binding; no trusted selector or production model exists.

## Implemented Diagnostic Handler leaf

Phase 1H implements this package boundary:

```text
packages/diagnostic-handler/
```

Its Composer name is `kongono/diagnostic-handler`, its production namespace is
`Kongono\DiagnosticHandler\`, and it requires PHP 8.5
or later. Its direct package dependencies are exactly:

```text
diagnostic-handler -> api-metadata
diagnostic-handler -> diagnostic-activation
diagnostic-handler -> diagnostic-presentation
diagnostic-handler -> kernel
diagnostic-handler -> application-graph
diagnostic-handler -> dependency-injection
```

Kernel, Application Graph, and Dependency Injection are direct dependencies
because their three separate exception markers form the presenter's
accepted source union. Diagnostic Handler has no direct dependency on
Diagnostics. No package depends on Diagnostic Handler, so it remains a leaf;
the framework root consumes it with the other seven packages.

The sole production declaration is the Experimental final
readonly `Kongono\DiagnosticHandler\TrustedDeveloperDiagnosticHtmlPresenter`.
It has no properties, explicit constructor, interface, trait, parent, factory,
alias, helper, exception family, diagnostic identifier, service registration,
DI definition, Graph node, Kernel binding, or ambient discovery. Its exact
method is:

```php
public function renderIfEligible(
    TrustedDeveloperPresentationEligibility $eligibility,
    KernelExceptionInterface|GraphExceptionInterface|DependencyInjectionExceptionInterface $source,
): ?string;
```

The presenter checks eligibility before any source accessor. False returns
`null`; true sequences the existing projector and renderer and returns the exact
HTML unchanged. Every failure propagates unchanged. The package has no adapter,
configuration reader, evidence evaluator, transport, delivery, response, or
production model. Kongono has thirteen implemented production packages and fourteen
Composer manifests including the root. Phase 2G is implemented; Phase 2H remains the
first runnable black-box milestone.

## Implemented Routing boundary

Phase 2D implements `packages/routing/` as `kongono/routing`. It contains ten
production declarations and no fixture files. Definitions and tables validate
bounded canonical names, methods, templates, and structural conflicts before
canonical binary name ordering. The matcher consumes only PSR-7 method and URI
path interfaces, preserves exact encoded bytes, chooses by specificity rather
than declaration order, and returns a fresh immutable match or `null`.

Routing itself creates no handler or middleware mapping, service binding, Graph node,
Kernel hook, current-route registry, authentication, authorization, bootstrap,
adapter, emission, generic production failure path, or runnable app.

## Implemented HTTP Server boundary

Phase 2E implements `packages/http-server/` as `kongono/http-server`. Its six
production declarations use the PSR-15 interfaces for an explicit immutable
pipeline of at most 128 middleware layers and a terminal routing handler with a
complete canonical route-name-to-handler map. A selected match is copied under
the reserved `RouteMatch::class` request attribute but remains untrusted and
non-authorizing. Unmatched output is delegated to an explicit handler.

HTTP Server catches no `Throwable`, supplies no error middleware, and creates no
bootstrap, Kernel operation scope, service binding, Graph node, adapter,
transport, emission, generic production response, or runnable app. Phase 2F
composes this unchanged lower layer. See the [HTTP server model](../architecture/http-server-model.md) and
[ADR 0047](../adr/0047-explicit-psr-15-middleware-and-terminal-routing.md).

## Implemented Web Application boundary

Phase 2F implements `packages/web-application/` as
`kongono/web-application`. Its six production declarations validate routing and
middleware composition before Kernel boot, expose the compiled application as a
PSR-15 handler, own one exact local Kernel scope per call, preserve primary and
cleanup failures, retire after uncertain lifecycle failure, and delegate
explicit shutdown. Two fixed empty-schema diagnostics and a separate package
marker own its deliberate failures.

The application retains only Kernel, pipeline, and a retirement boolean. It
creates no DI binding, Graph node, concrete HTTP Message dependency, ambient
request or scope, outer adapter, emission, generic failure mapping, trusted
diagnostic delivery, broader resource cleanup, or runnable fixture. See the
[Web Application model](../architecture/web-application-model.md) and
[ADR 0048](../adr/0048-web-application-bootstrap-and-kernel-operation-ownership.md).

See the [Phase 2 roadmap](../architecture/phase-2-roadmap.md) for the planned
milestone order without inferred package ownership.

## Phase 2 runnable fixture ownership

Phase 2H creates no fourteenth package. All runnable fixture PHP lives under
`tests/BlackBox/` in the root development namespace and depends on the existing
root dev autoload only. Production source and package tests never import it, and
its classes carry no API-status metadata.

The fixture implements the Public `WebDeliveryInterface` solely for black-box
acceptance, composes all thirteen real packages, and exposes a bounded CLI
subprocess protocol. Its fixed response snapshots and runner are test behavior,
not a production emitter, framework CLI, application template, or package
contract. See [ADR 0050](../adr/0050-phase-2-runnable-fixture-and-checkpoint.md)
and the [fixture model](../architecture/phase-2-runnable-fixture.md).
