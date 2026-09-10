# API Status

Phase 16H adds no production declaration and changes no API status. It accepts
the root-owned local external-service acceptance harness and closes Phase 16 at
one exact local checkpoint. The graph remains 87 packages / 355 local
production edges / acyclic; PHP inventories are 1,647 / 851 / 88. No
declaration is promoted to Stable/1.0, and Phase 17 has not begun. See [ADR
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
credential nor network authority. Phase 17 remains unauthorized.
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
