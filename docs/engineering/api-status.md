# API Status

## Purpose

Kongono requires explicit, machine-checkable compatibility metadata for every production class, interface, trait, and enum beneath `packages/*/src`. Namespace and file location do not silently supply a default.

Phase 0C defines the non-repeatable class-like `#[Api(ApiStatus::...)]` attribute and exactly three statuses; later phases apply the same convention to the Application Graph, Dependency Injection, package-local diagnostic catalogs, Diagnostics values, and Diagnostic Presentation projection types:

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
`TrustedDeveloperHtmlRenderer`, for 49 declarations without changing either
bootstrap type.

## Enforcement

The root PHPUnit suite deterministically discovers the exact 49 production class-like declarations and fails when a non-bootstrap type is missing a status, has the wrong status, cannot autoload, or falls outside the authorized inventory. `Api` and `ApiStatus` are Public bootstrap metadata by definition and are the only documented exception to self-classification. The five catalogs—`LifecycleDiagnostic`, `GraphDiagnostic`, `DependencyInjectionDiagnostic`, `DiagnosticPresentationDiagnostic`, and `DiagnosticsDiagnostic`—are Internal. The six Diagnostics context/schema/value/vocabulary types are Public. Diagnostic Presentation's four projection concepts, trusted HTML renderer, and concrete failure are Experimental, its marker is Public, and its base and catalog are Internal. The Public Graph, Kernel, and DI markers independently return the Public `DiagnosticContext` through `diagnosticContext()`; no Internal originating catalog type enters those signatures. The Diagnostics and Presentation markers retain only their three string accessors. The attribute is not repeatable, so multiple declarations are invalid at the language-metadata level and are also rejected by the convention check.

API status communicates exposure and compatibility intent only. It is not authorization, a security boundary, package visibility enforcement, an Application Graph node, or runtime access control. No third-party API-status package is used.

## Stability

The metadata convention is Public. The root architecture suite owns exact inventory enforcement across all six package source trees. The convention does not define Stable or Deprecated executable statuses, promotion procedure, general versioning mechanics, per-member metadata, or a future cross-language Graph representation; those remain open. The current PHP `Api` attribute used by Kernel, Graph, Dependency Injection, Diagnostics, and Diagnostic Presentation production types is compatibility metadata only, not Graph-domain, authorization, resolver, diagnostic-context, or runtime metadata.
