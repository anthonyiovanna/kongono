# Composition Build Artifact

## Current boundary

Phase 5D implements the Experimental `kongono/composition` build-time package.
It admits the exact Phase 5C `kongono.composition.json` source, validates its
closed version-1 declarations and inspected symbols, constructs a detached
canonical model, and returns one immutable artifact value.

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
