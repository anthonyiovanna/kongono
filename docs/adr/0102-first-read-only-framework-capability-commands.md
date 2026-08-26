# ADR 0102: First Read-Only Framework Capability Commands

## Status

Accepted and implemented by Phase 8F.

## Context

The Structured CLI foundation separates deterministic command definitions,
operation-scoped execution, and presentation. Kongono now needs a first bounded
capability-adapter family without creating the future executable or allowing a
handler to discover, run, or mutate a capability.

## Decision

Phase 8F adds the Experimental `kongono/console-commands` upper package with
exactly three read-only commands: `framework:composition`, `framework:graph`,
and `framework:quality`. Their definitions remain deterministic Console Build
evidence. One immutable family validates the exact definition set before
binding three typed handlers.

The handlers receive already-constructed immutable capability snapshots through
ordinary generated Composition constructor wiring. Graph inspection exposes
only node, node-kind, relation, and relation-kind counts. Composition inspection
exposes only the artifact, builder, model, source-set, and source SHA-256
identities. Hantei inspection exposes only its verdict and Pass, Fail, Blocked,
and total check counts. A Hantei `FAIL` or `BLOCKED` verdict is successful
inspection data rather than a command-execution failure.

Every selected result field is bounded, Safe, and presentation-neutral. No
handler reads ambient state, runs the supplied capability, performs I/O, starts
a process, or mutates framework or application state.

## Consequences

The package depends upward on Console, Console Runtime, Application Graph,
Composition, and Hantei, while no lower package depends on it. Exact generated
wiring proves typed object inputs and ordinary constructor calls, but creates no
supported application workflow.

Phase 8F adds no executable, native process adapter, stream owner, prompt,
mutation command, build execution command, or deployment behavior. `kongo`
remains the official future executable reserved for Phase 8G. Phase 8G and
Phase 8H remain unimplemented.
