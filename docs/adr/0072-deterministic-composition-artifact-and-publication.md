# ADR 0072: Deterministic Composition Artifact and Publication

Status: Accepted

Date: 2026-08-23

## Context

ADRs 0070 and 0071 define the inert, application-owned
`kongono.composition.json` contract. A supported build boundary must validate
that exact source before runtime and produce inspectable evidence without
turning the source into runtime service location or generated execution.

## Decision

The Experimental `kongono/composition` package implements build-time source
validation, canonical model construction, structural/current artifact
validation, and explicit content-addressed publication. It depends only on API
Metadata, Diagnostics, PHP, `ext-hash`, and `ext-json`.

The compact UTF-8 artifact has exactly one final LF, a 4,194,304-byte maximum,
and these ordered top-level members:

1. `schema` = `kongono.composition-artifact`
2. `version` = `1`
3. `source`
4. `project`
5. `builder`
6. `symbol_state`
7. `model_sha256`
8. `model`

`source` records the fixed source path plus its exact raw byte count and
SHA-256. `project` binds `composer.json` and `composer.lock`. `builder` binds
the package, contract version, and a closed manifest of all package production
files. `symbol_state` contains ordinally ordered declaring-source records and
their aggregate identity. `model_sha256` binds the standalone canonical model.

Semantic identity and exact-source identity are distinct. Reordering
non-semantic declarations leaves canonical model bytes and `model_sha256`
unchanged. When that reordering changes raw source bytes, `source.sha256`, the
complete artifact bytes, and the artifact SHA-256 change. Rebuilding the exact
unchanged source and project state is byte-for-byte deterministic.

`validateStructure()` accepts only the exact canonical grammar and derived
identities. `validateCurrent()` rebuilds from the explicit application root and
requires byte-identical output; stale source, project, symbol, or builder state
fails closed without repair.

Publication requires an explicit pre-existing ordinary output directory. The
filename is
`kongono-composition-v1-<lowercase-artifact-sha256>.json`. Publication writes,
flushes, and syncs a unique same-directory temporary file, then uses a
no-overwrite atomic link. An identical existing target is accepted; any unsafe
or different target fails. Temporary cleanup is mandatory.

## Consequences

- Build evidence is deterministic, inspectable, content-addressed, and bound
  to exact raw source and project state.
- Canonical semantic comparison remains available independently of raw-source
  provenance.
- Twelve `KGN-COMPOSITION-1001` through `1012` diagnostics expose only bounded
  Safe violation tokens through the package's separate Public exception marker.
- Applications must still arrange when to build, where to publish, and how a
  future runtime will select an accepted artifact.

## Rejected alternatives

- Removing raw-source provenance would conceal exact-source changes.
- Normalizing or replacing raw source bytes before hashing would weaken stale
  validation.
- Embedding the artifact hash recursively, splitting a second provenance
  envelope, or adding a latest pointer would add an unnecessary contract.
- Runtime parsing, scanning, reflection, service location, or hidden autowiring
  would violate the pre-runtime build boundary.
- Overwriting an existing publication target would break content addressing
  and no-clobber guarantees.

## Non-goals

Phase 5D does not add a CLI, schema file, generated PHP, container, resolver,
runtime loader, root construction, cleanup execution, cache layout, deployment
integration, request-time reflection, or DI/Application Graph/Kernel/routing/
HTTP integration. Phase 5E is next and has not begun.
