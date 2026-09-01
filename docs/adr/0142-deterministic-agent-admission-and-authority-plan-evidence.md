# ADR 0142: Deterministic Agent Admission and Inert Authority-Plan Evidence

- Status: Accepted
- Date: 2026-08-31
- Phase: 13D

## Context

Phase 13B supplies neutral Agent Principal values and Phase 13C supplies one
exact, current declaration artifact. Neither boundary authenticates a principal,
evaluates application policy, admits an agent, grants a capability, or performs
work. Applications need a deterministic boundary that can reconcile those
declaration facts with authentication and authorization evidence they have
already produced without transferring either evaluation responsibility to
Kongono.

Authentication, authorization, admission, capability grant, constraint
enforcement, and execution are distinct decisions. A principal kind is not
authentication. A capability request is not a grant. A policy receipt is
evidence about an application decision, not a reusable bearer capability.

## Decision

Kongono adds the opt-in Experimental `kongono/agent-admission` package. The
application supplies one explicit `AgentAdmissionRequest` containing:

- one exact `AgentDeclaration` and its exact `AgentDeclarationArtifact` bytes;
- one already-completed authentication outcome and bounded fact references;
- one ordered requested-capability list; and
- ordered application-owned authorization evidence for requested capabilities.

The admission builder first proves the declaration artifact structurally valid
and current against the exact supplied declaration. It performs no discovery,
loading, publication, credential verification, authentication orchestration, or
policy evaluation.

Reconciliation is deterministic and denied by default. Declared required and
optional capabilities remain distinct. A required capability must be requested
and have an application `Permit` receipt. An optional capability may be omitted;
a declaration containing only optional capabilities may therefore be admitted
with an empty effective authority plan. A requested undeclared capability,
missing evidence, `Deny`, or `Indeterminate` evidence is retained as a distinct
decision and cannot become effective authority. Admission is denied if the
principal is not authenticated, if authenticated identity does not exactly match
the declared Agent Principal, if any requested capability is undeclared or not
permitted, or if any required declared capability is not requested and
permitted.

The resulting `AgentAdmissionDecision`, `AgentAuthorityPlan`, capability
decisions, and `AgentAdmissionIdentity` are immutable inert evidence. Canonical
identity binds exact declaration bytes, principal identity, authentication
outcome, ordered requests, complete authorization-receipt fields, capability
decisions, constraints, and evidence references. Canonical bytes and SHA-256
identity are deterministic and preserve declaration order. They are not an
execution token, resolver, callable, policy bypass, credential, session,
delegation, consent, or durable grant.

The package has exactly eight production dependencies:
`agent-declaration-build`, `agent-principal-contract`, `api-metadata`,
`authentication`, `authorization`, `diagnostics`, `identity-access`, and PHP.
No existing production package depends on it.

## Security and authority consequences

The application owns authentication and authorization evaluation and owns the
currentness, expiry, revocation, delegation, consent, policy, and operational
meaning of its evidence. Phase 13D does not validate those external facts. It
does not create or mutate a Security Context and does not infer authority from
identity, visibility, Graph facts, extension state, prompts, completions,
provider responses, memory, retrieval, developer tools, or declaration bytes.

There is no ambient tool registry, container, service locator, filesystem,
process, shell, network, database, credential, secret, deployment, Git, Graph,
extension-host, or developer-tool authority. There is no provider or model
protocol, model request, tool invocation, capability execution, runtime loop,
workflow, persistence, publication, command, or application integration. An LLM
may propose values, but its output cannot authenticate, authorize, admit, grant,
enforce, or execute.

Failures use bounded non-disclosing diagnostics. Diagnostic context never
contains raw artifact bytes, raw rejected values, principal identifiers,
capability purposes, policy payloads, credentials, secrets, or filesystem paths.
Ordinary deny outcomes are returned as evidence rather than thrown.

## Alternatives rejected

- Treating declaration currentness as current authority would collapse evidence
  into authorization.
- Letting Kongono invoke authenticators or policy engines would transfer
  application authority into this build boundary.
- Returning executable capabilities, callables, registries, or service locators
  would make inert evidence ambient runtime authority.
- Discovering requests, tools, credentials, or policy from process state would
  make results non-deterministic and deny-by-default semantics unverifiable.

## Current limitations and next boundary

Phase 13D produces only deterministic admission and authority-plan evidence. It
does not prove that application evidence remains externally current, enforce
constraints, execute capabilities, call a model, manage lifecycle, or issue an
operational receipt. Phase 13E is separately authorized future work for a
provider-neutral bounded orchestration runtime. Phase 13E-13H remain planned.

## Related material

- [Agent Admission and Authority Plan](../architecture/agent-admission-and-authority-plan.md)
- [Agent Declaration Artifact](../architecture/agent-declaration-artifact.md)
- [Agent Principal Contract Foundation](../architecture/agent-principal-contract-foundation.md)
- [Agent Principal Platform](../architecture/agent-principal-platform.md)
- [Phase 13 Roadmap](../architecture/phase-13-roadmap.md)
