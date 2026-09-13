# Compiled-View Inspection and Intelligence

## Purpose and context

Phase 18G adds optional visibility over one explicit Phase 18F
`ViewApplicationProfile`. The `kongono/view-inspection` package sits above the
accepted view application/build/contract boundaries and beside the neutral
Graph, Observability, and Security Audit contracts. It is an evidence
transformer, not a loader, renderer, monitor, recorder, or authority boundary.

## Input and validation

`ViewInspectionRequest` contains exactly one already exact-current application
profile. The inspector passes the profile's in-memory metadata and generated
bytes to the Phase 18C structural validator, compares the reconstructed
artifact, and checks the profile, current-build, source, compiled, model,
generated, and view-count relationships.

This is validation of supplied caller-relative evidence. The package reads no
source or artifact path, scans no directory, loads or executes no generated
class, and does not establish that the filesystem or a deployment is still
current. It does not render, adapt a response, run an application, or observe a
production system.

## Canonical evidence and disclosure

`ViewInspection` uses schema `kongono.view-inspection`, version 1. Canonical JSON
is fixed-order UTF-8 with LF framing, and its identity is lowercase SHA-256 of
the exact payload excluding only the identity field.

Permitted evidence is closed: Safe schema, version, scope, posture, marker,
limitation, count, boolean, and role vocabulary; and Protected profile, source,
compiled, model, and generated digests. Paths, basenames, source definitions,
template text, contexts, presentation values, metadata bytes, generated PHP,
rendered output, classes, HTTP headers or bodies, exception details, traces,
and arbitrary caller content never cross the boundary.

## Developer roles and independent verification

The supported role mapping is exact:

| Identifier | Evidence purpose |
| --- | --- |
| `chie` | `explain` |
| `hantei` | `verify` |
| `oshie` | `teach` |

These are evidence addresses only. The package imports and executes no role
implementation and grants no authority. `ViewIntelligenceVerifier`
independently reconstructs every allowed field and ordering rule without
calling `ViewIntelligenceDeriver`. It accepts exact evidence and returns closed
matching or mismatch verdicts; it never normalizes, repairs, or discloses a
candidate.

## Graph, observation, and audit projection

Graph input is optional and must match the selected developer role. The Graph
projector copies only bounded Safe aggregate counts, fixed markers, and accepted
limitations. `view-to-graph-relationship` always remains `not-established`;
neither presence nor absence creates a Graph relationship.

The observation and Security Audit projectors construct neutral in-memory
values marked `constructed-not-recorded`. They do not call a recorder or
exporter and make no delivery, durability, tamper, authorization, security,
compliance, or certification claim.

## Side effects, lifecycle, and limitations

The package is deterministic, offline, read-only, stateless, and safe for
sequential, nested, interleaved, independent, and Fiber-local calls because it
retains no operation state. Work is bounded by the closed supplied artifact and
Graph evidence sizes.

There is no filesystem, environment, process, DNS, socket, HTTP, credential,
clock, randomness, generated-code loading, reflection discovery, rendering,
application execution, role execution, repair, replay, recording, exporting,
or certification effect. Structural validation cannot sandbox trusted bytes or
prove live filesystem, deployment, browser, performance, accessibility,
security, or production truth.

Diagnostics `KGN-VIEW-INSPECTION-1860` through `1864` fail closed for invalid
inspection, artifact or binding evidence, invalid intelligence or verification
evidence, and projection failure. Consumer-facing APIs are Experimental;
canonicalization and diagnostics are Internal. No Stable/1.0 or
production-readiness claim follows.

- [ADR 0186](../adr/0186-visibility-safe-compiled-view-inspection-and-developer-intelligence.md)
- [Phase 18 local acceptance](phase-18-server-rendered-presentation-acceptance.md)
- [Compiled View Application Workflow](compiled-view-application-workflow.md)
- [Compiled View Build](compiled-view-build.md)
- [Phase 18 Roadmap](phase-18-roadmap.md)
