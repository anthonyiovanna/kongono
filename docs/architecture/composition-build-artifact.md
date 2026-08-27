# Composition Build Artifact

## Current boundary

Phase 5D implements the Experimental `kongono/composition` build-time package.
Phase 9E extends its admitted source set to versions 1 and 2. Version 1 retains
its closed compatible declarations; version 2 requires an explicit application
or operation lifetime on every input. Both paths validate inspected symbols,
construct a detached canonical model, and return one immutable artifact value.

The public build surface is intentionally small:

- `CompositionArtifactBuilder::build()` builds from one explicit application
  root.
- `CompositionArtifactValidator::validateStructure()` checks detached bytes.
- `CompositionArtifactValidator::validateCurrent()` additionally requires an
  exact current rebuild.
- `CompositionArtifactPublisher::publish()` publishes into one explicit
  existing output directory.
- `CompositionArtifact` exposes only canonical bytes and the artifact, source,
  model, source-set, and builder SHA-256 identities.

## Identity layers

Canonical model bytes sort inputs, services, bindings, and roots ordinally;
constructor arguments preserve their semantic order, including the separate
member edges of an explicit typed aggregate. Thus non-semantic declaration reordering does not change model bytes or
`model_sha256`.

The canonical model preserves the admitted source contract version and each
input lifetime. Cross-scope application reachability to an operation input
fails before artifact construction.

The complete artifact also preserves the exact raw source byte count and hash.
Two semantically equal but byte-different sources therefore have different
artifact bytes and artifact hashes. The unchanged same source, project files,
declaring files, builder implementation, and schema produce the same bytes.

## Provenance and currentness

The artifact binds raw source, `composer.json`, `composer.lock`, all explicitly
admitted symbol source files, and the closed builder implementation manifest.
All paths are root-relative and ordinally ordered. Absolute paths, source
fragments, usernames, timestamps, native messages, and rejected values are not
included.

Current validation is exact equality with a fresh complete build. It does not
update, repair, publish, or select an artifact.

## Publication

Publication is content-addressed and no-clobber. The supported filename is
`kongono-composition-v1-<artifact-sha256>.json`. An identical existing ordinary
target is idempotent; a conflicting or unsafe target fails. No latest pointer,
index, cache convention, garbage collection, or runtime selector exists.

## Relationship to the application workflow

The Phase 5D API validates and records construction intent; it does not
construct services or execute cleanup. Phase 5E adds generated wiring and
isolated lifetime support, and Phase 5F adds fail-closed loading and existing
web lifecycle integration. Phase 5G's `ApplicationBuildWorkflow` composes these
build-time APIs, publishes both exact identities, and checks them without a
directory scan or mutable pointer. See the
[minimal application workflow](minimal-application-workflow.md).

Phase 9E does not make the build artifact runtime authority. Its source-version
identity feeds exact generated wiring and current validation; operation inputs
remain explicit caller values and never appear in artifact bytes.
