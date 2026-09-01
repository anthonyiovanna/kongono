# Agent Principal Runtime

> **Outer application boundary:** Phase 13F's
> [Agent Application Workflow](agent-application-workflow.md) binds one exact
> profile and transfers cleanup ownership to this runtime exactly once. The
> runtime's currentness, budget, cancellation, effect, receipt, and cleanup
> meanings remain unchanged.

> **Current boundary:** Phase 13E implements one opt-in, synchronous,
> provider-neutral operation runtime over explicit caller-owned ports. It binds
> one exact declaration to one matching admitted plan, rechecks current
> authority before each possible effect, applies nine explicit budgets, and
> retires every accepted port. It is not an autonomous agent system, provider
> adapter, application workflow, sandbox, or general orchestration product.

## Purpose and context

`kongono/agent-runtime` sits above the Phase 13B neutral contracts, Phase 13C
declaration artifact, and Phase 13D admission evidence. Use it when an
application already owns current authentication and authorization semantics and
wants a deterministic one-operation lifecycle around one proposal and at most
one capability invocation.

Do not use it for multi-step planning, tool discovery, provider selection,
background work, durable workflow, automatic retry, delegation, scheduling,
credential management, policy evaluation, application composition, or sandboxing.

## Input and exact binding

`AgentRuntimeRequest` contains exactly the declaration, authority plan,
operation, intent, `AgentRuntimeBudget`, one `AgentProposalPort`, an ordered
capability-port list, `AgentAuthorityCurrentness`, `AgentCancellation`, and
`AgentMonotonicClock`.

Construction validates that capability ports form an ordinal, unique list of at
most 32. Each identity must be declared and permitted; no extraneous port is
accepted. A permitted capability may omit a port unless the proposal selects it.

Before any possible effect, `AgentRuntime` rebuilds the supplied declaration
artifact and compares its identity and SHA-256 with the plan. It also compares
the exact principal, ordered constraints, ordered evidence references, ordered
capability identities, declared requirements, and decisions, and requires an
admitted decision. A mismatch is invalid input, not a runtime denial and never
an opportunity to infer replacement authority.

## One-operation lifecycle

The stateless `AgentRuntime::run()` performs this bounded sequence:

1. validate exact declaration/plan/budget binding;
2. observe time and cancellation before proposal;
3. require current authority, then request at most one proposal;
4. observe time and cancellation after proposal;
5. validate the proposal against the exact declaration and request;
6. observe time and cancellation before capability;
7. require current authority, then invoke at most one exact capability port;
8. observe time and cancellation after capability; and
9. close every accepted capability port in reverse order, then close the
   proposal port, and retire the operation.

The closed state vocabulary is `Created`, `Proposing`, `Proposed`, `Invoking`,
`Completed`, `Cancelled`, `Failed`, and `Retired`. There is no background
continuation or retained operation session. Authority currentness and
cancellation are caller-supplied evidence; neither grants capability.

## Proposal and capability ports

`AgentProposalPort` exposes `propose(AgentProposalRequest)` and `close()`. Its
request includes only the exact principal, operation, intent, permitted
capability identities, constraints, and evidence references. The plan is not
shared. The returned `AgentProposalResult` includes one `ActionProposal`, token
usage, output-byte usage, and an evidence digest. The proposal must preserve all
declaration- and caller-owned facts and select one declared, permitted
capability with the exact purpose and requirement.

`AgentCapabilityPort` exposes its immutable identity, one `invoke()` operation,
and `close()`. The invocation contains only the validated proposal, operation,
intent, matched capability identity, budget, and current usage. The result is
explicitly `Performed` or `Failed`, with bounded output bytes and an evidence
digest. Failed is never normalized to performed.

Ports are trusted in-process PHP collaborators, not a sandbox. The caller must
construct and constrain them according to the application's security boundary.

## Budgets and actual usage

The budget contains all nine existing constraint dimensions:

| Dimension | Runtime behavior |
| --- | --- |
| Steps | One for proposal and one for capability; no further steps |
| Duration milliseconds | Derived from caller monotonic readings at every checkpoint |
| Tokens | Exact proposer-reported usage |
| Capability calls | Zero or one |
| Retries | Always zero; no retry engine exists |
| Delegation depth | Always zero; no delegation exists |
| Output bytes | Proposer and capability bounded output evidence |
| Retained-state bytes | Always zero after the synchronous call |
| Concurrency | Exactly one operation |

Every requested ceiling must be non-negative and no greater than the matching
declaration constraint. A missing constraint is a zero ceiling. Exact-limit
usage is valid. Arithmetic is overflow-safe; the first observation beyond a
ceiling prevents the next possible effect or returns `BudgetExceeded` after an
already completed effect. `AgentRuntimeUsage` is operational evidence, not
billing truth.

## Outcomes, receipts, and determinism

`AgentRuntimeResult` reports exactly `Performed`, `CapabilityFailed`,
`Cancelled`, `BudgetExceeded`, or `AuthorityDenied`, terminal `Retired` state
after successful cleanup, actual usage, optional validated proposal and
capability result, ordered receipts, and successful-cleanup evidence.

Once a valid proposal exists, the runtime emits the applicable canonical subset
of `Proposed`, `Authorized`, `Attempted`, `Performed` or `Failed`, optional
`Cancelled`, and `Retired`. If cancellation or authority denial occurs before a
valid proposal, receipts may be empty because no proposal identity may be
invented. Repeated identical inputs and observations yield identical receipt
identities and evidence digests. These receipts are bounded claims, not grants,
credentials, bearer tokens, billing records, or perfect external truth.

## Failure, cleanup, and disclosure

Invalid input, collaborator or clock failure, cleanup failure, and combined
operation-plus-cleanup failure remain distinct. Every accepted capability port,
including an unused one, receives exactly one close attempt in reverse ordinal
order; the proposal port closes last. All cleanup attempts occur even after an
earlier cleanup failure. Combined exceptions preserve the exact primary
throwable separately from the ordered cleanup throwables. Cleanup uncertainty
means retired uncertainty and authorizes no automatic retry.

Diagnostics `KGN-AGENT-RUNTIME-1330` through `1333` carry only fixed bounded
metadata. Raw proposal content, capability output, identities, purposes,
evidence payloads, credentials, secrets, and paths are excluded from diagnostic
contexts, exception strings, and receipt identities.

## Side effects, concurrency, and security

Only the explicitly supplied proposal port and selected capability port may
cause effects. The runtime itself performs no filesystem, process, network,
database, environment, random, Git, Graph, Extension, Security Context,
telemetry, persistence, or application-workflow I/O. Calls share no mutable
state and concurrency is caller-owned outside the one-operation instance.

There is no ambient registry, service locator, container, hidden autowiring,
dynamic lookup, discovery, provider SDK, provider adapter, model protocol,
queue, scheduler, watcher, background worker, or retained session. Currentness
probes, plans, declarations, prompts, completions, retrieved content, memory,
visibility, provider responses, and receipts cannot expand authority.

## Stability and next boundary

Runtime request/result values, ports, enums, budgets, usage, and concrete
exceptions are Experimental. The exception marker is Public. Diagnostics, base
exception, validation guards, budget meter, receipt factory, and lifecycle
composer are Internal. No API is Stable.

Phase 13F through 13H remain planned and separately authorized. In particular,
there is no supported application workflow or Composition integration yet.

## Related material

- [ADR 0143](../adr/0143-provider-neutral-bounded-agent-principal-runtime.md)
- [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](agent-principal-contract-foundation.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Phase 13 External Acceptance](phase-13-external-acceptance.md)
- [Phase 13 Roadmap](phase-13-roadmap.md)
