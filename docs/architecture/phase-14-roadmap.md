# Phase 14 Roadmap: Provider-Neutral Model Invocation

## Status

Accepted as a fixed dependency roadmap. Phase 14A architecture and Phase 14B
neutral contracts, Phase 14C deterministic plan evidence, Phase 14D bounded
runtime, Phase 14E inert Agent proposal bridge, Phase 14F exact-current
application workflow, and Phase 14G visibility-safe inspection are complete.
Row 14H remains unimplemented and requires separate authenticated authority.

Phase 13 is closed at checkpoint
`63376c802ac4672c21458b67b2fcadaf8e03a019`. The Phase 13 proposal port remains
an explicit caller-supplied boundary. Phase 14 does not move provider invocation
inside Phase 13 authority.

## Roadmap rules

- The sequence records dependency order, not implementation authority.
- Every row requires a separately authenticated architect directive before any
  work begins.
- A row does not start automatically when the preceding row passes.
- Decimal correction rows may be inserted only by explicit authenticated
  correction authority and do not reorder or silently widen later rows.
- The roadmap contains no target dates, release promises, marketing milestones,
  provider commitments, or production-readiness claims.
- Phase 14 ends at 14H. Phase 14H does not authorize Phase 15.
- A first-party provider adapter or provider SDK integration is outside Phase 14
  unless an authenticated correction explicitly places it within scope.
- Model, provider, prompt, response, stream, tool suggestion, usage, cost,
  receipt, Graph, inspection, and developer-tool evidence remain
  non-authoritative throughout the phase.
- Kongono remains fully usable without AI, a provider, an account, credentials,
  or a network.

## Fixed rows

| Row | Accepted scope | Explicit exclusions |
| --- | --- | --- |
| **14A — Architecture and roadmap** | Accept [ADR 0147](../adr/0147-provider-neutral-model-invocation-and-adapter-boundary.md), the [Provider-Neutral Model Invocation guide](provider-neutral-model-invocation.md), and this fixed roadmap; reconcile current documentation after the Phase 13 checkpoint. Documentation architecture only. | No PHP, package, dependency, manifest, lockfile, schema, artifact, API, provider, SDK, endpoint, credential mechanism, profile, network, runtime, adapter, command, Agent bridge, Graph integration, test behavior, or production claim. |
| **14B — Neutral versioned contract foundation (complete)** | [ADR 0148](../adr/0148-neutral-versioned-model-invocation-contract-foundation.md) implements the optional Experimental `kongono/model-invocation-contract` package for bounded provider capabilities, application-owned profile references, immutable requests, normalized results, streaming events, usage/cost evidence, receipts, visibility, lifecycle, and failures. | No network, provider SDK, concrete adapter, credential discovery, invocation-plan artifact, Agent bridge, capability execution, application integration, registry, default provider, fallback, serialization, or command. |
| **14C — Deterministic invocation-plan evidence (complete)** | [ADR 0149](../adr/0149-deterministic-model-invocation-plan-artifact.md) implements the optional Experimental `kongono/model-invocation-build` build/currentness boundary over one explicit application-owned typed source, producing privacy-preserving exact versioned plan evidence with contained content-addressed no-clobber publication. | No credentials, provider call, network, SDK, adapter execution, model response, Agent proposal, authority decision, capability execution, application workflow, discovery, registry, or command. |
| **14D — Bounded invocation runtime (complete)** | [ADR 0150](../adr/0150-bounded-provider-neutral-model-invocation-runtime.md) implements the optional Experimental `kongono/model-invocation-runtime` over one explicit caller-supplied adapter, with exact plan binding, explicit budgets, cancellation, monotonic time, lifecycle, streaming rules, attempt evidence, failure preservation, and deterministic cleanup. | No concrete provider SDK, first-party adapter, ambient credential lookup, application workflow, Agent integration, registry, auto-selection, silent retry, automatic fallback, autonomous loop, retained session, background work, or command. |
| **14E — Inert Agent proposal bridge (complete)** | [ADR 0151](../adr/0151-inert-model-result-to-agent-proposal-bridge.md) implements the optional Experimental `kongono/model-invocation-agent-bridge`. It validates one successful retired one-attempt action-suggestion result and maps it through one exact application-owned action, capability, and usage mapping into inert proposal evidence suitable for an explicit Phase 13 proposal port. | No `AgentProposalPort` implementation, raw model arguments as capability input, authentication, admission, authorization, capability grant or invocation, effect proof, tool registry, provider invocation, authority bypass, automatic action, or change to Phase 13 current-authority checks. |
| **14F — Exact-current application workflow (complete)** | [ADR 0152](../adr/0152-exact-current-single-profile-model-invocation-application-workflow.md) implements the optional Experimental `kongono/model-invocation-application` boundary over one exact-current application-owned model profile, deterministic generated wiring, one explicit application-supplied adapter, and one terminal bounded invocation operation. | No ambient registry, auto-selection, automatic fallback, provider default, second profile, command, provider SDK selection, credential discovery, retained session, background work, deployment behavior, Agent port, or automatic bridge call. |
| **14G — Visibility-safe inspection and bounded evidence (complete)** | [ADR 0153](../adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md) implements the optional Experimental `kongono/model-invocation-inspection` boundary. It revalidates one exact supplied profile/wiring pair without loading generated PHP, optionally summarizes completed detached runtime evidence without replay, and emits bounded Graph, Security Audit, Observability, and role-addressed Chie/Hantei/Oshie evidence. | No provider invocation, credential access, authority decision, repair, recording, hidden reasoning, raw prompt/response disclosure, live application/provider/account observation, role execution, certification, or autonomous remediation. |
| **14H — External acceptance and checkpoint** | A separately authorized root-owned deterministic external harness proving the accepted Phase 14 boundaries and one exact local checkpoint. | No production expansion, first-party provider SDK or adapter, release, push, tag, Stable/1.0 claim, deployment, live-provider support claim, or Phase 15 work. |

## Dependency order

```text
14A architecture
  -> 14B neutral contracts
  -> 14C deterministic plan evidence
  -> 14D bounded adapter runtime
  -> 14E inert Agent proposal bridge
  -> 14F exact-current application workflow
  -> 14G visibility-safe inspection
  -> 14H external acceptance and checkpoint
```

The arrows mean only that a later row depends on accepted earlier contracts.
They grant no authority to begin, implement, stage, commit, release, or deploy a
later row.

## Persistent authority boundaries

### Application ownership

Applications retain model policy, exact profile choice, adapter supply,
credential policy, transport authority, budgets, retry and fallback policy,
content eligibility, result-to-proposal mapping, and every Phase 13 authority
decision. No roadmap row transfers those responsibilities to provider content,
an SDK, environment state, or a developer tool.

### Provider neutrality

Neutral packages must remain free of vendor SDKs and provider-specific transport
types. Provider adapters remain explicit outer leaves. There is no default
provider, ambient registry, dynamic discovery, automatic selection, hidden
network call, or silent compatibility conversion.

### Evidence is not authority

Plans, capability descriptions, profiles, requests, provider responses,
streaming events, normalized results, proposed actions, usage/cost evidence,
receipts, cleanup evidence, artifacts, generated wiring, Graph facts,
inspection, and role-addressed output remain evidence. They do not authenticate,
admit, authorize, grant, enforce, execute, or certify.

### Credentials

Credential material remains application-owned and outside profiles, artifacts,
generated code, Graph evidence, diagnostics, observability, audit, inspection,
receipts, developer-intelligence material, prompts, and model results. Phase 14A
does not choose a credential mechanism. Later rows may not infer one without
explicit authority.

### Lifecycle and effects

Every future operation must use explicit finite budgets, cancellation,
monotonic time, attempt accounting, terminal states, and deterministic cleanup.
Streaming partials are inert. Silent SDK retries and fallback are prohibited.
Uncertain transport or cleanup outcomes remain visible and do not authorize
replay.

### Developer-intelligence separation

Chie explains accepted exact-current sources, Hantei independently verifies
deterministic evidence, and Oshie teaches accepted material. They do not invoke
providers, supply credentials, select adapters, authorize tools, repair runtime
state, or certify themselves. Kongono remains usable without all three roles.

## Phase 14D current boundary

Phase 14D is complete when the exact runtime package, ADR 0150, [runtime
guide](model-invocation-runtime.md), root/tooling integration, 72/269 acyclic
topology, 1,294 production / 660 package-test / 76 root-test PHP inventories,
focused gates, canonical quality, preservation evidence, and report-last
handoff all pass. The runtime remains bounded and provider-neutral.

## Phase 14E current boundary

Phase 14E is complete when the exact bridge package, ADR 0151, [bridge
guide](model-result-agent-proposal-bridge.md), root/tooling integration, 73/275
acyclic topology, 1,307 production / 669 package-test / 76 root-test PHP
inventories, focused gates, canonical quality, preservation evidence, and
report-last handoff all pass. The bridge remains stateless and inert. A
normalized model result is not an Agent proposal; an Agent proposal is not
admission, authorization, a capability grant, a capability invocation, or
proof of effect. The bridge does not implement `AgentProposalPort`, trusted
application mapping remains separate from untrusted model arguments, and raw
arguments never become capability inputs.

## Phase 14F current boundary

Phase 14F is complete when the exact application package, ADR 0152,
[application workflow guide](model-invocation-application-workflow.md),
root/tooling integration, 74/280 acyclic topology, 1,336 production / 680
package-test / 76 root-test PHP inventories, focused gates, canonical quality,
preservation evidence, and report-last handoff all pass. One exact-current
profile generates deterministic inert wiring, one directly supplied matching
adapter is bound before activity, and one application instance delegates at
most one terminal operation to Phase 14D. Phase 14E remains a separate explicit
optional reduction step. Trusted generated and in-process PHP is not a sandbox.

## Phase 14G current boundary

Phase 14G implements the optional Experimental
[`kongono/model-invocation-inspection`](model-invocation-inspection-and-evidence.md)
upper evidence leaf under [ADR 0153](../adr/0153-visibility-safe-model-invocation-inspection-and-bounded-evidence.md).
It accepts one explicit exact supplied profile/wiring pair and an optional
completed detached runtime result. It performs offline, read-only, stateless,
presentation-neutral validation and never loads generated PHP or replays runtime
evidence. Its Graph, Observability, Security Audit, and role-addressed outputs
are bounded inert Safe aggregate values, not calls, recording, monitoring,
certification, authorization, or proof of live provider/application state.
Current topology is 75 packages / 289 local edges / acyclic, with 1,358
production / 686 package-test / 76 root-test PHP files.

The Phase 14A documentation acceptance remains historical evidence:

- ADR 0147, the architecture guide, and this roadmap exist as current Markdown;
- the authorized current-product and engineering documentation reconciles the
  Phase 13 checkpoint with the Accepted Phase 14A architecture;
- ADR 0147 appears exactly once in the ADR index;
- rows 14A through 14H appeared once, in order, with 14B through 14H clearly
  planned at the Phase 14A acceptance;
- local Markdown links resolve and documentation file-safety rules pass;
- no provider, SDK, credential mechanism, adapter, runtime, command, endpoint,
  or Phase 14C behavior is introduced by Phase 14A;
- canonical repository quality passes; and
- the caller-managed Git state, checkpoint, topology, PHP inventories,
  dependency lock, index, history, and unrelated untracked records remain
  preserved.

Phase 14H is implemented under [ADR 0154](../adr/0154-phase-14-external-model-invocation-acceptance-and-checkpoint.md)
and the [external acceptance guide](phase-14-external-acceptance.md). The
root-owned offline harness and exact local checkpoint close Phase 14 without a
provider SDK, first-party adapter, push, tag, release, live inspection, external
effect, or Phase 15 behavior.
