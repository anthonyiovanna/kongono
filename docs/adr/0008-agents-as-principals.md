# ADR 0008: Agents as Principals

## Status

Accepted

## Date

2026-08-12

## Context

AI agents may act within applications, but treating them only as generic API clients does not express identity, delegated authority, intent, constraints, consent, or audit needs.

## Decision

Model agents as a potential first-class kind of Principal alongside Human, Service, Organization, and System principals. Treat Agent Experience as a first-class consumption surface alongside Human Experience and Software/API Experience.

## Rationale

A broad Principal model allows agent actions to be governed through explicit identity, capability, delegation, trust, intent, consent, constraints, revocation, audit, and receipts rather than provider-specific assumptions.

## Consequences

Agent identity alone will never imply authority. Deterministic application security must govern agent operations. MCP and A2A may adapt Kongono concepts but may not define the internal domain model.

## Rejected alternatives

Treating agents as undifferentiated API clients, letting an LLM make authoritative authorization decisions, and allowing a provider or external protocol to define Kongono's identity model are rejected.

## What remains open

Principal data models, authentication, policies, delegation and consent formats, trust semantics, audit and receipt storage, protocol adapters, and agent execution APIs remain experimental.
