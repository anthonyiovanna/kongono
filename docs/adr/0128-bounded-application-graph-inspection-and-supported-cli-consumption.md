# ADR 0128: Bounded Application Graph Inspection and Supported CLI Consumption

## Status

Accepted.

## Date

2026-08-29.

## Context

Phase 11E can validate and explicitly load one canonical authoritative
Application Graph artifact, but it offers no presentation-safe summary and the
supported `kongo` application has no Graph-artifact consumer. Returning the raw
Graph, decoded artifact, or individual facts would cross the artifact's
Protected-content boundary and would create a general query surface.

## Decision

`kongono/application-graph-artifact` owns an immutable Experimental
`ApplicationGraphArtifactInspection`. It contains only the artifact SHA-256,
typed Graph identity, version-1 contract identity, total node and relation
counts, and one count for every closed version-1 node and relation type. Counts
are non-negative, every vocabulary case is present even at zero, and type sums
must equal their corresponding totals.

The artifact builder and structural validator derive this summary only from
their freshly re-admitted canonical Graph. Summary derivation reads fact types
but never field values. The artifact exposes `inspection()` but does not expose
the reconstructed Graph, raw decoded objects, individual facts, fields,
provenance, paths, or a query, filter, traversal, or lookup API.

The existing upper `kongono/console-application` package adds exactly one
read-only command, `application-graph:inspect`. It accepts one required ordinary
`artifact-path`, loads that explicit path exactly once through the Phase 11E
loader, and returns fifteen Safe structural fields through the existing neutral
Console result and human/machine presenters. `CommandResultRecord` retains its
accepted ordinal case-sensitive field-name ordering.

Artifact-package marker failures map to the fixed
`KGN-CONSOLE-APPLICATION-1004` Precondition result. Invalid invocation shape
maps to InvalidInput, and unexpected failures map to Infrastructure. No rejected
path, artifact content, field value, exception message, or trace enters output
or diagnostic context.

The command proves structural validity only. It does not prove that the
artifact is current relative to Composition, PHP source, generated wiring, a
workspace, deployment, or a running process. Identities and counts are
evidence, never authorization.

## Rationale

The artifact package already owns structural validation and exact Graph
identities, so it is the narrowest owner of a presentation-neutral summary.
Keeping the CLI adapter in the existing supported application leaf preserves
package direction and reuses established parsing, operation cleanup,
presentation, and exit semantics.

## Consequences

- Inspection is deterministic and O(nodes + relations) within existing Graph
  and artifact bounds.
- Repeated, nested, interleaved, and concurrent use retains no mutable state.
- `kongono/console-application` gains one local dependency on
  `kongono/application-graph-artifact`; no lower package gains a Console edge.
- The Phase 0J `framework:graph` handler remains unchanged and semantically
  distinct.
- The feature is Experimental, pre-1.0, and not a production-readiness claim.

## Rejected alternatives

- Exposing the raw Graph or decoded artifact: rejected because it discloses
  individual facts and creates an unbounded consumer contract.
- Adding a second decoder, schema, loader, query package, or result-record type:
  rejected because accepted owners already exist.
- Discovering an artifact or selecting the newest file: rejected because path
  authority must remain explicit.
- Treating identities, counts, visibility, or provenance as authorization:
  rejected because evidence grants no permission.
- Changing Console result ordering: rejected because ordinal field-name order
  is already accepted.

## What remains open

Phase 11G visibility-safe developer-intelligence derivation and verification,
Phase 11H external acceptance/checkpoint work, general Graph queries,
visualization, runtime integration, authorization, registries, caching,
watching, and deployment behavior remain unstarted and require separate
authenticated authority.
