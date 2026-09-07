# Agent Model

Phase 14B implements only the optional neutral values described by [ADR
0148](../adr/0148-neutral-versioned-model-invocation-contract-foundation.md)
and the [contract guide](model-invocation-contract-foundation.md) as a separate
proposal-producing concern. A model profile,
request, prompt, provider response, stream event, normalized result, tool-call
suggestion, usage/cost claim, or receipt does not identify, authenticate,
authorize, or grant an Agent Principal. Any future bridge may create only inert
proposal evidence for the existing Phase 13 authority flow. Phase 14B adds no
provider/model call, adapter, Agent bridge, authority, or effect behavior.

> **Current boundary:** Phase 13B implements the opt-in neutral
> [Agent Principal Contract Foundation](agent-principal-contract-foundation.md),
> Phase 13C implements the opt-in deterministic
> [Agent Declaration Artifact](agent-declaration-artifact.md), Phase 13D
> implements deterministic [Agent Admission and Authority Plan](agent-admission-and-authority-plan.md)
> evidence, and Phase 13E implements the provider-neutral
> [Agent Principal Runtime](agent-principal-runtime.md). Applications own
> authentication, authorization, currentness, and explicit effect ports. The
> plan remains evidence, receipts remain claims, and trusted in-process PHP is
> not sandboxed. Phase 13F implements one exact-current
> [Agent Application Workflow](agent-application-workflow.md) without a provider,
> profile registry, command, retained session, background work, or inspection.

## Purpose

Kongono treats AI agents as potential first-class application actors rather
than merely undifferentiated API clients. Future agent actions must remain
explicit, deterministically authorized, constrained, attributable, inspectable,
and evidenced without claiming perfect auditability.

## Principal model

The neutral Identity Access contracts include the broader concept of a
Principal:

```text
Principal
|-- Human
|-- Agent
|-- Service
|-- Organization
`-- System
```

Principal kinds express actor context without implying credentials,
authentication, authorization, delegation, consent, capability, visibility,
trust, or execution authority.

## Authority concepts

Future first-class concepts include Delegation, Capability, Intent, Consent,
Constraints, Revocation, execution results, and Receipts. These concepts are
intended to answer who acted, under whose authority, toward what outcome,
within which limits, and with what bounded evidence.

Exact identity data, authorization algorithms, delegation formats, consent evidence, trust semantics, revocation propagation, audit storage, and receipt schemas remain open.

## Agent Experience

Agent Experience is a first-class consumption surface alongside Human Experience and Software/API Experience. A capability should eventually be defined once and exposed through appropriate surfaces only where authorization permits.

Agents may later consume only separately accepted visibility-filtered
Application Graph, documentation, operational, and security evidence through
explicit capabilities. Identity as an agent grants no visibility. This is
planned direction, not implemented Agent Principal tooling.

## Protocol and provider boundaries

MCP and A2A may become adapters around Kongono-owned concepts. They must not determine Kongono's internal identity, capability, delegation, or security model. AI provider SDKs likewise must not define the architecture; provider independence is required.

## Developer companions are not application principals

Kongono Chie, Kongono Hantei, and Kongono Oshie are optional Experimental
developer companions under [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md).
Chie helps build, Hantei independently proves, and Oshie teaches from accepted
version-matched sources. They operate around developer knowledge and do not
create or exercise application Principal identity, authentication,
authorization, delegation, consent, trust, MCP, or A2A behavior.

Phases 4C-4H implement their deterministic knowledge and bounded role
boundaries. They add no model, provider, production integration, or application
Agent Principal behavior. The deterministic framework stays usable offline and
without AI. Role authority cannot flow from the application Principal model
merely because both concepts involve agents.

## Security

Agent identity is not authority. Every operation must eventually be governed by deterministic authentication, authorization, delegation, consent, trust, and policy mechanisms as applicable. An LLM must never make the authoritative authorization decision. Sensitive Graph, log, trace, schema, and security data must not become universally visible merely because an agent interface exists.

Prompts, retrieved material, model output, provider responses, memory, tool
output, extension state, and Graph facts are untrusted evidence. They cannot
expand scope or grant capabilities. Future capabilities are explicit, typed,
versioned, purpose-bounded, attributable, constrained, and denied by default.

## Stability

Agents-as-Principals and the three consumption surfaces are **LOCKED**
direction. The deterministic authority and bounded-lifecycle direction in
[ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
is **Accepted**. Exact data models, APIs, policy language, protocols, evidence
formats, providers, storage, and execution runtime remain **EXPERIMENTAL** or
open.

## Related concepts

- [Application Graph](application-graph.md)
- [Agent Principal Platform](agent-principal-platform.md)
- [Phase 13 roadmap](phase-13-roadmap.md)
- [Extension platform](extension-platform.md)
- [Glossary](../glossary.md)
- [ADR 0008](../adr/0008-agents-as-principals.md)
- [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md)
- [ADR 0139](../adr/0139-governed-agent-principals-and-deterministic-authority-boundary.md)
- [Developer intelligence](developer-intelligence.md)

## Phase 13A governed architecture

Phase 13A keeps Principal identity, authenticated identity evidence,
authorization, delegation, consent, requested intent, proposed action,
capability request, explicit grant, constraints, execution result, receipt, and
operational evidence separate. Models may propose actions; deterministic
application and Security boundaries decide admissibility and authorization.

Future work is explicitly bounded by applicable steps, duration, model budget,
capability calls, retries, delegation depth, output, retained state, and
concurrency. Cancellation, timeout, revocation, retirement, cleanup, and
partial or uncertain failure stay visible. Provider SDKs, MCP, and A2A remain
outer adapters and do not define Kongono's internal model.

See the [Agent Principal Platform](agent-principal-platform.md) and the
[Phase 13 roadmap](phase-13-roadmap.md). Phase 13A implements documentation
only, and each Phase 13B-13H row requires separate authenticated authority.

## Phase 11A Graph relationship

Phase 11A accepts the future authoritative Application Graph and
language-neutral IR architecture without implementing Agent Principal behavior.
An agent may eventually consume a separately accepted visibility-filtered Graph
view, but identity as an Agent Principal does not grant that access and Graph
facts do not grant identity, delegation, capability, consent, trust, or
authorization.

AI output cannot establish Graph facts. Inferred or proposed content remains
labeled and non-authoritative until deterministic validation admits it for one
exact application state. Omitted facts are not false. Phase 11A adds no agent
schema, protocol, execution API, Graph consumer, MCP/A2A behavior, or production
authority. See [ADR 0123](../adr/0123-authoritative-application-graph-and-language-neutral-ir-boundary.md)
and the [Phase 11 roadmap](phase-11-roadmap.md).

## Phase 13 closure

Phase 13H externally exercises the separately accepted Agent Principal stack
without changing this model. Agent identity remains distinct from
authentication, authorization, delegation, consent, capability grants, Graph
facts, and execution authority. The root harness is deterministic offline test
evidence only. See [Phase 13 external acceptance](phase-13-external-acceptance.md).
