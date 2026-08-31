# ADR 0134: Deterministic Extension Selection, Admission, and Graph IR Projection

- Status: Accepted
- Date: 2026-08-30
- Decision semantics: LOCKED boundary; Experimental API

## Context

Phase 12B supplies inert declarations and Phase 12C supplies deterministic
declaration-set evidence. Neither selects an extension, resolves conflicts, or
admits contribution records. Phase 12D needs one deterministic application-
owned boundary before any future grant or execution work.

## Decision

Add `kongono/extension-admission`. An application supplies the complete
canonical declaration list, an exact declaration-set-bound selection plan, and
canonical Graph IR contribution bundles. Selection names exact extension
identities and opaque exact versions; there are no ranges, priorities,
discovery, newest selection, fallback, or repair.

Every selected contribution must use schema `kongono.application-graph-ir`
version 1 and carry provenance derived from the selected identity/version,
contribution identity, and exact contribution source digest under producer
contract `kongono.extension/1`. Every selected claimant is validated.

When multiple selected extensions claim one contribution identity, the
application must name exactly one claimant as winner. Missing, unnecessary,
unknown, and non-claimant resolutions fail. Duplicate winning node identities,
relation identities, or relation tuples fail without merging or partial
acceptance.

The complete winning record set is passed once through the accepted
Application Graph builder. The temporary Graph proves structural admission and
is discarded. The package returns fresh canonical IR records plus bounded
admission evidence and a deterministic identity. These values await a
separately owned application Graph build and are not authoritative Graph facts.

## Consequences and boundaries

Declaration, admission, grant, and execution remain separate. Admission grants
no capability, authorization, Principal status, Composition wiring, loading,
hosting, filesystem/network/process authority, or execution. Graph vocabulary,
contract version 1, schemas, and Composition producer behavior remain unchanged
apart from adding the exact attributable producer contract.

## Rejected alternatives

Rejected: scanning installed packages, mutable registries, version ranges,
priority or lexical winners, newest selection, fallback, best effort, merging
records, partial Graph construction, self-declared trust, and returning or
publishing an authoritative Graph.

## Deferred

Capability grant planning and generated Composition wiring belong to Phase
12E and require separate authenticated authority. Hosting, inspection,
developer-intelligence integration, and external acceptance remain later work.
