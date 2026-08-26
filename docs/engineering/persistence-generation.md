# Persistence Generation

## Purpose and stability

Phase 7D extends the Experimental `kongono/persistence-build` build boundary with
deterministic ordinary PHP state evidence. It is for build/review tooling that
already holds one exact-current Phase 7C `PersistenceArtifact`. It is not a
runtime ORM entry point, loader, command, repository, or database capability.

## Generation flow

1. Build or obtain the persistence artifact from explicit application and root
   inputs.
2. Call `GeneratedPersistenceStateGenerator::generate()` with the artifact and
   the same explicit current inputs used by Phase 7C.
3. The generator calls `PersistenceArtifactValidator::validateCurrent()`, uses
   the returned detached model, binds current builder/generator identities,
   emits canonical PHP, enforces the 16 MiB bound, and performs non-executing
   structural validation.
4. Before loading any output, call
   `GeneratedPersistenceStateValidator::validateCurrent()` with the generated
   bytes and all explicit current inputs. It regenerates and requires exact
   byte identity.
5. If persistence is desired, call
   `GeneratedPersistenceStatePublisher::publish()` with an already existing
   admitted application-owned destination root. Publication writes only the
   fixed basename
   `kongono-persistence-state-v1-<generated-file-sha256>.php` in that root.

Missing, malformed, stale, mixed, oversized, or unverifiable evidence fails
closed. Rebuild from current authoritative inputs; never edit generated output,
select the newest file, postprocess bytes, or bypass exact-current validation.

## Generated contract

The namespace is `<generated_namespace>\\Artifact_<first32-artifact-sha>`. One
ordinal final state-adapter class is emitted for each entity. The artifact
exposes the exact entity-to-adapter class map.

Each adapter has only these public static operations:

- `hydrate(array $record): Entity` validates the exact record shape and calls
  the declared public constructor or named factory.
- `extract(Entity $entity): array` calls declared public zero-argument accessors
  and returns the exact record-key shape.
- `identity(Entity $entity): EntityIdentity` returns a bounded tagged scalar
  identity.
- `snapshot(Entity $entity): array` returns detached typed field evidence.
- `changes(Entity $entity, array $snapshot): ChangeSet` compares by strict
  scalar identity and returns either no changes or one update record with field
  names and expected concurrency evidence.
- `insertion(Entity $entity): ChangeSet` returns one insert record.
- `deletion(Entity $entity): ChangeSet` returns one delete record with identity
  and optional expected concurrency evidence.

Snapshots and change sets contain evidence only. They do not register objects,
perform database I/O, own sessions or transactions, flush, retry, load
relationships, or retain application objects beyond the returned neutral value.

## Scalar and encoding rules

Record and snapshot boundaries preserve declared `bool`, `int`, finite `float`,
UTF-8 `string`, binary `string`, and permitted `null`. Text rejects malformed
UTF-8. Floats reject NaN and positive/negative infinity.

Identity and concurrency values are encoded with non-overlapping tags:

| Declared kind | Encoding |
|---|---|
| Boolean | `b:0` or `b:1` |
| Integer | `i:` plus canonical integer text |
| Float | `f:` plus hexadecimal big-endian IEEE-754 bytes |
| Text | `t:` plus Base64 of UTF-8 bytes |
| Binary | `x:` plus Base64 of opaque bytes |

Encoded identity/token values are bounded to 512 bytes. Equal raw bytes declared
as text and binary remain distinct. Application constructor, named-factory, and
accessor exceptions propagate unchanged; generated diagnostics do not include
the rejected values.

## Validation and security

`validateStructure()` checks UTF-8-without-BOM, LF line endings, one final LF,
canonical provenance JSON, the content-addressed namespace, exact class/method
inventory, fixed imports and declarations, and a closed token/function grammar.
It parses and tokenizes only; it never includes or executes the candidate.
Unexpected top-level statements, free function calls, `exit`, process/network/
filesystem features, Reflection, dynamic loading, `eval`, globals,
superglobals, and other forbidden capabilities are rejected.

`validateCurrent()` is the freshness boundary. Structural validity alone does
not authorize loading. Exact-current validation regenerates from the explicit
Phase 7C source/mapping/domain/root inputs and the reviewed implementations, then
compares exact bytes.

## Publication and stale output

Publication is content-addressed, atomic, idempotent for identical bytes,
initial-only, and no-clobber. An existing conflicting destination is never
replaced. Path aliases, traversal, alternate data streams, links, junctions,
reparse points, non-ordinary files, and containment changes fail closed.

When current validation reports stale output, discard that candidate from the
load path and regenerate from current inputs. Do not overwrite a published
content-addressed file, relabel stale bytes, weaken validation, or infer that a
successful prior test proves currentness.

## External proof before use

Fresh output must be written outside the repository and pass, in order: PHP
lint, maximum PHPStan, the configured cache-disabled style check, structural
validation, exact-current validation, and focused behavior proof for every
adapter. Adversarial probes must establish no execution during rejection.
Remove all temporary files and directories after proof.

## Explicit non-claims

Phase 7D does not provide a runtime generated-code loader, autoload registration,
CLI/Composer command, or checked-in application output. Phase 7E separately
implements one explicit single-entity context through the generic
`PersistenceStateAdapter` capability; it does not load or select these generated
classes. Generated-state bridging remains deferred. Neither phase adds a
SQL generation, transaction owner, migration, lazy loading, proxy, runtime
subclass, private-state bypass, Composition integration, or deployment workflow.
Phase 7F separately generates stateless aggregate repositories with named bounded
loads and explicit one-level cascades; it does not alter generated state adapters.

See [ADR 0091](../adr/0091-deterministic-generated-persistence-state-evidence.md),
the [persistence build guide](persistence-build.md), the
[aggregate repository guide](aggregate-repositories.md), the
[Deterministic Persistence guide](../architecture/deterministic-persistence.md),
and the [Phase 7 roadmap](../architecture/phase-7-roadmap.md).
