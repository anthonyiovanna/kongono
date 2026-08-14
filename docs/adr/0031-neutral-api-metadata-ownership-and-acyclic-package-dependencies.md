# ADR 0031: Neutral API Metadata Ownership and Acyclic Package Dependencies

## Status

Accepted

## Date

2026-08-13

## Implementation

Implemented by Phase 0S on 2026-08-14. The implementation creates the exact
fifth package and acyclic dependency graph accepted here, performs the atomic
namespace migration, and adds no later runtime capability.

Phase 0T uses the resulting topology to add the exact one-way
`kongono/kernel -> kongono/diagnostics` dependency for six bounded Kernel
lifecycle contexts. The graph remains acyclic; Phase 0S behavior and metadata
ownership remain unchanged.

Phase 0U adds the exact independent
`kongono/dependency-injection -> kongono/diagnostics` dependency for 25 bounded
DI schemas across 26 throw sites. The graph remains acyclic and no reverse or
universal-exception edge is introduced.

## Context

When this decision was accepted in Phase 0R, Kongono had four physical packages. `kongono/kernel` owned
`Kongono\Kernel\Api\Api` and `Kongono\Kernel\Api\ApiStatus` and required only
PHP. `kongono/diagnostics` required Kernel only for those two compatibility-
metadata types. `kongono/application-graph` required Diagnostics for its bounded
Graph context and Kernel only for the same metadata. `kongono/dependency-
injection` likewise required Kernel only for the metadata.

That Phase 0R topology prevented Kernel from consuming Diagnostics for a future
bounded diagnostic context: adding that edge then would have formed the exact
Composer cycle `kernel -> diagnostics -> kernel`. The ownership problem therefore
had to be resolved without moving diagnostic values into Kernel, weakening
API-status enforcement, or creating overlapping metadata identities.

## Decision

Accept a fifth monorepo package named `kongono/api-metadata` with the production
namespace `Kongono\ApiMetadata\`. Phase 0S implements it with only
`php >=8.5` and exactly these two bootstrap declarations:

```text
Kongono\ApiMetadata\Api
Kongono\ApiMetadata\ApiStatus
```

Phase 0S migrates both types atomically:

```text
Kongono\Kernel\Api\Api
  -> Kongono\ApiMetadata\Api

Kongono\Kernel\Api\ApiStatus
  -> Kongono\ApiMetadata\ApiStatus
```

The implemented acyclic package graph is:

```text
kongono/api-metadata
  requires php >=8.5 only

kongono/kernel
  requires kongono/api-metadata

kongono/diagnostics
  requires kongono/api-metadata

kongono/application-graph
  requires kongono/api-metadata
  requires kongono/diagnostics

kongono/dependency-injection
  requires kongono/api-metadata

kongono/framework root
  requires kongono/api-metadata as @dev
  retains @dev requirements for application-graph, dependency-injection,
  diagnostics, and kernel
```

Each of the four consuming package manifests uses exactly
`"kongono/api-metadata": "*"`; the root uses exactly
`"kongono/api-metadata": "@dev"`. Application Graph, Dependency Injection,
and Diagnostics no longer depend on Kernel merely for compatibility metadata.
Repository-wide API-status inventory and enforcement remain a
root architecture-suite responsibility.

`ApiStatus` remains a string-backed enum with exactly `Internal = internal`,
`Experimental = experimental`, and `Public = public`. `Api` remains final,
readonly, non-repeatable, targeted to class-like declarations, and constructed
with exactly one `ApiStatus` through the same public readonly promoted property.
The two declarations remain Public bootstrap metadata by definition and
the only self-classification exceptions. Status remains compatibility and
exposure metadata, not authorization, security, package visibility, Graph data,
or runtime access control.

Phase 0R recorded this architecture without implementation. Phase 0S creates the
package, moves both types, migrates imports and manifests, updates the mechanical
local lock references, and preserves all Phase 0Q runtime behavior. It does not
add Kernel diagnostic context.

## Rationale

Neutral ownership removes metadata-only coupling to Kernel and preserves a
directed acyclic package graph. It also makes a later one-way
`kongono/kernel -> kongono/diagnostics` dependency possible without authorizing
or implementing that edge now. A narrowly named package containing exactly the
two bootstrap declarations is more precise than making Kernel, Diagnostics, or
a generic shared package own unrelated responsibilities.

## Consequences

Phase 0S implements an atomic, pre-alpha namespace correction across
production imports, tests, manifests, autoload contracts, tooling inventory,
and documentation. The total production declaration count remains 40
because the two declarations moved and were renamed rather than duplicated.

Consumers import `Kongono\ApiMetadata\Api` and
`Kongono\ApiMetadata\ApiStatus`; the old Kernel FQCNs are unavailable. Kernel,
Graph, and DI now independently expose bounded diagnostic context, and no renderer,
error page, transport, logger, telemetry hook, or incident system is introduced.

The accepted topology does not select an external dependency, Composer plugin,
package version, license, release mechanism, repository split, or publishing
promise.

## Compatibility impact

Phase 0S removes both old Kernel FQCNs in the same atomic change. It is an
intentionally breaking pre-alpha correction. No alias,
`class_alias`, proxy attribute, deprecated forwarder, compatibility shim,
duplicate enum, overlapping namespace ownership, or simultaneous second status
identity is provided.

Phase 0R itself changed documentation only. Phase 0S implements the accepted
compatibility break without changing the two declarations' semantics.

## Security considerations

This decision changes no authorization or disclosure behavior. API status
continues to describe compatibility and supported use only. Existing bounded
Graph context, Safe and Protected classifications, forbidden-capture rules, and
package-local exception boundaries remain unchanged. No diagnostic field,
ambient state, I/O, disclosure policy, trusted mode, renderer, or transport is
added.

## Performance implications

Phase 0R had no runtime or memory cost. Phase 0S moves two inert metadata
declarations and changes dependency ownership; it does not select caching,
reflection optimization, runtime services, or a new execution path.

## Rejected alternatives

- Adding `kernel -> diagnostics` now and accepting a cycle is rejected because
  Composer package dependencies must remain acyclic.
- Leaving the metadata types permanently owned by Kernel is rejected because it
  would retain metadata-only Kernel coupling in every framework package.
- Moving Diagnostics values into Kernel is rejected because presentation-
  neutral diagnostic values are not Kernel lifecycle responsibilities.
- Removing status metadata or exempting Diagnostics types is rejected because
  root-owned deterministic API-status enforcement remains required.
- Duplicating `Api` or `ApiStatus` in multiple packages is rejected because two
  status identities would make compatibility classification ambiguous.
- Keeping `Kongono\Kernel\Api\` under a different physical package through a
  split or overlapping namespace is rejected because ownership and autoload
  boundaries would become misleading.
- Compatibility aliases or forwarders for the old FQCNs are rejected in favor
  of one atomic pre-alpha identity correction.
- An external third-party API-status dependency is rejected because the exact
  three-value bootstrap contract remains Kongono-owned.
- A broad `foundation`, `common`, `shared`, or `contracts` package is rejected
  because it would become an unbounded dumping ground rather than a precise
  two-type ownership boundary.

## What remains open

- Package publication, versioning, split-repository, and release policy.
- Promotion, deprecation, and per-member API-status mechanics.
- Cross-language representation of API status.
- Context-construction failure handling at existing exception throw sites.
- Production disclosure allowlists and trusted developer-mode selection.
- Renderer ownership and APIs.
- HTTP, CLI, and other transport mappings.
- Serialization, localization, source providers, stack providers, and version-
  matched documentation lookup.
- Logging, telemetry, incident storage, retry, cancellation, cleanup, recovery,
  and simultaneous-failure representation.
- Every later framework capability excluded from Phases 0R and 0S.
