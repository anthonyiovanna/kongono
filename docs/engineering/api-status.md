# API Status

## Phase 21 Object Storage status

Phase 21A accepts
[ADR 0206](../adr/0206-optional-object-storage-platform-and-explicit-authority-boundary.md),
[ADR 0207](../adr/0207-deterministic-storage-integrity-lifecycle-and-provider-separation.md),
the [Storage Platform guide](../architecture/storage-platform.md), the
[storage-authoring guide](storage-authoring.md), and the fixed
[Phase 21 roadmap](../architecture/phase-21-roadmap.md). Phase 21B accepts
[ADR 0208](../adr/0208-neutral-storage-contract-and-bounded-evidence-vocabulary.md),
and Phase 21C accepts
[ADR 0209](../adr/0209-deterministic-storage-profile-artifact-and-contained-publication.md)
and the [deterministic storage-profile build guide](../architecture/deterministic-storage-profile-build.md).
Phase 21D accepts
[ADR 0210](../adr/0210-neutral-storage-adapter-and-contained-local-filesystem-implementation.md)
and the [contained local adapter guide](../architecture/contained-local-storage-adapter.md).
Phase 21E accepts
[ADR 0211](../adr/0211-exact-current-storage-runtime-and-operation-scoped-lifecycle.md)
and the [exact-current storage runtime guide](../architecture/exact-current-storage-runtime.md).
Phase 21F accepts
[ADR 0212](../adr/0212-supported-storage-application-workflow-and-explicit-authorization.md)
and the [storage application workflow guide](../architecture/storage-application-workflow.md).
Phase 21G accepts [ADR 0213](../adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [storage inspection guide](../architecture/storage-inspection-and-intelligence.md).
Phase 21H adds a root-owned test harness under
[ADR 0214](../adr/0214-phase-21-external-storage-acceptance-and-checkpoint.md),
with no production API change.

Phase 21A creates zero PHP declarations. Phase 21B accepts the optional
`kongono/storage-contract` leaf with exactly 40 declarations: 37 Experimental
values, enums, interfaces, and the concrete validation exception; and three
Internal diagnostic, abstract-exception, and guard declarations. No Phase 21B
declaration is Public or Stable.

Phase 21C accepts the optional `kongono/storage-build` leaf with exactly 24
declarations: 17 Experimental profile/build/artifact values and operations,
one Public exception-family interface, and six Internal diagnostic/exception/
canonical/path declarations. No declaration is Stable.

Phase 21D accepts the optional `kongono/storage-adapter-contract` leaf with
exactly 15 declarations: 11 Experimental protocol/descriptor/guarantee values,
one Public exception-family interface, and three Internal diagnostic,
exception, and guard declarations. It also accepts the separately optional
`kongono/storage-adapter-local` leaf with exactly 18 declarations: five
Experimental adapter/configuration/version and concrete exceptions, one Public
exception-family interface, and 12 Internal diagnostic, exception, record,
path, lock, source, and codec declarations. Neither package has a Stable
declaration. No 1.0, provider-compatibility, production-availability, PHP 8.6,
or PHP 9 claim is authorized.

Phase 21E accepts the separately optional `kongono/storage-runtime` leaf with
exactly 17 declarations: eight Experimental operation and concrete-exception
declarations, eight Internal diagnostic, abstract-exception, validation, and
lifecycle declarations, and one Public exception-family interface. No
declaration is Stable. It depends only on accepted neutral layers and does not
require the local adapter. Phase 21F accepts the separately optional
`kongono/storage-application` leaf with exactly 21 declarations: 16
Experimental, four Internal, and one Public exception-family interface.
The three new Console handlers are Experimental. No new declaration is Stable.
The optional `kongono/storage-inspection` leaf has 23 declarations: 20
Experimental, two Internal, and one Public exception-family interface. None
is Stable. Phase 21H external acceptance adds no production declarations;
security audit, architect acceptance, and checkpoint remain separate gates.

## Phase 20 Derived-Data Cache status

Phase 20A accepts [ADR 0197](../adr/0197-explicit-derived-data-cache-and-non-authority-boundary.md),
[ADR 0198](../adr/0198-deterministic-cache-freshness-atomicity-and-lifecycle.md),
the [Cache Platform guide](../architecture/cache-platform.md), the
[cache-authoring guide](cache-authoring.md), and the fixed
[Phase 20 roadmap](../architecture/phase-20-roadmap.md) as documentation.

Phase 20B accepts [ADR 0199](../adr/0199-neutral-cache-contract-and-bounded-evidence-vocabulary.md)
and the optional `kongono/cache-contract` leaf. It has exactly 21 production
declarations: 17 Experimental values, enums, and the concrete validation
exception; one Public exception-family interface; and three Internal
diagnostic/exception/guard declarations. No declaration is Stable.

Phase 20C accepts [ADR 0200](../adr/0200-deterministic-cache-profile-artifact-and-publication.md)
and the optional `kongono/cache-build` leaf. It has exactly 24 production
declarations: 17 Experimental profile/build/artifact values and operations,
one Public exception-family interface, and six Internal diagnostic/exception/
canonical/path declarations. No declaration is Stable.

Phase 20D accepts [ADR 0201](../adr/0201-neutral-cache-adapter-and-bounded-memory-implementation.md),
the optional `kongono/cache-adapter-contract` leaf with 18 production
declarations (14 Experimental and four Internal), and the separately optional
`kongono/cache-adapter-memory` leaf with 10 production declarations (six
Experimental and four Internal). No declaration is Public or Stable.

Phases 20A-H are Accepted and Phase 20 is closed. The accepted packages remain
Experimental and supply no framework serialization, external backend, or
provider integration. Phase 20H adds no production declaration or status
change. Future PSR-6,
PSR-16, Redis, Memcached,
filesystem, database, cloud, and vendor interoperability cannot define
Kongono's internal correctness model.

## Phase 19 Asset Platform status

Phase 19A is Accepted documentation architecture. Phase 19B accepts the
optional Experimental `kongono/asset-contract` API through
[ADR 0190](../adr/0190-neutral-asset-contract-and-bounded-declaration-vocabulary.md).
It has exactly 19 production declarations: 15 Experimental values, enums, and
the concrete validation exception; one Public exception-family interface; and
three Internal diagnostic/exception/guard declarations. No declaration is
Stable.

Logical/content identities, declarations, dependency and entry-point relations,
integrity evidence, preload hints, and delivery metadata are inert and bounded.
An empty declaration set is valid. There is no path, URL, filesystem, schema,
manifest, graph traversal, build, resolver, adapter, HTTP, view, command,
runtime, Vite, or frontend integration. Future surfaces begin Experimental,
and Stable or 1.0 requires separate authority.

Phase 19C accepts the optional `kongono/asset-build` API through
[ADR 0191](../adr/0191-deterministic-native-asset-graph-and-build-artifact.md).
It has exactly 30 production declarations: 22 Experimental source, graph,
build, artifact, publication, identity, enum, and concrete exception
declarations; one Public package exception-family interface; and seven Internal
graph-node, diagnostic, abstract exception, and guard declarations. No
declaration is Stable. The leaf performs only explicit in-memory native-copy
build evidence and contained publication; it adds no adapter, network, URL,
HTTP, view, command, or runtime surface.

Phase 19D accepts [ADR 0192](../adr/0192-replaceable-asset-build-adapter-and-bounded-vite-integration.md),
the optional `kongono/asset-adapter-contract`, and the separately optional
`kongono/asset-adapter-vite`. They add exactly 49 production declarations: 33
Experimental values, enums, exceptions, and boundaries; two Public
exception-family interfaces; and 14 Internal diagnostics, exceptions,
workspace values, and implementation declarations. No declaration is Stable.
The fixed Vite implementation accepts one explicit application-provided
executable and performs at most one bounded no-shell subprocess in a contained
workspace. It strictly translates and validates complete output, then attempts
cleanup. It adds no Node/Vite package, installation, network, watcher/HMR,
development server, runtime resolver, URL/HTTP/view, command, or frontend
surface. Phase 19E-G add the accepted resolver, application, and inspection
Experimental APIs without promoting any declaration. Phase 19H adds root-test
evidence only and no production declaration or status change. Phase 19A-H are
Accepted and Phase 19 is closed.

Vite is the first optional replaceable adapter and never a core
dependency or the neutral conceptual model. Native or no-bundler and no-asset
applications remain first-class. PHP 8.5 is the current baseline; PHP 8.6 or 9
readiness means explicit contracts and replaceable adapters, not unverified
future syntax or behavior. See [Asset Platform](../architecture/asset-platform.md),
[asset authoring](asset-authoring.md), the
[replaceable adapter guide](../architecture/replaceable-asset-build-adapters.md),
the [Phase 19 roadmap](../architecture/phase-19-roadmap.md), and
[ADR 0196](../adr/0196-phase-19-external-asset-acceptance-and-checkpoint.md).

## Phase 18A-G presentation status

Phase 18A accepts [ADR 0179](../adr/0179-server-rendered-presentation-and-compiled-view-boundary.md),
[ADR 0180](../adr/0180-context-aware-output-safety-and-presentation-lifecycle.md),
the [presentation guide](../architecture/server-rendered-presentation.md), the
[authoring guide](view-authoring.md), and the
[fixed roadmap](../architecture/phase-18-roadmap.md) as documentation doctrine.
Phase 18B accepts [ADR 0181](../adr/0181-neutral-view-contract-and-closed-presentation-values.md)
and the optional `kongono/view-contract` API. Phase 18C accepts
[ADR 0182](../adr/0182-deterministic-structured-view-source-and-compiled-artifact.md)
and the optional Experimental `kongono/view-build` API. Phase 18D accepts
[ADR 0183](../adr/0183-exact-current-compiled-view-runtime-and-bounded-rendering.md),
the [runtime guide](../architecture/compiled-view-runtime.md), and the optional
Experimental `kongono/view-runtime` API. Phase 18E accepts
[ADR 0184](../adr/0184-explicit-completed-view-to-psr-7-response-adaptation.md),
the [HTTP adapter guide](../architecture/compiled-view-http.md), and the
optional Experimental `kongono/view-http` API.

Phase 18F accepts [ADR 0185](../adr/0185-supported-compiled-view-application-workflow.md)
and the optional Experimental `kongono/view-application` API. It adds 20
declarations: ten Experimental workflow/profile/application/summary values,
one Public exception marker, and nine Internal diagnostic/exception/helper
declarations.

Phase 18G accepts [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
and the optional Experimental `kongono/view-inspection` API. It adds 22
declarations: 20 Experimental inspection, derivation, Graph, projection,
exception, role, and verification declarations, plus the Internal diagnostic
enum and canonical JSON helper. Nothing in Phase 18 is Stable, 1.0,
production-observing, browser-certified, or a compatibility promise. Phase 18H
adds root-test evidence only, no production declaration or status change.

View Contract contains exactly 30 production declarations: 26 Experimental
consumer-facing values/enums and the concrete validation exception, one Public
exception marker, and three Internal diagnostic/base/guard declarations.
View Build adds 30 declarations: 18 Experimental source/grammar/artifact/
concrete-exception declarations, one Public exception marker, and 11 Internal
model/emitter/sink/diagnostic/base/helper declarations. View Runtime adds 19
declarations: 10 Experimental loader/loaded-handle/renderer/specific-exception
declarations, one Public exception marker, and eight Internal diagnostic/base/
loader/escaper/sink/operation/guard/reader declarations. Nothing in Phase 18 is Stable, 1.0,
production-certified, browser-certified, or a compatibility promise.

View HTTP adds 11 declarations: six Experimental adapter/policy/concrete-
exception declarations, one Public exception marker, and four Internal
diagnostic/base/guard/assembler declarations.

Phase 17A-H are Accepted. [ADRs 0172 through
0177](../architecture/phase-17-roadmap.md) add 77 production declarations
across the optional OpenAI Responses provider and inspection packages: 47 are
Experimental and 30 are Internal. Phase 17H accepts [ADR
0178](../adr/0178-phase-17-openai-responses-local-acceptance-and-checkpoint.md),
adds no production declaration, and closes Phase 17 with root-owned local
acceptance. No Phase 17 declaration is Public, Stable, or Deprecated, and no
provider-certified, compatible, or production-ready claim exists. The graph
is 95 packages / 402 local production edges / acyclic; PHP inventories are
1,861 / 962 / 96. Phase 18A-H are Accepted and Phase 18 is closed.

Phase 16H adds no production declaration and changes no API status. It accepts
the root-owned local external-service acceptance harness and closes Phase 16 at
one exact local checkpoint. After Phase 17B, the graph is 88 packages / 358
local production edges / acyclic; PHP inventories are 1,665 / 860 / 88. No
declaration is promoted to Stable/1.0. Phase 17A is accepted above as
documentation only. See [ADR
0170](../adr/0170-phase-16-external-service-acceptance-and-checkpoint.md).

Phase 16G adds 22 `kongono/external-service-inspection` production
declarations. The diagnostic enum, canonical JSON helper, and abstract base
exception are Internal; the remaining 19 declarations are Experimental. No
declaration is Public, Stable, or Deprecated. These statuses grant no
credential access, network access, authority, external truth, replay safety,
erasure proof, or certification. See [ADR
0169](../adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md).

Phase 16F adds 20 `kongono/external-service-application` production
declarations. `ExceptionInterface` is the sole Public declaration. The six
helpers under `Internal` are Internal; the diagnostic, abstract and concrete
exceptions, profile values/factory, request-factory port, input/result values,
application, and lifecycle enum are Experimental. Nothing is Stable or
Deprecated. These statuses grant neither credential nor network authority.
The current graph is 87 packages / 355 local production edges / acyclic; PHP
inventories are 1,647 / 851 / 88. See [ADR
0168](../adr/0168-exact-current-external-service-binding-application.md).

Phase 16E adds 20 `kongono/external-service-credential-runtime` production
declarations. `ExceptionInterface` is the sole Public declaration. The clock,
request, result, lifecycle/outcome/failure values, runtime, and four concrete
exceptions are Experimental. The diagnostic, abstract exception, lease guard,
budget meter, lifecycle tracker, result factory, and failure composer are
Internal. Nothing is Stable or Deprecated, and API status grants neither
credential nor network authority. In that Phase 16E state, Phase 17 remained
unauthorized.
See [ADR
0167](../adr/0167-operation-scoped-credential-lease-runtime.md).

Phase 16C adds 27 `kongono/external-service-binding-build` production
declarations. `ExceptionInterface` is the only Public declaration. Source,
artifact, generated-PHP, generator, validator, publisher, version, and concrete
exception declarations are Experimental. The diagnostic enum, abstract
exception, codecs, canonical JSON and path helpers, emitter, and implementation
identity helper are Internal. No Stable or Deprecated declaration is added.
These statuses describe compatibility, not credential or network authority.
In that Phase 16C state, Phase 16D-H and Phase 17 remained unauthorized. See [ADR 0165](../adr/0165-deterministic-external-service-binding-artifact-and-generated-php.md).

Phase 16B adds 27 `kongono/external-service-contract` production declarations.
`ExceptionInterface` is Public only as the exception-family marker. The
diagnostic enum, abstract package exception, and `ContractGuard` are Internal;
all other declarations are Experimental. The package contains no Stable or
Deprecated declaration. These statuses describe compatibility, not credential
or network authority. In that Phase 16B state, Phase 16C-H and Phase 17 remained unauthorized.

Phase 14G adds 22 `kongono/model-invocation-inspection` production
declarations. `ExceptionInterface` is Public. The inspection request/result,
inspector, bounded evidence values and projectors, developer-role value,
independent verifier, verdict, verification, and typed failures are
Experimental. The diagnostic, canonical JSON helper, abstract package
exception, and construction-only failures are Internal. No Stable or Deprecated
API is added. The repository has 1,358 production PHP files across 75 packages.
See [ADR 0153](../adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md)
and the [inspection guide](../architecture/model-invocation-inspection-and-evidence.md).

Phase 14F adds 29 `kongono/model-invocation-application` production
declarations. `ExceptionInterface` is Public. Profile, wiring, workflow,
application, run input, state, and typed operational failures are Experimental.
Diagnostics, abstract bases, canonical/path/generation helpers, and
construction-only failures are Internal. No Stable or Deprecated API is added.
That Phase 14F inventory was 1,336 production PHP files across 74 packages. See
[ADR 0152](../adr/0152-exact-current-single-profile-model-invocation-application-workflow.md)
and the [application workflow guide](../architecture/model-invocation-application-workflow.md).

Phase 14E adds 13 `kongono/model-invocation-agent-bridge` production
declarations. `ExceptionInterface` is Public. The bridge, action mapping,
usage selector, and typed rejection are Experimental. The diagnostic, abstract
exception, construction failure, and five internal helpers are Internal. No
Stable or Deprecated API is added. That Phase 14E inventory was 1,307
production PHP files across 73 packages.

Phase 14D adds 21 `kongono/model-invocation-runtime` production declarations.
Only `ExceptionInterface` is Public. Adapter/session/emission, clock,
cancellation, execution values, runtime, and concrete exceptions are
Experimental; the diagnostic, abstract exception, and five helpers are
Internal. That Phase 14D inventory was 1,294 production PHP files across 72
packages.

Phase 14B adds the `kongono/model-invocation-contract` status inventory.
Phase 14C adds 19 `kongono/model-invocation-build` production declarations:
`ExceptionInterface` is Public; the diagnostic enum, abstract package
exception, `CanonicalJson`, and `PathGuard` are Internal; the three budget
values, three plan values, artifact value, builder, validator, publisher, and
four concrete exceptions are Experimental. No alias, loader, workflow,
registry, command, or generic validator interface is added.

The Phase 14B inventory remains:
`ExceptionInterface` is Public; all immutable values, backed enums, and
`InvalidModelInvocationContractException` are Experimental; `ContractGuard`,
`ModelInvocationContractDiagnostic`, and the abstract
`ModelInvocationContractException` are Internal. No Stable or Deprecated API
is added. The global inventory is 1,273 production declarations across 71
packages. See [ADR 0148](../adr/0148-neutral-versioned-model-invocation-contract-foundation.md)
the [contract guide](../architecture/model-invocation-contract-foundation.md),
and the [plan-artifact guide](../architecture/model-invocation-plan-artifact.md).

## Purpose

Kongono requires explicit, machine-checkable compatibility metadata for every production class, interface, trait, and enum beneath `packages/*/src`. Namespace and file location do not silently supply a default.

Phase 13G retains the Agent Principal Contract, declaration-build, admission,
and runtime classifications. It classifies the agent-application
`ExceptionInterface` as Public; profile, generated-wiring, workflow,
application, and concrete exception types as Experimental; and diagnostics,
canonical emitters, path guards, and lifecycle helpers as Internal. The
agent-runtime
`ExceptionInterface` as Public; runtime values, ports, enums, and concrete
exceptions as Experimental; and runtime guards, budget meter, receipt factory,
lifecycle composer, diagnostics, and abstract exception as Internal. Phase 13D
classifies the agent-admission `ExceptionInterface` as
Public; admission requests, decisions, authority-plan evidence, identities, and
concrete exceptions as Experimental; and guards, canonicalization, diagnostics,
and its abstract exception as Internal. The declaration-build
`ExceptionInterface` remains Public; declaration values, artifact operations,
and concrete exceptions remain Experimental; and its
diagnostic catalog, helpers, and abstract exception as Internal. It adds no
Stable declaration. Agent Inspection classifies its package exception marker
as Public; requests, inspections, projectors, Graph/role/verification values,
and concrete exceptions as Experimental; and diagnostics, canonical encoding,
and the abstract exception as Internal. It adds no Stable declaration. The
historical Phase 13 inventory was 1,213 production declarations across 69 packages.

Phase 0C defines the non-repeatable class-like `#[Api(ApiStatus::...)]` attribute and exactly three statuses; later phases apply the same convention to the Application Graph, Dependency Injection, package-local diagnostic catalogs, Diagnostics values, Diagnostic Presentation projection types, and HTTP messages and factories:

| Status | Supported use | Compatibility and extension contract |
| --- | --- | --- |
| Internal | Kongono internals only; external use unsupported | No backward-compatibility promise; extension prohibited unless a later Accepted contract says otherwise |
| Experimental | Early adopters and evaluators who explicitly accept instability | No backward-compatibility promise before promotion; extension only as the experimental contract documents |
| Public | Application and component consumers | Supported under Kongono's versioning policy and release maturity; extension only as the specific contract documents |

Public does not mean Stable or unchangeable forever. A type's status applies to its public and protected members in the current repository convention. Per-member status is not authorized.

Phase 0S owns the attribute and enum at `Kongono\ApiMetadata\Api` and
`Kongono\ApiMetadata\ApiStatus` in the neutral `kongono/api-metadata` package.
They retain the exact three statuses, attribute shape, and two bootstrap
self-classification exceptions. The former `Kongono\Kernel\Api\*` identities
were removed atomically without a shim. Phase 0X expands the production
inventory from 40 to 48 declarations, and Phase 0Z adds the one Experimental
`TrustedDeveloperHtmlRenderer`, for 49 declarations. Phase 1C adds the one
Experimental `TrustedDeveloperBootstrapPermission`, for 50 declarations without
changing either bootstrap type. Phase 1D adds the Experimental
`AdapterControlledDeliveryPermission`, for 51 declarations and 49 explicit
status attributes. Phase 1E adds the Experimental final readonly
`TrustedDeveloperPresentationEligibility`, for 52 declarations and 50 explicit
status attributes. Phase 1H adds the Experimental final readonly
`TrustedDeveloperDiagnosticHtmlPresenter`, for 53 declarations and 51 explicit
status attributes. Phase 2C adds 17 HTTP Message declarations for 70 total:
Experimental concrete messages, factories, and exceptions; one Public marker;
and Internal helpers and diagnostic catalog. Phase 2D adds ten Routing
declarations for 80 total: seven Experimental declaration/match/matcher and
exception types, one Public exception marker, and two Internal syntax/diagnostic
types. No Stable routing API is created. Phase 2E adds six HTTP Server
declarations for 86 total: two Experimental runtime types and one Experimental
exception, one Public exception marker, and two Internal implementation/catalog
types. No Stable HTTP Server API is created.
Phase 2F adds six Web Application declarations for 92 total: the Experimental
application and two concrete failures, one Public exception marker, and the
Internal abstract base and diagnostic catalog. No Stable Web Application API is
created.

## Enforcement

The root PHPUnit suite deterministically discovers the exact 92 production class-like declarations and fails when a non-bootstrap type is missing a status, has the wrong status, cannot autoload, or falls outside the authorized inventory. `Api` and `ApiStatus` are Public bootstrap metadata by definition and are the only documented exception to self-classification. The nine catalogs—`LifecycleDiagnostic`, `GraphDiagnostic`, `DependencyInjectionDiagnostic`, `DiagnosticPresentationDiagnostic`, `DiagnosticsDiagnostic`, `HttpMessageDiagnostic`, `RoutingDiagnostic`, `HttpServerDiagnostic`, and `WebApplicationDiagnostic`—are Internal. The six Diagnostics context/schema/value/vocabulary types are Public. HTTP Message concrete messages, factories, and exceptions are Experimental, its marker is Public, and its helpers and catalog are Internal. Routing definitions, table, match, matcher contract/implementation, and concrete exceptions are Experimental; its marker is Public and syntax/catalog are Internal. HTTP Server's pipeline, routing handler, and concrete exception are Experimental; its marker is Public and layer/catalog are Internal. Web Application and its concrete failures are Experimental, its marker is Public, and its abstract base/catalog are Internal. The attribute is not repeatable, so multiple declarations are invalid at the language-metadata level and are also rejected by the convention check.

API status communicates exposure and compatibility intent only. It is not authorization, a security boundary, package visibility enforcement, an Application Graph node, or runtime access control. No third-party API-status package is used.

## Stability

The metadata convention is Public. The root architecture suite owns exact inventory enforcement across all thirteen package source trees and 100 production declarations. The convention does not define Stable or Deprecated executable statuses, promotion procedure, general versioning mechanics, per-member metadata, or a future cross-language Graph representation; those remain open. The current PHP `Api` attribute is compatibility metadata only, not Graph-domain, authorization, resolver, diagnostic-context, or runtime metadata.

Phase 2G adds eight Web Runtime declarations. `WebDeliveryInterface` and the
package exception marker are Public compatibility surfaces; the adapter and
concrete failures are Experimental, while the catalog, base exception, and
response factory are Internal. This status does not make a concrete delivery
trusted or select a stable server integration. Phase 2H's concrete delivery and
runner are test-owned, carry no `#[Api]` declaration, and create no compatibility
promise or production declaration. Phase 3F adds Internal `CgiWebDelivery` and
Experimental `CgiWebEntry` plus `CgiDeliveryCleanupException`; the Public
`WebDeliveryInterface` is unchanged. Phase 3G adds Experimental
`CgiFrontController` and `CgiEntryShutdownException`; `PhpCgiDiagnostic` remains
Internal. The production inventory is now exactly 126 declarations across
fourteen packages. The front controller adds no direct PHP-CGI-to-Web-
Application edge and owns only one entry attempt plus one supplied shutdown
attempt. Phase 3H's test-owned external harness proves the selected synchronous
PHP-CGI FastCGI RESPONDER integration profile without becoming a production or
supported server/client and without adding a production package dependency.
Production trusted diagnostics remain denied; process, proxy, TLS, credentials,
logs, monitoring, incidents, and deployment ownership remain external. Phase 3
is complete, but it is not the full framework, Stable API, 1.0, universal SAPI
support, deployment automation, or production-hardening certification. No next
phase starts automatically.

## Knowledge package

Phase 4C adds 18 production types. `Kongono\Knowledge\Exception\ExceptionInterface`
is Public. `FrameworkKnowledgeArtifact`, its generator, validator, and publisher;
`KnowledgeSource` and `KnowledgeSourceAuthority`; and the four concrete package
exceptions are Experimental. `KnowledgeCommand`, `KnowledgeDiagnostic`,
`KnowledgeException`, `CanonicalJson`, `PathGuard`,
`KnowledgeSourceDefinition`, and `KnowledgeSourceInventory` are Internal.
Nothing in the package is Stable, and status does not confer authority or
permission to publish, mutate source, access a workspace, or use production.

## Chie package

## Hantei package

Hantei, its evidence/report values, verdict/outcome enums, and invalid-request
exception are Experimental. Its exception marker is Public. The command,
diagnostic, base exception, invalid-value boundary, canonical JSON, profile,
subject, process, and verdict evaluator are Internal. Nothing is Stable.
Phase 4D adds 23 production types. `Kongono\Chie\Exception\ExceptionInterface`
is Public. `Chie`, its request/response/citation/claim values, operation/status/
topic/claim enums, diagnostic observation, and four concrete exceptions are
Experimental. `ChieCommand`, `ChieDiagnostic`, `ChieException`,
`ArtifactFileReader`, `CanonicalJson`, `DiagnosticLocator`, `SectionExtractor`,
and `TopicCatalog` are Internal. Nothing is Stable. Status communicates
supported use only; it grants no prompt, model, workspace, write, verification,
Git, network, deployment, or production authority.

Phase 4F marks Oshie's facade, immutable value objects, enums, request/response types, and concrete operational exceptions Experimental; its package exception marker is Public; and command, diagnostic, base exception, value-boundary exception, artifact reader, canonical encoder, curriculum catalog, and section extractor are Internal. Nothing is Stable.
## Phase 4G Chie declarations

The seven proposal values and three proposal exceptions are Experimental. The
proposal-spec reader, unified-diff builder, and proposal builder are Internal.
No Phase 4G declaration is Stable. The statuses describe exposure and evolution;
they do not grant authority to apply or verify a candidate.

## Phase 5B platform metadata

Phase 5B applies `>=8.5.0 <8.6.0` to the root and all eighteen packages. PHP
8.5 is the one active compatibility-certified minor, and PHP 8.5.9 on Windows
is the proved environment. Stable eligible 8.5 patches remain within range;
PHP 8.6, PHP 9, prerelease, nightly, and automatic future-minor support are not
claimed. This installability-metadata migration changes no API status or
runtime semantics and adds no package, dependency, public API, polyfill, shim,
release/LTS promise, or composition/build capability. Phase 5A remains
accepted.

## Phase 5D composition API

`CompositionArtifact`, its builder, validator, and publisher are Experimental.
The four final package exceptions and their marker are Public; the abstract
base, diagnostic enum, canonical JSON, compiler, path guard, and strict decoder
are Internal. Phase 5E adds six Experimental generated-wiring/lifecycle
interfaces and four Public package exceptions; emitter and lifecycle helpers
remain Internal. These statuses expose build and isolated lifecycle-support
surfaces only. They do not authorize service lookup, production loading, or
runtime integration; Phase 5F has not begun.
