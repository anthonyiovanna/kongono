# Application Graph Inspection and Consumption

## Purpose and current status

Phase 11F implements the first supported bounded read-only consumer of one
authoritative Application Graph artifact. The Experimental inspection value is
owned by `kongono/application-graph-artifact`; the supported command is owned by
the upper `kongono/console-application` package.

## Usage

Supply one explicit canonical absolute artifact path:

```text
kongo application-graph:inspect --artifact-path=C:/path/to/kongono-application-graph-v1-<sha256>.json
kongo --json application-graph:inspect --artifact-path=C:/path/to/kongono-application-graph-v1-<sha256>.json --no-interaction
```

The command is read-only, has no prompt, accepts only Observe intent, and loads
the supplied path exactly once. It performs no discovery, directory
enumeration, newest-file selection, publication, repair, retry, fallback,
cache, watch, or rebuild.

## Output contract

The neutral result contains exactly:

- schema `kongono.application-graph-inspection` and integer version `1`;
- validated artifact SHA-256 and typed Graph identity;
- integer Graph contract version `1`;
- total node and relation counts;
- counts for every version-1 Composition node and relation type.

The existing `CommandResultRecord` returns the fifteen fields in ordinal
case-sensitive field-name order. Every field is Safe bounded structural
evidence. No artifact path, canonical bytes, fact identity, field name/value,
provenance, source location, class, service, contract, root, exception message,
or trace is returned.

## Meaning and limits

Successful inspection proves that the explicit artifact is structurally valid
under the accepted Phase 11E contract. It does not prove currentness relative
to Composition, PHP source, generated wiring, a workspace, deployment, or a
running process. Graph and artifact identities and all counts are evidence,
never authorization.

The summary exposes aggregate structure only. It offers no individual node or
relation listing, lookup, filtering, traversal, diff, impact analysis,
visualization, arbitrary projection, or raw Graph accessor.

## Failure mapping and security

- Invalid command identifier, policy, interaction, intent, input count, or
  input name produces InvalidInput.
- Any Application Graph Artifact marker failure produces the fixed
  `KGN-CONSOLE-APPLICATION-1004` Precondition result.
- Any unexpected failure produces the existing Infrastructure result.

Diagnostics contain only fixed Safe tokens. Rejected paths and nested failure
details never cross the presentation boundary. Artifact contents may include
Protected facts even though the aggregate inspection fields are Safe.

## Lifecycle, isolation, and performance

Each invocation uses the existing Console Runtime operation lifecycle and
cleanup. The handler and inspection retain no call state. Sequential, nested,
interleaved, and concurrent processes remain isolated.

Inspection is O(nodes + relations), bounded by the accepted Graph and artifact
limits. It never reads field values and performs no filesystem, network,
environment, clock, randomness, reflection, process, logging, telemetry,
database, security-decision, DI, discovery, or global-state access. Filesystem
access belongs solely to the existing explicit loader.

## Package direction and exclusions

`kongono/console-application` depends on
`kongono/application-graph-artifact`. No lower package depends on Console or
Console Application. Phase 8F `kongono/console-commands` and its Phase 0J
`framework:graph` handler remain byte-identical and semantically distinct.

This Phase 11F boundary adds no Chie, Hantei, Oshie, AI/provider/model,
authorization, runtime registry, Kernel/HTTP/database/persistence/telemetry
integration, or role execution. Phase 11G consumes only this aggregate
inspection through its separately bounded package; it does not widen the
inspection API. Kongono remains pre-1.0 and not production-ready.

Phase 11H externally proves the explicit loader and supported command through
the root-owned harness. That proof adds no query, raw-fact access, global
freshness claim, production consumer, or runtime authority.

## Related material

- [ADR 0128](../adr/0128-bounded-application-graph-inspection-and-supported-cli-consumption.md)
- [ADR 0129](../adr/0129-visibility-safe-application-graph-developer-intelligence.md)
- [Application Graph developer intelligence](application-graph-developer-intelligence.md)
- [Application Graph artifact and currentness](application-graph-artifact-and-currentness.md)
- [Authoritative Application Graph](authoritative-application-graph.md)
- [Structured CLI](structured-cli.md)
- [Phase 11 roadmap](phase-11-roadmap.md)
- [Phase 11 external acceptance](phase-11-external-acceptance.md)
