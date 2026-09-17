# Cache inspection and developer intelligence

Phase 20G accepts the optional Experimental `kongono/cache-inspection` package
as an offline, read-only, stateless upper evidence leaf. It consumes one
already loaded exact-current `CurrentCacheApplicationBuild` and optional
caller-supplied detached aggregate runtime evidence. It does not load a source,
read an artifact path, construct a runtime or adapter, or observe a backend.

## Input boundary

```php
use Kongono\CacheInspection\Inspection\CacheInspectionRequest;
use Kongono\CacheInspection\Inspection\CacheInspector;

$request = new CacheInspectionRequest($currentCacheApplicationBuild);
$inspection = (new CacheInspector())->inspect($request);
```

The inspector copies each public immutable profile declaration into a new
`CacheProfileSet`. It separately validates the exact public artifact bytes and
then compares canonical order, profile count, source identity, build identity,
artifact identity, artifact equality, and profile-set equality. Artifact bytes
are used only inside this reconstruction and never enter returned evidence.

The inspection proves only that the supplied typed build is structurally
coherent and carries exact-current evidence established upstream at the point
it was supplied.

## Optional detached runtime evidence

Detached evidence is constructed from three exact lowercase SHA-256 bindings
and the eleven closed outcome counts:

```php
use Kongono\CacheInspection\RuntimeEvidence\DetachedCacheRuntimeEvidence;

$runtimeEvidence = new DetachedCacheRuntimeEvidence(
    [
        'profile_set_sha256' => $current->profiles()->identity()->value(),
        'build_sha256' => $current->artifact()->buildIdentity()->value(),
        'artifact_sha256' => $current->artifact()->artifactIdentity()->value(),
    ],
    [
        'lookup_fresh_hit' => 12,
        'lookup_miss' => 3,
        'lookup_expired' => 1,
        'lookup_rejected' => 0,
        'lookup_unavailable' => 0,
        'mutation_stored' => 4,
        'mutation_removed' => 1,
        'mutation_not_found' => 0,
        'mutation_conflict' => 0,
        'mutation_rejected' => 0,
        'mutation_unavailable' => 0,
    ],
);

$inspection = (new CacheInspector())->inspect(
    new CacheInspectionRequest($current, $runtimeEvidence),
);
```

Each count and their total is bounded to 1,000,000. The evidence fixes
`caller_supplied`, `detached`, `keys_omitted`, and `values_omitted` to true and
fixes `live_backend_state_established` and `runtime_observed` to false. It
accepts no runtime, adapter, result, key, value, profile name, codec identity,
provider identity, operation record, timestamp, duration, or message. When it
is absent, status is `not-supplied` and every runtime count is zero.

## Evidence inventory

| Evidence | Disclosure | Meaning |
| --- | --- | --- |
| Schema/version/scope/posture/disclosure/authority | Safe | Closed inspection protocol tokens. |
| Runtime status, profile count, outcome counts and total | Safe | Bounded aggregate supplied evidence. |
| Structural, omission, and no-effect flags | Safe | Fixed limits on what inspection did. |
| Limitations and role purposes | Safe | Closed explanatory vocabulary. |
| Inspection identity | Protected | Digest of the canonical identity payload. |
| Profile-set/source, build, artifact identities | Protected | Exact-current binding digests. |
| Detached runtime identity | Protected | Present only when detached evidence is supplied. |

Returned maps and lists are detached copies. Equal input produces byte-equal
canonical output and the same lowercase SHA-256 identity.

## Roles, Graph, and projections

`CacheInspectionDeveloperRole` maps Chie to `explain`, Hantei to `verify`, and
Oshie to `teach`. These values select evidence purpose only; no agent is
loaded, prompted, authorized, or observed.

An optional `ApplicationGraphIntelligenceView` must have the same role. Only
its bounded aggregate counts and fixed evidence tokens are copied. Individual
Graph facts are omitted and the relationship remains `not-established` even
when Graph evidence is supplied.

The Observation and Security Audit projectors require an explicit
`ObservabilityContext`. They copy only fixed tokens and bounded Safe aggregate
fields into neutral in-memory values and label the projection
`constructed-not-recorded`. They do not invoke a provider, recorder, exporter,
transport, policy engine, clock, runtime, adapter, or backend.

## Independent verification

The verifier receives the original request and all claimed evidence. It
independently reconstructs the inspection, runtime status/count/bindings,
role/purpose, ordered statements and limitations, optional Graph evidence,
no-effect markers, serialization identity, Observation, and Security Audit
projection. Honest evidence returns `pass`; tampering returns `fail` with
closed ordered failure tokens. Verification never repairs or applies changes.

## Claim matrix

| Claim | Established? | Reason |
| --- | --- | --- |
| Supplied profile set is structurally reconstructed | Yes | Public declarations are copied into a new bounded set. |
| Supplied artifact bytes and identities are coherent | Yes | Exact bytes are structurally revalidated and compared. |
| Supplied build carried exact-current upstream evidence | Yes, narrowly | The typed current build and all identity relations match when inspected. |
| Detached aggregate is bound to that build | Yes, when supplied | All three SHA-256 bindings match. |
| Cache operations actually occurred | No | Counts are caller-supplied detached evidence. |
| A source file or published artifact is currently present | No | Product code performs no filesystem access. |
| Keys, values, namespaces, profile/codec/provider identities are known | No | Those fields are deliberately omitted. |
| Live backend/runtime state is current | No | No backend, adapter, runtime, or clock is accessed. |
| Cache correctness or freshness is established | No | No operation or freshness decision is inspected. |
| Graph ownership, loading, authorization, or currentness exists | No | Relationship is permanently `not-established`. |
| Observation or audit evidence was recorded/exported | No | Projections are constructed in memory only. |
| Production readiness, performance, security, availability, or certification | No | Phase 20G makes no such claim. |

See [ADR 0204](../adr/0204-visibility-safe-cache-inspection-and-developer-intelligence.md),
the [Cache Platform](cache-platform.md), the
[Cache Application workflow](cache-application-workflow.md), the
[Phase 20 external acceptance guide](phase-20-external-cache-acceptance.md), and the
[Phase 20 roadmap](phase-20-roadmap.md).
