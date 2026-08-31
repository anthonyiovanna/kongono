# Extension Declaration Artifact

Phase 12H externally proves deterministic build, strict structural validation,
caller-relative currentness, and contained no-clobber publication. The artifact
remains declaration evidence rather than admission, a grant, loaded code, or
execution authority. See [Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and boundary

Phase 12C implements deterministic evidence for one explicit caller-supplied
set of Phase 12B extension declarations. The artifact makes bytes, identities,
limits, validation, currentness, and no-clobber publication inspectable without
discovering, selecting, admitting, granting, loading, hosting, or executing an
extension.

## Input and ordering

The builder accepts a PHP list containing 0-256 `ExtensionDeclaration` values.
Extension identities must be unique and in strict ascending binary order. A
non-list, wrong type, duplicate, bad order, or 257th entry fails. The builder
does not sort, deduplicate, scan installed packages, read Composer metadata, or
retain the caller array.

## Canonical fields and identities

Each declaration preserves, in order, contract version, identity, extension
version, category, source identity and digest, lifecycle, capability requests,
and contributions. Capability entries preserve identity, version, purpose, and
requirement. Contribution entries preserve identity, schema, schema version,
visibility, and source digest. No payload, path, class, service, callable,
grant, Graph fact, or default is inferred.

The root schema is `kongono.extension-declaration-artifact`, version 1. It
contains the declaration-set identity and digest, the fixed builder record,
exact declaration count, and declaration list. Canonical encoding is compact
UTF-8 JSON with fixed insertion order, unescaped slashes and Unicode, no BOM,
CR, NUL, duplicate/unknown keys, or second terminal LF, and exactly one final
LF. Complete artifacts are bounded to 4,194,304 bytes.

`declaration_set_sha256` is the lowercase SHA-256 of the canonical object whose
only key is `declarations`; `declaration_set_identity` is the same digest. The
complete artifact has a separate lowercase SHA-256 that is not embedded in its
own bytes.

## Validation and currentness

Structural validation checks framing, JSON, exact key shapes and scalar types,
schema, versions, builder identity, counts, bounds, ordering, uniqueness,
enums, identities, provenance, and digests. It reconstructs every Phase 12B
value using the public neutral constructors, rebuilds the complete artifact,
and requires byte-for-byte equality.

Currentness is caller-relative. `validateCurrent()` first proves structure,
then builds from the exact caller-supplied current declaration list. It has no
filesystem, environment, clock, Composer, registry, installed-package,
Application Graph, or global-state input.

## Publication and path safety

The publisher revalidates bytes and the stored artifact identity before I/O.
The caller supplies one canonical absolute existing ordinary directory. Empty,
relative, ambiguous, linked, junction/reparse-backed, changed, or unsafe-
ancestor paths fail. The exact basename is
`kongono-extension-declarations-v1-<artifact_sha256>.json`.

Publication creates a temporary ordinary file in the same directory, completes
bounded writes, flushes and synchronizes it, then creates the target with an
atomic no-overwrite hard link. Temporary cleanup is mandatory. An existing
target is accepted only after a stable bounded read proves exact bytes and
strict structural validity. Conflicting, linked, unreadable, changed, or
invalid targets fail without overwrite or repair. This private equality read
does not create a public loader.

## Diagnostics and disclosure

Diagnostics `KGN-EXTENSION-1208` through `KGN-EXTENSION-1212` cover invalid
input, invalid artifacts, stale currentness, publication failure, and fixed
limits. Context contains only fixed violation/kind tokens and optional
nonnegative counts, positions, or byte counts. Rejected JSON, declarations,
identities, digests, paths, credentials, source text, and previous exception
messages are not presented. Throwable traces are not claimed to be sanitized.

## API and dependencies

The public surface consists of the package exception marker; artifact values,
builder, validator, publisher, identity, and consumer exceptions are
Experimental; diagnostics, the abstract exception base, canonical codec, and
path guard are Internal. The package depends only on API Metadata, Diagnostics,
Extension Contract, and PHP.

## Explicit non-goals

There is no public loader, scanner, registry, resolver, selector, admission or
conflict policy, capability grant, authorization integration, Application Graph
projection, Composition wiring, host, callback, command, executable, telemetry,
marketplace, signature verification, sandbox, or external protocol. Phase 12D
and Phase 12E consume this evidence through separate packages without changing
the artifact boundary. Phases 12F-12H are implemented in separate boundaries
without changing this artifact contract. Kongono remains
Experimental, pre-1.0, and not production-ready.

## Related concepts

- [ADR 0133](../adr/0133-deterministic-extension-declaration-artifact.md)
- [Extension Contract foundation](extension-contract-foundation.md)
- [Extension Platform](extension-platform.md)
- [Phase 12 roadmap](phase-12-roadmap.md)

## Phase 12D currentness use

Extension Admission rebuilds Phase 12C declaration evidence directly from the
complete caller-supplied canonical declaration list and compares the exact
`DeclarationSetIdentity` in the application plan. It does not scan for, load,
or select an artifact file, and Phase 12C publication remains unchanged.

## Phase 12E exact-source use

Extension Composition rebuilds the declaration artifact from the complete
caller-supplied declaration list and requires its identity to equal Phase 12D
admission. It does not discover or load a published declaration file. The
resulting generated wiring binds declaration-set, admission, grant-plan, and
generator implementation identities while leaving Phase 12C publication and
currentness semantics unchanged.
