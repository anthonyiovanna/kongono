# Kongono External-Service Inspection

This optional Experimental package transforms one explicit exact-current Phase
16F external-service application profile and an optional detached completed
result into bounded, visibility-safe evidence. Currentness is caller-relative;
the evidence does not establish live application, credential, endpoint,
provider, authority, or network truth.

## Use when

Use the package when trusted application code already owns a Phase 16F profile
and needs deterministic offline inspection. Supply a completed result only to
verify its selected binding, operation and receipt identities, service outcome,
successful credential lifecycle, cleanup, and nested Phase 15 terminal evidence
without replay.

## Do not use when

Do not use inspection to acquire or expose credentials, resolve an endpoint,
perform DNS, TLS, HTTP, or provider work, build a request, load generated PHP,
start an application, execute a runtime, evaluate authority, retry, replay,
repair, record, export, probe live state, or certify an effect.

## Contract

`ExternalServiceInspector` reconstructs the supplied profile through the public
Phase 16F profile factory using only its source, canonical artifact bytes,
selected binding identity, and nested network profile. It independently compares
the complete binding, artifact, endpoint, credential-reference, capability, and
nested policy relations. Endpoint and credential-reference values are compared
only through package-owned equality; their protected projections are bound to
the selected content-addressed artifact without reading their raw representation.

Optional completed evidence must show credential success, a Retired state,
exactly one acquisition, material-use, and retirement attempt, and completed
cleanup. That evidence is not a physical-erasure claim. Possible-effect and
uncertain-effect outcomes remain failed/uncertain evidence and never authorize
retry or establish a network or provider effect.

`ExternalServiceInspection` emits canonical version-1 JSON containing only
lowercase Protected SHA-256 bindings, closed tokens, Safe bounded counts,
booleans, and fixed limitations. Raw identities, origins, endpoints, credential
references or material, requests, responses, bodies, artifact bytes, generated
PHP, paths, provider data, and foreign messages are omitted.

The Graph projector accepts only an explicit same-role Phase 11G Safe aggregate
view and states that the external-service-to-Graph relationship is not
established. Observability and Security Audit projectors construct values only.
The role enum selects Chie, Hantei, or Oshie evidence purposes without importing
or executing those roles. The independent verifier reconstructs the permitted
shape without calling the deriver or repairing a candidate.

## Lifecycle and side effects

The package is stateless, synchronous, and bounded by supplied in-memory
evidence. Independent calls are concurrency-safe because no state is retained.
It performs no filesystem, environment, clock, randomness, process, credential,
resolver, DNS, socket, network, recording, or export operation. Trusted generated
and in-process PHP is not a sandbox; generated PHP is never accepted or loaded.

## Stability

Consumer-facing declarations are Experimental. Canonicalization, diagnostics,
and the base exception are Internal. See the [architecture guide](../../docs/architecture/external-service-inspection-and-evidence.md)
and [ADR 0169](../../docs/adr/0169-visibility-safe-external-service-inspection-and-bounded-evidence.md).
Phase 16H exercises the package through root-owned local acceptance without
changing its API or effect boundary; see [ADR
0170](../../docs/adr/0170-phase-16-external-service-acceptance-and-checkpoint.md).
