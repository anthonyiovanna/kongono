# Package Layout

Phase 20B adds exactly the optional Experimental `packages/cache-contract`
neutral leaf with 21 production and 14 package-test PHP files, plus its manifest
and README. Phase 20C adds exactly the optional Experimental
`packages/cache-build` leaf with 24 production and 15 package-test PHP files,
plus its manifest and README. Phase 20D adds exactly the optional Experimental
`packages/cache-adapter-contract` leaf with 18 production and 13 package-test
PHP files and the separately optional Experimental
`packages/cache-adapter-memory` leaf with 10 production and 12 package-test PHP
files, plus their manifests and READMEs. The contract's three local production
edges point to API Metadata, Diagnostics, and Cache Contract. The memory
implementation adds those three plus Cache Adapter Contract. No existing
production package depends on either new leaf. The graph is 109 packages / 467 local production edges /
acyclic, with inventories 2,161 production, 1,138 package-test, and 104
root-test PHP files. The accepted cache packages define only
bounded immutable namespace/key, identity, codec, opaque-byte, caller-time,
finite-freshness, entry-version, operation/outcome, detached entry-evidence,
and nondisclosing diagnostic vocabulary plus explicit profiles, canonical
artifacts, exact-current validation, contained publication, a neutral adapter
protocol, and one bounded process-local implementation. They have no
serialization or external backend/provider compatibility. Phase 20E adds the
exact-current `packages/cache-runtime` leaf. Phase 20F adds
`packages/cache-application` and `skeletons/cache-minimal`. Phase 20G adds the
optional Experimental `packages/cache-inspection` upper evidence leaf with 23
production and six package-test PHP files. Its eight local production
dependencies are API Metadata, Application Graph Intelligence, Cache
Application, Cache Build, Cache Contract, Diagnostics, Observability, and
Security Audit. Phase 20H adds four root-test PHP files and no package or
production declaration. Phases 20A-H are Accepted and Phase 20 is closed. See [ADR 0199](../adr/0199-neutral-cache-contract-and-bounded-evidence-vocabulary.md).
See also [ADR 0200](../adr/0200-deterministic-cache-profile-artifact-and-publication.md).
See also [ADR 0201](../adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md).
See also [ADR 0203](../adr/0203-supported-cache-application-workflow-and-explicit-codec-policy-mapping.md).
See also [ADR 0204](../adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md)
and the [cache inspection guide](../architecture/cache-inspection-and-intelligence.md).
See also [ADR 0205](../adr/0205-phase-20-external-cache-acceptance-and-checkpoint.md)
and the [external acceptance guide](../architecture/phase-20-external-cache-acceptance.md).

Phase 19B adds exactly the optional Experimental `packages/asset-contract`
neutral leaf with 19 production and 11 package-test PHP files, plus its manifest
and README. Its two local production edges point only to API Metadata and
Diagnostics; no existing production package depends on it. The graph is 96
packages / 404 local production edges / acyclic, with inventories 1,880
production, 973 package-test, and 96 root-test PHP files. It defines only
bounded immutable logical/content identities, closed declarations and
relations, integrity evidence, inert preload hints, delivery metadata, and six
nondisclosing diagnostics. Empty declaration sets are valid. It has no path,
URL, filesystem, graph traversal, build, adapter, HTTP, view, or runtime API.
See [ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md)
and the [Asset Platform guide](../architecture/asset-platform.md). Phase 19A-B
are Accepted.

Phase 19C adds exactly the optional Experimental `packages/asset-build` leaf
with 30 production and 17 package-test PHP files, plus its manifest and README.
Its three local production edges point only to API Metadata, Diagnostics, and
Asset Contract; no existing production package depends on it. Phase 19D adds
the neutral Asset Adapter Contract and the separately optional bounded Vite
adapter. The graph is 99 packages / 416 local production edges / acyclic, with
inventories 1,959 production, 1,025 package-test, and 96 root-test PHP files.
Asset Build accepts
only explicit in-memory source bytes, validates one complete graph, copies bytes
under the fixed native profile, validates canonical exact-current artifact
evidence, and publishes contained content-addressed no-clobber output. It adds
no adapter, subprocess, network, URL/HTTP/view integration, command, runtime,
Vite, or Node dependency. See [ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md)
and [deterministic asset build](../architecture/deterministic-asset-build.md).
Phase 19A-H are Accepted and Phase 19 is closed. Phase 19E-G add the resolver,
application, and inspection packages without changing the native build
boundary. Phase 19H adds no production package or package-test PHP; it adds
four root-test PHP files under `tests/External/Asset`. Final topology is 102
packages / 435 local production edges / acyclic, with inventories 2,026
production / 1,055 package-test / 100 root-test PHP files. See the
[external acceptance guide](../architecture/phase-19-external-asset-acceptance.md).

Phase 18B adds the optional Experimental `packages/view-contract` neutral leaf
with 30 production and 17 package-test PHP files. Phase 18C adds exactly the
optional Experimental `packages/view-build` leaf with 30 production and 16
package-test PHP files, plus its manifest and README. View Build depends only
on PHP, API Metadata, Diagnostics, and View Contract; no existing production
package depends on it. The graph is 91 packages / 380 local edges / acyclic,
with inventories 1,786 production, 927 package-test, and 92 root-test PHP files.
View Build contains the closed source/compiler/artifact/publisher boundary but
no loader, renderer, runtime escaper, network/HTTP behavior, command, workflow,
inspection, or runtime. See [ADR 0181](../adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and [ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md).

Phase 18D adds exactly the optional Experimental `packages/view-runtime` leaf
with 19 production and 11 package-test PHP files, plus its manifest and README.
It depends only on PHP, API Metadata, Diagnostics, View Build, and View Contract;
no lower production package depends on it. The graph is 92 packages / 384 local
edges / acyclic, with inventories 1,805 production, 938 package-test, and 92
root-test PHP files. It authenticates only one explicit fixed two-file artifact
and renders synchronously with contextual escaping and operation-local bounds.
It parses or compiles no source while rendering. Generated code and the
deployment filesystem are trusted inputs. HTTP, commands, application workflow,
inspection, and acceptance remain outside the package. See [ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md)
and the [runtime guide](../architecture/compiled-view-runtime.md). Phase 18A-F
are Accepted at that boundary; Phase 18G-H are now Accepted and Phase 18 is
closed.

Phase 18E adds exactly the optional Experimental `packages/view-http` leaf with
11 production and 10 package-test PHP files, plus its manifest and README. It
depends only on PHP, API Metadata, Diagnostics, View Contract, and PSR HTTP
factory/message interfaces; `kongono/http-message` is development-only. The
graph is 93 packages / 387 local production edges / acyclic, with inventories
1,816 production, 948 package-test, and 92 root-test PHP files. Only completed
neutral results enter the adapter. Application code owns all response and
browser-security policy. See [ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md)
and the [HTTP adapter guide](../architecture/compiled-view-http.md). Phase 18A-F
are Accepted at that boundary; Phase 18G-H are now Accepted and Phase 18 is
closed.

Phase 18F adds exactly the optional Experimental `packages/view-application`
upper leaf with 20 production and five package-test PHP files, plus its manifest
and README. It depends only on PHP, API Metadata, Diagnostics, View Contract,
View Build, View Runtime, View HTTP, and PSR HTTP factory/message interfaces;
`kongono/http-message` remains development-only. At the Phase 18F boundary the
console application adds the sole production reverse edge. That graph is 94 packages / 394
local production edges / acyclic, with inventories 1,839 production, 956
package-test, and 92 root-test PHP files. See [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md)
and the [application workflow guide](../architecture/compiled-view-application-workflow.md).

Phase 18G adds exactly the optional Experimental `packages/view-inspection`
upper evidence leaf with 22 production and six package-test PHP files, plus its
manifest and README. Its eight local production dependencies are API Metadata,
Application Graph Intelligence, Diagnostics, Observability, Security Audit,
View Application, View Build, and View Contract. No production package depends
on it. The graph is 95 packages / 402 local production edges / acyclic, with
inventories 1,861 production, 962 package-test, and 92 root-test PHP files. It
contains no command, loader, renderer, generated-code execution, filesystem
inspection, recorder, exporter, repair, role implementation, or certification
surface. See [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the [inspection guide](../architecture/compiled-view-inspection-and-intelligence.md).

Phase 18H adds no production package or package-test PHP. It adds four
root-test PHP files under `tests/External/View`, so current inventories are
1,861 production, 962 package-test, and 96 root-test PHP files. The graph
remains 95 packages / 402 local production edges / acyclic. See
[ADR 0187](../adr/0187-phase-18-server-rendered-presentation-acceptance-and-checkpoint.md).

Phase 14G adds exactly the optional Experimental
`packages/model-invocation-inspection` upper evidence leaf with 22 production
and six package-test PHP files. It depends only on PHP, API Metadata,
Application Graph Intelligence, Diagnostics, Model Invocation Application,
Build, Contract, and Runtime, Observability, and Security Audit; no existing
production package depends on it. The graph is 75 packages / 289 local edges /
acyclic. It contains no provider SDK or adapter, credential lookup, registry,
retry/fallback, command, generated-PHP loading, recording, telemetry delivery,
repair, role execution, or background work. See the
[inspection guide](../architecture/model-invocation-inspection-and-evidence.md).

Phase 14F adds exactly the optional Experimental
`packages/model-invocation-application` outer leaf with 29 production and 11
package-test PHP files. It depends only on PHP, API Metadata, Diagnostics,
Model Invocation Contract, Model Invocation Build, and Model Invocation Runtime;
no existing production package depends on it. The graph is 74 packages / 280
local edges / acyclic. It contains no provider SDK or first-party adapter,
credential discovery, registry/default, retry/fallback, second profile, CLI,
retained session, background work, Agent port, or bridge dependency. See the
[application workflow guide](../architecture/model-invocation-application-workflow.md).

Phase 14E adds exactly the optional Experimental
`packages/model-invocation-agent-bridge` library with 13 production and nine
package-test PHP files. It depends inward only on Agent Principal Contract,
Agent Runtime, API Metadata, Diagnostics, Model Invocation Contract, and Model
Invocation Runtime; no production package depends on it. The graph is 73
packages / 275 local edges / acyclic. The package contains no proposal-port
implementation, provider, adapter, capability executor, registry, or workflow.

Phase 14D adds exactly the optional Experimental
`packages/model-invocation-runtime` library with 21 production declarations and
12 package tests. It depends only on PHP, API Metadata, Diagnostics, the
model-invocation contract, and model-invocation build packages; no existing
production package depends on it. The graph is 72 packages / 269 local edges / acyclic.

Phase 14B adds exactly the optional Experimental
`packages/model-invocation-contract` library with 41 production PHP files and
17 package-test PHP files. It depends only on API Metadata and Diagnostics; no
only `kongono/model-invocation-build` depends on it. Phase 14C adds exactly the
optional Experimental `packages/model-invocation-build` library with 19
production and 14 package-test PHP files. It depends only on API Metadata,
Diagnostics, and Model Invocation Contract; no existing production package
depends on it. The production topology is 71 packages / 265 local edges /
acyclic. The [contract
guide](../architecture/model-invocation-contract-foundation.md) keeps neutral
contracts away from vendor SDKs and concrete
provider adapters as explicit application-supplied outer leaves. It selects no
provider, adapter, model
profile, credential mechanism, or runtime. The [plan-artifact
guide](../architecture/model-invocation-plan-artifact.md) defines the separate
deterministic privacy-preserving evidence and publication boundary.

Phase 13B adds the opt-in Experimental `packages/agent-principal-contract`
package. Phase 13C adds the opt-in Experimental
`packages/agent-declaration-build` package. Phase 13D adds exactly the opt-in
Experimental `packages/agent-admission` package above declaration build, Agent
Principal Contract, API Metadata, Authentication, Authorization, Diagnostics,
Identity Access, and PHP. No existing production package depends on admission.
Phase 13E adds exactly the opt-in Experimental `packages/agent-runtime`
package above Agent Admission, Agent Declaration Build, Agent Principal
Contract, API Metadata, Diagnostics, and PHP. No existing production package
depends on runtime except the root aggregate and the Phase 13F outer package.
Phase 13F adds exactly the opt-in Experimental `packages/agent-application`
package with those six local dependencies. No production package depends on
Agent Application except the root aggregate and Phase 13G's upper evidence
leaf. Phase 13G adds exactly the opt-in Experimental
`packages/agent-inspection` package with PHP and the ten Agent Admission, Agent
Application, Agent Declaration Build, Agent Principal Contract, Agent Runtime,
API Metadata, Application Graph Intelligence, Diagnostics, Observability, and
Security Audit dependencies. No production package depends on Agent Inspection
except the root aggregate. The topology is exactly 69 acyclic production
packages / 260 local production edges. Phase 13H adds only the root-owned
external harness and checkpoint evidence named by the
[roadmap](../architecture/phase-13-roadmap.md); it adds no package, manifest,
dependency, namespace, or public API.

Phase 12H adds no production package and no local production edge. The topology
remains exactly 63 packages / 226 acyclic edges; the new root-owned harness is
test evidence outside every package. See
[Phase 12 external acceptance](../architecture/phase-12-external-acceptance.md).

> **Phase 12 current boundary:** Phases 12A-12H are implemented to their
> accepted boundaries. Phase 12H adds no package or production edge; the
> topology remains 63 packages / 226 acyclic local production edges.

> **Phase 13 current boundary:** Separate opt-in contract, declaration-build,
> admission, one-operation runtime, exact-current single-profile Agent
> Application, and visibility-safe Agent Inspection packages exist. No provider
> SDK, profile registry, live monitor, command, retained session, or background
> workflow exists.

## Purpose and convention

Kongono's initial monorepo gives each authorized framework package explicit
source ownership beneath `packages/`. Phase 3C adds the fourteenth production
package:

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
|-- php-cgi/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- routing/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
|-- web-application/
|   |-- composer.json
|   |-- README.md
|   |-- src/
|   `-- tests/
`-- web-runtime/
    |-- composer.json
    |-- README.md
    |-- src/
    `-- tests/
```

Empty or speculative package directories remain prohibited. A package may later
be published independently, but this layout selects no splitting, publishing,
release, or version-coordination mechanism.

## Composer integration

Each package manifest requires `>=8.5.0 <8.6.0`, owns its production namespace
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
boundary. Web Runtime depends on it, and it has no concrete HTTP
Message, Graph, DI, Activation, Presentation, Handler, adapter, or transport edge.

`kongono/web-runtime` owns `Kongono\Web\Runtime\` as the top-level leaf above
Web Application and the trusted-diagnostic foundations. It owns the Public
delivery port, fixed generic or eligible trusted failure selection, and exact
send/close orchestration without selecting a production SAPI, server, listener,
emitter, or deployment profile.

Composer does not import a dependency's `autoload-dev` mapping into the consuming
root project. The monorepo root therefore maps all thirty-five package test namespaces in
its own `autoload-dev`, alongside the root `Kongono\Tests\` mapping. This makes
package tests directly autoloadable during root development without exposing
them through production `autoload`.

The root manifest uses one canonical Composer path repository at `packages/*`
and requires all thirty-five local packages with exact `@dev` constraints. These per-package
stability flags permit untagged local packages without changing repository-wide
minimum stability or inventing versions. Composer generates the lock and root
autoload metadata; manual lock edits and custom autoloaders remain prohibited.

Run package-manifest validation from the root with:

```bash
composer validate:packages
```

The command validates all thirty-five package manifests in deterministic
lexicographic package-directory order and is part of `composer quality`
immediately after root-manifest validation. Source uses package-owned production
namespaces, package tests use package development namespaces, and all package
source and tests participate in root style, static-analysis, and PHPUnit checks.
Repository-wide production inventory and API-status enforcement belongs to the
root architecture suite rather than any package.

## Boundaries and stability

The thirty-five physical package boundaries are implemented. The root-owned Phase
2 fixture is test infrastructure, not a package or production application.
Other package boundaries and dependency
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
php: >=8.5.0 <8.6.0
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

## Current implemented dependency graph

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
web-runtime -> api-metadata
web-runtime -> application-graph
web-runtime -> dependency-injection
web-runtime -> diagnostic-activation
web-runtime -> diagnostic-handler
web-runtime -> diagnostics
web-runtime -> http-message
web-runtime -> kernel
web-runtime -> web-application
web-runtime -> psr/http-message
php-cgi -> api-metadata
php-cgi -> diagnostics
php-cgi -> http-message
php-cgi -> psr/http-message
console-runtime -> api-metadata
console-runtime -> console
console-runtime -> diagnostics
console-presentation -> api-metadata
console-presentation -> console
console-presentation -> diagnostics
console-commands -> api-metadata
console-commands -> application-graph
console-commands -> composition
console-commands -> console
console-commands -> console-runtime
console-commands -> diagnostics
console-commands -> hantei
framework root -> all thirty-five packages
```

The twenty API Metadata consuming package-manifest constraints are
`"kongono/api-metadata": "*"`; the root constraint is
`"kongono/api-metadata": "@dev"`. Cycles, overlapping production namespaces,
self-dependencies, and undeclared package dependencies remain prohibited.
The former `Kongono\Kernel\Api\*` namespace is absent and no compatibility shim
exists. Kernel, Graph, and DI independently require Diagnostics; none requires
another semantic consumer. Current Composer/autoload behavior implements the
thirty-five-package graph documented above.

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
part of the then-current fourteen-package tree and fifteen-manifest count including
the root.

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
Diagnostics. Web Runtime depends on Diagnostic Handler, while Handler retains no
reverse edge; the framework root consumes all thirty-five packages.

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
production model. At Phase 2G, Kongono had fourteen implemented production packages and fifteen
Composer manifests including the root. Phase 2G is implemented, and Phase 2H's
root-owned fixture proves the runnable black-box milestone.

## Implemented Routing boundary

Phase 2D implements `packages/routing/` as `kongono/routing`. It contains ten
production declarations and no fixture files. Definitions and tables validate
bounded canonical names, methods, templates, and structural conflicts before
canonical binary name ordering. The matcher consumes only PSR-7 method and URI
path interfaces, preserves exact encoded bytes, chooses by specificity rather
than declaration order, and returns a fresh immutable match or `null`.

Routing itself creates no handler or middleware mapping, service binding, Graph node,
Kernel hook, current-route registry, authentication, authorization, bootstrap,
adapter, emission, or generic production failure path. The root-owned Phase 2
fixture composes Routing externally; it is not Routing package behavior or a
production application.

## Implemented HTTP Server boundary

Phase 2E implements `packages/http-server/` as `kongono/http-server`. Its six
production declarations use the PSR-15 interfaces for an explicit immutable
pipeline of at most 128 middleware layers and a terminal routing handler with a
complete canonical route-name-to-handler map. A selected match is copied under
the reserved `RouteMatch::class` request attribute but remains untrusted and
non-authorizing. Unmatched output is delegated to an explicit handler.

HTTP Server catches no `Throwable`, supplies no error middleware, and creates no
bootstrap, Kernel operation scope, service binding, Graph node, adapter,
transport, emission, or generic production response. Phase 2F
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
diagnostic delivery, or broader resource cleanup. The root-owned Phase 2 fixture
proves this package through Web Runtime without becoming Web Application package
behavior or a production application. See the
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

## Phase 3 PHP CGI package boundary

[ADR 0052](../adr/0052-php-cgi-fastcgi-responder-profile.md), [ADR
0053](../adr/0053-bounded-php-cgi-inbound-conversion.md), [ADR
0054](../adr/0054-bounded-php-cgi-response-emission.md), [ADR
0055](../adr/0055-immutable-php-cgi-operator-bootstrap-and-trust-inputs.md), and the [PHP CGI
profile](../architecture/php-cgi-fastcgi-profile.md) define the implemented
Phase 3C/3D/3E package boundary:

```text
directory              packages/php-cgi
Composer package       kongono/php-cgi
production namespace   Kongono\PhpCgi\
role                   outer leaf for PHP cgi-fcgi FastCGI Responder
```

The current leaf depends only on PHP `^8.5`, API Metadata, Diagnostic Activation,
Diagnostics, HTTP Message, and `psr/http-message ^2.0`. The dependency on
Diagnostic Activation supplies existing typed permission values; no reverse edge
exists. A Web Runtime edge remains later and may be added only when authorized
source requires it. PHP CGI must
not depend directly on Kernel, Routing, HTTP Server, Web
Application, Application Graph, Dependency Injection, Diagnostic Presentation,
Diagnostic Handler, a web server, a FastCGI library, or another framework. No
lower package may depend on it.

The converter, emitter, literal operator profile, immutable bootstrap result,
package marker, and concrete exceptions are Experimental; diagnostics,
bootstrap validation, current-delivery permission evaluation, and host-specific
helpers are Internal. Phase 3C implements bounded explicit inbound conversion,
Phase 3D implements bounded response staging/emission, and Phase 3E implements
the two independent trust inputs. Only bootstrap validation reads `PHP_SAPI` and
ten exact INI values. The implemented package count is exactly fourteen. Phase
3F adds an Internal concrete delivery and Experimental entry within the existing
PHP CGI outer leaf. PHP CGI now depends directly on Web Runtime and still does
not depend directly on Web Application; no lower package depends on PHP CGI.
Only the delivery capture additionally reads bounded superglobal/PHP-SAPI/input
state. Phase 3G adds `CgiFrontController` and
`CgiEntryShutdownException` inside the same outer leaf. The controller accepts
Web Runtime plus a typed shutdown closure, so no PHP-CGI-to-Web-Application or
reverse edge is added. It owns one entry attempt and one shutdown attempt;
application composition and partial-boot cleanup remain caller-owned. The
deployment constraints add no production package, manifest, listener, server
configuration, or deployment artifact. Phase 3H adds only root-owned external
acceptance code; no production package depends on it. That harness proves the
selected synchronous PHP-CGI FastCGI RESPONDER profile but is not a production
or supported server/client. Uncertain emission or shutdown requires external
process retirement, production trusted diagnostics remain denied, and process,
proxy, TLS, credentials, logs, monitoring, incidents, and deployment ownership
remain external. Phase 3 is complete; no next phase starts automatically.

## Phase 4A package boundary

Phase 3 is complete at checkpoint
`6aacafc92f07b567818173d660e2e182dfa83d23`. The implemented production layout
remains exactly fourteen packages and the existing acyclic dependency graph is
unchanged. Phase 4A selects no developer-intelligence or knowledge package,
namespace, autoload mapping, manifest, service, Graph edge, command, or runtime.

Kongono Chie, Kongono Hantei, and Kongono Oshie were planned optional developer
companions above a future AI-independent knowledge foundation at Phase 4A; they
were not application Agent Principals or implemented then. Phase 4A retained
the mixed `>=8.5` and `^8.5` transitional constraints. See the
[developer-intelligence architecture](../architecture/developer-intelligence.md)
and [Phase 4 roadmap](../architecture/phase-4-roadmap.md).

### Knowledge

`packages/knowledge` is the fifteenth acyclic production package. Its namespace
is `Kongono\Knowledge\`; production code lives under `src/`, package tests under
`tests/`, the exact inventory under `resources/`, and the extensionless Composer
bin entry under `bin/kongono-knowledge`. It depends only on API Metadata and
Diagnostics plus PHP's hash and JSON extensions. The root consumes it; no other
package does. It has no Kernel, Graph, DI, HTTP, routing, web, SAPI, role,
provider, database, network, or third-party dependency.

### Chie

### Hantei

`packages/hantei` is the seventeenth package and an optional leaf consumed by
root composition. It depends directly on API Metadata, Diagnostics, and
Knowledge, never Chie. Its resource owns the closed profile; source owns the
verifier and bounded internals; tests own direct semantic and disclosure proof.
`packages/chie` is the sixteenth acyclic production package. Its namespace is
`Kongono\Chie\`; production code lives under `src/`, six package test files
under `tests/`, the closed topic catalog under `resources/`, and the
extensionless Composer bin under `bin/kongono-chie`. It depends only on API
Metadata, Diagnostics, and Knowledge plus `ext-json`. The root consumes it; no
other package does. It has no Kernel, Graph, DI, HTTP, routing, web, SAPI,
presentation, provider, database, network, model, search, or third-party
dependency.

### `packages/oshie`

The eighteenth package owns the Experimental read-only teaching facade, closed curriculum resource, immutable cited lesson and exercise values, CLI boundary, diagnostics, and tests. It depends only on API Metadata, Diagnostics, Knowledge, PHP, `ext-hash`, and `ext-json`; it has no Chie, Hantei, HTTP, process, network, model, or provider dependency.

## Phase 5A package boundary

Phase 4 is complete at checkpoint
`358e412929d6bb7316195d3a1748c346a2de0e1b` with exactly eighteen acyclic
production packages and eighteen package manifests. Phase 5A is documentation
only and creates no nineteenth package, namespace, manifest, autoload mapping,
dependency edge, command, generated-artifact directory, or application
template.

No package name or ownership boundary is selected for future composition
source, deterministic build/check, generated wiring, or application skeleton
work. The current Dependency Injection package and Application Graph remain
unchanged and unintegrated. Phase 5B completes the separately authenticated
rolling current-minor Composer/platform metadata migration. PHP 8.5 is the sole
compatibility-certified minor, with PHP 8.5.9 on Windows proved and stable
eligible 8.5 patches remaining in range. PHP 8.6, PHP 9, prerelease, nightly,
and automatic future-minor support are not claimed. The migration adds no
runtime semantics, package, dependency, public API, polyfill, shim, release/LTS
promise, composition/build capability, or generated-runtime API. Phase 5A
remains accepted. Phase 5B is accepted and its bounded PHP constraint is
current.

Phase 5C accepts source/declaration/lifecycle architecture. Phase 5D creates the
nineteenth package, `packages/composition` / `kongono/composition`, under
`Kongono\Composition\`. It depends exactly on API Metadata, Diagnostics, PHP,
`ext-hash`, and `ext-json`; it owns the Experimental build/artifact and
generated-wiring/lifecycle-support APIs, the reusable application workflow, and
18 diagnostics. It has no package command, schema file, generated-artifact directory,
runtime integration, DI, Graph, Kernel, routing, HTTP, web, or developer-role
dependency. Phase 5F adds the exact generated loader within Composition and
one `web-runtime -> composition` edge; it adds no second package. PHP-CGI uses
its existing Web Runtime edge, and Kernel, Routing, HTTP Server, Web Application,
and PHP-CGI gain no Composition edge. Current DI and Graph remain unintegrated.
See the
[composition source guide](../architecture/composition-source.md) and
[declaration guide](composition-declarations.md).
[composition build guide](composition-build.md).
[generated composition wiring guide](../architecture/generated-composition-wiring.md).
[generated runtime integration guide](../architecture/generated-runtime-integration.md).
[application workflow guide](application-workflow.md). Phase 5G adds only
application-owned Composer scripts in the copy-once skeleton; it adds no
package `bin`, framework CLI, listener, or deployment behavior.

Phase 11E adds the separate leaf `packages/application-graph-artifact` /
`kongono/application-graph-artifact`. Phase 11F brings it to 18 production PHP
files and nine package-test PHP files. It owns canonical
serialization, structural and caller-relative current validation, guarded
explicit loading, atomic no-clobber publication, and bounded aggregate
inspection. Only its loader and publisher perform filesystem I/O. The existing
upper `kongono/console-application` package is its sole production consumer,
creating exactly one new local edge and no reverse lower-layer dependency.

## Phase 6E database package boundary

Phase 5 is complete at checkpoint
`c431482bea664ce3c3201794332c62cab1ed034e` with exactly nineteen production
packages and nineteen package manifests. Phase 6A is documentation and
architecture only. It creates no twentieth package, namespace, manifest,
autoload mapping, dependency edge, command, configuration key, provider,
database runtime, SQL API, migration system, mapper, repository, generated data
artifact, or Application Graph integration.

Phase 6B accepts exactly two Experimental package boundaries and Phase 6C
implements them:

| Package | Namespace | Direct dependencies |
| --- | --- | --- |
| `kongono/database` | `Kongono\Database\` | PHP `>=8.5.0 <8.6.0`; `kongono/api-metadata @dev`; `kongono/diagnostics @dev` |
| `kongono/database-pdo-pgsql` | `Kongono\DatabasePdoPgsql\` | PHP `>=8.5.0 <8.6.0`; `ext-pdo`; `ext-pdo_pgsql`; `kongono/database @dev` |

The first adapter depends on the neutral package; the neutral package never
depends on the adapter. Neither has a dependency on Kernel, Composition,
Dependency Injection, Application Graph, HTTP, developer-intelligence roles, or
another Data Platform layer. PostgreSQL 17.2 through PDO `pdo_pgsql` is only the
first proved profile. Phase 6G adds composition integration only in the outer
database-application package.

Phase 6D adds two higher-layer boundaries:

| Package | Namespace | Direct dependencies |
| --- | --- | --- |
| `kongono/database-sql` | `Kongono\DatabaseSql\` | PHP `>=8.5.0 <8.6.0`; `kongono/api-metadata @dev`; `kongono/database @dev`; `kongono/diagnostics @dev` |
| `kongono/database-sql-pgsql` | `Kongono\DatabaseSqlPgsql\` | PHP `>=8.5.0 <8.6.0`; `kongono/database @dev`; `kongono/database-sql @dev` |

Phase 6E adds two more higher-layer boundaries:

| Package | Namespace | Direct dependencies |
| --- | --- | --- |
| `kongono/database-migration` | `Kongono\DatabaseMigration\` | PHP `>=8.5.0 <8.6.0`; `ext-hash`; `ext-json`; `kongono/api-metadata @dev`; `kongono/database @dev`; `kongono/database-sql @dev`; `kongono/diagnostics @dev` |
| `kongono/database-migration-pgsql` | `Kongono\DatabaseMigrationPgsql\` | PHP `>=8.5.0 <8.6.0`; `kongono/api-metadata @dev`; `kongono/database @dev`; `kongono/database-migration @dev`; `kongono/database-sql @dev` |

Phase 6F adds one optional higher-layer boundary:

| Package | Namespace | Direct dependencies |
| --- | --- | --- |
| `kongono/database-mapping` | `Kongono\DatabaseMapping\` | PHP `>=8.5.0 <8.6.0`; `ext-hash`; `ext-json`; `kongono/api-metadata @dev`; `kongono/database @dev`; `kongono/database-sql @dev`; `kongono/diagnostics @dev` |

Phase 6G adds one provider-neutral outer boundary:

| Package | Namespace | Direct dependencies |
| --- | --- | --- |
| `kongono/database-application` | `Kongono\DatabaseApplication\` | PHP `>=8.5.0 <8.6.0`; `kongono/api-metadata @dev`; `kongono/composition @dev`; `kongono/database @dev`; `kongono/database-mapping @dev`; `kongono/diagnostics @dev` |

Phase 6G adds `kongono/database-application` as the eighth Data Platform package.
That phase brought the graph to twenty-seven package manifests. The SQL compiler depends one
way on neutral plans; neither low-level package depends on either higher layer.
The migration executor depends one way on the neutral migration model and lower
database/SQL contracts. Mapping depends one way on the neutral database and SQL
contracts; no lower package depends upward on mapping. The root requirements,
autoload and validation tools include all eight database
packages, while the PHP range and Phase 5 lifecycle ownership remain unchanged.
PDO stays internal to the existing adapter, and the provider harness remains
external test evidence. Mapping source and generated output remain application-
owned derived build evidence and are not checked in. The outer integration
package owns exact-current loading, composition, and operation sessions. No ORM,
pooling, migration startup, deployment behavior, or runtime telemetry exists.
Phase 6H completes the milestone at checkpoint
`f5903134e77bb2ecfaece6ee6b435a8ab2ecbd05` with external acceptance,
observational measurement evidence, documentation reconciliation, and no new
production package. See the
[Data Platform guide](../architecture/data-platform.md), [database contract](../architecture/database-contract.md),
[SQL plan guide](../architecture/database-sql-plans.md),
[migration guide](../architecture/database-migrations.md),
[mapping guide](../architecture/database-mapping.md),
[engineering contract tables](database-contracts.md), and [Phase 6 roadmap](../architecture/phase-6-roadmap.md).

## Phase 7 persistence package boundary

At the Phase 7 checkpoint, the graph had exactly thirty production packages and
thirty package manifests. Phase 7B adds `kongono/persistence` with the
`Kongono\Persistence\` production namespace and package-owned test namespace.
Phase 7C adds `kongono/persistence-build` with `Kongono\PersistenceBuild\`. The
upper build package depends on API Metadata, Database Mapping, Diagnostics,
neutral Persistence, and PHP. Neither neutral Persistence nor Database Mapping
depends upward on it. The root registers both for Composer, style, static
analysis, PHPUnit, Hantei, and API-status/inventory validation.

The persistence capability remains an optional upper layer. Lower Phase 6
Database, SQL, migration, mapping, and database-application packages must not
depend upward on it, and direct SQL/Data Mapper use remains first-class. The
Phase 7C build package implements strict source/model/artifact compilation,
validation, and guarded publication. Phase 7D extends the same package with
generated ordinary PHP state evidence, structural/current validation, and
content-addressed publication; it adds no package or dependency.
Phase 7E adds `kongono/database` as the sole new direct dependency of
`kongono/persistence` and implements one concrete single-entity operation
context there. Lower database packages do not depend upward; `persistence-build`
remains above persistence. Phase 7F adds repository-source compilation and
generated aggregate repositories within these two existing packages. No new
package, generated-code loader, transaction ownership, SQL generation, command,
skeleton, or Composition integration exists. Phase 7G adds the upper
`kongono/persistence-application` package, exact-current loaders, and optional
`persistence-minimal` skeleton without changing lower-package ownership. This
brings the graph to thirty production packages. Phase 7H closes the milestone
with root-owned external acceptance and adds no production package or upward
authority to a lower package.
See [ADR 0087](../adr/0087-explicit-persistence-model-and-compile-time-orm-boundary.md),
[ADR 0088](../adr/0088-operation-scoped-identity-change-tracking-and-transaction-authority.md),
[ADR 0089](../adr/0089-neutral-persistence-contracts-and-package-boundary.md),
[ADR 0090](../adr/0090-deterministic-persistence-model-and-artifact-compilation.md),
[ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md),
[ADR 0095](../adr/0095-phase-7-external-persistence-acceptance-and-checkpoint.md),
[persistence build engineering](persistence-build.md),
[persistence generation engineering](persistence-generation.md),
the [Deterministic Persistence guide](../architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](../architecture/phase-7-roadmap.md).

## Phase 8A through Phase 8G Structured CLI package boundary

Phase 8B adds `kongono/console` under `Kongono\Console\` with a package-owned
test namespace. It depends only on API Metadata, Diagnostics, and PHP. The root
requires it and includes its source and tests in Composer autoloading, package
validation, style, PHPStan, PHPUnit, Hantei, and API-status/inventory proof.
Phase 8C adds `kongono/console-build` under `Kongono\ConsoleBuild\`. It depends
only on Console, API Metadata, Diagnostics, Hash, JSON, and PHP, and participates
in the same root quality surfaces. Phase 8D adds `kongono/console-runtime` under
`Kongono\ConsoleRuntime\`. It depends only on Console, API Metadata,
Diagnostics, and PHP and participates in the same root quality surfaces. Phase
8E adds `kongono/console-presentation` under
`Kongono\ConsolePresentation\`, depending only on Console, API Metadata,
Diagnostics, and PHP. Phase 8F adds `kongono/console-commands` under
`Kongono\ConsoleCommands\`. Its production dependencies are exactly API
Metadata, Application Graph, Composition, Console, Console Runtime,
Diagnostics, Hantei, and PHP; Console Build is development-only. Phase 8G adds
`kongono/console-application` under `Kongono\ConsoleApplication\`. Its exact
production dependencies are API Metadata, Composition, Console, Console
Presentation, Console Runtime, Diagnostics, and PHP; Console Build is
development-only. It owns the sole package binary source, `bin/kongo`. The
graph now has exactly thirty-six acyclic production packages and manifests.
Existing Kernel, Application Graph, Composition, HTTP, database, persistence,
and developer-intelligence ownership remains unchanged.

The neutral CLI contracts remain independent of capability
adapters. Capability adapters may depend inward on them; lower packages never
depend on the outer executable. The executable and application
integration are an upper Composition leaf. Runtime scanning, Reflection
discovery, mutable registries, service location, container injection, hidden
autowiring, and property-hook dependency resolution are rejected.

Phase 8A accepts [ADR 0096](../adr/0096-explicit-structured-cli-and-automation-boundary.md),
[ADR 0097](../adr/0097-deterministic-command-lifecycle-and-presentation-separation.md),
the [Structured CLI guide](../architecture/structured-cli.md), and the
[Phase 8 roadmap](../architecture/phase-8-roadmap.md). Exact package ownership
for later implementation is fixed by
[ADR 0098](../adr/0098-neutral-structured-cli-contracts-and-package-boundary.md).
[ADR 0099](../adr/0099-deterministic-command-parsing-and-definition-evidence.md)
owns the parser and build-evidence boundary. [ADR 0100](../adr/0100-operation-scoped-command-execution-and-cleanup.md)
owns the explicit operation-scoped execution boundary. ADR 0101 owns the
implemented deterministic presentation boundary. ADR 0102 owns the implemented
read-only framework capability package and exact generated Composition wiring.
ADR 0103 owns the implemented `kongono/console-application` upper leaf, the
official one-shot `kongo` executable, and exactly `application:build` with
non-mutating plan and explicit apply. No lower dependency, alias, second
command, daemon, watcher, installer, deployment manager, or shell is added.
Phase 8H adds only four root test PHP files and two documentation authorities;
it adds no package or production dependency. Its real-process contract is
documented in the [Phase 8 external acceptance guide](../architecture/phase-8-external-acceptance.md).

## Phase 9B Identity Access package boundary

Phase 9B adds `packages/identity-access` as production package
`kongono/identity-access` under `Kongono\IdentityAccess\`. Its description is
`Neutral identity and access contracts for Kongono Framework.` Its exact
production dependencies are `kongono/api-metadata: *`,
`kongono/diagnostics: *`, and PHP `>=8.5.0 <8.6.0`. The graph now has exactly
thirty-seven acyclic production packages and manifests at Phase 9B. No existing production
package depends on Identity Access.

The root source directory owns the neutral principal, authentication outcome,
authorization decision, action/resource, and Security Context values.
`src/Diagnostic` owns only the Internal five-case diagnostic catalog;
`src/Exception` owns the Public package marker, Internal abstract base, and
Experimental invalid-value exception. Every other production declaration is
Experimental. The package test namespace is
`Kongono\IdentityAccess\Tests\`.

Identity Access remains below credential/session transports, provider adapters,
HTTP integration, policy/application workflows, and executable edges. It does
not depend on Kernel, Dependency Injection, Composition, Application Graph,
HTTP, Routing, Web Runtime, PHP CGI, database, persistence, Console,
developer-intelligence, telemetry, extensions, applications, Symfony, PSR
security packages, or third-party runtime packages. It owns no schema,
generated output, command, executable, skeleton, or runtime integration.
Composition remains the owner of later explicit construction and generated
typed wiring.

See [ADR 0105](../adr/0105-explicit-identity-authentication-and-authorization-boundary.md),
[ADR 0106](../adr/0106-fail-closed-security-context-and-policy-separation.md),
[ADR 0107](../adr/0107-neutral-identity-and-access-contracts-and-package-boundary.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9C Authentication package boundary

Phase 9C adds `packages/authentication` as production package
`kongono/authentication` under `Kongono\Authentication\`. Its description is
`Deterministic explicit authentication orchestration for Kongono Framework.`
Its exact production dependencies are `kongono/api-metadata: *`,
`kongono/diagnostics: *`, `kongono/identity-access: *`, and PHP
`>=8.5.0 <8.6.0`. The graph now has exactly thirty-eight acyclic production
packages and manifests. No existing production package depends on
Authentication.

The root source directory owns canonical mechanisms, explicit instants,
strength and requirement values, caller-owned evidence and verifier SPIs,
validated facts, verification outcomes, and the stateless orchestrator.
`src/Diagnostic` owns the Internal eight-case diagnostic catalog.
`src/Exception` owns the Public package marker, Internal abstract base, and
Experimental validation, cleanup, and combined-failure exceptions. Every other
production declaration is Experimental. The package test namespace is
`Kongono\Authentication\Tests\`.

Authentication is an upper consumer of neutral Identity Access vocabulary. It
does not depend on Kernel, Dependency Injection, Composition, Application
Graph, HTTP, Routing, Web Runtime, PHP CGI, database, persistence, Console,
Chie, Hantei, Oshie, telemetry, application code, Symfony, PSR security
packages, or provider SDKs. It defines no credential/provider, session/cookie
transport, authorization evaluator, policy, Security Context construction,
Composition integration, audit, telemetry, command, or application workflow.

See [ADR 0108](../adr/0108-deterministic-explicit-authentication-orchestration.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9D Authorization package boundary

Phase 9D adds `packages/authorization` as production package
`kongono/authorization` under `Kongono\Authorization\`. Its description is
`Deterministic deny-by-default authorization evaluation for Kongono Framework.`
Its exact production dependencies are `kongono/api-metadata: *`,
`kongono/diagnostics: *`, `kongono/identity-access: *`, and PHP
`>=8.5.0 <8.6.0`. The graph now has exactly thirty-nine acyclic production
packages and manifests. No existing production package depends on
Authorization.

The root source directory owns exact policy identity values, opaque evidence
SPIs, the explicit request, evaluator SPI, deny-by-default engine, receipt
reason, bounded receipt, and inseparable evaluation. `src/Diagnostic` owns the
Internal six-case catalog; `src/Exception` owns the Public empty marker,
Internal abstract base, and Experimental validation and evaluation exceptions.
Every other production declaration is Experimental. The package test namespace
is `Kongono\Authorization\Tests\`.

Authorization is an upper consumer of neutral Identity Access vocabulary. It
does not depend on Authentication, Kernel, Dependency Injection, Composition,
Application Graph, HTTP, Routing, Web Runtime, PHP CGI, database, persistence,
Console, Chie, Hantei, Oshie, telemetry, application code, Symfony, PSR security
packages, or provider SDKs. It defines no policy language/build/loader,
roles/permissions, ambient guard, Security Context construction,
protected-capability enforcement, HTTP/session transport, database enforcement,
audit, telemetry, command, workflow, or Phase 9E behavior.

See [ADR 0109](../adr/0109-deterministic-deny-by-default-authorization-evaluation.md),
the [Identity and Access Security guide](../architecture/identity-and-access-security.md),
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9E Security Runtime package boundary

Phase 9E adds `packages/security-runtime` as production package
`kongono/security-runtime` under `Kongono\SecurityRuntime\`. Its exact
production dependencies are PHP `>=8.5.0 <8.6.0`, `kongono/api-metadata: *`,
`kongono/authentication: *`, `kongono/composition: *`,
`kongono/diagnostics: *`, and `kongono/identity-access: *`. The graph now has
exactly forty acyclic production packages and manifests. No existing inward
production package depends on Security Runtime.

The package owns stateless context construction, exact generated
security-profile startup, one marker-only operation root, and one operation
owner. `src/Diagnostic` owns the Internal three-case catalog;
`src/Exception` owns the Public package marker, Internal abstract base, and
Experimental validation, profile, and lifecycle exceptions. Every other
production declaration is Experimental.

Security Runtime is an outer consumer of Authentication, Composition, and
neutral Identity Access. It has no Authorization, HTTP, Web Runtime, Kernel,
Database, Persistence, Dependency Injection, Application Graph, Console,
developer-intelligence, provider SDK, or third-party dependency. It implements
no policy evaluation/enforcement, credential/session transport, login, cookie
profile, policy build/currentness, workflow, skeleton, or Phase 9F behavior.

See [ADR 0110](../adr/0110-operation-scoped-security-context-and-generated-composition-inputs.md),
the [operation-scoped Security Context guide](../architecture/operation-scoped-security-context.md),
and the fixed [Phase 9 roadmap](../architecture/phase-9-roadmap.md).

## Phase 9F Security HTTP and Enforcement package boundaries

Phase 9F adds `packages/security-http` as `kongono/security-http` under
`Kongono\SecurityHttp\`. Its exact production dependencies are PHP
`>=8.5.0 <8.6.0`, API Metadata, Authentication, Diagnostics, Security Runtime,
and `psr/http-message:^2.0`. It owns only the selected same-origin HTTPS
`browser.session.v1` adapter, bounded cookie/CSRF transport values, deterministic
cookie header values, and operation-entry owner. It has no Authorization,
Security Enforcement, HTTP Server, Routing, Web Runtime, Kernel, storage,
provider SDK, or new third-party dependency.

Phase 9F also adds `packages/security-enforcement` as
`kongono/security-enforcement` under `Kongono\SecurityEnforcement\`. Its exact
production dependencies are PHP `>=8.5.0 <8.6.0`, API Metadata, Authorization,
Diagnostics, and Identity Access. It owns the generic protected-capability
contract and one stateless exact-Permit enforcer. It has no Authentication,
Security Runtime, HTTP, Routing, Web Runtime, Composition, Kernel, database,
persistence, Console, developer-intelligence, provider SDK, or new third-party
dependency.

Each package owns one Internal contiguous diagnostic catalog, one Public
exception marker, one Internal abstract nondisclosing base, and only its minimum
Experimental concrete exceptions. Other public surfaces are Experimental. The
graph contained exactly forty-two acyclic production packages at Phase 9F. Session syntax/
emission is not session truth, cryptography, storage, login, or authorization;
only exact Permit at the capability boundary proceeds. No ambient current-user,
session, context, or decision state exists. See [ADR 0111](../adr/0111-secure-browser-session-transport-and-capability-enforcement.md)
and the [browser-session security and capability enforcement guide](../architecture/browser-session-security-and-capability-enforcement.md).

## Phase 9G Security Policy Build and Security Application boundaries

Phase 9G adds `kongono/security-policy-build` under
`Kongono\SecurityPolicyBuild\` as the forty-third package. It depends only on
PHP, ext-hash, ext-json, API Metadata, Authorization, and Diagnostics. It owns
the closed rule-free source reader, canonical artifact, structural/current
validators, no-clobber publisher, current evidence, workflow, and bounded
diagnostics. It has no evaluator semantics, provider, HTTP, runtime,
Composition, Console, database, developer-intelligence, or third-party package.

`kongono/security-application` under `Kongono\SecurityApplication\` is the
forty-fourth package. It depends only on PHP, API Metadata, Authorization,
Diagnostics, Security Enforcement, Security HTTP, Security Policy Build, and
PSR HTTP Message interfaces. It owns one supported browser-session protected-
capability lifecycle and no policy, provider, session store, route, middleware,
audit, telemetry, or deployment behavior. No lower package depends on either
new upper package. The exact forty-four-package graph remains acyclic. See
[ADR 0112](../adr/0112-deterministic-security-policy-evidence-and-supported-application-workflow.md)
and the [security application workflow](../architecture/security-application-workflow.md).

## Phase 9H external acceptance layout

Phase 9H adds four root test PHP files under `tests/External/Security` and two
documentation authorities. Root test PHP inventory therefore changes from 56
to 60. Production packages remain 44, production PHP remains 732, package-test
PHP remains 379, and skeleton manifests remain four. The external harness is
test-owned and adds no production package or dependency edge.

## Phase 10B neutral observability package boundary

Phase 10B creates exactly `kongono/observability` under
`Kongono\Observability\` as the forty-fifth acyclic production package. It
depends only on PHP, API Metadata, and Diagnostics. The implemented topology is
45 production package manifests, 749 production PHP files, 388 package-test PHP
files, 60 root-test PHP files, and four skeleton manifests at the Phase 10B
boundary.

Dependency direction is inward: the neutral observability contracts depend
only on accepted foundation packages and PHP; signal runtimes may
depend inward on those contracts; outer HTTP, CGI, Console, database,
persistence, security, application, and export adapters may depend inward.
Lower packages never depend outward on exporters, OpenTelemetry SDKs, global
providers, proprietary backends, or application adapters. Composition remains
construction/lifecycle owner and Kernel stays observability-neutral.

The package supplies 17 production declarations and nine focused test files.
All contracts are Experimental except the Public package catch interface and
Internal diagnostic, exception-base, and validation details. Export adapters, codecs,
propagation parsers, batching, semantic mappings, and host details remain
Internal unless separately promoted. See
[ADR 0114](../adr/0114-explicit-observability-and-operational-evidence-boundary.md),
[ADR 0115](../adr/0115-structured-signals-context-and-exporter-neutrality.md),
[ADR 0116](../adr/0116-neutral-observability-contracts-and-package-boundary.md),
the [Observability and Operational Evidence guide](../architecture/observability-and-operational-evidence.md),
and fixed [Phase 10 roadmap](../architecture/phase-10-roadmap.md).

## Phase 10C tracing package boundary

Phase 10C creates exactly `kongono/tracing` under `Kongono\Tracing\` as the
forty-sixth acyclic production package. It depends only on PHP, API Metadata,
Diagnostics, and Observability; no other production package depends on it. The
implemented topology is 46 package manifests, 772 production PHP files, 401
package-test PHP files, 60 root-test PHP files, and four skeleton manifests.

The package supplies exactly 23 production PHP files and 13 test PHP files.
All declarations are Experimental except the Public package exception marker
and Internal diagnostic, exception base, and `Internal` implementations. It
owns typed IDs/propagation, explicit collaborator interfaces, bounded
root/child lifecycle, reserved trace-observation construction, cleanup,
ownership/isolation, and nondisclosing failure only. It owns no collaborator
implementation, ambient current span, carrier parser, exporter, provider,
backend, metric/event/audit runtime within Tracing, or application integration.
See [ADR 0117](../adr/0117-operation-scoped-tracing-lifecycle-and-typed-propagation.md).

## Phase 10D metrics package boundary

Phase 10D creates exactly `kongono/metrics` under `Kongono\Metrics\` as the
forty-seventh acyclic production package. It depends only on PHP, API Metadata,
Diagnostics, and Observability, independently of Tracing; no production package
depends on Metrics. The implemented topology is 47 package manifests, 160 local
edges, 802 production PHP files, 416 package-test PHP files, 60 root-test PHP
files, and four skeleton manifests.

The package supplies exactly 30 production PHP files and 15 test PHP files.
The exception marker is Public; diagnostic, exception-base, and `Internal`
details are Internal; every other declaration is Experimental. It owns finite
typed metric values, bounded canonical schema dimensions and plans, four exact
instrument kinds, deterministic immediate aggregation, canonical observations,
and one explicit Active/Collected/Retired collection lifecycle. It owns no
dynamic labels, raw measurement log, clock, identifier source, registry,
provider, exporter, backend, OpenTelemetry or PSR logging dependency,
event/audit pipeline inside Metrics or application integration. See
[ADR 0118](../adr/0118-bounded-cardinality-metric-aggregation-and-explicit-collection-lifecycle.md).

## Phase 10E operational-events and security-audit package boundaries

Phase 10E creates exactly `kongono/operational-events` under
`Kongono\OperationalEvents\` and `kongono/security-audit` under
`Kongono\SecurityAudit\` as separate forty-eighth and forty-ninth acyclic
production packages. Each depends only on PHP, API Metadata, Diagnostics, and
Observability. Neither depends on or converts to the other, and no existing
production package depends on either. The topology is 49 package manifests,
166 local edges, 831 production PHP files, 432 package-test PHP files, 60
root-test PHP files, and four skeleton manifests.

Operational Events supplies exactly 14 production and eight test PHP files;
Security Audit supplies exactly 15 production and eight test PHP files. Package
diagnostics, exception bases, and `Internal` mapping/validation details are
Internal; every other declaration, including each separate catch marker, is
Experimental. The packages own finite typed schemas, bounded canonical
observation mapping, explicit domain semantics, and stateless recorder calls
only. They own no logger facade, conversion API, buffer, retry, exporter, store,
retention enforcement, tamper evidence, compliance, authorization behavior,
product analytics, or application integration. See
[ADR 0119](../adr/0119-structured-operational-events-and-bounded-security-audit-evidence.md).

## Phase 10F OpenTelemetry package boundary

Phase 10F creates exactly `kongono/opentelemetry` under
`Kongono\OpenTelemetry\` as the fiftieth acyclic production package. It depends
only on PHP, API Metadata, Diagnostics, Observability, generated OTLP protobuf
1.10.0, and stable semantic conventions 1.38.0. No existing production package
depends on it. At the Phase 10F checkpoint, the topology was 50 package
manifests, 169 local edges, 855 production PHP files, 445 package-test PHP
files, 60 root-test PHP files, and four skeleton manifests.

The package supplies exactly 24 production and 13 test PHP files. Publicly
constructible values, the bridge, capability interfaces, signal/state enums,
and catch marker are Experimental; diagnostics, concrete exceptions, queue
records, validators, mappers, and request factory are Internal. It owns one
locked direct-protobuf profile and explicit bounded queue/flush/close behavior.
It owns no SDK/provider/global state, discovery, HTTP client, retry, backend,
application integration, or lower-package behavior. See
[ADR 0120](../adr/0120-explicit-opentelemetry-otlp-http-protobuf-bridge.md).

## Phase 10G observability build and application boundaries

`kongono/observability-build` depends inward only on API Metadata,
Diagnostics, OpenTelemetry, Hash, JSON, and PHP. The separate
`kongono/observability-application` depends inward only on API Metadata,
Diagnostics, Metrics, Observability, Observability Build, OpenTelemetry,
Operational Events, Security Audit, Tracing, and PHP. Console Application gains
one outward dependency on Observability Build. The resulting graph contains 52
acyclic packages and 182 local production edges.

Package exception markers are Public, operational contracts are Experimental,
and validators/codecs/path/publication/diagnostic helpers are Internal. No lower
Phase 10 package depends outward on either new package.

## Phase 10H root-owned acceptance boundary

Phase 10H creates no package and changes no production dependency edge. The
topology remains exactly 52 acyclic production packages and 182 local edges,
with five skeleton manifests. Its six new paths live only under root-owned
external tests and documentation; the harness is not package autoload or
runtime behavior. The exact Phase 10 checkpoint contains 889 production PHP
files, 461 package-test PHP files, and 64 root-test PHP files.

The root acceptance boundary may compose public package/application APIs with
test-owned clocks, identifiers, sampler, work, and capture transport. Those
fixtures are proof collaborators only and cannot become production defaults,
providers, exporters, discovery, deployment, or durable storage.
## Phase 11B Application Graph contract package boundary

Phase 11B adds the separate Experimental
`packages/application-graph-contract` package under
`Kongono\ApplicationGraphContract\`. It requires only API Metadata,
Diagnostics, and PHP. No other production package depends on it in this phase;
the root adds the only development integration. The current graph therefore
contains exactly 53 acyclic production packages and 184 local production edges.

The package contains 23 production PHP files and six package-test PHP files,
bringing the repository inventories to 912 production PHP files, 467 package-
test PHP files, and 64 root-test PHP files. It owns contract values only: no IR,
Graph implementation, Composition projection, serializer, artifact, command,
consumer, I/O, or runtime authority. The Phase 0J
`kongono/application-graph` package remains byte-identical and distinct.

## Phase 11C Application Graph build package boundary

Phase 11C adds `packages/application-graph-build` under
`Kongono\ApplicationGraphBuild\`. It depends only on API Metadata, Application
Graph Contract, Diagnostics, and PHP; no production package depends on it. The
repository therefore contains exactly 54 acyclic production packages and 187
local production edges.

The package contains 13 production PHP files and seven package-test PHP files,
bringing the repository inventories to 925 production PHP files, 474 package-
test PHP files, and 64 root-test PHP files. It owns explicit IR admission and
canonical immutable fact construction only. It owns no discovery, Composition
projection, I/O, serializer, artifact, currentness, publication, inspection,
consumer, or runtime authority.

## Phase 11G Application Graph intelligence package boundary

Phase 11G adds `packages/application-graph-intelligence` under
`Kongono\ApplicationGraphIntelligence\`. It depends only on PHP, API Metadata,
Application Graph Artifact, Application Graph Contract, and Diagnostics. No
existing production package depends on it. The corrected topology contains
exactly 57 acyclic production packages and 201 local production edges.

The package contains ten production PHP files and six package-test PHP files,
bringing the repository inventories to 965 production PHP files, 497 package-
test PHP files, and 64 root-test PHP files. It owns one closed three-role label,
one Safe aggregate view and deterministic deriver, one bounded independent
verification result and verifier, and non-disclosing diagnostics/exceptions.
It owns no raw Graph or individual-fact access, role execution, model, provider,
network, registry, authorization, mutation, runtime integration, or Phase 11H
behavior.

## Phase 12A package boundary

Phase 12A is historical documentation-only architecture. Phase 12B creates
exactly `packages/extension-contract`, depending only on API Metadata,
Diagnostics, and PHP. It owns neutral declaration values and no manifest
artifact, host, loader, registry, grant, generated output, command, Composition
wiring, or Application Graph integration. Phase 12B brought the production
topology to 58 acyclic packages and 203 local production edges. Construction is not
admission/currentness/trust; requests are not grants, contributions are not
Graph facts, visibility is not authorization, lifecycle is not hosting, and
category is not a security decision. Phase 12C brings the current topology to
59 packages and 206 local production edges and creates exactly
`packages/extension-build`, depending only on API Metadata, Diagnostics,
Extension Contract, and PHP. It owns canonical declaration artifact build,
strict reconstruction, caller-relative currentness, and atomic no-clobber
publication. No existing production package depends on it, and it owns no
public loader, discovery, admission, grant, Graph, Composition, host, or
execution surface. Phase 12D adds `packages/extension-admission`, depending only
on API Metadata, Diagnostics, Extension Contract, Extension Build, Application
Graph Contract, Application Graph Build, and PHP. That topology was 60 packages
/ 212 acyclic local edges. Phase 12E adds `packages/extension-composition`,
depending on API Metadata, Diagnostics, Extension Contract, Extension Build,
Extension Admission, and PHP. Phase 12F adds `packages/extension-host`, depending
only on API Metadata, Diagnostics, Extension Composition, and PHP. It owns exact
one-file loading and bounded application/operation lifecycle; no existing lower
production package depends on it. The current topology is 62 packages / 220
acyclic local edges. Phase 12G adds `packages/extension-inspection`, depending
only on API Metadata, Diagnostics, Extension Build, Extension Composition, and
Extension Contract. Console Application adds the only upper command dependency.
The current topology is 63 packages / 226 edges; Phase 12H closes the milestone with root-owned external evidence only.

## Phase 15A-15G package boundary

Phase 14 is closed with 75 production packages and 289 acyclic local
production dependency edges at checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`. Phase 15A adds documentation
architecture. Phase 15B adds only the optional Experimental
`kongono/network-egress-contract` package, with 28 production PHP files and
16 package-test PHP files. It depends locally only on API Metadata and
Diagnostics and reuses the external PSR-7 interfaces. Phase 15C adds only
`kongono/network-egress-policy-build`, with exactly 22 production PHP files and
12 package-test PHP files. It depends locally only on API Metadata,
Diagnostics, and Network Egress Contract. Phase 15D adds only
`kongono/network-egress-runtime`, with exactly 31 production PHP files and 16
package-test PHP files. It depends locally only on API Metadata, Diagnostics,
Network Egress Contract, and Network Egress Policy Build, and reuses the
existing PSR-7 interfaces. The current graph is 81 packages / 317 local edges /
acyclic and inventories are 1,514 / 768 / 80. The accepted
[egress architecture](../architecture/outbound-http-and-network-egress.md)
keeps neutral behavior below explicit application-supplied resolver and
transport adapters and below service/provider semantics. See
[ADR 0155](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md),
[ADR 0156](../adr/0156-neutral-versioned-network-egress-contract-foundation.md),
and the [Phase 15 roadmap](../architecture/phase-15-roadmap.md). The build
package is optional, Experimental, deterministic, inert, and offline. It is not
a resolver, DNS implementation, transport, client, credential provider,
runtime authorizer, loader, provider adapter, or network runtime. Phase 15D
adds the separate one-operation runtime but no concrete resolver, transport,
credential, provider, command, or workflow. Phase 15E adds only the optional
Experimental `kongono/network-egress-curl` package. Phase 15F adds exactly the
optional Experimental `kongono/network-egress-application` package, with 29
production and 13 package-test PHP files and five local inward dependencies.
Phase 15G adds exactly the optional Experimental
`kongono/network-egress-inspection` upper evidence leaf, with 22 production
and six package-test PHP files and nine local inward dependencies. No existing
production package depends on the leaf. Phase 15H adds no package; it accepts
only the root-owned external harness and exact local checkpoint.

## Phase 15E cURL transport and Phase 15F application workflow status

Phase 15E accepts ADR 0159 and implements the optional Experimental
`kongono/network-egress-curl` HTTP/1.1 adapter. It binds the canonical origin
to the single Phase 15D selected address, uses explicit in-memory CA-bundle
bytes with strict TLS verification, and performs one bounded synchronous
transfer without proxy, redirect, cookie, netrc, automatic authentication,
retry, pooling, or PSR-18 control-plane behavior.

Phase 15F accepts ADR 0160 and implements the optional Experimental
`kongono/network-egress-application` exact-current single-profile workflow.
It derives canonical profile and generated-wiring evidence from one supplied
policy source, validates and publishes the wiring without overwrite, loads one
exact contained generated class, checks required transport capabilities before
resolution, and delegates one terminal synchronous operation to a fresh Phase
15D runtime. Authority currentness, resolver, transport, cancellation, and
clock collaborators remain explicit and caller-owned.

Phase 15G accepts ADR 0161 and implements the optional Experimental
`kongono/network-egress-inspection` offline upper evidence leaf. It
revalidates exact supplied profile/wiring evidence and can summarize one exactly
paired detached operation/result without generated-code loading, networking,
authority evaluation, recording, replay, repair, certification, or role
execution.

The current topology is 81 packages / 317 local edges / acyclic, with PHP
inventories 1,514 / 768 / 84. Phase 15A-H are Accepted and Phase 15 is closed
at commit `b4cb57ffa01eea130a693daee0be605ae7f0dda5`. No first-party
resolver, credential provider, provider adapter, live inspection probe,
inspection command, public compatibility certification, Stable/1.0 claim,
or production-readiness claim exists.

## Phase 16A-C package direction

Phase 16A accepts [ADR 0163](../adr/0163-explicit-external-service-binding-and-secret-lease-boundary.md),
the [external-service binding guide](../architecture/external-service-binding-and-secret-leases.md),
and the [Phase 16 roadmap](../architecture/phase-16-roadmap.md). Phase 16B
accepts [ADR 0164](../adr/0164-neutral-external-service-binding-and-credential-lease-contracts.md)
and adds exactly `packages/external-service-contract`: 27 production and 15
test PHP files.

The package depends inward only on PHP, API Metadata, Diagnostics, Network
Egress Contract, and Network Egress Runtime. No existing production package
depends on it. It reuses Phase 15 origin, capability, resolver, and resolution
evidence and provides no concrete resolver, credential source, lease, provider,
artifact, runtime, application, command, or network behavior. Phase 16C accepts
[ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md)
and adds exactly `packages/external-service-binding-build`: 27 production and
15 test PHP files. Its four local production dependencies are API Metadata,
Diagnostics, External Service Contract, and Network Egress Contract. It builds
and validates only inert secret-free artifact and ordinary-PHP bytes and
publishes each independently without loading generated PHP. Phase 16D accepts
[ADR 0166](../adr/0166-explicit-bounded-system-resolver-adapter.md) and adds
exactly `packages/external-service-resolver-system`: 17 production and 13 test
PHP files. Its five local production dependencies are API Metadata,
Diagnostics, External Service Contract, Network Egress Contract, and Network
Egress Runtime; it additionally requires PHP 8.5 and `ext-sockets`. It is an
optional leaf and no existing package depends on it. One explicit profile and
one system lookup produce bounded deterministic Phase 15 resolution evidence
without connection, credential, cache, retry, fallback, provider, command, or
runtime-orchestration behavior. Phase 16E accepts [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md) and adds exactly
`packages/external-service-credential-runtime`: 20 production and 16 test PHP
files plus its manifest and README. Its three local production edges point only
to API metadata, diagnostics, and the neutral external-service contract.
Applications without external credentials do not install it. Phase 16F accepts
[ADR 0168](../adr/0168-exact-current-external-service-binding-application.md)
and adds exactly `packages/external-service-application`: 20 production and 18
test PHP files plus its manifest and README. Its nine local production edges
point to API metadata, diagnostics, the Phase 16 binding/contract/credential
packages, and the Phase 15 application/contract/policy/runtime packages; PSR-7
is its sole non-PHP external production contract. Applications without
external-service access do not install it. Phase 16G accepts [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md)
and adds exactly `packages/external-service-inspection`: 22 production and six
test PHP files plus its manifest and README. Its 13 local production edges point
only to the explicit Phase 16/15 evidence packages plus API metadata, Graph
intelligence, diagnostics, observability, and security audit. It is an optional
upper evidence leaf. Phase 16H adds no package; it adds four root-test PHP files
under `tests/External/ExternalService/`, acceptance documentation, and one
explicit root Composer script. Phase 16 is closed at one exact local checkpoint.
Provider adapters, Stable/1.0, and production readiness remain unauthorized;
Phase 17A is separately accepted below as documentation only. Phase 17B-G add
one optional mapping/adapter application leaf and one inspection leaf. Current
topology is 89 packages / 375 local production edges / acyclic; PHP inventories
are 1,726 / 894 / 88. See [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md).

## Phase 17A-G package direction

Phase 17B accepts one optional outer
`kongono/model-provider-openai-responses` package. Its 12 local production
edges point inward to API Metadata, Diagnostics, HTTP messages, and the
accepted Phase 14-16 packages. No existing production package depends outward
on it. Its 55 production PHP files, 35 package-test PHP files, and authenticated
synthetic fixtures cover offline mapping, one-shot and pull-based adapter
behavior, and the exact-current application workflow. No SDK or third-party
production dependency exists. Phase 17G adds the separate optional
`kongono/model-provider-openai-responses-inspection` upper evidence leaf with
22 production PHP files, six package-test PHP files, and eight inward local
edges. It is offline, read-only, stateless, and visibility-safe, and no existing
production package depends outward on it. See [ADR 0171](../adr/0171-first-party-openai-responses-adapter-boundary.md),
[ADR 0172](../adr/0172-deterministic-openai-responses-offline-protocol-mapping.md),
[ADR 0176](../adr/0176-exact-current-openai-responses-application-workflow.md),
[ADR 0177](../adr/0177-visibility-safe-openai-responses-inspection-and-bounded-evidence.md),
and the [Phase 17 roadmap](../architecture/phase-17-roadmap.md).
