# ADR 0154: Phase 14 External Model-Invocation Acceptance and Checkpoint

## Status

Accepted.

## Context

Phases 14B through 14G implement separate Experimental provider-neutral
contract, plan-artifact, runtime, inert Agent bridge, application, and
inspection boundaries. Closing Phase 14 requires root-owned external-process
evidence that those boundaries compose without adding provider authority and an
exact local checkpoint containing only authenticated Phase 14 content.

## Decision

Phase 14H adds one deterministic, offline root harness. It uses production APIs
with root-owned fixtures, one explicit caller-supplied fake adapter, and a
unique system-temporary workspace. The fake performs no network or credential
lookup and is not a first-party provider integration. The harness bounds both
process streams, proves success, intentional failure, sequential and concurrent
execution, preserves repository state, and removes temporary state in `finally`.

Neutral identities, capabilities, requests, plans, currentness, results, usage,
cost, receipts, generated wiring, proposals, inspection, projections, and
verifier output remain evidence. They do not authenticate a provider, account,
model, credential, response, action, authority, cost, deployment, availability,
or effect. Provider and model identities are bounded labels, not discovery.

The runtime makes at most one bounded attempt through one directly supplied
matching adapter. Cancellation, budgets, streaming order, primary failure,
cleanup uncertainty, and retirement remain explicit. The bridge produces inert
proposal evidence only; it neither implements nor invokes `AgentProposalPort`,
admission, authorization, capability grants, or effects. The application binds
one exact-current profile/wiring pair and one adapter for one terminal operation,
without a registry, default, discovery, retry, fallback, retained session, or
background work. Trusted generated and in-process PHP is not a sandbox.

Inspection is offline, read-only, stateless Safe aggregate evidence. Graph,
Observability, and Security Audit projections are inert values and are not
delivered or persisted. Chie, Hantei, and Oshie are addressed evidence roles;
the harness and inspection package do not execute them.

The Phase 14 checkpoint is one local commit. Directives and reports are excluded.
No provider SDK, account, credentials, network, command, remote service, memory,
autonomous loop, background worker, deployment, marketplace, push, tag, release,
or Phase 15 behavior is added. Kongono remains Experimental, pre-1.0,
provider-neutral, and usable without AI or a provider.

## Consequences

Phase 14 is closed after committed-tree verification. The harness is test
evidence, not production authority, certification, monitoring, recording,
repair, current external state, or proof of effect.

## Related material

- [Phase 14 external acceptance](../architecture/phase-14-external-acceptance.md)
- [Phase 14 roadmap](../architecture/phase-14-roadmap.md)
- [Provider-neutral model invocation](../architecture/provider-neutral-model-invocation.md)
