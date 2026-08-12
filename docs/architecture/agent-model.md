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

## Security

Agent identity is not authority. Every operation must eventually be governed by deterministic authentication, authorization, delegation, consent, trust, and policy mechanisms as applicable. An LLM must never make the authoritative authorization decision. Sensitive Graph, log, trace, schema, and security data must not become universally visible merely because an agent interface exists.

## Stability

Agents-as-Principals and the three consumption surfaces are **LOCKED** direction. The data model, APIs, policy language, protocols, evidence formats, and execution runtime remain **EXPERIMENTAL** or open.

## Related concepts

- [Application Graph](application-graph.md)
- [Extension platform](extension-platform.md)
- [Glossary](../glossary.md)
- [ADR 0008](../adr/0008-agents-as-principals.md)
