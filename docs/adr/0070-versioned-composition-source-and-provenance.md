# ADR 0070: Versioned Composition Source and Provenance

- **Status:** Accepted
- **Date:** 2026-08-23
- **Phase:** 5C

## Context

Phase 5A accepted one supported application composition root and a deterministic
build-before-runtime boundary. Phase 5B made `>=8.5.0 <8.6.0` the current root
and eighteen-package installability contract. Application construction remains
caller-owned: Kongono has no supported declaration reader, schema artifact,
composition package, build command, generated artifact, or runtime integration.

Phase 5C must select one bounded, inspectable source contract before Phase 5D
may implement validation and an artifact. The source must remain application
authority, inert data, and safe under persistent-runtime and hostile-filesystem
conditions.

## Decision

### Singular source authority

The accepted version-1 source is exactly
`<application-root>/kongono.composition.json`. A future build/check caller must
supply the application root explicitly. There is no current-working-directory
inference, parent traversal, scan, glob, Composer discovery, fallback name,
merge, overlay, environment variant, or remote source.

The application-owned file is authoritative application code in data form. It
is not framework authority, generated output, cache, or runtime state. Missing,
multiple, ambiguous, unsafe, unsupported, or changed-during-read source fails
closed.

### Inert versioned JSON

The source is UTF-8 JSON data and is never included, required, evaluated, or
treated as PHP. It must be UTF-8 without BOM, LF-only, NUL-free, and end in
exactly one LF. Its root is one object with exactly these members:

```text
schema
version
inputs
services
bindings
roots
```

`schema` must equal `"kongono.composition"`; the accepted integer version `1`
must appear as `version: 1`. Comments, trailing commas, NaN/Infinity, callables,
expressions, PHP code,
objects/resources as executable values, duplicate members, unknown members,
extensions, and compatibility guesses are rejected. Duplicate members are
detected rather than silently resolved.

Source order is semantic only for constructor `arguments` and an explicit
ordered `services` argument list. All other collections are canonicalized with
ordinal case-sensitive byte ordering before identity or output generation.

### File and path safety

The future boundary resolves the supplied application root once, appends only
the fixed relative filename, and proves containment before and after reading.
It rejects a missing or non-directory root; wrong-case, outside-root, missing,
non-ordinary, symlink, junction, reparse-point, alternate-stream, device, or
UNC-ambiguous source; case collisions; and any identity, size, or byte change
during the read/build proof. Validation failure never publishes or modifies an
artifact.

### Bounds

Version 1 admits at most:

| Measure | Maximum |
| --- | ---: |
| Source bytes | 1,048,576 |
| JSON nesting depth | 32 |
| Inputs | 256 |
| Services | 4,096 |
| Bindings | 4,096 |
| Roots | 256 |
| Arguments per service | 128 |
| Items in one `services` argument | 1,024 |
| Total dependency edges | 32,768 |
| Symbol bytes | 255 |
| Parameter or method-name bytes | 128 |

Counts apply to parsed declarations before deduplication. Empty source,
duplicates, unknown members, and exceeded bounds fail closed without truncation.

### Canonical model and provenance

A future builder must detach one canonical semantic model before artifact
generation. It uses exact declared strings; ordinal case-sensitive sorting for
inputs, services, bindings, and roots; and preserves constructor-argument and
explicit services-list order. Canonicalization does not rewrite the source.

Provenance binds the schema name/version, raw source SHA-256, canonical model
SHA-256, root `composer.json` and `composer.lock` SHA-256 values, an ordinal
manifest of the exact source files declaring every admitted service, input,
binding, root, constructor, and cleanup symbol, the implementing builder
package/version identity, and the Phase
5D-selected generated-artifact identity. It excludes absolute host paths,
usernames, timestamps, randomness, environment values, credentials, request
data, and Protected diagnostic data. Required relative paths are exact-case,
application-root-relative `/` paths.

Changed source, lock metadata, symbol bytes, builder identity, schema version,
or canonical model makes an earlier artifact stale. Phase 5D must separately
select artifact encoding, atomic publication, no-clobber, and verification.

### Deterministic validation and diagnostics

The accepted first-failure category order is:

1. application-root/source-path safety;
2. byte, encoding, JSON syntax, duplicate-member, and changed-during-read proof;
3. schema/version/unknown-member validation;
4. bounds and list/object shape;
5. canonical symbol and declaration existence;
6. duplicate/collision validation;
7. binding and input resolution;
8. constructor argument/signature compatibility;
9. root resolution;
10. lifetime reachability and cycle validation;
11. cleanup signature validation; and
12. provenance/source-set stability.

Within a category, selection uses ordinal canonical declaration order and then
explicit argument/list position. Non-semantic source ordering cannot change the
selected semantic failure.

Future package-owned failures must identify a fixed category, violated
invariant, bounded Safe context, and remediation. They must not disclose raw
source fragments or values, absolute paths, credentials, environment values,
native parser text, stack arguments, or Protected context. Phase 5C reserves no
diagnostic ID or exception class.

## Security and persistent-runtime implications

Source bytes are untrusted inert data and cannot enlarge build, tool, runtime,
Git, network, deployment, or authorization authority. Generated state contains
symbol contracts and bounded provenance, never runtime input values or secrets.
Production must eventually fail closed before accepting work when source or
artifact state is missing, stale, partial, mixed, ambiguous, or unverifiable.

## Consequences

The accepted source has one identity, one schema version, deterministic
ordering, bounded processing, inspectable provenance, and explicit invalidation.
Applications cannot merge profiles or execute configuration. Later schema
versions require a separate migration and compatibility decision.

## Rejected alternatives

- Executable PHP, expressions, callbacks, and `eval` blur data and authority.
- Attribute/classpath/directory scanning makes discovery ambient and unstable.
- Multiple files, overlays, environment variants, fallbacks, and remote sources
  make authority and provenance ambiguous.
- Silent duplicate-key or unknown-member tolerance conceals mistakes.
- Absolute paths, timestamps, or environment values make artifacts host-bound
  or disclose sensitive state.

## Explicit non-goals

This ADR implements no parser, duplicate-key detector, schema file, package,
API, command, artifact, generator, wiring, Graph integration, runtime reader, or
deployment behavior. It selects no artifact encoding or publication mechanism.
Phase 5D has not begun.

## Related material

- [ADR 0068](0068-supported-application-composition-and-build-boundary.md)
- [ADR 0069](0069-generated-typed-wiring-and-no-runtime-service-location.md)
- [ADR 0071](0071-roots-bindings-lifetimes-and-cleanup.md)
- [Composition source architecture](../architecture/composition-source.md)
- [Composition declaration engineering guide](../engineering/composition-declarations.md)
- [Phase 5 roadmap](../architecture/phase-5-roadmap.md)
