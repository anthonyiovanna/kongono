# Package Layout

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
