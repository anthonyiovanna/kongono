# Phase 11 Roadmap

> **Phase 11 complete boundary:** Phase 11H adds root-owned external acceptance
> and the exact local checkpoint. It adds no production Graph behavior, raw-fact
> consumer, role execution, runtime integration, authorization, or Phase 12 work.


## Purpose and status

Phase 10 is complete at checkpoint
`6f7865dbacc7dd9c4b90d2b60bd229b89efbd535`. Phase 11 establishes the future
authoritative Application Graph and language-neutral Intermediate
Representation in a fixed dependency order.

Phase 11A is historical and complete. It accepted
[ADR 0123](../adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
and the [architecture guide](authoritative-application-graph.md). Phase 11B is
implemented and current through
[ADR 0124](../adr/0124-versioned-application-graph-contract-foundation.md) and
the separate contract package. Phase 11C is implemented through
[ADR 0125](../adr/0125-language-neutral-application-graph-ir-and-deterministic-construction.md)
and `kongono/application-graph-build`. Phase 11D implements the supported
Composition projection, and Phase 11E implements the canonical artifact,
caller-relative currentness, explicit loading, and no-clobber publication
boundary. Phase 11F implements bounded structural inspection and the supported
one-path CLI consumer. Phase 11G implements visibility-safe role-addressed
aggregate derivation and independent verification. Phase 11H implements the
root-owned external proof accepted by ADR 0130 and closes the exact local Phase
11 checkpoint without beginning Phase 12.

## Fixed dependency order

| Phase | Boundary |
| --- | --- |
| `11A` | Architecture, doctrine, terminology, and fixed roadmap. |
| `11B` | Versioned identities, typed vocabularies, schemas, provenance, and visibility. |
| `11C` | Language-neutral IR records and deterministic Graph construction. |
| `11D` | Supported Composition-to-IR projection and application Graph build. |
| `11E` | Canonical Graph artifact, currentness validation, and no-clobber publication. |
| `11F` | Bounded read-only inspection and application/CLI consumption. |
| `11G` | Visibility-safe developer-intelligence derivation and verification. |
| `11H` | Root-owned external acceptance and the exact Phase 11 checkpoint. |

```text
11A architecture, doctrine, terminology, and fixed roadmap
 |
11B versioned identities, typed vocabularies, schemas, provenance, visibility
 |
11C language-neutral IR records and deterministic Graph construction
 |
11D supported Composition-to-IR projection and application Graph build
 |
11E canonical Graph artifact, currentness validation, no-clobber publication
 |
11F bounded read-only inspection and application/CLI consumption
 |
11G visibility-safe developer-intelligence derivation and verification
 |
11H root-owned external acceptance and exact Phase 11 checkpoint
```

The table and diagram express dependency order, not parallel work and not
authorization to implement the next row. Acceptance of one row does not begin
another automatically.

## Preserved lower-level use

Applications may continue to use Kernel, Composition, HTTP, database,
persistence, security, observability, and the implemented Phase 0J structural
Graph directly. The future authoritative artifact is not a prerequisite for
those accepted lower-level paths.

The Phase 0J package remains Experimental and unchanged. Its opaque local keys
and open kind labels are not stable Phase 11 identities or vocabularies.

## Milestone boundary

Phase 11 does not implement extensions, Agent Principals, frontend contracts,
or every planned Graph domain. It establishes the substrate on which those
future capabilities may later build under separate governance.

Phase 11B supplies versioned identities, vocabulary, schemas, provenance,
visibility, and disclosure. Phase 11C supplies explicit claim records and
complete deterministic construction of fresh canonical immutable facts. Phase
11D supplies the first Composition projection; Phase 11E supplies the canonical
artifact boundary; Phase 11F supplies aggregate inspection plus one read-only
supported command; and Phase 11G supplies a role-addressed Safe aggregate view
with six-check independent verification. Neither Phase 11F nor Phase 11G grants
individual-fact access, role execution, external currentness, extension
behavior, or production authority. Kongono remains pre-1.0 and not
production-ready.

Phase 11H externally proves the complete bounded stack through
`composer acceptance:phase11`. The harness remains repository test evidence,
and the checkpoint excludes every implementation directive and task report.

## Related material

- [ADR 0123](../adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
- [ADR 0124](../adr/0124-versioned-application-graph-contract-foundation.md)
- [ADR 0125](../adr/0125-language-neutral-application-graph-ir-and-deterministic-construction.md)
- [ADR 0128](../adr/0128-bounded-application-graph-inspection-and-supported-cli-consumption.md)
- [ADR 0129](../adr/0129-visibility-safe-application-graph-developer-intelligence.md)
- [ADR 0130](../adr/0130-phase-11-external-application-graph-acceptance-and-checkpoint.md)
- [Phase 11 external acceptance](phase-11-external-acceptance.md)
- [Inspection and consumption](application-graph-inspection-and-consumption.md)
- [Application Graph developer intelligence](application-graph-developer-intelligence.md)
- [IR and construction](application-graph-ir-and-construction.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Current Application Graph](application-graph.md)
- [Application Graph structural snapshot](../engineering/application-graph-structure.md)
