# Kongono Model Invocation Inspection

`kongono/model-invocation-inspection` is the optional Experimental Phase 14G
upper evidence leaf. It inspects one explicit Phase 14F application profile,
its exact generated wiring, and optionally one already completed detached
Phase 14D runtime result.

## Use when

Use the package when an application or developer tool needs deterministic,
presentation-neutral aggregate evidence without loading generated PHP,
starting an application, invoking an adapter or provider, or replaying an
operation. `ModelInvocationInspector` delegates structural and exact-current
wiring proof to Phase 14F and independently compares every public binding.

The result is canonical `kongono.model-invocation-inspection` version 1
evidence. It contains fixed version and limitation tokens, Protected technical
digests required for exact comparison, Safe request aggregates, and an optional
bounded terminal runtime summary. Raw messages, prompts, responses, streaming
payloads, action arguments, provider/model/adapter labels, arbitrary
identities, receipt details, usage metric names, currencies, credentials,
paths, URLs, and foreign failure messages are omitted.

## Cross-system projections

- `ModelInvocationGraphEvidenceProjector` accepts only an explicit matching-role
  Phase 11G Safe aggregate view. Absence is explicit and no model-to-Graph
  relationship, currentness, executability, or authority is inferred.
- `ModelInvocationObservationProjector` constructs one neutral
  `kongono.model-invocation.inspection` operational event without recording it.
- `ModelInvocationSecurityAuditProjector` constructs one Safe
  `kongono.model-invocation.inspection.security` audit value without delivery,
  durability, tamper-evidence, compliance, or application-security claims.
- Chie, Hantei, and Oshie role tokens map to explain, verify, and teach. The
  deriver executes no role, model, prompt, retrieval system, provider, tool, or
  repair mechanism. The verifier independently compares fixed ordered evidence
  groups and never calls the deriver.

## Calling and lifecycle contract

Supply typed detached values only. Optional runtime evidence must already be
retired, cleanup-complete, terminal, and attributable to the exact request and
model profile. The package is stateless; repeated, nested, and interleaved
calls over equal inputs return equal bytes and retain no cross-call state.

The package performs no filesystem, environment, clock, randomness, network,
process, database, provider, Graph operation, generated-code loading,
Reflection discovery, recording, export, monitoring, command, UI, worker,
scheduler, retry, fallback, repair, or background work. Exact supplied evidence
does not establish external profile/provider currentness, application state,
provider/account/network state, authentication, authorization, billing truth,
an external effect, or certification. Trusted generated and in-process PHP is
not sandboxed, although the inspector never loads generated PHP.

## Stability and related material

The request, inspection value, projectors, role enum, derived view, verifier,
verdict, verification value, and operational exceptions are Experimental. The
package exception marker is Public; construction helpers and diagnostics are
Internal. Nothing is Stable.

- [ADR 0153](../../docs/adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md)
- [Model Invocation Inspection and Evidence](../../docs/architecture/model-invocation-inspection-and-evidence.md)
- [Phase 14 roadmap](../../docs/architecture/phase-14-roadmap.md)
- [Phase 14 external acceptance](../../docs/architecture/phase-14-external-acceptance.md)

Phase 14H is implemented as root-owned offline external evidence. It does not
change this package's read-only, stateless, non-loading boundary or turn Safe
aggregate projections and role views into authority, monitoring, recording,
certification, repair, or provider/application truth.
