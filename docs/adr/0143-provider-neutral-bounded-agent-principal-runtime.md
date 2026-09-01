# ADR 0143: Provider-Neutral Bounded Agent Principal Runtime

- Status: Accepted
- Date: 2026-08-31
- Phase: 13E

## Context

Phase 13B supplies inert neutral Agent Principal values, Phase 13C supplies one
exact declaration artifact, and Phase 13D deterministically reconciles that
declaration with completed application-owned authentication and authorization
evidence. An admitted authority plan is still evidence, not a callable grant.
It neither proves external authority remains current nor performs work.

Applications need a deliberately small boundary that can coordinate one
proposal and at most one capability through explicit caller-owned ports while
keeping identity, admission, current authority, proposal, effect, result,
receipt, and cleanup semantically distinct.

## Decision

Kongono adds the opt-in Experimental `kongono/agent-runtime` package. Its final,
stateless `AgentRuntime` exposes only one synchronous `run(AgentRuntimeRequest)`
operation. A request contains exactly one declaration, matching admitted
authority plan, operation, intent, nine-dimensional budget, one proposal port,
an ordinal list of at most 32 capability ports, and explicit authority-
currentness, cancellation, and monotonic-clock ports.

Before opening a possible effect, the runtime rebuilds the declaration artifact
and exactly binds its identity and digest, principal, constraints, evidence
references, capability identities, declared requirements, and permitted plan
decisions. Denied, mismatched, contradictory, or malformed plans are invalid
runtime input. Immediately before proposal and capability effects, the runtime
requires the caller's currentness port to affirm the exact plan and operation.
That evidence port grants nothing by its presence.

The proposal port is provider-neutral. It sees only bounded request evidence,
never the authority plan, registry, resolver, credential, or ambient context.
Its returned proposal must preserve the exact principal, operation, intent,
declared capability request, constraints, evidence, provenance, and visibility.
The runtime invokes at most one matching declared and permitted capability port.
No provider SDK, provider adapter, protocol, or model implementation is selected.

Cancellation and monotonic time are observed before and after proposal and
before and after capability invocation. Time must be non-negative and
nondecreasing. The budget contains step, duration-millisecond, token,
capability-call, retry, delegation-depth, output-byte, retained-state-byte, and
concurrency ceilings. Missing declaration constraints are zero ceilings;
requested ceilings cannot exceed declaration ceilings; concurrency is exactly
one; retry, delegation, and retained-state usage remain zero. Exact boundaries
are valid and overflow-safe limit-plus-one observations stop the next possible
effect.

The runtime returns the exact outcomes `Performed`, `CapabilityFailed`,
`Cancelled`, `BudgetExceeded`, or `AuthorityDenied` and actual bounded usage.
It emits an ordered applicable subset of the existing `AgentReceipt` contract:
`Proposed`, `Authorized`, `Attempted`, `Performed` or `Failed`, optional
`Cancelled`, and `Retired`. Receipt identities and evidence digests are
deterministic length-framed claims. Receipts are not grants, credentials,
execution tokens, billing truth, or perfect external truth. An explicit failed
capability outcome remains failed; post-effect cancellation preserves the
performed receipt.

All accepted capability ports are operation-owned for cleanup and receive one
close attempt in reverse ordinal order, including unused ports, followed by the
proposal port. Primary and cleanup failures preserve their exact throwable
identities through distinct operation, cleanup, and combined exceptions.
Cleanup uncertainty retires the operation; it does not authorize retry.

## Security and authority consequences

The only possible effects are the explicitly supplied proposal port and one
explicitly selected capability port. There is no ambient tool registry, service
locator, container, hidden autowiring, dynamic lookup, discovery, filesystem,
process, network, database, credential, secret, Git, Graph, Extension, Security
Context, environment, randomness, application workflow, queue, scheduler, or
background authority.

Trusted in-process PHP is not sandboxed. The caller owns port construction,
external authentication and authorization evaluation, currentness semantics,
revocation, cancellation signaling, monotonic time, capability implementation,
and effect verification. Prompts, completions, provider responses, memory,
retrieval, visibility, declaration bytes, admission plans, and receipts cannot
expand authority.

Failures use bounded non-disclosing diagnostics. Raw proposals, capability
outputs, identities, purposes, policy content, evidence payloads, credentials,
secrets, and filesystem paths do not enter exception messages, diagnostic
contexts, or receipt identities.

## Alternatives rejected

- Treating an admitted plan as durable execution authority would collapse
  admission evidence into current authorization.
- Passing a provider SDK, tool registry, resolver, or service locator would
  create ambient and dynamically extensible authority.
- Automatic retry, delegation, multi-step planning, retained sessions, queues,
  or background loops would exceed the one-operation lifecycle and require new
  persistence, cancellation, budget, and recovery decisions.
- Omitting cleanup of unused accepted ports would make ownership depend on the
  proposal and leave resources with uncertain lifecycle.
- Treating trusted in-process PHP as a sandbox would make a false security
  claim.

## Current limitations and next boundary

Phase 13E is a synchronous one-operation runtime, not a general orchestration
product. It has no provider SDK, application workflow, generated Composition
integration, command, inspection surface, autonomous loop, background worker,
retained session, persistence, sandbox, effect attestation, or retry/delegation
engine. Phase 13F through 13H remain planned and separately authorized.

## Related material

- [Agent Principal Runtime](../architecture/agent-principal-runtime.md)
- [Agent Admission and Authority Plan](../architecture/agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](../architecture/agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](../architecture/agent-principal-contract-foundation.md)
- [Agent Principal Platform](../architecture/agent-principal-platform.md)
- [Phase 13 Roadmap](../architecture/phase-13-roadmap.md)
