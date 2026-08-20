# API Status

## Purpose

Kongono requires explicit, machine-checkable compatibility metadata for every production class, interface, trait, and enum beneath `packages/*/src`. Namespace and file location do not silently supply a default.

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
promise or production declaration. The production inventory remains exactly 100
declarations across thirteen packages. Fixture-runnable status does not promote
an Experimental API, create a Stable server surface, or imply production readiness.
