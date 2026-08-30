# Agent Model

## Purpose

Kongono treats AI agents as potential first-class application actors rather than merely undifferentiated API clients. Agent actions must remain explicit, authorized, constrained, inspectable, and auditable.

## Principal model

The future identity model begins with the broader concept of a Principal:

```text
Principal
|-- Human
|-- Agent
|-- Service
|-- Organization
`-- System
```

Principal kinds express actor context without implying equal credentials, permissions, trust, or interaction patterns.

## Authority concepts

Future first-class concepts include Delegation, Capability, Trust, Intent, Consent, Constraints, Delegation chains, Revocation, Audit, and Receipts. These concepts are intended to answer who acted, under whose authority, toward what outcome, within which limits, and with what evidence.

Exact identity data, authorization algorithms, delegation formats, consent evidence, trust semantics, revocation propagation, audit storage, and receipt schemas remain open.

## Agent Experience

Agent Experience is a first-class consumption surface alongside Human Experience and Software/API Experience. A capability should eventually be defined once and exposed through appropriate surfaces only where authorization permits.

Agents should consume structured framework knowledge through mechanisms such as Application Graph inspection, routes, schemas, dependencies, documentation, tests, logs, traces, architecture rules, impact analysis, and security information. This is planned direction, not implemented tooling.

## Protocol and provider boundaries

MCP and A2A may become adapters around Kongono-owned concepts. They must not determine Kongono's internal identity, capability, delegation, or security model. AI provider SDKs likewise must not define the architecture; provider independence is required.

## Developer companions are not application principals

Kongono Chie, Kongono Hantei, and Kongono Oshie are planned optional developer
companions under [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md).
Chie helps build, Hantei independently proves, and Oshie teaches from accepted
version-matched sources. They operate around developer knowledge and do not
create or exercise application Principal identity, authentication,
authorization, delegation, consent, trust, MCP, or A2A behavior.

Phase 4A accepts their architecture only. No role, knowledge artifact, command,
provider, patch proposal, or runtime exists. The deterministic framework stays
usable offline and without AI. Any future role authority must be separately
bounded and cannot flow from the application Principal model merely because
both concepts involve agents.

## Security

Agent identity is not authority. Every operation must eventually be governed by deterministic authentication, authorization, delegation, consent, trust, and policy mechanisms as applicable. An LLM must never make the authoritative authorization decision. Sensitive Graph, log, trace, schema, and security data must not become universally visible merely because an agent interface exists.

## Stability

Agents-as-Principals and the three consumption surfaces are **LOCKED** direction. The data model, APIs, policy language, protocols, evidence formats, and execution runtime remain **EXPERIMENTAL** or open.

## Related concepts

- [Application Graph](application-graph.md)
- [Extension platform](extension-platform.md)
- [Glossary](../glossary.md)
- [ADR 0008](../adr/0008-agents-as-principals.md)
- [ADR 0060](../adr/0060-human-centered-developer-intelligence-family.md)
- [Developer intelligence](developer-intelligence.md)

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
