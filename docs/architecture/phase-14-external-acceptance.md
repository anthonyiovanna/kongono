# Phase 14 External Model-Invocation Acceptance

## Purpose and scope

The root-owned `tests/External/ModelInvocation/run.php` harness closes Phase 14
by exercising the accepted Phase 14B-G production APIs in fresh PHP processes.
It owns only test fixtures, an explicit deterministic fake adapter, and bounded
temporary state outside the repository. It performs no network I/O and supplies
no provider SDK, account, endpoint, credentials, registry, default, discovery,
retry, fallback, retained session, background work, or external effect.

## Evidence covered

The harness constructs neutral version-1 values, deterministic plan evidence,
exact current validation, content-addressed no-clobber publication, one bounded
runtime attempt, inert Agent proposal-reduction boundaries, exact-current
application wiring, and visibility-safe inspection. It proves canonical output,
fixed non-disclosing failure output, bounded process streams, sequential and
concurrent determinism, repository preservation, process closure, and complete
temporary cleanup.

The fake adapter proves only the accepted adapter interface. Provider/model
labels are not discovery or availability. Plans, profiles, currentness, usage,
cost, receipts, proposals, Graph facts, observations, audit values, role views,
and verifier results are inert caller-relative evidence, not authentication,
authority, billing truth, deployment truth, certification, or proof of effect.
Raw messages, results, stream chunks, action arguments, generated PHP, paths,
private labels, and exception details are omitted from public process output.

## Lifecycle and trust

One explicitly supplied identity-matching adapter serves at most one synchronous
operation. Budgets, cancellation, attempt count, streaming order, terminal state,
primary failure, cleanup evidence, and retirement stay explicit. A later fresh
runtime remains isolated. Generated and in-process PHP is trusted and not
sandboxed. Inspection never loads generated PHP and does not replay runtime.

Chie, Hantei, and Oshie remain separately addressed evidence roles and are not
executed. Graph, Observability, and Security Audit values are pure projections,
not recording, delivery, persistence, monitoring, repair, or live state.

## Checkpoint

ADR 0154 accepts exact local Phase 14 checkpoint
`05bf8367b56e694a8c388ccb96cdc305cb6e40ae`. Directives and reports are
excluded. The checkpoint performs no push, tag, release, provider integration,
or Phase 15 runtime work. Phase 14A-H are implemented and Phase 14 is closed
while the framework remains Experimental and pre-1.0.

## Related material

- [ADR 0154](../adr/0154-phase-14-external-model-invocation-acceptance-and-checkpoint.md)
- [Phase 14 roadmap](phase-14-roadmap.md)
- [Model-invocation inspection](model-invocation-inspection-and-evidence.md)
- [ADR 0155: Explicit outbound HTTP and governed network egress](../adr/0155-explicit-outbound-http-and-governed-network-egress-boundary.md)
- [Phase 15 roadmap](phase-15-roadmap.md)

Phase 15A is documentation architecture only. It accepts a general egress
foundation beneath future external-service adapters; it adds no provider
adapter, transport, network authority, or Phase 15B implementation.
