# Storage inspection and developer intelligence

Phase 21G accepts the optional Experimental `kongono/storage-inspection`
package. It inspects one already loaded
exact-current storage application build in memory and emits bounded evidence.
It never opens or lists an object or contacts a provider.

## Input and reconstruction

```php
use Kongono\StorageInspection\Inspection\StorageInspectionRequest;
use Kongono\StorageInspection\Inspection\StorageInspector;

$inspection = (new StorageInspector())->inspect(
    new StorageInspectionRequest($currentStorageApplicationBuild),
);
```

The inspector copies every accepted immutable profile declaration field into
a new `StorageProfileSet`. It separately revalidates the supplied artifact's
exact bytes, then compares source, build, artifact, complete profiles, count,
and canonical order. Its claim is coherence of supplied in-memory evidence;
the current source file, published artifact, authorization policy, provider
state, and operation outcome are not checked.

## Detached aggregate evidence

The optional `DetachedStorageRuntimeEvidence` accepts exactly three ordered
SHA-256 bindings: `profile_set_sha256`, `build_sha256`, and `artifact_sha256`.
Its 17 ordered counters are:

`read_completed`, `read_absent`, `read_rejected`, `read_unsupported`,
`read_unavailable`, `mutation_completed`, `mutation_absent`,
`mutation_already_exists`, `mutation_conflict`, `mutation_rejected`,
`mutation_unsupported`, `mutation_unavailable`, `mutation_uncertain`,
`list_completed`, `list_rejected`, `list_unsupported`, and `list_unavailable`.

Each counter and their total is an integer from 0 through 1,000,000, with
the total also bounded to 1,000,000. The evidence identity is a digest of
fixed-order canonical content. All-zero supplied evidence remains distinct
from no evidence. These caller-supplied counts do not prove events occurred;
`mutation_uncertain` stays separate from completed and failed outcomes.

## Disclosure and projections

Inspection JSON is compact UTF-8 with one terminal LF. Safe fields are closed
protocol tokens, profile count, outcome counters and total, fixed structural
and no-effect flags, limitations, and role purposes. Profile-set, build,
artifact, inspection, detached evidence, and serialization digests are
Protected. Raw profile names, namespaces, object identities or contents,
metadata, versions, paths, locators, credentials, artifact bytes, event
details, timestamps, and foreign messages are omitted.

Chie, Hantei, and Oshie select `explain`, `verify`, and `teach` evidence only;
no role executes. Optional matching-role Application Graph evidence contributes
only bounded aggregates. Its relationship to storage stays `not-established`.
Observation and Security Audit projections use an explicitly supplied
`ObservabilityContext`, construct only neutral in-memory values, and declare
`constructed-not-recorded`. The bounded Observation carries summary counters,
including uncertain mutations; the inspection retains the full 17 counters.
No recorder, exporter, authority evaluator, runtime, or provider is invoked.

The verifier independently reconstructs the expected inspection, role,
Graph, serialization, Observation, and Security Audit evidence from the
original request. It returns only a closed pass/fail verdict and failure
tokens. It never normalizes a forged candidate into a pass.

## Claim boundary

| Claim | Established |
| --- | --- |
| Supplied profiles and exact artifact bytes are coherent | Yes, in memory |
| Detached counts bind to that supplied build | Yes, if supplied |
| Operations occurred or uncertain mutations resolved | No |
| Source files, published artifacts, or provider state are current now | No |
| Authorization, durability, security, or availability is certified | No |
| Graph relationship, role execution, recording, or export occurred | No |

See [ADR 0213](../adr/0213-visibility-safe-storage-inspection-and-developer-intelligence.md)
and the [Phase 21 roadmap](phase-21-roadmap.md).
