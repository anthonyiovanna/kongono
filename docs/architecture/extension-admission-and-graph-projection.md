# Extension Admission and Graph Projection

Phase 12H externally proves this admission boundary without changing it.
Selection remains application-owned, projected records remain attributable
neutral IR rather than authoritative Graph facts or execution authority, and
visibility remains distinct from authorization. See
[Phase 12 external acceptance](phase-12-external-acceptance.md).

## Purpose and current boundary

Phase 12D implements deterministic application-owned selection and admission
above the existing Extension Contract, Extension Build, and Application Graph
contract/build packages. Its checkpoint graph was 60 packages and 212 acyclic
local edges. Phase 12E now consumes this exact admission evidence for explicit
grants and generated wiring without changing admission or Graph authority.

## Inputs

The caller supplies one complete canonical declaration list, one plan bound to
its exact declaration-set identity, and one canonical list of contribution
bundles. Each selection names one exact identity and version. Each bundle names
its selected owner and declared contribution and contains canonical bounded
node and relation record lists.

Construction and admission never inspect Composer, directories, classes,
environment variables, current working directory, network state, clocks, or a
mutable registry. Noncanonical input fails instead of being reordered.

## Attribution and conflicts

Only `kongono.application-graph-ir` version 1 is supported. Every record in a
bundle must carry the exact `kongono.extension/1` producer/source provenance
derived from extension identity, version, contribution identity, and declared
source digest.

If selected declarations reuse a contribution identity, the application must
provide exactly one resolution naming a selected claimant. All bundles are
validated, but only the winner enters the final IR. Record-identity and
relation-tuple conflicts across winners fail closed; there is no merge,
priority, fallback, or partial result.

## Output and authority

Winning records are sorted by stable identity and passed once through the
accepted Application Graph builder. Its temporary authoritative object is
discarded. `AdmittedExtensionGraphInput` contains fresh canonical IR records,
admitted-selection evidence, applied resolutions, the exact declaration-set
identity, and an ambient-free canonical admission identity.

The result is input to a separately owned future application Graph build. It is
not itself current Graph truth and grants no capability, authorization,
Composition binding, loading, hosting, or execution authority.

## Failure, disclosure, lifecycle, and concurrency

Failures use `KGN-EXTENSION-ADMISSION-1201` with a closed failure kind and only
bounded Safe/Protected context. Messages and deterministic string forms omit
raw Graph field values, source bytes, paths, environment values, and rejected
arbitrary values. The package performs no I/O, retains no cross-call state, and
has no start/retire lifecycle. Immutable outputs are safe to retain and share.

## Related material

- [ADR 0134](../adr/0134-deterministic-extension-selection-admission-and-graph-ir-projection.md)
- [Extension Platform](extension-platform.md)
- [Extension Contract Foundation](extension-contract-foundation.md)
- [Extension Declaration Artifact](extension-declaration-artifact.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Phase 12 roadmap](phase-12-roadmap.md)
