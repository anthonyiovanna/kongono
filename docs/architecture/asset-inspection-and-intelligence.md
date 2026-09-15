# Asset inspection and developer intelligence

## Scope

`kongono/asset-inspection` is the optional Experimental Phase 19G evidence
leaf. It accepts exactly one already constructed
`CurrentAssetApplicationBuild`; it accepts no path, URL, JSON document, output
map, callback, workflow, resolver, container, or service locator.

The inspector recreates the supplied `AssetResolutionArtifact` using new
resolution objects and entries built from public immutable values, then
compares exact canonical bytes and identities. It also checks the current
result's declaration, dependency, entry-point, and output counts against the
reconstructed entry and reachable-object evidence. No source or output byte is
read.

## Programmatic use

```php
use Kongono\AssetInspection\Derivation\AssetIntelligenceDeriver;
use Kongono\AssetInspection\Inspection\AssetInspectionRequest;
use Kongono\AssetInspection\Inspection\AssetInspector;
use Kongono\AssetInspection\Projection\AssetObservationProjector;
use Kongono\AssetInspection\Projection\AssetSecurityAuditProjector;
use Kongono\AssetInspection\Role\AssetInspectionDeveloperRole;
use Kongono\AssetInspection\Verification\AssetIntelligenceVerifier;
use Kongono\Observability\ObservabilityContext;

$request = new AssetInspectionRequest($currentAssetApplicationBuild);
$inspection = (new AssetInspector())->inspect($request);
$intelligence = (new AssetIntelligenceDeriver())->derive(
    $inspection,
    AssetInspectionDeveloperRole::Hantei,
    $optionalMatchingRoleGraphView,
);
$context = ObservabilityContext::uncorrelated();
$observation = (new AssetObservationProjector())->project($inspection, $context);
$audit = (new AssetSecurityAuditProjector())->project($inspection, $context);
$verification = (new AssetIntelligenceVerifier())->verify(
    $request,
    $inspection,
    $intelligence,
    $observation,
    $audit,
    $optionalMatchingRoleGraphView,
);
```

The returned observation and audit evidence are in-memory values. Consumers
choose whether to pass them to any separately owned recorder or exporter.

## Evidence vocabulary

Safe evidence contains only fixed tokens, bounded counts, and booleans.
Protected evidence contains only lowercase SHA-256 identities for the
inspection, resolution artifact, build, source set, asset graph, and aggregate
content set. Lists and maps have exact key order, are unique and bounded, and
accessors return detached arrays.

Chie selects `explain`, Hantei selects `verify`, and Oshie selects `teach`.
These labels select an audience purpose only. Optional Graph evidence is
accepted only for the same role, discloses aggregate counts and fixed
uncertainties only, and always reports `not-established` as the relationship.

## Claim matrix

| Evidence | Proved by inspection | Not proved |
| --- | --- | --- |
| Resolution artifact | Supplied canonical structure, ordering, relations, metadata, and identities reconstruct exactly in memory | Published files still exist or match |
| Current result | Supplied summary counts bind coherently to reconstructed evidence | A build or check ran during inspection |
| Content set | One deterministic digest binds the sorted content-identity set | Individual identities, keys, paths, MIME values, integrity strings, or bytes |
| Developer role | One of three fixed purposes was selected | Agent execution, authority, hidden access, repair, or application behavior |
| Graph input | Matching-role aggregate evidence is structurally supported | Asset ownership, loading, authorization, or Graph currentness |
| Observation/audit | Neutral evidence was constructed in memory | Recording, export, tracing, live audit, policy enforcement, or production observation |
| Verification | Candidate evidence matches independent reconstruction | Certification, vulnerability scanning, deployment correctness, browser safety, accessibility, or performance |

## Failure and isolation

Invalid requests/evidence use `KGN-ASSET-INSPECTION-1950`; artifact or current
binding failures use `1951`; role, Graph, and intelligence failures use `1952`;
projection failures use `1953`; verification-value failures use `1954`.
Diagnostic context is Safe and closed. Foreign messages and traces are never
included in exception stringification.

Services retain no request, artifact, projection, role, or verification state.
Sequential and overlapping calls therefore have no cross-operation cache or
cleanup lifecycle. The package performs no filesystem, environment, clock,
randomness, process, network, build, resolution, serving, repair, apply,
recording, export, role execution, production observation, or certification.

## Status

Phase 19A-H are Accepted and Phase 19 is closed by the root-owned
[external acceptance](phase-19-external-asset-acceptance.md) and exact
checkpoint. This package is Experimental and makes no Stable, 1.0, live-currentness,
deployment, CDN, browser, security, accessibility, performance, or production
readiness claim.

- [ADR 0195](../adr/0195-visibility-safe-asset-inspection-and-developer-intelligence.md)
- [Asset Platform](asset-platform.md)
- [Phase 19 roadmap](phase-19-roadmap.md)
